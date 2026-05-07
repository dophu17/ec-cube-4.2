<?php

namespace Plugin\RemisePayment42\Repository;

use Eccube\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry as RegistryInterface;
use Symfony\Component\HttpKernel\KernelInterface;

use Plugin\RemisePayment42\Entity\Config;

/**
 * プラグイン設定情報アクセスクラス
 */
class ConfigRepository extends AbstractRepository
{
    /**
     * コンストラクタ
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Config::class);
    }

    /**
     * @param string $code
     *
     * @return null|Config
     */
    public function findOneByCode($code)
    {
        return $this->findOneBy(array('code' => $code));
    }
}
