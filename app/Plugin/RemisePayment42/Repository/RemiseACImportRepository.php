<?php

namespace Plugin\RemisePayment42\Repository;

use Eccube\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry as RegistryInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Eccube\Doctrine\Query\Queries;
use Eccube\Util\StringUtil;

use Plugin\RemisePayment42\Entity\RemiseACImport;

/**
 * ルミーズ定期購買メンバ情報アクセスクラス
 */
class RemiseACImportRepository extends AbstractRepository
{
    /**
     * コンストラクタ
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry, Queries $queries)
    {
        parent::__construct($registry, RemiseACImport::class);
        $this->queries = $queries;
    }

    /**
     * get query builder.
     *
     * @param  array $searchData
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getQueryBuilderBySearchDataForAdmin()
    {
        $qb = $this->createQueryBuilder('rms_aci');
        $qb->select('rms_aci');
        $qb->orderBy('rms_aci.exec_date', 'DESC');

        return$qb;
    }

    /**
     * get query builder.
     *
     * @param  string  $execDate  Ymd形式の文字列
     *
     * @return RemiseACImport|null|Object
     */
    public function getExecData($execDate)
    {
        $execDateStart = new \DateTime($execDate.'000000');
        $execDateEnd = new \DateTime($execDate.'235959');
        $qb = $this->createQueryBuilder('rms_aci');
        $qb->select('rms_aci');
        $qb->andWhere(':exec_date_start <= rms_aci.exec_date AND rms_aci.exec_date <= :exec_date_end')
            ->setParameter('exec_date_start', $execDateStart)
            ->setParameter('exec_date_end', $execDateEnd);
        $res = $qb->getQuery()->getResult();

        return $res;
    }
}

