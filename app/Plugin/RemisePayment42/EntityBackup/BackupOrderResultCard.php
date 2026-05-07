<?php

namespace Plugin\RemisePayment42\EntityBackup;

use Doctrine\ORM\Mapping as ORM;

/**
 * カード決済履歴詳細
 *
 * @ORM\Table(name="backup_plg_remise_payment4_order_result_card")
 * @ORM\Entity(repositoryClass="Plugin\RemisePayment42\RepositoryBackup\BackupOrderResultCardRepository")
 */
class BackupOrderResultCard
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="state", type="integer", options={"unsigned":true,"default":0})
     */
    private $state;

    /**
     * @var string
     *
     * @ORM\Column(name="job", type="string", length=10, nullable=true)
     */
    private $job;

    /**
     * @var string
     *
     * @ORM\Column(name="use_payquick_id", type="string", length=20, nullable=true)
     */
    private $use_payquick_id;

    /**
     * @var string
     *
     * @ORM\Column(name="tranid", type="string", length=28, nullable=true)
     */
    private $tranid;

    /**
     * @var string
     *
     * @ORM\Column(name="r_code", type="string", length=6, nullable=true)
     */
    private $r_code;

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
     * @ORM\Column(name="member_id", type="string", length=14, nullable=true)
     */
    private $member_id;

    /**
     * @var string
     *
     * @ORM\Column(name="ac_total", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0}, nullable=true)
     */
    private $ac_total = 0;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="ac_next_date", type="datetimetz", nullable=true)
     */
    private $ac_next_date;

    /**
     * @var string
     *
     * @ORM\Column(name="ac_interval", type="string", length=4, nullable=true)
     */
    private $ac_interval;

    /**
     * @var string
     *
     * @ORM\Column(name="prev_tranid", type="string", length=28, nullable=true)
     */
    private $prev_tranid;

    /**
     * @var string
     *
     * @ORM\Column(name="prev_payment_total", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0})
     */
    private $prev_payment_total = 0;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="result_date", type="datetimetz", nullable=true)
     */
    private $result_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="sales_date", type="datetimetz", nullable=true)
     */
    private $sales_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="cancel_date", type="datetimetz", nullable=true)
     */
    private $cancel_date;

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
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param int $state
     *
     * @return $this;
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return string
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * @param string $job
     *
     * @return $this;
     */
    public function setJob($job)
    {
        $this->job = $job;

        return $this;
    }

    /**
     * @return string
     */
    public function getUsePayquickId()
    {
        return $this->use_payquick_id;
    }

    /**
     * @param string $use_payquick_id
     *
     * @return $this;
     */
    public function setUsePayquickId($use_payquick_id)
    {
        $this->use_payquick_id = $use_payquick_id;

        return $this;
    }

    /**
     * @return string
     */
    public function getTranid()
    {
        return $this->tranid;
    }

    /**
     * @param string $tranid
     *
     * @return $this;
     */
    public function setTranid($tranid)
    {
        $this->tranid = $tranid;

        return $this;
    }

    /**
     * @return string
     */
    public function getRCode()
    {
        return $this->r_code;
    }

    /**
     * @param string $r_code
     *
     * @return $this;
     */
    public function setRCode($r_code)
    {
        $this->r_code = $r_code;

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
    public function getMemberId()
    {
        return $this->member_id;
    }

    /**
     * @param string $member_id
     *
     * @return $this;
     */
    public function setMemberId($member_id)
    {
        $this->member_id = $member_id;

        return $this;
    }

    /**
     * @return string
     */
    public function getAcTotal()
    {
        return $this->ac_total;
    }

    /**
     * @param string $ac_total
     *
     * @return $this;
     */
    public function setAcTotal($ac_total)
    {
        if (empty($ac_total) || $ac_total === "")
        {
            return;
        }
        $this->ac_total = $ac_total;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getAcNextDate()
    {
        return $this->ac_next_date;
    }

    /**
     * @param \DateTime $ac_next_date
     *
     * @return $this;
     */
    public function setAcNextDate($ac_next_date)
    {
        $this->ac_next_date = $ac_next_date;

        return $this;
    }

    /**
     * @return string
     */
    public function getAcInterval()
    {
        return $this->ac_interval;
    }

    /**
     * @param string $ac_interval
     *
     * @return $this;
     */
    public function setAcInterval($ac_interval)
    {
        $this->ac_interval = $ac_interval;

        return $this;
    }

    /**
     * @return string
     */
    public function getPrevTranid()
    {
        return $this->prev_tranid;
    }

    /**
     * @param string $prev_tranid
     *
     * @return $this;
     */
    public function setPrevTranid($prev_tranid)
    {
        $this->prev_tranid = $prev_tranid;

        return $this;
    }

    /**
     * @return string
     */
    public function getPrevPaymentTotal()
    {
        return $this->prev_payment_total;
    }

    /**
     * @param string $prev_payment_total
     *
     * @return $this;
     */
    public function setPrevPaymentTotal($prev_payment_total)
    {
        $this->prev_payment_total = $prev_payment_total;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getResultDate()
    {
        return $this->result_date;
    }

    /**
     * @param \DateTime $result_date
     *
     * @return $this;
     */
    public function setResultDate($result_date)
    {
        $this->result_date = $result_date;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getSalesDate()
    {
        return $this->sales_date;
    }

    /**
     * @param \DateTime $sales_date
     *
     * @return $this;
     */
    public function setSalesDate($sales_date)
    {
        $this->sales_date = $sales_date;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCancelDate()
    {
        return $this->cancel_date;
    }

    /**
     * @param \DateTime $cancel_date
     *
     * @return $this;
     */
    public function setCancelDate($cancel_date)
    {
        $this->cancel_date = $cancel_date;

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
