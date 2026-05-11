<?php

namespace Customize\Repository;

use Customize\Entity\Subscription;
use Customize\Entity\SubscriptionEventLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SubscriptionEventLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubscriptionEventLog::class);
    }

    /**
     * @return SubscriptionEventLog[]
     */
    public function findRecentBySubscription(Subscription $subscription, int $limit = 50): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.Subscription = :sub')
            ->setParameter('sub', $subscription)
            ->orderBy('e.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
