<?php

namespace Customize\Service\Subscription;

use Customize\Entity\Subscription;
use Customize\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;

final class SubscriptionAdminService
{
    private EntityManagerInterface $entityManager;
    private SubscriptionRepository $subscriptionRepository;
    private SubscriptionBillingRunner $billingRunner;
    private SubscriptionCancellationService $cancellationService;
    private bool $subscriptionEnabled;

    public function __construct(
        EntityManagerInterface $entityManager,
        SubscriptionRepository $subscriptionRepository,
        SubscriptionBillingRunner $subscriptionBillingRunner,
        SubscriptionCancellationService $subscriptionCancellationService,
        bool $subscriptionEnabled
    ) {
        $this->entityManager = $entityManager;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->billingRunner = $subscriptionBillingRunner;
        $this->cancellationService = $subscriptionCancellationService;
        $this->subscriptionEnabled = $subscriptionEnabled;
    }

    public function isSubscriptionEnabled(): bool
    {
        return $this->subscriptionEnabled;
    }

    /**
     * Chạy một lần charge gia hạn (giống cron) trong transaction. Cho phép active hoặc past_due (sẽ kích hoạt lại trước khi charge).
     *
     * @throws \RuntimeException
     */
    public function billNow(Subscription $subscription): void
    {
        if (!$this->subscriptionEnabled) {
            throw new \RuntimeException('Subscription đang tắt (SUBSCRIPTION_ENABLED=0).');
        }

        $id = (int) $subscription->getId();
        if ($id <= 0) {
            throw new \RuntimeException('Subscription không hợp lệ.');
        }

        $this->entityManager->beginTransaction();
        try {
            $s = $this->subscriptionRepository->find($id);
            if (!$s instanceof Subscription) {
                throw new \RuntimeException('Không tìm thấy subscription.');
            }

            $status = $s->getStatus();
            if (\in_array($status, [Subscription::STATUS_CANCELLED, Subscription::STATUS_EXPIRED], true)) {
                throw new \RuntimeException('Subscription đã kết thúc, không thể charge.');
            }
            if (Subscription::STATUS_PAUSED === $status) {
                throw new \RuntimeException('Đang tạm dừng — không charge được từ Admin (khách cần resume trên MyPage).');
            }
            if (Subscription::STATUS_PENDING_ACTIVATION === $status) {
                throw new \RuntimeException('Chưa kích hoạt — không charge được từ Admin.');
            }

            $now = new \DateTime('now', new \DateTimeZone('UTC'));
            if (Subscription::STATUS_PAST_DUE === $status) {
                $s->setStatus(Subscription::STATUS_ACTIVE);
                $s->setRetryCount(0);
                $s->setNextBillingAt(clone $now);
                $s->setUpdateDate($now);
                $this->entityManager->flush();
            }

            if (Subscription::STATUS_ACTIVE !== $s->getStatus()) {
                throw new \RuntimeException('Trạng thái hiện tại không cho phép charge.');
            }

            $this->billingRunner->execute($s, false);

            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->commit();
            }
        } catch (\Throwable $e) {
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }
            throw $e;
        }
    }

    /**
     * @return string 'cancelled'|'force_cancelled'
     */
    public function cancelSubscription(Subscription $subscription): string
    {
        $s = $this->subscriptionRepository->find($subscription->getId());
        if (!$s instanceof Subscription) {
            throw new \RuntimeException('Không tìm thấy subscription.');
        }

        if (\in_array($s->getStatus(), [Subscription::STATUS_CANCELLED, Subscription::STATUS_EXPIRED], true)) {
            throw new \RuntimeException('Subscription đã ở trạng thái kết thúc.');
        }

        if (Subscription::STATUS_ACTIVE === $s->getStatus()) {
            try {
                $this->cancellationService->cancel($s);

                return 'cancelled';
            } catch (\RuntimeException $e) {
                // Hủy theo luật nghiệp vụ thất bại → hủy bắt buộc (admin)
            }
        }

        $this->forceCancel($s);

        return 'force_cancelled';
    }

    private function forceCancel(Subscription $subscription): void
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $subscription->setStatus(Subscription::STATUS_CANCELLED);
        $subscription->setCancelledAt($now);
        $subscription->setUpdateDate($now);
        $this->entityManager->flush();
    }
}
