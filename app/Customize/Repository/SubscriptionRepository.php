<?php

namespace Customize\Repository;

use Customize\Entity\Subscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    public function getAdminListQueryBuilder(
        ?string $status,
        ?\DateTimeInterface $nextBillingFrom,
        ?\DateTimeInterface $nextBillingTo,
        ?int $retryExact
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.Customer', 'c')->addSelect('c')
            ->orderBy('s.id', 'DESC');

        if (null !== $status && '' !== $status) {
            $qb->andWhere('s.status = :st')->setParameter('st', $status);
        }
        if ($nextBillingFrom instanceof \DateTimeInterface) {
            $qb->andWhere('s.next_billing_at >= :nbf')->setParameter('nbf', $nextBillingFrom);
        }
        if ($nextBillingTo instanceof \DateTimeInterface) {
            $qb->andWhere('s.next_billing_at <= :nbt')->setParameter('nbt', $nextBillingTo);
        }
        if (null !== $retryExact) {
            $qb->andWhere('s.retry_count = :rc')->setParameter('rc', $retryExact);
        }

        return $qb;
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
