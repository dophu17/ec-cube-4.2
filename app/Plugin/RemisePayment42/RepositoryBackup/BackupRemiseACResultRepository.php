<?php

namespace Plugin\RemisePayment42\RepositoryBackup;

use Eccube\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry as RegistryInterface;
use Eccube\Doctrine\Query\Queries;

use Plugin\RemisePayment42\EntityBackup\BackupRemiseACResult;

/**
 * ルミーズ定期購買結果情報アクセスクラス
 */
class BackupRemiseACResultRepository extends AbstractRepository
{
    /**
     * コンストラクタ
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry, Queries $queries)
    {
        parent::__construct($registry, BackupRemiseACResult::class);
        $this->queries = $queries;
    }
}

