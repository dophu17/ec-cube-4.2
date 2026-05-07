<?php

namespace Plugin\RemisePayment42\Repository;

use Eccube\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry as RegistryInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Eccube\Doctrine\Query\Queries;
use Eccube\Util\StringUtil;
use Eccube\Entity\Master\OrderItemType;

use Plugin\RemisePayment42\Entity\RemiseACMember;

/**
 * ルミーズ定期購買メンバ情報アクセスクラス
 */
class RemiseACMemberRepository extends AbstractRepository
{
    /**
     * コンストラクタ
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry, Queries $queries)
    {
        parent::__construct($registry, RemiseACMember::class);
        $this->queries = $queries;
    }

    /**
     * get query builder.
     *
     * @param  array $searchData
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getQueryBuilderBySearchDataForAdmin($searchData)
    {
        $qb = $this->createQueryBuilder('rms_acm');
        $qb->innerJoin('\Plugin\RemisePayment42\Entity\OrderResultCard', 'rms_orc', 'WITH', 'rms_orc.member_id = rms_acm.id');
        $qb->innerJoin('\Plugin\RemisePayment42\Entity\OrderResult', 'rms_or', 'WITH', 'rms_or.id = rms_orc.id');
        $qb->innerJoin('\Eccube\Entity\Order', 'ec_o', 'WITH', 'ec_o.id = rms_or.id');
        $qb->select('rms_acm');
        $qb->groupBy('rms_acm.id');
        $qb->orderBy('rms_acm.update_date', 'DESC');

        // multi
        if (isset($searchData['multi']) && StringUtil::isNotBlank($searchData['multi'])) {

            $multiCustomerId = preg_match('/^\d{0,10}$/', $searchData['multi']) ? $searchData['multi'] : null;

            $qb
            ->andWhere(
                'rms_acm.id = :multi'
                . ' OR ec_o.Customer = :multi_customer_id'
                . ' OR CONCAT(ec_o.name01, ec_o.name02) LIKE :likemulti'
                )
                ->setParameter('multi', $searchData['multi'])
                ->setParameter('multi_customer_id', $multiCustomerId)
                ->setParameter('likemulti', '%' . preg_replace("/( |　)/", "", $searchData['multi'] ) . '%');
        }
        // 定期購買の状態
        if (isset($searchData['status']) && StringUtil::isNotBlank($searchData['status'])) {
            $qb
            ->andWhere('rms_acm.status = :status')
            ->setParameter('status', $searchData['status']);
        }
        // メンバーID（ルミーズ発番のID）
        if (isset($searchData['member_id']) && StringUtil::isNotBlank($searchData['member_id'])) {
            $qb
            ->andWhere('rms_acm.id = :member_id')
            ->setParameter('member_id', $searchData['member_id']);
        }
        // 注文番号
        if (isset($searchData['order_no']) && StringUtil::isNotBlank($searchData['order_no'])) {
            $qb
            ->andWhere('ec_o.order_no = :order_no')
            ->setParameter('order_no', $searchData['order_no']);
        }
        // 会員番号
        if (isset($searchData['customer_id']) && StringUtil::isNotBlank($searchData['customer_id'])) {

            $customerId = preg_match('/^\d{0,10}$/', $searchData['customer_id']) ? $searchData['customer_id'] : null;

            $qb
            ->andWhere('ec_o.Customer = :customer_id')
            ->setParameter('customer_id', $customerId);
        }
        // お名前
        if (isset($searchData['customer_name']) && StringUtil::isNotBlank($searchData['customer_name'])) {
            $qb
            ->andWhere('CONCAT(ec_o.name01, ec_o.name02) LIKE :customer_name')
            ->setParameter('customer_name', '%' . preg_replace("/( |　)/", "", $searchData['customer_name'] ) . '%');
        }
        // お名前(フリガナ)
        if (isset($searchData['customer_kana']) && StringUtil::isNotBlank($searchData['customer_kana'])) {
            $qb
            ->andWhere('CONCAT(ec_o.kana01, ec_o.kana02) LIKE :customer_kana')
            ->setParameter('customer_kana', '%' . preg_replace("/( |　)/", "", $searchData['customer_kana'] ) . '%');
        }
        // メールアドレス
        if (isset($searchData['customer_mail']) && StringUtil::isNotBlank($searchData['customer_mail'])) {
            $qb
            ->andWhere('ec_o.email = :customer_mail')
            ->setParameter('customer_mail', $searchData['customer_mail']);
        }
        // 電話番号
        if (isset($searchData['customer_tel']) && StringUtil::isNotBlank($searchData['customer_tel'])) {
            $qb
            ->andWhere('ec_o.phone_number LIKE :customer_tel')
            ->setParameter('customer_tel', $searchData['customer_tel']);
        }
        // 購入商品名
        if (isset($searchData['product_name']) && StringUtil::isNotBlank($searchData['product_name'])) {
        $qb->leftJoin('\Eccube\Entity\OrderItem', 'ec_oi', 'WITH', 'ec_oi.Order = ec_o.id')
            ->andWhere('ec_oi.OrderItemType = :order_item_type_id')
            ->andWhere('ec_oi.product_name LIKE :product_name')
            ->setParameter('order_item_type_id', OrderItemType::PRODUCT)
            ->setParameter('product_name', '%' . $searchData['product_name'] . '%');
        }
        // 定期購買金額
        if (isset($searchData['total_start']) && StringUtil::isNotBlank($searchData['total_start'])) {
            $qb
            ->andWhere('rms_acm.total >= :total_start')
            ->setParameter('total_start', $searchData['total_start']);
        }
        if (isset($searchData['total_end']) && StringUtil::isNotBlank($searchData['total_end'])) {
            $qb
            ->andWhere('rms_acm.total <= :total_end')
            ->setParameter('total_end', $searchData['total_end']);
        }
        // 受注日
        if (!empty($searchData['order_date_start']) && $searchData['order_date_start']) {
            $date = $searchData['order_date_start']
            ->format('Y-m-d H:i:s');
            $qb
            ->andWhere('ec_o.order_date >= :order_date_start')
            ->setParameter('order_date_start', $date);
        }
        if (!empty($searchData['order_date_end']) && $searchData['order_date_end']) {
            $date = clone $searchData['order_date_end'];
            $date = $date
            ->modify('+1 days')
            ->format('Y-m-d H:i:s');
            $qb
            ->andWhere('ec_o.order_date < :order_date_end')
            ->setParameter('order_date_end', $date);
        }
        // 次回課金日
        if (!empty($searchData['next_date_start']) && $searchData['next_date_start']) {
            $date = $searchData['next_date_start']
            ->format('Y-m-d H:i:s');
            $qb
            ->andWhere('rms_acm.next_date >= :next_date_start')
            ->setParameter('next_date_start', $date);
        }
        if (!empty($searchData['next_date_end']) && $searchData['next_date_end']) {
            $date = clone $searchData['next_date_end'];
            $date = $date
            ->modify('+1 days')
            ->format('Y-m-d H:i:s');
            $qb
            ->andWhere('rms_acm.next_date < :next_date_end')
            ->setParameter('next_date_end', $date);
        }

        return $this->queries->customize('RemiseACMember.getQueryBuilderBySearchData', $qb, $searchData);
    }

    /**
     * @param $customerIds
     *
     * @return RemiseACMember|null|object
     */
    public function getCustomersByCustomerIds($customerIds)
    {
        $qb = $this->createQueryBuilder('rms_acm');
        $qb->innerJoin('\Plugin\RemisePayment42\Entity\OrderResultCard', 'rms_orc', 'WITH', 'rms_orc.member_id = rms_acm.id');
        $qb->innerJoin('\Plugin\RemisePayment42\Entity\OrderResult', 'rms_or', 'WITH', 'rms_or.id = rms_orc.id');
        $qb->innerJoin('\Eccube\Entity\Order', 'ec_o', 'WITH', 'ec_o.id = rms_orc.id');
        $qb->innerJoin('\Eccube\Entity\Customer', 'ec_cus', 'WITH', 'ec_cus.id = ec_o.Customer');
        $qb->select('ec_cus');
        $qb->groupBy('ec_cus.id');

        // 顧客を指定
        $qb
        ->where("ec_o.Customer IN (:customerIds)")
        ->setParameter('customerIds', $customerIds);

        // 定期購買継続中を絞り込む
        $qb
        ->andWhere('rms_acm.status = :status')
        ->setParameter('status', 1);

        $res = $qb->getQuery()->getResult();

        return $res;
    }

    /**
     * @param $customerIds
     *
     * @return RemiseACMember|null|object
     */
    public function getRemiseACMembersByCustomerId($customerId)
    {
        $qb = $this->createQueryBuilder('rms_acm');
        $qb->innerJoin('\Plugin\RemisePayment42\Entity\OrderResultCard', 'rms_orc', 'WITH', 'rms_orc.member_id = rms_acm.id');
        $qb->innerJoin('\Plugin\RemisePayment42\Entity\OrderResult', 'rms_or', 'WITH', 'rms_or.id = rms_orc.id');
        $qb->innerJoin('\Eccube\Entity\Order', 'ec_o', 'WITH', 'ec_o.id = rms_orc.id');
        $qb->select('rms_acm');
        $qb->orderBy('rms_acm.update_date', 'DESC');

        // 顧客を指定
        $qb
        ->where("ec_o.Customer = :customerId")
        ->setParameter('customerId', $customerId);

        // 定期購買継続中を絞り込む
        $qb
        ->andWhere('rms_acm.status = :status')
        ->setParameter('status', 1);

        $res = $qb->getQuery()->getResult();

        return $res;
    }
}

