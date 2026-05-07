<?php

namespace Plugin\RemisePayment42\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\CustomerAddress;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Repository\AbstractRepository;

/**
 * EMV3DS情報アクセスクラス
 */
class Emv3dsRepository extends AbstractRepository
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * お届け先住所の最終変更日を取得
     *
     * @param \Eccube\Entity\Customer $Customer
     *
     * @return \DateTime|null
     */
    public function getLastUpdateDate($Customer)
    {
        $queryBuilder = $this->entityManager->createQueryBuilder('ca');
        $queryBuilder
            ->select('ca.update_date')
            ->from(CustomerAddress::class, 'ca')
            ->andWhere('ca.Customer = :Customer')
            ->andWhere('ca.update_date > :update_date')
            ->setParameters([
                'Customer' => $Customer,
                'update_date' => $Customer->getUpdateDate(),
            ])
            ->orderBy('ca.update_date', 'desc')
            ->setMaxResults(1);

        $result = $queryBuilder->getQuery()->getResult();
        $updateDate = null;
        if (!is_null($result) && count($result) > 0) {
            $updateDate = $result[0]['update_date'];
        }

        return $updateDate;
    }

    /**
     * カード決済の受注件数を取得
     *
     * @param \Eccube\Entity\Customer $Customer
     * @param \Eccube\Entity\Payment $Payment
     * @param \DateTime $orderDate
     *
     * @return string
     */
    public function getActivityCount($Customer, $Payment, $orderDate)
    {
        $queryBuilder = $this->entityManager->createQueryBuilder('o');
        $queryBuilder
            ->select('count(o.id)')
            ->from(Order::class, 'o')
            ->andWhere('o.Customer = :Customer')
            ->andWhere('o.OrderStatus <> '. OrderStatus::PENDING)
            ->andWhere('o.OrderStatus <> '. OrderStatus::PROCESSING)
            ->andWhere('o.Payment = :Payment')
            ->andWhere('o.order_date > :order_date')
            ->setParameters([
                'Customer' => $Customer,
                'Payment' => $Payment,
                'order_date' => $orderDate
            ]);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * 全支払方法の受注件数を取得
     *
     * @param \Eccube\Entity\Customer $Customer
     * @param \DateTime $orderDate
     *
     * @return string
     */
    public function getPurchaseCount($Customer, $orderDate) 
    {
        $queryBuilder = $this->entityManager->createQueryBuilder('o');
        $queryBuilder
            ->select('count(o.id)')
            ->from(Order::class, 'o')
            ->andWhere('o.Customer = :Customer')
            ->andWhere($queryBuilder->expr()->in(
                'o.OrderStatus', [OrderStatus::DELIVERED, OrderStatus::PAID]
                ))
            ->andWhere('o.order_date > :order_date')
            ->setParameters([
                'Customer' => $Customer,
                'order_date' => $orderDate,
            ]);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * 出荷情報が同じ受注情報の注文日時を取得
     *
     * @param \Eccube\Entity\Customer $Customer
     * @param \Eccube\Entity\Shipping $Shipping
     *
     * @return \DateTime|null
     */
    public function getShipAddressUsage($Customer, $Shipping)
    {
        $queryBuilder = $this->entityManager->createQueryBuilder('o');
        $queryBuilder
            ->select('o.order_date')
            ->from(Order::class, 'o')
            ->join('o.Shippings', 's')
            ->andWhere('o.Customer = :Customer')
            ->andWhere('s.Pref = :Pref')
            ->andWhere('s.addr01 = :addr01')
            ->andWhere('s.addr02 = :addr02')
            ->andWhere($queryBuilder->expr()->in('o.OrderStatus', [OrderStatus::DELIVERED, OrderStatus::PAID]))
            ->andWhere('o.order_date is NOT NULL')
            ->setParameters([
                'Customer' => $Customer,
                'Pref' => $Shipping->getPref(),
                'addr01' => $Shipping->getAddr01(),
                'addr02' => $Shipping->getAddr02(),
            ])
            ->orderBy('o.order_date')
            ->setMaxResults(1);

        $result = $queryBuilder->getQuery()->getResult();
        $orderDate = null;
        if (!is_null($result) && count($result) > 0) {
            $orderDate = $result[0]['order_date'];
        }

        return $orderDate;
    }

    /**
     * 同じ商品の受注件数を取得
     *
     * @param \Eccube\Entity\Order $Order
     * @param \Eccube\Entity\Customer $Customer
     *
     * @return string
     */
    public function getReorderItemCount($Order, $Customer)
    {
        $subQueryBuilder = $this->entityManager->createQueryBuilder('oi2')
            ->from(OrderItem::class, 'oi2')
            ->select('p.id')
            ->leftJoin('oi2.Product', 'p')
            ->andWhere('oi2.Order = :Order')
            ->andWhere('oi2.OrderItemType = '. OrderItemType::PRODUCT);

        $queryBuilder = $this->entityManager->createQueryBuilder('o');
        $queryBuilder->select('count(o.id)')
            ->from(Order::class, 'o')
            ->join('o.OrderItems', 'oi')
            ->andWhere('o.Customer = :Customer')
            ->andWhere('oi.Product IN ('. $subQueryBuilder->getDQL(). ')')
            ->andWhere('o.id != :order_id')
            ->andWhere($queryBuilder->expr()->in('o.OrderStatus', [OrderStatus::DELIVERED, OrderStatus::PAID]))
            ->setParameters([
                'Order' => $Order,
                'Customer' => $Customer,
                'order_id' => $Order->getId()
            ]);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }
}