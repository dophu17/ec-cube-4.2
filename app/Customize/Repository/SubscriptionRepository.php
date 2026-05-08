<?php

namespace Customize\Repository;

use Customize\Entity\Subscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    /**
     * @return Subscription[]
     */
    public function findDueActive(int $limit): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.status = :st')
            ->andWhere('s.next_billing_at <= CURRENT_TIMESTAMP()')
            ->setParameter('st', Subscription::STATUS_ACTIVE)
            ->orderBy('s.next_billing_at', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
