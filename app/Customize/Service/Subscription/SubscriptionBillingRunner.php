<?php

namespace Customize\Service\Subscription;

use Customize\Entity\Subscription;
use Customize\Entity\SubscriptionEventLog;
use Customize\Entity\SubscriptionOrder;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Customize\Service\Gmo\GmoApiClient;

final class SubscriptionBillingRunner
{
    private EntityManagerInterface $entityManager;
    private SubscriptionRenewalOrderFactory $renewalOrderFactory;
    private PurchaseFlow $shoppingPurchaseFlow;
    private SubscriptionScheduler $subscriptionScheduler;
    private GmoApiClient $gmoApiClient;
    private SubscriptionMailNotifier $subscriptionMailNotifier;

    /** @var int[] */
    private array $retryDayOffsets;

    public function __construct(
        EntityManagerInterface $entityManager,
        SubscriptionRenewalOrderFactory $renewalOrderFactory,
        PurchaseFlow $shoppingPurchaseFlow,
        SubscriptionScheduler $subscriptionScheduler,
        GmoApiClient $gmoApiClient,
        SubscriptionMailNotifier $subscriptionMailNotifier,
        string $retryDayOffsetsCsv
    ) {
        $this->entityManager = $entityManager;
        $this->renewalOrderFactory = $renewalOrderFactory;
        // @eccube.purchase.flow.shopping
        $this->shoppingPurchaseFlow = $shoppingPurchaseFlow;
        $this->subscriptionScheduler = $subscriptionScheduler;
        $this->gmoApiClient = $gmoApiClient;
        $this->subscriptionMailNotifier = $subscriptionMailNotifier;

        $this->retryDayOffsets = array_values(array_filter(array_map('intval', array_map('trim', explode(',', $retryDayOffsetsCsv))), static fn ($x) => $x > 0));
        if ([] === $this->retryDayOffsets) {
            $this->retryDayOffsets = [1, 3, 7];
        }
    }

    public function execute(Subscription $subscription, bool $dryRun): void
    {
        if (Subscription::STATUS_ACTIVE !== $subscription->getStatus()) {
            return;
        }

        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        // snapshot kỳ này
        /** @var \DateTimeInterface $billingAnchorDue */
        $billingAnchorDue = $subscription->getNextBillingAt();
        $billingKey = sprintf(
            'sub_%d_%s_r%d',
            $subscription->getId(),
            $billingAnchorDue->format('YmdHis'),
            $subscription->getRetryCount()
        );
        $billingKey = sprintf('%s_%s', $billingKey, bin2hex(random_bytes(3)));

        if ($dryRun) {
            log_info('[subscription] dry-run: skip charge', ['billing_key' => $billingKey, 'subscription_id' => $subscription->getId()]);

            return;
        }

        $subRow = new SubscriptionOrder();
        $subRow->setSubscription($subscription);
        $subRow->setBillingCycleKey($billingKey);
        $scheduled = $billingAnchorDue instanceof \DateTime
            ? clone $billingAnchorDue
            : new \DateTime($billingAnchorDue->format('c'));
        $subRow->setBillingScheduledAt($scheduled);
        $subRow->setBillingStatus(SubscriptionOrder::BILLING_STATUS_PENDING);
        $subRow->setCreateDate($now);
        $subRow->setUpdateDate($now);

        $this->entityManager->persist($subRow);
        $this->entityManager->flush();

        $newOrder = null;
        try {
            $newOrder = $this->renewalOrderFactory->createRenewalOrder($subscription);
            $subRow->setOrderId((int) $newOrder->getId());
            $this->entityManager->flush();

            log_info('[subscription] renewal prepare', ['renewal_order_id' => $newOrder->getId(), 'billing_key' => $billingKey]);

            $context = new PurchaseContext();
            $this->shoppingPurchaseFlow->prepare($newOrder, $context);

            $paymentResult = $this->gmoApiClient->payWithTestCard($newOrder);

            $msgParts = [];
            $msgParts[] = '[Subscription renewal] GMO';
            $msgParts[] = $this->gmoApiClient->isMockMode() ? '(mock)' : '(live)';
            if (!empty($paymentResult['TranID'])) {
                $msgParts[] = 'TranID: '.$paymentResult['TranID'];
            }
            if (!empty($paymentResult['Approve'])) {
                $msgParts[] = 'Approve: '.$paymentResult['Approve'];
            }
            $newOrder->setPaymentDate($now);
            $existingMessage = trim((string) $newOrder->getMessage());
            $renewalMessage = implode(' ', $msgParts);
            $newOrder->setMessage(
                '' === $existingMessage ? $renewalMessage : $existingMessage."\n".$renewalMessage
            );

            $this->shoppingPurchaseFlow->commit($newOrder, $context);

            $subRow->setBillingStatus(SubscriptionOrder::BILLING_STATUS_SUCCESS);
            $subRow->setBillingExecutedAt($now);
            $subRow->setUpdateDate($now);
            if (!empty($paymentResult['TranID'])) {
                $subRow->setGmoTranId($paymentResult['TranID']);
            }
            if (!empty($paymentResult['Approve'])) {
                $subRow->setGmoApprove($paymentResult['Approve']);
            }
            if (!empty($paymentResult['Status'])) {
                $subRow->setGmoStatus($paymentResult['Status']);
            }
            $subscription->setRetryCount(0);
            $subscription->setLastBilledAt($now);
            $currentFulfillmentAt = $subscription->getNextFulfillmentAt();
            $subscription->setLastFulfilledAt($currentFulfillmentAt);
            $nextFulfillmentAt = $this->subscriptionScheduler->computeNextFulfillmentAfter(
                $currentFulfillmentAt,
                $subscription->getPlanType(),
                $subscription->getIntervalCount()
            );
            $subscription->setNextFulfillmentAt($nextFulfillmentAt);
            $subscription->setNextBillingAt($this->subscriptionScheduler->computePreBillingAt($nextFulfillmentAt));

            $log = new SubscriptionEventLog();
            $log->setSubscription($subscription);
            $log->setEventType('renewal_success');
            $log->setPayload(json_encode(['order_id' => $newOrder->getId(), 'billing_key' => $billingKey], JSON_UNESCAPED_UNICODE));
            $log->setCreateDate($now);
            $this->entityManager->persist($log);

            $this->entityManager->flush();
            $this->subscriptionMailNotifier->notifyRenewalSuccess($subscription, $newOrder);
            log_info('[subscription] renewal success', ['billing_key' => $billingKey, 'order_id' => $newOrder->getId()]);
        } catch (\Throwable $e) {
            log_error('[subscription] renewal failed '.$e->getMessage(), ['billing_key' => $billingKey, 'subscription_id' => $subscription->getId()]);

            try {
                if ($newOrder && $newOrder->getId()) {
                    /**
                     * Nếu commit thất bại giữ đơn ở trạng thái xử lý — vận hành có thể hủy tay.
                     * Chuẩn hơn trong production: rollback transaction / void GMO.
                     */
                }
                $subRow->setBillingStatus(SubscriptionOrder::BILLING_STATUS_FAILED);
                $subRow->setBillingExecutedAt($now);
                $subRow->setErrorMessage(mb_substr($e->getMessage(), 0, 2000));
                $subRow->setUpdateDate($now);

                $subscription->incrementRetryCount();
                if ($subscription->getRetryCount() > $subscription->getMaxRetry()) {
                    $subscription->setStatus(Subscription::STATUS_PAST_DUE);
                    $subscription->setNextBillingAt((new \DateTime('now', new \DateTimeZone('UTC')))->modify('+365 days')); // không chạy sớm; vận hành vào tay
                    log_alert('[subscription] past_due', ['subscription_id' => $subscription->getId()]);
                    $this->subscriptionMailNotifier->notifyRenewalFailed($subscription, $e->getMessage(), true);
                } else {
                    $subscription->setNextBillingAt(
                        $this->subscriptionScheduler->computeRetryNextBilling($subscription, $this->retryDayOffsets)
                    );
                    $this->subscriptionMailNotifier->notifyRenewalFailed($subscription, $e->getMessage(), false);
                }
                $subscription->setUpdateDate($now);

                $log = new SubscriptionEventLog();
                $log->setSubscription($subscription);
                $log->setEventType('renewal_failure');
                $log->setPayload(json_encode([
                    'billing_key' => $billingKey,
                    'error' => $e->getMessage(),
                ], JSON_UNESCAPED_UNICODE));
                $log->setCreateDate($now);
                $this->entityManager->persist($log);

                $this->entityManager->flush();
            } catch (\Throwable $nested) {
                log_error('[subscription] failure handler error '.$nested->getMessage());
            }
        }
    }

}
