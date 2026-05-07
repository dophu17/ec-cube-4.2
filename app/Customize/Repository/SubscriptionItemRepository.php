<?php

namespace Customize\Repository;

use Customize\Entity\Subscription;
use Customize\Entity\SubscriptionItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SubscriptionItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubscriptionItem::class);
    }

    /**
     * @return SubscriptionItem[]
     */
    public function findBySubscriptionOrdered(Subscription $subscription): array
    {
        return $this->createQueryBuilder('si')
            ->where('si.Subscription = :s')
            ->setParameter('s', $subscription)
            ->orderBy('si.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
