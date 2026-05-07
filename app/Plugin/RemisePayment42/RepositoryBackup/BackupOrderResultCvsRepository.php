<?php

namespace Plugin\RemisePayment42\RepositoryBackup;

use Eccube\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry as RegistryInterface;
use Symfony\Component\HttpKernel\KernelInterface;

use Plugin\RemisePayment42\EntityBackup\BackupOrderResultCvs;

/**
 * マルチ決済履歴詳細アクセスクラス
 */
class BackupOrderResultCvsRepository extends AbstractRepository
{
    /**
     * コンストラクタ
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, BackupOrderResultCvs::class);
    }
}
