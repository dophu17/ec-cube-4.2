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
        ?int $retryExact,
        ?int $customerId = null
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
        if (null !== $customerId && $customerId > 0) {
            $qb->andWhere('c.id = :customerId')->setParameter('customerId', $customerId);
        }

        return $qb;
    }

    /**
     * @return Subscription[]
     */
    public function findByCustomerIdForAdmin(int $customerId): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.Customer', 'cust')
            ->where('cust.id = :cid')
            ->setParameter('cid', $customerId)
            ->orderBy('s.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int[] $customerIds
     *
     * @return array<int, int> customer_id => subscription count
     */
    public function countGroupedByCustomerIds(array $customerIds): array
    {
        if ([] === $customerIds) {
            return [];
        }
        $rows = $this->createQueryBuilder('s')
            ->select('c.id AS cid')
            ->addSelect('COUNT(s.id) AS cnt')
            ->join('s.Customer', 'c')
            ->where('c.id IN (:ids)')
            ->groupBy('c.id')
            ->setParameter('ids', $customerIds)
            ->getQuery()
            ->getArrayResult();

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['cid']] = (int) $row['cnt'];
        }

        return $out;
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
