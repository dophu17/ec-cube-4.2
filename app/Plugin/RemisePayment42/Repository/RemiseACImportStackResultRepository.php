<?php

namespace Plugin\RemisePayment42\Repository;

use Eccube\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry as RegistryInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Plugin\RemisePayment42\Entity\RemiseACImportStackResult;

/**
 * ルミーズ定期購買バッチ取込情報アクセスクラス
 */
class RemiseACImportStackResultRepository extends AbstractRepository
{
    /**
     * コンストラクタ
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry,  RemiseACImportStackResult::class);
    }

    /**
     * ルミーズ定期購買バッチ取込情報の取得
     *
     * @param string  $chargeDate  取込日
     * @return  RemiseACImportStackResult  ルミーズ定期購買バッチ取込情報
     */
    public function getAcResultData($chargeDate) {
        $execDateStart = new \DateTime($chargeDate.'000000');
        $execDateEnd = new \DateTime($chargeDate.'235959');
        $qb = $this->createQueryBuilder('rms_acisr')
            ->select('rms_acisr')
            ->andWhere('rms_acisr.import_date >= :exec_date_start AND rms_acisr.import_date <= :exec_date_end')
            ->setParameter('exec_date_start', $execDateStart)
            ->setParameter('exec_date_end', $execDateEnd);

        return $qb->getQuery()->getResult();
    }
}