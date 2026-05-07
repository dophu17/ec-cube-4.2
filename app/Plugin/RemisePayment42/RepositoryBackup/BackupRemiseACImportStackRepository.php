<?php

namespace Plugin\RemisePayment42\RepositoryBackup;

use Eccube\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry as RegistryInterface;
use Eccube\Doctrine\Query\Queries;
use Plugin\RemisePayment42\EntityBackup\BackupRemiseACImportStack;

/**
 * ルミーズ定期購買バッチ取込管理情報アクセスクラス
 */
class BackupRemiseACImportStackRepository extends AbstractRepository
{
    /**
     * コンストラクタ
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry, Queries $queries)
    {
        parent::__construct($registry, BackupRemiseACImportStack::class);
        $this->queries = $queries;
    }
}