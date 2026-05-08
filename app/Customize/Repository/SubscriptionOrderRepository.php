<?php

namespace Customize\Repository;

use Customize\Entity\Subscription;
use Customize\Entity\SubscriptionOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SubscriptionOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubscriptionOrder::class);
    }

    public function hasSuccessfulPreBillingForCurrentFulfillment(Subscription $subscription): bool
    {
        $fulfillmentAt = $subscription->getNextFulfillmentAt();
        $windowStart = clone $fulfillmentAt;
        $windowStart->modify('-1 day');

        $count = (int) $this->createQueryBuilder('so')
            ->select('COUNT(so.id)')
            ->where('so.Subscription = :subscription')
            ->andWhere('so.billing_status = :status')
            ->andWhere('so.billing_scheduled_at >= :windowStart')
            ->andWhere('so.billing_scheduled_at < :fulfillmentAt')
            ->setParameter('subscription', $subscription)
            ->setParameter('status', SubscriptionOrder::BILLING_STATUS_SUCCESS)
            ->setParameter('windowStart', $windowStart)
            ->setParameter('fulfillmentAt', $fulfillmentAt)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
