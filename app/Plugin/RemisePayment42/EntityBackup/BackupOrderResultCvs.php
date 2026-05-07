<?php

namespace Plugin\RemisePayment42\EntityBackup;

use Doctrine\ORM\Mapping as ORM;

/**
 * マルチ決済履歴詳細
 *
 * @ORM\Table(name="backup_plg_remise_payment4_order_result_cvs")
 * @ORM\Entity(repositoryClass="Plugin\RemisePayment42\RepositoryBackup\BackupOrderResultCvsRepository")
 */
class BackupOrderResultCvs
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="jobid", type="string", length=17, nullable=true)
     */
    private $jobid;

    /**
     * @var string
     *
     * @ORM\Column(name="r_code", type="string", length=6, nullable=true)
     */
    private $r_code;

    /**
     * @var string
     *
     * @ORM\Column(name="pay_way", type="string", length=3, nullable=true)
     */
    private $pay_way;

    /**
     * @var string
     *
     * @ORM\Column(name="pay_csv", type="string", length=4, nullable=true)
     */
    private $pay_csv;

    /**
     * @var string
     *
     * @ORM\Column(name="pay_no1", type="string", length=20, nullable=true)
     */
    private $pay_no1;

    /**
     * @var string
     *
     * @ORM\Column(name="pay_no2", type="string", length=500, nullable=true)
     */
    private $pay_no2;

    /**
     * @var string
     *
     * @ORM\Column(name="rec_cvscode", type="string", length=6, nullable=true)
     */
    private $rec_cvscode;

    /**
     * @var string
     *
     * @ORM\Column(name="rec_cvsname", type="string", length=50, nullable=true)
     */
    private $rec_cvsname;

    /**
     * @var string
     *
     * @ORM\Column(name="rec_scode", type="string", length=8, nullable=true)
     */
    private $rec_scode;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="pay_date", type="datetimetz", nullable=true)
     */
    private $pay_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="rec_date", type="datetimetz", nullable=true)
     */
    private $rec_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="cen_date", type="datetimetz", nullable=true)
     */
    private $cen_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="receipt_date", type="datetimetz", nullable=true)
     */
    private $receipt_date;

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
     * @return string
     */
    public function getJobid()
    {
        return $this->jobid;
    }

    /**
     * @param string $jobid
     *
     * @return $this;
     */
    public function setJobid($jobid)
    {
        $this->jobid = $jobid;

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
    public function getPayWay()
    {
        return $this->pay_way;
    }

    /**
     * @param string $pay_way
     *
     * @return $this;
     */
    public function setPayWay($pay_way)
    {
        $this->pay_way = $pay_way;

        return $this;
    }

    /**
     * @return string
     */
    public function getPayCsv()
    {
        return $this->pay_csv;
    }

    /**
     * @param string $pay_csv
     *
     * @return $this;
     */
    public function setPayCsv($pay_csv)
    {
        $this->pay_csv = $pay_csv;

        return $this;
    }

    /**
     * @return string
     */
    public function getPayNo1()
    {
        return $this->pay_no1;
    }

    /**
     * @param string $pay_no1
     *
     * @return $this;
     */
    public function setPayNo1($pay_no1)
    {
        $this->pay_no1 = $pay_no1;

        return $this;
    }

    /**
     * @return string
     */
    public function getPayNo2()
    {
        return $this->pay_no2;
    }

    /**
     * @param string $pay_no2
     *
     * @return $this;
     */
    public function setPayNo2($pay_no2)
    {
        $this->pay_no2 = $pay_no2;

        return $this;
    }

    /**
     * @return string
     */
    public function getRecCvscode()
    {
        return $this->rec_cvscode;
    }

    /**
     * @param string $rec_cvscode
     *
     * @return $this;
     */
    public function setRecCvscode($rec_cvscode)
    {
        $this->rec_cvscode = $rec_cvscode;

        return $this;
    }

    /**
     * @return string
     */
    public function getRecCvsname()
    {
        return $this->rec_cvsname;
    }

    /**
     * @param string $rec_cvsname
     *
     * @return $this;
     */
    public function setRecCvsname($rec_cvsname)
    {
        $this->rec_cvsname = $rec_cvsname;

        return $this;
    }

    /**
     * @return string
     */
    public function getRecScode()
    {
        return $this->rec_scode;
    }

    /**
     * @param string $rec_scode
     *
     * @return $this;
     */
    public function setRecScode($rec_scode)
    {
        $this->rec_scode = $rec_scode;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getPayDate()
    {
        return $this->pay_date;
    }

    /**
     * @param \DateTime $pay_date
     *
     * @return $this;
     */
    public function setPayDate($pay_date)
    {
        $this->pay_date = $pay_date;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getRecDate()
    {
        return $this->rec_date;
    }

    /**
     * @param \DateTime $rec_date
     *
     * @return $this;
     */
    public function setRecDate($rec_date)
    {
        $this->rec_date = $rec_date;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCenDate()
    {
        return $this->cen_date;
    }

    /**
     * @param \DateTime $cen_date
     *
     * @return $this;
     */
    public function setCenDate($cen_date)
    {
        $this->cen_date = $cen_date;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getReceiptDate()
    {
        return $this->receipt_date;
    }

    /**
     * @param \DateTime $receipt_date
     *
     * @return $this;
     */
    public function setReceiptDate($receipt_date)
    {
        $this->receipt_date = $receipt_date;

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
