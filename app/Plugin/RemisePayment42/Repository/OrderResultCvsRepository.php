<?php

namespace Plugin\RemisePayment42\Repository;

use Eccube\Entity\Order;
use Eccube\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry as RegistryInterface;
use Symfony\Component\HttpKernel\KernelInterface;

use Plugin\RemisePayment42\Entity\OrderResultCvs;

/**
 * マルチ決済履歴詳細アクセスクラス
 */
class OrderResultCvsRepository extends AbstractRepository
{
    /**
     * コンストラクタ
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, OrderResultCvs::class);
    }

    /**
     * @param $Order
     *
     * @return OrderResultCvs|null|object
     */
    public function findOrCreate($Order)
    {
        $orderId = $Order->getId();
        $OrderResultCvs = $this->find($orderId);

        if (!$OrderResultCvs)
        {
            $OrderResultCvs = new OrderResultCvs();
            $OrderResultCvs->setId($Order->getId());
            $OrderResultCvs->getCreateDate(new \DateTime());
        }

        return $OrderResultCvs;
    }
}
