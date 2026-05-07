<?php

namespace Plugin\RemisePayment42\RepositoryBackup;

use Eccube\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry as RegistryInterface;
use Eccube\Doctrine\Query\Queries;
use Plugin\RemisePayment42\EntityBackup\BackupRemiseACImportStackResult;

/**
 * ルミーズ定期購買バッチ取込情報アクセスクラス
 */
class BackupRemiseACImportStackResultRepository extends AbstractRepository
{
    /**
     * コンストラクタ
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry, Queries $queries)
    {
        parent::__construct($registry, BackupRemiseACImportStackResult::class);
        $this->queries = $queries;
    }
}