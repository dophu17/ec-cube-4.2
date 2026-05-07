<?php

namespace Plugin\RemisePayment42\Repository;

use Eccube\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry as RegistryInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Plugin\RemisePayment42\Entity\RemiseACImportStack;

/**
 * ルミーズ定期購買バッチ取込管理情報アクセスクラス
 */
class RemiseACImportStackRepository extends AbstractRepository
{
    /**
     * コンストラクタ
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry,  RemiseACImportStack::class);
    }

    /**
     * ルミーズ定期購買バッチ取込管理情報の取得
     *
     * @param string $chargeDate 取込日
     * @return  RemiseACImportStack  ルミーズ定期購買バッチ取込管理情報
     */
    public function getExecData($chargeDate) {
        $execDateStart = new \DateTime($chargeDate.'000000');
        $execDateEnd = new \DateTime($chargeDate.'235959');
        $qb = $this->createQueryBuilder('rms_acis')
            ->select('rms_acis')
            ->andWhere('rms_acis.import_date >= :exec_date_start AND rms_acis.import_date <= :exec_date_end AND rms_acis.exec_flg = 0')
            ->setParameter('exec_date_start', $execDateStart)
            ->setParameter('exec_date_end', $execDateEnd)
            ->orderBy('rms_acis.start_num', 'ASC');

        return $qb->getQuery()->getResult();
    }
}