<?php

namespace Customize\Service\Subscription;

use Customize\Entity\Subscription;

final class SubscriptionScheduler
{
    public function computeNextFulfillmentAfter(\DateTimeInterface $from, string $planType, int $intervalCount): \DateTime
    {
        $mutable = $from instanceof \DateTime ? clone $from : new \DateTime($from->format('c'));

        $immutable = \DateTimeImmutable::createFromMutable($mutable);
        if ($immutable === false) {
            throw new \RuntimeException('Không khởi tạo được DateTimeImmutable từ mốc billing.');
        }

        if (Subscription::PLAN_TEST_MINUTE === $planType) {
            $nextImmutable = $immutable->modify(sprintf('+%d minutes', max(1, $intervalCount)));
        } elseif (Subscription::PLAN_WEEKLY === $planType) {
            $nextImmutable = $immutable->modify(sprintf('+%d weeks', max(1, $intervalCount)));
        } elseif (Subscription::PLAN_MONTHLY === $planType) {
            $nextImmutable = $immutable->modify(sprintf('+%d months', max(1, $intervalCount)));
        } else {
            throw new \InvalidArgumentException('Không nhận diện được plan_type: '.$planType);
        }

        $out = \DateTime::createFromImmutable($nextImmutable);

        return $out instanceof \DateTime ? $out : new \DateTime($mutable->format('c'));
    }

    public function computePreBillingAt(\DateTimeInterface $fulfillmentAt): \DateTime
    {
        $billingAt = $fulfillmentAt instanceof \DateTime
            ? clone $fulfillmentAt
            : new \DateTime($fulfillmentAt->format('c'));

        if ($billingAt === false) {
            $billingAt = new \DateTime('now', new \DateTimeZone('UTC'));
        }

        return $billingAt->modify('-1 day');
    }

    /**
     * Lịch gia lại retry (MVP): test minute + vài phút; chuẩn +1 ngày.
     *
     * @return \DateTime
     */
    public function computeRetryNextBilling(Subscription $subscription, array $retryDayOffsets): \DateTime
    {
        $nextIndex = min($subscription->getRetryCount(), count($retryDayOffsets) - 1);
        $offset = max(1, $retryDayOffsets[$nextIndex] ?? end($retryDayOffsets));

        if (Subscription::PLAN_TEST_MINUTE === $subscription->getPlanType()) {
            $base = new \DateTime('now', new \DateTimeZone('UTC'));

            return $base->modify('+'.max(2, min(60, $offset * 10)).' minutes');
        }

        return (new \DateTime('now', new \DateTimeZone('UTC')))->modify('+'.$offset.' days');
    }
}
