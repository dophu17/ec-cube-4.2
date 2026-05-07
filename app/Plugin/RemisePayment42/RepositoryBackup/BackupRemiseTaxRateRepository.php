<?php

namespace Plugin\RemisePayment42\RepositoryBackup;

use Eccube\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry as RegistryInterface;
use Symfony\Component\HttpKernel\KernelInterface;

use Plugin\RemisePayment42\EntityBackup\BackupRemiseTaxRate;

/**
 * ルミーズ販売種別アクセスクラス
 */
class BackupRemiseTaxRateRepository extends AbstractRepository
{
    /**
     * コンストラクタ
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, BackupRemiseTaxRate::class);
    }
}
