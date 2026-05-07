<?php

namespace Plugin\RemisePayment42\Repository;

use Eccube\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry as RegistryInterface;
use Symfony\Component\HttpKernel\KernelInterface;

use Plugin\RemisePayment42\Entity\RemiseTaxRate;

/**
 *ルミーズ消費税率設定情報アクセスクラス
 */
class RemiseTaxRateRepository extends AbstractRepository
{
    /**
     * コンストラクタ
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, RemiseTaxRate::class);
    }

    /**
     * 現在の税率設定情報を取得
     *
     */
    public function getCurrentTaxRate()
    {
        // 適用中の税率を返却
        $today = new \DateTime();
        $qb = $this->createQueryBuilder('rms_tax');
        $qb
        ->andWhere('rms_tax.apply_date <= :applyDate')
        ->setParameter('applyDate', $today->format('Y-m-d H:i:s'))
        ->orderBy('rms_tax.apply_date','DESC');

        $res = $qb->getQuery()
                  ->setMaxResults(1)
                  ->getOneOrNullResult();

        return $res;
    }

    /**
     * 有効な全税率設定情報を取得
     *
     */
    public function getAllTaxRate(){

        // 税率設定情報の一覧を取得
        $qb = $this->createQueryBuilder('rms_tax');
        $qb->orderBy('rms_tax.apply_date','ASC');

        $res = $qb->getQuery()->getResult();

        return $res;
    }

}
