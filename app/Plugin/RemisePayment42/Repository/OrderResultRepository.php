<?php

namespace Plugin\RemisePayment42\Repository;

use Eccube\Entity\Order;
use Eccube\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry as RegistryInterface;
use Symfony\Component\HttpKernel\KernelInterface;

use Plugin\RemisePayment42\Entity\OrderResult;

/**
 * 決済履歴アクセスクラス
 */
class OrderResultRepository extends AbstractRepository
{
    /**
     * コンストラクタ
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, OrderResult::class);
    }

    /**
     * @param $Order
     *
     * @return OrderResult|null|object
     */
    public function findOrCreate($Order)
    {
        $orderId = $Order->getId();
        $OrderResult = $this->find($orderId);

        if (!$OrderResult)
        {
            $OrderResult = new OrderResult();
            $OrderResult->setId($Order->getId());
            $OrderResult->getCreateDate(new \DateTime());
        }
        $OrderResult->setPaymentId($Order->getPayment()->getId());
        $OrderResult->setPaymentTotal($Order->getPaymentTotal());

        return $OrderResult;
    }
}
