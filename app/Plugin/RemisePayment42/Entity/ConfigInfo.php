<?php

namespace Plugin\RemisePayment42\Entity;

/**
 * 設定情報
 */
class ConfigInfo
{
    /**
     * @var string
     */
    private $shopco;

    /**
     * @var string
     */
    private $hostid;

    /**
     * @var string
     */
    private $use_payment;

    /**
     * @var string
     */
    private $use_option;

    /**
     * @var string
     */
    private $card_url;

    /**
     * @var string
     */
    private $job;

    /**
     * @var string
     */
    private $payquick_flag;

    /**
     * @var string
     */
    private $use_method;

    /**
     * @var string
     */
    private $ptimes;

    /**
     * @var string
     */
    private $cvs_url;

    /**
     * @var string
     */
    private $pay_date;

    /**
     * @var string
     */
    private $acpt_mail_flag;

    /**
     * @var string
     */
    private $extset_hostid;

    /**
     * @var string
     */
    private $extset_recv_url;

    /**
     * @var string
     */
    private $extset_card_url;

    /**
     * @var string
     */
    private $ac_appid;

    /**
     * @var string
     */
    private $ac_password;

    /**
     * @var string
     */
    private $ac_result_url;

    /**
     * @var string
     */
    private $ac_edit_url;

    /**
     * @var string
     */
    private $ac_acquisition_method;

    /**
     * @var integer
     */
    private $ac_max_number_acquisitions;

    /**
     * @return string
     */
    public function getShopco()
    {
        return $this->shopco;
    }

    /**
     * @param string $shopco
     *
     * @return $this;
     */
    public function setShopco($shopco)
    {
        $this->shopco = $shopco;

        return $this;
    }

    /**
     * @return string
     */
    public function getHostid()
    {
        return $this->hostid;
    }

    /**
     * @param string $hostid
     *
     * @return $this;
     */
    public function setHostid($hostid)
    {
        $this->hostid = $hostid;

        return $this;
    }

    /**
     * @return string
     */
    public function getUsePayment()
    {
        return $this->use_payment;
    }

    /**
     * @return boolean
     */
    public function usePaymentCard()
    {
        if ($this->use_payment === "") return false;
        foreach ($this->use_payment as $key => $value)
        {
            if ($value == '1')
            {
                return true;
            }
        }
        return false;
    }

    /**
     * @return boolean
     */
    public function usePaymentCvs()
    {
        if ($this->use_payment === "") return false;
        foreach ($this->use_payment as $key => $value)
        {
            if ($value == '2')
            {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $use_payment
     *
     * @return $this;
     */
    public function setUsePayment($use_payment)
    {
        $this->use_payment = $use_payment;

        return $this;
    }

    /**
     * @return string
     */
    public function getUseOption()
    {
        return $this->use_option;
    }

    /**
     * @return boolean
     */
    public function useOptionExtset()
    {
        if ($this->use_option === "") return false;
        foreach ($this->use_option as $key => $value)
        {
            if ($value == '1')
            {
                return true;
            }
        }
        return false;
    }

    /**
     * @return boolean
     */
    public function useOptionAC()
    {
        if ($this->use_option === "") return false;
        foreach ($this->use_option as $key => $value)
        {
            if ($value == '2')
            {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $use_option
     *
     * @return $this;
     */
    public function setUseOption($use_option)
    {
        $this->use_option = $use_option;

        return $this;
    }

    /**
     * @return string
     */
    public function getCardUrl()
    {
        return $this->card_url;
    }

    /**
     * @param string $card_url
     *
     * @return $this;
     */
    public function setCardUrl($card_url)
    {
        $this->card_url = $card_url;

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
    public function getPayquickFlag()
    {
        return $this->payquick_flag;
    }

    /**
     * @param string $payquick_flag
     *
     * @return $this;
     */
    public function setPayquickFlag($payquick_flag)
    {
        $this->payquick_flag = $payquick_flag;

        return $this;
    }

    /**
     * @return string
     */
    public function getUseMethod()
    {
        return $this->use_method;
    }

    /**
     * @param string $use_method
     *
     * @return $this;
     */
    public function setUseMethod($use_method)
    {
        $this->use_method = $use_method;

        return $this;
    }

    /**
     * @return string
     */
    public function getPtimes()
    {
        return $this->ptimes;
    }

    /**
     * @param string $ptimes
     *
     * @return $this;
     */
    public function setPtimes($ptimes)
    {
        $this->ptimes = $ptimes;

        return $this;
    }

    /**
     * @return string
     */
    public function getCvsUrl()
    {
        return $this->cvs_url;
    }

    /**
     * @param string $cvs_url
     *
     * @return $this;
     */
    public function setCvsUrl($cvs_url)
    {
        $this->cvs_url = $cvs_url;

        return $this;
    }

    /**
     * @return string
     */
    public function getPayDate()
    {
        return $this->pay_date;
    }

    /**
     * @param string $pay_date
     *
     * @return $this;
     */
    public function setPayDate($pay_date)
    {
        $this->pay_date = $pay_date;

        return $this;
    }

    /**
     * @return string
     */
    public function getAcptMailFlag()
    {
        return $this->acpt_mail_flag;
    }

    /**
     * @param string $acpt_mail_flag
     *
     * @return $this;
     */
    public function setAcptMailFlag($acpt_mail_flag)
    {
        $this->acpt_mail_flag = $acpt_mail_flag;

        return $this;
    }

    /**
     * @return string
     */
    public function getExtsetHostid()
    {
        return $this->extset_hostid;
    }

    /**
     * @param string $extset_hostid
     *
     * @return $this;
     */
    public function setExtsetHostid($extset_hostid)
    {
        $this->extset_hostid = $extset_hostid;

        return $this;
    }

    /**
     * @return string
     */
    public function getExtsetRecvUrl()
    {
        return $this->extset_recv_url;
    }

    /**
     * @param string $extset_recv_url
     *
     * @return $this;
     */
    public function setExtsetRecvUrl($extset_recv_url)
    {
        $this->extset_recv_url = $extset_recv_url;

        return $this;
    }

    /**
     * @return string
     */
    public function getExtsetCardUrl()
    {
        return $this->extset_card_url;
    }

    /**
     * @param string $extset_card_url
     *
     * @return $this;
     */
    public function setExtsetCardUrl($extset_card_url)
    {
        $this->extset_card_url = $extset_card_url;

        return $this;
    }

    /**
     * @return string
     */
    public function getAcAppid()
    {
        return $this->ac_appid;
    }

    /**
     * @param string $ac_appid
     *
     * @return $this;
     */
    public function setAcAppid($ac_appid)
    {
        $this->ac_appid = $ac_appid;

        return $this;
    }

    /**
     * @return string
     */
    public function getAcPassword()
    {
        return $this->ac_password;
    }

    /**
     * @param string $ac_password
     *
     * @return $this;
     */
    public function setAcPassword($ac_password)
    {
        $this->ac_password = $ac_password;

        return $this;
    }

    /**
     * @return string
     */
    public function getAcResultUrl()
    {
        return $this->ac_result_url;
    }

    /**
     * @param string $ac_result_url
     *
     * @return $this;
     */
    public function setAcResultUrl($ac_result_url)
    {
        $this->ac_result_url = $ac_result_url;

        return $this;
    }

    /**
     * @return string
     */
    public function getAcEditUrl()
    {
        return $this->ac_edit_url;
    }

    /**
     * @param string $ac_edit_url
     *
     * @return $this;
     */
    public function setAcEditUrl($ac_edit_url)
    {
        $this->ac_edit_url = $ac_edit_url;

        return $this;
    }

    /**
     * Get ac_acquisition_method.
     *
     * @return string
     */
    public function getAcAcquisitionMethod()
    {
        return $this->ac_acquisition_method;
    }

    /**
     * Set ac_acquisition_method.
     *
     * @param string $ac_acquisition_method
     *
     * @return ConfigInfo
     */
    public function setAcAcquisitionMethod($ac_acquisition_method)
    {
        $this->ac_acquisition_method = $ac_acquisition_method;

        return $this;
    }

    /**
     * Get ac_max_number_acquisitions.
     *
     * @return integer
     */
    public function getAcMaxNumberAcquisitions()
    {
        return $this->ac_max_number_acquisitions;
    }

    /**
     * Set ac_max_number_acquisitions.
     *
     * @param integer $ac_max_number_acquisitions
     *
     * @return ConfigInfo
     */
    public function setAcMaxNumberAcquisitions($ac_max_number_acquisitions)
    {
        $this->ac_max_number_acquisitions = $ac_max_number_acquisitions;

        return $this;
    }

}
