<?php

namespace Plugin\RemisePayment42\Repository;

use Eccube\Entity\Customer;
use Eccube\Entity\Order;
use Eccube\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry as RegistryInterface;
use Symfony\Component\HttpKernel\KernelInterface;

use Plugin\RemisePayment42\Entity\OrderResult;
use Plugin\RemisePayment42\Entity\OrderResultCard;

/**
 * カード決済履歴詳細アクセスクラス
 */
class OrderResultCardRepository extends AbstractRepository
{
    /**
     * コンストラクタ
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, OrderResultCard::class);
    }

    /**
     * @param $Order
     *
     * @return OrderResultCard|null|object
     */
    public function findOrCreate($Order)
    {
        $orderId = $Order->getId();
        $OrderResultCard = $this->find($orderId);

        if (!$OrderResultCard)
        {
            $OrderResultCard = new OrderResultCard();
            $OrderResultCard->setId($Order->getId());
            $OrderResultCard->getCreateDate(new \DateTime());
        }

        return $OrderResultCard;
    }

    /**
     * @param $orderIds
     *
     * @return OrderResultCard|null|object
     */
    public function getNotCompletedOrder($orderIds)
    {
        $qb = $this->createQueryBuilder('rms_orc');
        $qb->innerJoin('\Plugin\RemisePayment42\Entity\OrderResult', 'rms_or', 'WITH', 'rms_or.id = rms_orc.id');
        $qb->innerJoin('\Eccube\Entity\Order', 'ec_o', 'WITH', 'ec_o.id = rms_orc.id');

        // 受注を特定
        $qb
            ->where("rms_orc.id IN (:orderIds)")
            ->setParameter('orderIds', $orderIds);

        // 受注未確定を特定
        $qb
            ->andWhere('rms_orc.state = :state')
            ->setParameter('state', trans('remise_payment4.common.label.card.state.result'));

        $res = $qb->getQuery()->getResult();

        return $res;
    }

    /**
     * @param $orderIds
     *
     * @return OrderResultCard|null|object
     */
    public function getAcFailedOrder($orderIds)
    {
        $qb = $this->createQueryBuilder('rms_orc');
        $qb->innerJoin('\Plugin\RemisePayment42\Entity\OrderResult', 'rms_or', 'WITH', 'rms_or.id = rms_orc.id');
        $qb->innerJoin('\Eccube\Entity\Order', 'ec_o', 'WITH', 'ec_o.id = rms_orc.id');

        // 受注を特定
        $qb
        ->where("rms_orc.id IN (:orderIds)")
        ->setParameter('orderIds', $orderIds);

        // 課金失敗を特定
        $qb
        ->andWhere('rms_orc.state = :state')
        ->setParameter('state', trans('remise_payment4.common.label.card.state.result.ac.failed'));

        $res = $qb->getQuery()->getResult();

        return $res;
    }

    /**
     * @param $orderIds
     * @param $states
     *
     * @return OrderResultCard|null|object
     */
    public function getOrderResultCardsOrderIdsStates($orderIds,$states=Array())
    {
        $qb = $this->createQueryBuilder('rms_orc');
        $qb->innerJoin('\Plugin\RemisePayment42\Entity\OrderResult', 'rms_or', 'WITH', 'rms_or.id = rms_orc.id');
        $qb->innerJoin('\Eccube\Entity\Order', 'ec_o', 'WITH', 'ec_o.id = rms_orc.id');

        // 受注を特定
        $qb
        ->where("rms_orc.id IN (:orderIds)")
        ->setParameter('orderIds', $orderIds);

        // 受注ステータスを指定
        if($states)
        {
            $qb
            ->andWhere("rms_orc.state IN (:states)")
            ->setParameter('states', $states);
        }

        $res = $qb->getQuery()->getResult();

        return $res;
    }

    /**
     * @param $member_id
     *
     * @return OrderResultCard|null|object
     */
    public function getOrderResultCardsMemberIdJoinACResult($member_id)
    {
        $qb = $this->createQueryBuilder('rms_orc');
        $qb->innerJoin('\Plugin\RemisePayment42\Entity\RemiseACResult', 'rms_acr', 'WITH', 'rms_acr.id = rms_orc.id');
        $qb->where("rms_orc.member_id = :memberId")
        ->setParameter('memberId', $member_id);

        $qb->orderBy('rms_acr.charge_date', 'DESC');

        $res = $qb->getQuery()->getResult();

        return $res;
    }

}
