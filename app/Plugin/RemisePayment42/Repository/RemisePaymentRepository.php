<?php

namespace Plugin\RemisePayment42\Repository;

use Eccube\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry as RegistryInterface;
use Symfony\Component\HttpKernel\KernelInterface;

use Plugin\RemisePayment42\Entity\RemisePayment;

/**
 * ルミーズ支払方法アクセスクラス
 */
class RemisePaymentRepository extends AbstractRepository
{
    /**
     * コンストラクタ
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, RemisePayment::class);
    }

    /**
     * @param string $kind
     *
     * @return null|RemisePayment
     */
    public function findByKind($kind)
    {
        return $this->findBy(array('kind' => $kind));
    }
}
