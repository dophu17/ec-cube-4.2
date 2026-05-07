<?php

namespace Plugin\RemisePayment42\Repository;

use Eccube\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry as RegistryInterface;
use Eccube\Doctrine\Query\Queries;

use Plugin\RemisePayment42\Entity\RemiseACResult;

/**
 * ルミーズ定期購買結果情報アクセスクラス
 */
class RemiseACResultRepository extends AbstractRepository
{
    /**
     * コンストラクタ
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry, Queries $queries)
    {
        parent::__construct($registry, RemiseACResult::class);
        $this->queries = $queries;
    }
}

