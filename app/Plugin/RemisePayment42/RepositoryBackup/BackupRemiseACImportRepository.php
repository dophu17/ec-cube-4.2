<?php

namespace Plugin\RemisePayment42\RepositoryBackup;

use Eccube\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry as RegistryInterface;
use Eccube\Doctrine\Query\Queries;

use Plugin\RemisePayment42\EntityBackup\BackupRemiseACImport;

/**
 * ルミーズ定期購買メンバ情報アクセスクラス
 */
class BackupRemiseACImportRepository extends AbstractRepository
{
    /**
     * コンストラクタ
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry, Queries $queries)
    {
        parent::__construct($registry, BackupRemiseACImport::class);
        $this->queries = $queries;
    }
}

