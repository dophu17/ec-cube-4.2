<?php

namespace Customize\Service\Subscription;

use Customize\Entity\Subscription;
use RuntimeException;

final class SubscriptionCycleParser
{
    /**
     * Form value => [plan_type, interval_count].
     *
     * @return array{0: string, 1: int}
     */
    public static function fromFormValue(string $cycleKey, bool $allowTestInterval): array
    {
        switch ($cycleKey) {
            case 'test_10m':
                if (!$allowTestInterval) {
                    throw new RuntimeException('subscription: chu kỳ test 10 phút chưa được bật.');
                }

                return [Subscription::PLAN_TEST_MINUTE, 10];
            case 'weekly_1':
                return [Subscription::PLAN_WEEKLY, 1];
            case 'monthly_1':
                return [Subscription::PLAN_MONTHLY, 1];
            case 'monthly_3':
                return [Subscription::PLAN_MONTHLY, 3];
            default:
                throw new RuntimeException('subscription: không hỗ trợ chu kỳ '.$cycleKey);
        }
    }
}
