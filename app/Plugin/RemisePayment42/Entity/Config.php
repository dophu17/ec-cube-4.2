<?php

namespace Plugin\RemisePayment42\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * プラグイン設定情報
 *
 * @ORM\Table(name="plg_remise_payment4_config")
 * @ORM\Entity(repositoryClass="Plugin\RemisePayment42\Repository\ConfigRepository")
 */
class Config
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
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="info", type="string", length=4000, nullable=true)
     */
    private $info;

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
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     *
     * @return $this;
     */
    public function setCode($code)
    {
        $this->code = $code;

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
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @return array
     */
    public function getUnserializeInfo()
    {
        $configInfo = new ConfigInfo();

        if (empty($this->info)) return $configInfo;

        $data = unserialize($this->info);

        if (isset($data['shopco'])) {
            $configInfo->setShopco($data['shopco']);
        }
        if (isset($data['hostid'])) {
            $configInfo->setHostid($data['hostid']);
        }
        if (isset($data['use_payment'])) {
            $configInfo->setUsePayment($data['use_payment']);
        }
        if (isset($data['use_option'])) {
            $configInfo->setUseOption($data['use_option']);
        }
        if (isset($data['card_url'])) {
            $configInfo->setCardUrl($data['card_url']);
        }
        if (isset($data['job'])) {
            $configInfo->setJob($data['job']);
        }
        if (isset($data['payquick_flag'])) {
            $configInfo->setPayquickFlag($data['payquick_flag']);
        }
        if (isset($data['use_method'])) {
            $configInfo->setUseMethod($data['use_method']);
        }
        if (isset($data['ptimes'])) {
            $configInfo->setPtimes($data['ptimes']);
        }
        if (isset($data['cvs_url'])) {
            $configInfo->setCvsUrl($data['cvs_url']);
        }
        if (isset($data['pay_date'])) {
            $configInfo->setPayDate($data['pay_date']);
        }
        if (isset($data['acpt_mail_flag'])) {
            $configInfo->setAcptMailFlag($data['acpt_mail_flag']);
        }
        if (isset($data['extset_hostid'])) {
            $configInfo->setExtsetHostid($data['extset_hostid']);
        }
        if (isset($data['extset_recv_url'])) {
            $configInfo->setExtsetRecvUrl($data['extset_recv_url']);
        }
        if (isset($data['extset_card_url'])) {
            $configInfo->setExtsetCardUrl($data['extset_card_url']);
        }
        if (isset($data['ac_appid'])) {
            $configInfo->setAcAppid($data['ac_appid']);
        }
        if (isset($data['ac_password'])) {
            $configInfo->setAcPassword($data['ac_password']);
        }
        if (isset($data['ac_result_url'])) {
            $configInfo->setAcResultUrl($data['ac_result_url']);
        }
        if (isset($data['ac_edit_url'])) {
            $configInfo->setAcEditUrl($data['ac_edit_url']);
        }
        if (isset($data['ac_acquisition_method'])) {
            $configInfo->setAcAcquisitionMethod($data['ac_acquisition_method']);
        }
        if (isset($data['ac_max_number_acquisitions'])) {
            $configInfo->setAcMaxNumberAcquisitions($data['ac_max_number_acquisitions']);
        }
        return $configInfo;
    }

    /**
     * @param string $info
     *
     * @return $this;
     */
    public function setInfo($info)
    {
        $this->info = $info;

        return $this;
    }

    /**
     * @param ConfigInfo $configInfo
     *
     * @return $this;
     */
    public function setSerializeInfo($configInfo)
    {
        $info = [];
        $info['shopco'] = $configInfo->getShopco();
        $info['hostid'] = $configInfo->getHostid();
        $info['use_payment'] = $configInfo->getUsePayment();
        $info['use_option'] = $configInfo->getUseOption();
        $info['card_url'] = $configInfo->getCardUrl();
        $info['job'] = $configInfo->getJob();
        $info['payquick_flag'] = $configInfo->getPayquickFlag();
        $info['use_method'] = $configInfo->getUseMethod();
        $info['ptimes'] = $configInfo->getPtimes();
        $info['cvs_url'] = $configInfo->getCvsUrl();
        $info['pay_date'] = $configInfo->getPayDate();
        $info['acpt_mail_flag'] = $configInfo->getAcptMailFlag();
        $info['extset_hostid'] = $configInfo->getExtsetHostid();
        $info['extset_recv_url'] = $configInfo->getExtsetRecvUrl();
        $info['extset_card_url'] = $configInfo->getExtsetCardUrl();
        $info['ac_appid'] = $configInfo->getAcAppid();
        $info['ac_password'] = $configInfo->getAcPassword();
        $info['ac_result_url'] = $configInfo->getAcResultUrl();
        $info['ac_edit_url'] = $configInfo->getAcEditUrl();
        $info['ac_acquisition_method'] = $configInfo->getAcAcquisitionMethod();
        $info['ac_max_number_acquisitions'] = $configInfo->getAcMaxNumberAcquisitions();

        $this->info = serialize($info);

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
