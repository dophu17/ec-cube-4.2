<?php

namespace Plugin\RemisePayment42\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ルミーズ定期購買バッチ取込情報
 *
 * @ORM\Table(name="plg_remise_payment4_remise_ac_import_stack_result")
 * @ORM\Entity(repositoryClass="Plugin\RemisePayment42\Repository\RemiseACImportStackResultRepository")
 */
class RemiseACImportStackResult
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="import_date", type="datetimetz", nullable=true)
     */
    private $import_date;

    /**
     * @var string
     *
     * @ORM\Column(name="r_code", type="string", length=6, nullable=true)
     */
    private $r_code;

    /**
     * @var string
     *
     * @ORM\Column(name="member_id", type="string", length=14, nullable=true)
     */
    private $member_id;

    /**
     * @var string
     *
     * @ORM\Column(name="s_kaiin_no", type="string", length=17, nullable=true)
     */
    private $s_kaiin_no;

    /**
     * @var string
     *
     * @ORM\Column(name="ac_name", type="string", length=60, nullable=true)
     */
    private $ac_name;

    /**
     * @var string
     *
     * @ORM\Column(name="ac_kana", type="string", length=60, nullable=true)
     */
    private $ac_kana;

    /**
     * @var string
     *
     * @ORM\Column(name="ac_tel", type="string", length=11, nullable=true)
     */
    private $ac_tel;

    /**
     * @var string
     *
     * @ORM\Column(name="ac_mail", type="string", length=50, nullable=true)
     */
    private $ac_mail;

    /**
     * @var integer
     *
     * @ORM\Column(name="ac_amount", type="integer")
     */
    private $ac_amount;

    /**
     * @var string
     *
     * @ORM\Column(name="ac_result", type="string")
     */
    private $ac_result;

    /**
     * @var string
     *
     * @ORM\Column(name="tranid", type="string", length=28, nullable=true)
     */
    private $tranid;

    /**
     * @var string
     *
     * @ORM\Column(name="errcode", type="string", length=3, nullable=true)
     */
    private $errcode;

    /**
     * @var string
     *
     * @ORM\Column(name="errinfo", type="string", length=9, nullable=true)
     */
    private $errinfo;

    /**
     * @var string
     *
     * @ORM\Column(name="ac_next_date", type="string", length=8, nullable=true)
     */
    private $ac_next_date;

    /**
     * @var string
     *
     * @ORM\Column(name="card", type="string", length=4, nullable=true)
     */
    private $card;

    /**
     * @var string
     *
     * @ORM\Column(name="expire", type="string", length=4, nullable=true)
     */
    private $expire;

    /**
     * @var string
     *
     * @ORM\Column(name="note", type="text", nullable=true)
     */
    private $note;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string")
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="order_id", type="string")
     */
    private $order_id;

    /**
     * @var string
     *
     * @ORM\Column(name="order_no", type="string")
     */
    private $order_no;

    /**
     * Get id.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get import_date.
     *
     * @return \DateTime
     */
    public function getImportDate()
    {
        return $this->import_date;
    }

    /**
     * Set import_date.
     *
     * @param \DateTime $import_date
     *
     * @return RemiseACImportStack
     */
    public function setImportDate($import_date)
    {
        $this->import_date = $import_date;

        return $this;
    }

    /**
     * Get r_code.
     *
     * @return string
     */
    public function getRCode()
    {
        return $this->r_code;
    }

    /**
     * Set r_code.
     *
     * @param string $r_code
     *
     * @return RemiseACImportStackResult
     */
    public function setRCode($r_code)
    {
        $this->r_code = $r_code;

        return $this;
    }

    /**
     * Get member_id.
     *
     * @return string
     */
    public function getMemberId()
    {
        return $this->member_id;
    }

    /**
     * Set member_id.
     *
     * @param string $member_id
     *
     * @return RemiseACImportStackResult
     */
    public function setMemberId($member_id)
    {
        $this->member_id = $member_id;

        return $this;
    }

    /**
     * Get s_kaiin_no.
     *
     * @return string
     */
    public function getSKaiinNo()
    {
        return $this->s_kaiin_no;
    }

    /**
     * Set s_kaiin_no.
     *
     * @param string $s_kaiin_no
     *
     * @return RemiseACImportStackResult
     */
    public function setSKaiinNo($s_kaiin_no)
    {
        $this->s_kaiin_no = $s_kaiin_no;

        return $this;
    }

    /**
     * Get ac_name.
     *
     * @return string
     */
    public function getAcName()
    {
        return $this->ac_name;
    }

    /**
     * Set ac_name.
     *
     * @param string $ac_name
     *
     * @return RemiseACImportStackResult
     */
    public function setAcName($ac_name)
    {
        $this->ac_name = $ac_name;

        return $this;
    }

    /**
     * Get ac_kana.
     *
     * @return string
     */
    public function getAcKana()
    {
        return $this->ac_kana;
    }

    /**
     * Set ac_kana.
     *
     * @param string $ac_kana
     *
     * @return RemiseACImportStackResult
     */
    public function setAcKana($ac_kana)
    {
        $this->ac_kana = $ac_kana;

        return $this;
    }

    /**
     * Get ac_tel.
     *
     * @return string
     */
    public function getAcTel()
    {
        return $this->ac_tel;
    }

    /**
     * Set ac_tel.
     *
     * @param string $ac_tel
     *
     * @return RemiseACImportStackResult
     */
    public function setAcTel($ac_tel)
    {
        $this->ac_tel = $ac_tel;

        return $this;
    }

    /**
     * Get ac_mail.
     *
     * @return string
     */
    public function getAcMail()
    {
        return $this->ac_mail;
    }

    /**
     * Set ac_mail.
     *
     * @param string $ac_mail
     *
     * @return RemiseACImportStackResult
     */
    public function setAcMail($ac_mail)
    {
        $this->ac_mail = $ac_mail;

        return $this;
    }

    /**
     * Get ac_amount.
     *
     * @return integer
     */
    public function getAcAmount()
    {
        return $this->ac_amount;
    }

    /**
     * Set ac_amount.
     *
     * @param integer $ac_amount
     *
     * @return RemiseACImportStackResult
     */
    public function setAcAmount($ac_amount)
    {
        $this->ac_amount = $ac_amount;

        return $this;
    }

    /**
     * Get ac_result.
     *
     * @return string
     */
    public function getAcResult()
    {
        return $this->ac_result;
    }

    /**
     * Set ac_result.
     *
     * @param string $ac_result
     *
     * @return RemiseACImportStackResult
     */
    public function setAcResult($ac_result)
    {
        $this->ac_result = $ac_result;

        return $this;
    }

    /**
     * Get tranid.
     *
     * @return string
     */
    public function getTranid()
    {
        return $this->tranid;
    }

    /**
     * Set tranid.
     *
     * @param string $tranid
     *
     * @return RemiseACImportStackResult
     */
    public function setTranid($tranid)
    {
        $this->tranid = $tranid;

        return $this;
    }

    /**
     * Get errcode.
     *
     * @return string
     */
    public function getErrcode()
    {
        return $this->errcode;
    }

    /**
     * Set errcode.
     *
     * @param string $errcode
     *
     * @return RemiseACImportStackResult
     */
    public function setErrcode($errcode)
    {
        $this->errcode = $errcode;

        return $this;
    }

    /**
     * Get errinfo.
     *
     * @return string
     */
    public function getErrinfo()
    {
        return $this->errinfo;
    }

    /**
     * Set errinfo.
     *
     * @param string $errinfo
     *
     * @return RemiseACImportStackResult
     */
    public function setErrinfo($errinfo)
    {
        $this->errinfo = $errinfo;

        return $this;
    }

    /**
     * Get ac_next_date.
     *
     * @return string
     */
    public function getAcNextDate()
    {
        return $this->ac_next_date;
    }

    /**
     * Set ac_next_date.
     *
     * @param string $ac_next_date
     *
     * @return RemiseACImportStackResult
     */
    public function setAcNextDate($ac_next_date)
    {
        $this->ac_next_date = $ac_next_date;

        return $this;
    }

    /**
     * Get card.
     *
     * @return string
     */
    public function getCard()
    {
        return $this->card;
    }

    /**
     * Set card.
     *
     * @param string $card
     *
     * @return RemiseACImportStackResult
     */
    public function setCard($card)
    {
        $this->card = $card;

        return $this;
    }

    /**
     * Get expire.
     *
     * @return string
     */
    public function getExpire()
    {
        return $this->expire;
    }

    /**
     * Set expire.
     *
     * @param string $expire
     *
     * @return RemiseACImportStackResult
     */
    public function setExpire($expire)
    {
        $this->expire = $expire;

        return $this;
    }

    /**
     * Get note.
     *
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Set note.
     *
     * @param string $note
     *
     * @return RemiseACImportStackResult
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * Get status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set status.
     *
     * @param string $status
     *
     * @return RemiseACImportStackResult
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }
    /**
     * Get order_id.
     *
     * @return string
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set order_id.
     *
     * @param string $order_id
     *
     * @return RemiseACImportStackResult
     */
    public function setOrderId($order_id)
    {
        $this->order_id = $order_id;

        return $this;
    }

    /**
     * Get order_no.
     *
     * @return string
     */
    public function getOrderNo()
    {
        return $this->order_no;
    }

    /**
     * Set order_no.
     *
     * @param string $order_no
     *
     * @return RemiseACImportStackResult
     */
    public function setOrderNo($order_no)
    {
        $this->order_no = $order_no;

        return $this;
    }
}