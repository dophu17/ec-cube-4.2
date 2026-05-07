<?php

namespace Plugin\RemisePayment42\EntityBackup;

use Doctrine\ORM\Mapping as ORM;

/**
 * ペイクイック
 *
 * @ORM\Table(name="backup_plg_remise_payment4_payquick")
 * @ORM\Entity(repositoryClass="Plugin\RemisePayment42\RepositoryBackup\BackupPayquickRepository")
 */
class BackupPayquick
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="customer_id", type="integer", options={"unsigned":true})
     */
    private $customer_id;

    /**
     * @var string
     *
     * @ORM\Column(name="payquick_id", type="string", length=20, nullable=true)
     */
    private $payquick_id;

    /**
     * @var string
     *
     * @ORM\Column(name="card_parts", type="string", length=11, nullable=true)
     */
    private $card_parts;

    /**
     * @var string
     *
     * @ORM\Column(name="expire", type="string", length=4, nullable=true)
     */
    private $expire;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=30, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="card_brand", type="string", length=20, nullable=true)
     */
    private $card_brand;

    /**
     * @var string
     *
     * @ORM\Column(name="pre_payquick_id", type="string", length=20, nullable=true)
     */
    private $pre_payquick_id;

    /**
     * @var string
     *
     * @ORM\Column(name="pre_card_parts", type="string", length=11, nullable=true)
     */
    private $pre_card_parts;

    /**
     * @var string
     *
     * @ORM\Column(name="pre_expire", type="string", length=4, nullable=true)
     */
    private $pre_expire;

    /**
     * @var string
     *
     * @ORM\Column(name="pre_name", type="string", length=30, nullable=true)
     */
    private $pre_name;

    /**
     * @var string
     *
     * @ORM\Column(name="pre_card_brand", type="string", length=20, nullable=true)
     */
    private $pre_card_brand;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetimetz")
     */
    private $create_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_date", type="datetimetz")
     */
    private $update_date;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this;
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getCustomerId()
    {
        return $this->customer_id;
    }

    /**
     * @param int $customer_id
     *
     * @return $this;
     */
    public function setCustomerId($customer_id)
    {
        $this->customer_id = $customer_id;

        return $this;
    }

    /**
     * @return string
     */
    public function getPayquickId()
    {
        return $this->payquick_id;
    }

    /**
     * @param string $payquick_id
     *
     * @return $this;
     */
    public function setPayquickId($payquick_id)
    {
        $this->payquick_id = $payquick_id;

        return $this;
    }

    /**
     * @return string
     */
    public function getCardParts()
    {
        return $this->card_parts;
    }

    /**
     * @param string $card_parts
     *
     * @return $this;
     */
    public function setCardParts($card_parts)
    {
        $this->card_parts = $card_parts;

        return $this;
    }

    /**
     * @return string
     */
    public function getExpire()
    {
        return $this->expire;
    }

    /**
     * @param string $expire
     *
     * @return $this;
     */
    public function setExpire($expire)
    {
        $this->expire = $expire;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this;
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getCardBrand()
    {
        return $this->card_brand;
    }

    /**
     * @param string $card_brand
     *
     * @return $this;
     */
    public function setCardBrand($card_brand)
    {
        $this->card_brand = $card_brand;

        return $this;
    }

    /**
     * @return string
     */
    public function getPrePayquickId()
    {
        return $this->pre_payquick_id;
    }

    /**
     * @param string $pre_payquick_id
     *
     * @return $this;
     */
    public function setPrePayquickId($pre_payquick_id)
    {
        $this->pre_payquick_id = $pre_payquick_id;

        return $this;
    }

    /**
     * @return string
     */
    public function getPreCardParts()
    {
        return $this->pre_card_parts;
    }

    /**
     * @param string $pre_card_parts
     *
     * @return $this;
     */
    public function setPreCardParts($pre_card_parts)
    {
        $this->pre_card_parts = $pre_card_parts;

        return $this;
    }

    /**
     * @return string
     */
    public function getPreExpire()
    {
        return $this->pre_expire;
    }

    /**
     * @param string $pre_expire
     *
     * @return $this;
     */
    public function setPreExpire($pre_expire)
    {
        $this->pre_expire = $pre_expire;

        return $this;
    }

    /**
     * @return string
     */
    public function getPreName()
    {
        return $this->pre_name;
    }

    /**
     * @param string $pre_name
     *
     * @return $this;
     */
    public function setPreName($pre_name)
    {
        $this->pre_name = $pre_name;

        return $this;
    }

    /**
     * @return string
     */
    public function getPreCardBrand()
    {
        return $this->pre_card_brand;
    }

    /**
     * @param string $pre_card_brand
     *
     * @return $this;
     */
    public function setPreCardBrand($pre_card_brand)
    {
        $this->pre_card_brand = $pre_card_brand;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * @param \DateTime $create_date
     *
     * @return $this;
     */
    public function setCreateDate($create_date)
    {
        $this->create_date = $create_date;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }

    /**
     * @param \DateTime $update_date
     *
     * @return $this;
     */
    public function setUpdateDate($update_date)
    {
        $this->update_date = $update_date;

        return $this;
    }
}
