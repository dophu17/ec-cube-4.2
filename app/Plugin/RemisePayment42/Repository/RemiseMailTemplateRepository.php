<?php

namespace Plugin\RemisePayment42\Repository;

use Eccube\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry as RegistryInterface;
use Symfony\Component\HttpKernel\KernelInterface;

use Plugin\RemisePayment42\Entity\RemiseMailTemplate;

/**
 * ルミーズメールテンプレートアクセスクラス
 */
class RemiseMailTemplateRepository extends AbstractRepository
{
    /**
     * コンストラクタ
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, RemiseMailTemplate::class);
    }

    /**
     * @param string $kind
     *
     * @return null|RemiseMailTemplate
     */
    public function findOneByKind($kind)
    {
        return $this->findOneBy(array('kind' => $kind));
    }
}
