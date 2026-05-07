<?php

namespace Plugin\RemisePayment42\Repository;

use Eccube\Entity\Customer;
use Eccube\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry as RegistryInterface;
use Symfony\Component\HttpKernel\KernelInterface;

use Plugin\RemisePayment42\Entity\Payquick;

/**
 * ペイクイックアクセスクラス
 */
class PayquickRepository extends AbstractRepository
{
    /**
     * コンストラクタ
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Payquick::class);
    }

    /**
     * @param Customer $Customer
     *
     * @return null|Payquick
     */
    public function findByCustomer($Customer)
    {
        return $this->findOneBy(array('customer_id' => $Customer->getId()));
    }

    /**
     * @param Customer $Customer
     *
     * @return null|Payquick
     */
    public function findOrCreate($Customer)
    {
        $customerId = $Customer->getId();

        $Payquick = $this->findOneBy(array(
            'customer_id' => $customerId,
        ));

        if (!$Payquick)
        {
            $Payquick = new Payquick();
            $Payquick->setCustomerId($customerId);
            $Payquick->getCreateDate(new \DateTime());
        }

        return $Payquick;
    }
}
