<?php

namespace Plugin\RemisePayment42\EntityBackup;

use Doctrine\ORM\Mapping as ORM;

/**
 * ルミーズ定期購買取込情報
 *
 * @ORM\Table(name="backup_plg_remise_payment4_remise_ac_import")
 * @ORM\Entity(repositoryClass="Plugin\RemisePayment42\RepositoryBackup\BackupRemiseACImportRepository")
 */
class BackupRemiseACImport
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
     * success_cnt
     *
     * @ORM\Column(name="success_cnt", type="integer", nullable=true, options={"unsigned":true})
     */
    private $success_cnt;

    /**
     * success_amount
     *
     * @ORM\Column(name="success_amount", type="bigint", nullable=true, options={"unsigned":true})
     */
    private $success_amount;

    /**
     * fail_cnt
     *
     * @ORM\Column(name="fail_cnt", type="integer", nullable=true, options={"unsigned":true})
     */
    private $fail_cnt;

    /**
     * fail_amount
     *
     * @ORM\Column(name="fail_amount", type="bigint", nullable=true, options={"unsigned":true})
     */
    private $fail_amount;

    /**
     * total_cnt
     *
     * @ORM\Column(name="total_cnt", type="integer", nullable=true, options={"unsigned":true})
     */
    private $total_cnt;

    /**
     * total_amount
     *
     * @ORM\Column(name="total_amount", type="bigint", nullable=true, options={"unsigned":true})
     */
    private $total_amount;

    /**
     * exec_date
     *
     * @ORM\Column(name="exec_date", type="datetimetz")
     */
    private $exec_date;

    /**
     * import_flg
     *
     * @ORM\Column(name="import_flg", type="integer", nullable=true, options={"unsigned":true})
     */
    private $import_flg;

    /**
     * import_cnt
     *
     * @ORM\Column(name="import_cnt", type="integer", nullable=true, options={"unsigned":true})
     */
    private $import_cnt;

    /**
     * import_date
     *
     * @ORM\Column(name="import_date", type="datetimetz", nullable=true)
     */
    private $import_date;

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
     * Get id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set total.
     *
     * @return int
     */
    public function setTotal($total = null)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Get total.
     *
     * @return int
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * success_cnt の設定
     *
     * @param  integer $success_cnt
     * @return BackupRemiseACImport
     */
    public function setSuccessCnt($success_cnt)
    {
        $this->success_cnt = $success_cnt;
        return $this;
    }

    /**
     * success_cnt の取得
     *
     * @return integer
     */
    public function getSuccessCnt()
    {
        return $this->success_cnt;
    }

    /**
     * success_amount の設定
     *
     * @param  integer $success_amount
     * @return BackupRemiseACImport
     */
    public function setSuccessAmount($success_amount)
    {
        $this->success_amount = $success_amount;
        return $this;
    }

    /**
     * success_amount の取得
     *
     * @return integer
     */
    public function getSuccessAmount()
    {
        return $this->success_amount;
    }

    /**
     * fail_cnt の設定
     *
     * @param  integer $fail_cnt
     * @return BackupRemiseACImport
     */
    public function setFailCnt($fail_cnt)
    {
        $this->fail_cnt = $fail_cnt;
        return $this;
    }

    /**
     * fail_cnt の取得
     *
     * @return integer
     */
    public function getFailCnt()
    {
        return $this->fail_cnt;
    }

    /**
     * fail_amount の設定
     *
     * @param  integer $fail_amount
     * @return BackupRemiseACImport
     */
    public function setFailAmount($fail_amount)
    {
        $this->fail_amount = $fail_amount;
        return $this;
    }

    /**
     * fail_amount の取得
     *
     * @return integer
     */
    public function getFailAmount()
    {
        return $this->fail_amount;
    }

    /**
     * total_cnt の設定
     *
     * @param  integer $total_cnt
     * @return BackupRemiseACImport
     */
    public function setTotalCnt($total_cnt)
    {
        $this->total_cnt = $total_cnt;
        return $this;
    }

    /**
     * total_cnt の取得
     *
     * @return integer
     */
    public function getTotalCnt()
    {
        return $this->total_cnt;
    }

    /**
     * total_amount の設定
     *
     * @param  integer $total_amount
     * @return BackupRemiseACImport
     */
    public function setTotalAmount($total_amount)
    {
        $this->total_amount = $total_amount;
        return $this;
    }

    /**
     * total_amount の取得
     *
     * @return integer
     */
    public function getTotalAmount()
    {
        return $this->total_amount;
    }

    /**
     * exec_date の設定
     *
     * @param  \DateTime $exec_date
     * @return BackupRemiseACImport
     */
    public function setExecDate($exec_date)
    {
        $this->exec_date = $exec_date;
        return $this;
    }

    /**
     * exec_date の取得
     *
     * @return \DateTime
     */
    public function getExecDate()
    {
        return $this->exec_date;
    }

    /**
     * exec_date の取得
     *
     * @return \DateTime
     */
    public function getExecDateStr($format = 'Ymd')
    {
        if (!$this->exec_date) return "";
        if ($this->exec_date instanceof \DateTime) {
            $workDate = $this->exec_date;
            $workDate->setTimeZone(new \DateTimeZone('Asia/Tokyo'));
            return $workDate->format($format);
        }
        return "";
    }

    /**
     * import_flg の設定
     *
     * @param  integer $import_flg
     * @return BackupRemiseACImport
     */
    public function setImportFlg($import_flg)
    {
        $this->import_flg = $import_flg;
        return $this;
    }

    /**
     * import_flg の取得
     *
     * @return integer
     */
    public function getImportFlg()
    {
        return $this->import_flg;
    }

    /**
     * import_cnt の設定
     *
     * @param  integer $import_cnt
     * @return BackupRemiseACImport
     */
    public function setImportCnt($import_cnt)
    {
        $this->import_cnt = $import_cnt;
        return $this;
    }

    /**
     * import_cnt の取得
     *
     * @return integer
     */
    public function getImportCnt()
    {
        return $this->import_cnt;
    }

    /**
     * import_date の設定
     *
     * @param  \DateTime $import_date
     * @return BackupRemiseACImport
     */
    public function setImportDate($import_date)
    {
        $this->import_date = $import_date;
        return $this;
    }

    /**
     * import_date の取得
     *
     * @return \DateTime
     */
    public function getImportDate()
    {
        return $this->import_date;
    }

    /**import_date
     * exec_date の取得
     *
     * @return \DateTime
     */
    public function getImportDateStr($format = 'Ymd')
    {
        if (!$this->import_date) return "";
        if ($this->import_date instanceof \DateTime) {
            $workDate = $this->import_date;
            $workDate->setTimeZone(new \DateTimeZone('Asia/Tokyo'));
            return $workDate->format($format);
        }
        return "";
    }

    /**
     * Set createDate.
     *
     * @param \DateTime $createDate
     *
     * @return BackupRemiseACImport
     */
    public function setCreateDate($createDate)
    {
        $this->create_date = $createDate;

        return $this;
    }

    /**
     * Get createDate.
     *
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * Set updateDate.
     *
     * @param \DateTime $updateDate
     *
     * @return BackupRemiseACImport
     */
    public function setUpdateDate($updateDate)
    {
        $this->update_date = $updateDate;

        return $this;
    }

    /**
     * Get updateDate.
     *
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }
}
