<?php

namespace Plugin\RemisePayment42\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ルミーズ定期購買取込詳細情報
 *
 */
class RemiseACImportInfo
{
    /**
     * r_code
     */
    private $r_code;

    /**
     * member_id
     */
    private $member_id;

    /**
     * s_kaiin_no
     */
    private $s_kaiin_no;

    /**
     * ac_name
     */
    private $ac_name;

    /**
     * ac_kana
     */
    private $ac_kana;

    /**
     * ac_tel
     */
    private $ac_tel;

    /**
     * ac_mail
     */
    private $ac_mail;

    /**
     * ac_amount
     */
    private $ac_amount;

    /**
     * ac_result
     */
    private $ac_result;

    /**
     * tranid
     */
    private $tranid;

    /**
     * errcode
     */
    private $errcode;

    /**
     * errinfo
     */
    private $errinfo;

    /**
     * ac_next_date
     */
    private $ac_next_date;

    /**
     * card
     */
    private $card;

    /**
     * expire
     */
    private $expire;

    /**
     * name
     */
    private $name;

    /**
     * note
     */
    private $note;

    /**
     * status
     */
    private $status;

    /**
     * order_id
     */
    private $order_id;

    /**
     * order_no
     */
    private $order_no;

    /**
     * r_code の設定
     *
     * @param  string $r_code
     * @return RemiseACImportInfo
     */
    public function setRCode($r_code)
    {
        $this->r_code = $r_code;
        return $this;
    }

    /**
     * r_code の取得
     *
     * @return string
     */
    public function getRCode()
    {
        return $this->r_code;
    }

    /**
     * member_id の設定
     *
     * @param  string $member_id
     * @return RemiseACImportInfo
     */
    public function setMemberId($member_id)
    {
        $this->member_id = $member_id;
        return $this;
    }

    /**
     * member_id の取得
     *
     * @return string
     */
    public function getMemberId()
    {
        return $this->member_id;
    }

    /**
     * s_kaiin_no の設定
     *
     * @param  string $s_kaiin_no
     * @return RemiseACImportInfo
     */
    public function setSKaiinNo($s_kaiin_no)
    {
        $this->s_kaiin_no = $s_kaiin_no;
        return $this;
    }

    /**
     * s_kaiin_no の取得
     *
     * @return string
     */
    public function getSKaiinNo()
    {
        return $this->s_kaiin_no;
    }

    /**
     * ac_name の設定
     *
     * @param  string $ac_name
     * @return RemiseACImportInfo
     */
    public function setAcName($ac_name)
    {
        $this->ac_name = $ac_name;
        return $this;
    }

    /**
     * ac_name の取得
     *
     * @return string
     */
    public function getAcName()
    {
        return $this->ac_name;
    }

    /**
     * ac_kana の設定
     *
     * @param  string $ac_kana
     * @return RemiseACImportInfo
     */
    public function setAcKana($ac_kana)
    {
        $this->ac_kana = $ac_kana;
        return $this;
    }

    /**
     * ac_kana の取得
     *
     * @return string
     */
    public function getAcKana()
    {
        return $this->ac_kana;
    }

    /**
     * ac_tel の設定
     *
     * @param  string $ac_tel
     * @return RemiseACImportInfo
     */
    public function setAcTel($ac_tel)
    {
        $this->ac_tel = $ac_tel;
        return $this;
    }

    /**
     * ac_tel の取得
     *
     * @return string
     */
    public function getAcTel()
    {
        return $this->ac_tel;
    }

    /**
     * ac_mail の設定
     *
     * @param  string $ac_mail
     * @return RemiseACImportInfo
     */
    public function setAcMail($ac_mail)
    {
        $this->ac_mail = $ac_mail;
        return $this;
    }

    /**
     * ac_mail の取得
     *
     * @return string
     */
    public function getAcMail()
    {
        return $this->ac_mail;
    }

    /**
     * ac_amount の設定
     *
     * @param  string $ac_amount
     * @return RemiseACImportInfo
     */
    public function setAcAmount($ac_amount)
    {
        $this->ac_amount = $ac_amount;
        return $this;
    }

    /**
     * ac_amount の取得
     *
     * @return string
     */
    public function getAcAmount()
    {
        return $this->ac_amount;
    }

    /**
     * ac_result の設定
     *
     * @param  string $ac_result
     * @return RemiseACImportInfo
     */
    public function setAcResult($ac_result)
    {
        $this->ac_result = $ac_result;
        return $this;
    }

    /**
     * ac_result の取得
     *
     * @return string
     */
    public function getAcResult()
    {
        return $this->ac_result;
    }

    /**
     * tranid の設定
     *
     * @param  string $tranid
     * @return RemiseACImportInfo
     */
    public function setTranid($tranid)
    {
        $this->tranid = $tranid;
        return $this;
    }

    /**
     * tranid の取得
     *
     * @return string
     */
    public function getTranid()
    {
        return $this->tranid;
    }

    /**
     * errcode の設定
     *
     * @param  string $errcode
     * @return RemiseACImportInfo
     */
    public function setErrcode($errcode)
    {
        $this->errcode = $errcode;
        return $this;
    }

    /**
     * errcode の取得
     *
     * @return string
     */
    public function getErrcode()
    {
        return $this->errcode;
    }

    /**
     * errinfo の設定
     *
     * @param  string $errinfo
     * @return RemiseACImportInfo
     */
    public function setErrinfo($errinfo)
    {
        $this->errinfo = $errinfo;
        return $this;
    }

    /**
     * errinfo の取得
     *
     * @return string
     */
    public function getErrinfo()
    {
        return $this->errinfo;
    }

    /**
     * ac_next_date の設定
     *
     * @param  string $ac_next_date
     * @return RemiseACImportInfo
     */
    public function setAcNextDate($ac_next_date)
    {
        $this->ac_next_date = $ac_next_date;
        return $this;
    }

    /**
     * ac_next_date の取得
     *
     * @return string
     */
    public function getAcNextDate()
    {
        return $this->ac_next_date;
    }

    /**
     * card の設定
     *
     * @param  string $card
     * @return RemiseACImportInfo
     */
    public function setCard($card)
    {
        $this->card = $card;
        return $this;
    }

    /**
     * card の取得
     *
     * @return string
     */
    public function getCard()
    {
        return $this->card;
    }

    /**
     * expire の設定
     *
     * @param  string $expire
     * @return RemiseACImportInfo
     */
    public function setExpire($expire)
    {
        $this->expire = $expire;
        return $this;
    }

    /**
     * expire の取得
     *
     * @return string
     */
    public function getExpire()
    {
        return $this->expire;
    }

    /**
     * note の設定
     *
     * @param  string $note
     * @return RemiseACImportInfo
     */
    public function setNote($note)
    {
        $this->note = $note;
        return $this;
    }

    /**
     * note の取得
     *
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * status の設定
     *
     * @param  string $status
     * @return RemiseACImportInfo
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * status の取得
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * order_id の設定
     *
     * @param  integer $order_id
     * @return RemiseACImportInfo
     */
    public function setOrderId($order_id)
    {
        $this->order_id = $order_id;
        return $this;
    }

    /**
     * order_id の取得
     *
     * @return integer
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * order_no の設定
     *
     * @param  integer $order_no
     * @return RemiseACImportInfo
     */
    public function setOrderNo($order_no)
    {
        $this->order_no = $order_no;
        return $this;
    }

    /**
     * order_no の取得
     *
     * @return integer
     */
    public function getOrderNo()
    {
        return $this->order_no;
    }
}
