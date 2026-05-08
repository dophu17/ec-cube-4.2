<?php

namespace Customize\Service\Subscription;

use Customize\Entity\Subscription;
use Customize\Entity\SubscriptionEventLog;
use Customize\Entity\SubscriptionItem;
use Customize\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;

final class SubscriptionActivator
{
    private EntityManagerInterface $entityManager;
    private SubscriptionRepository $subscriptionRepository;
    private SubscriptionScheduler $subscriptionScheduler;

    public function __construct(
        EntityManagerInterface $entityManager,
        SubscriptionRepository $subscriptionRepository,
        SubscriptionScheduler $subscriptionScheduler
    ) {
        $this->entityManager = $entityManager;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->subscriptionScheduler = $subscriptionScheduler;
    }

    /**
     * @internal Truyền chuỗi chu kỳ từ form (vd: test_10m).
     *
     * @throws \Throwable
     */
    public function createFromPaidOrder(Order $Order, string $cycleFormKey, bool $allowTestInterval, int $maxRetry): void
    {
        $existing = $this->subscriptionRepository->findOneBy(['base_order_id' => $Order->getId()]);
        if (null !== $existing) {
            return;
        }

        [$planType, $intervalCount] = SubscriptionCycleParser::fromFormValue($cycleFormKey, $allowTestInterval);
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        $sub = new Subscription();
        $sub->setCustomer($Order->getCustomer());
        $sub->setBaseOrderId((int) $Order->getId());
        $sub->setStatus(Subscription::STATUS_ACTIVE);
        $sub->setPlanType($planType);
        $sub->setIntervalCount($intervalCount);
        $sub->setRetryCount(0);
        $sub->setMaxRetry(max(1, $maxRetry));
        $int = fn ($v): int => is_numeric($v) ? (int) $v : 0;

        $sub->setSubtotalAmount((int) $Order->getSubtotal());
        $sub->setDiscountAmount(abs((int) $Order->getDiscount()));
        $sub->setShippingFee($int($Order->getDeliveryFeeTotal()));
        $sub->setTaxAmount($int($Order->getTax()));
        $sub->setTotalAmount((int) $Order->getPaymentTotal());

        $sub->setPaymentGateway('gmo');
        $sub->setGmoMemberId(null);
        $sub->setGmoCardSeq(null);
        $sub->setLastBilledAt($now);
        $nextFulfillmentAt = $this->subscriptionScheduler->computeNextFulfillmentAfter($now, $planType, $intervalCount);
        $sub->setNextFulfillmentAt($nextFulfillmentAt);
        $sub->setNextBillingAt($this->subscriptionScheduler->computePreBillingAt($nextFulfillmentAt));

        $sub->setCreateDate($now);
        $sub->setUpdateDate($now);

        foreach ($Order->getProductOrderItems() as $OrderItem) {
            /** @var OrderItem $OrderItem */
            $line = new SubscriptionItem();
            $line->setSubscription($sub);
            $line->setProduct($OrderItem->getProduct());
            $line->setProductClass($OrderItem->getProductClass());
            $line->setProductNameSnapshot((string) $OrderItem->getProductName());
            $line->setProductCodeSnapshot($OrderItem->getProductCode());
            $line->setQuantity((int) $OrderItem->getQuantity());
            $line->setUnitPriceSnapshot((int) $OrderItem->getPrice());
            $taxRate = method_exists($OrderItem, 'getTaxRate') ? $OrderItem->getTaxRate() : null;
            $line->setTaxRateSnapshot(null !== $taxRate ? (string) $taxRate : null);
            $line->setIsCombo(false);
            $line->setCreateDate($now);
            $line->setUpdateDate($now);
            $this->entityManager->persist($line);
        }

        $this->entityManager->persist($sub);

        $log = new SubscriptionEventLog();
        $log->setSubscription($sub);
        $log->setEventType('activated');
        $log->setPayload(json_encode(['cycle' => $cycleFormKey], JSON_UNESCAPED_UNICODE));
        $log->setCreateDate($now);
        $this->entityManager->persist($log);

        $this->entityManager->flush();
    }
}
