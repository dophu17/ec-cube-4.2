<?php

namespace Customize\Service\Subscription;

use Customize\Entity\Subscription;
use Customize\Repository\SubscriptionOrderRepository;
use Doctrine\ORM\EntityManagerInterface;

final class SubscriptionCancellationService
{
    private EntityManagerInterface $entityManager;
    private SubscriptionOrderRepository $subscriptionOrderRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        SubscriptionOrderRepository $subscriptionOrderRepository
    ) {
        $this->entityManager = $entityManager;
        $this->subscriptionOrderRepository = $subscriptionOrderRepository;
    }

    public function canCancel(Subscription $subscription): bool
    {
        if (Subscription::STATUS_ACTIVE !== $subscription->getStatus()) {
            return false;
        }

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        if ($subscription->getNextFulfillmentAt() <= $now) {
            return false;
        }

        return !$this->subscriptionOrderRepository->hasSuccessfulPreBillingForCurrentFulfillment($subscription);
    }

    public function cancel(Subscription $subscription): void
    {
        if (!$this->canCancel($subscription)) {
            throw new \RuntimeException('Không thể hủy: kỳ hiện tại đã thanh toán thành công hoặc đã qua fulfillment.');
        }

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $subscription->setStatus(Subscription::STATUS_CANCELLED);
        $subscription->setCancelledAt($now);
        $subscription->setUpdateDate($now);
        $this->entityManager->flush();
    }
}
