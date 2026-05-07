<?php

namespace Plugin\RemisePayment42\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ルミーズ定期購買バッチ取込管理情報
 *
 * @ORM\Table(name="plg_remise_payment4_remise_ac_import_stack")
 * @ORM\Entity(repositoryClass="Plugin\RemisePayment42\Repository\RemiseACImportStackRepository")
 */
class RemiseACImportStack
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
     * @var integer
     *
     * @ORM\Column(name="start_num", type="integer")
     */
    private $start_num;

    /**
     * @var integer
     *
     * @ORM\Column(name="end_num", type="integer")
     */
    private $end_num;

    /**
     * @var integer
     *
     * @ORM\Column(name="exec_flg", type="integer")
     */
    private $exec_flg;

    /**
     * @var integer
     *
     * @ORM\Column(name="complete_flg", type="integer")
     */
    private $complete_flg;

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
     * Get start_num.
     *
     * @return integer
     */
    public function getStartNum()
    {
        return $this->start_num;
    }

    /**
     * Set start_num.
     *
     * @param integer $start_num
     *
     * @return RemiseACImportStack
     */
    public function setStartNum($start_num)
    {
        $this->start_num = $start_num;

        return $this;
    }

    /**
     * Get end_num.
     *
     * @return integer
     */
    public function getEndNum()
    {
        return $this->end_num;
    }

    /**
     * Set end_num.
     *
     * @param integer $end_num
     *
     * @return RemiseACImportStack
     */
    public function setEndNum($end_num)
    {
        $this->end_num = $end_num;

        return $this;
    }

    /**
     * Get exec_flg.
     *
     * @return integer
     */
    public function getExecFlg()
    {
        return $this->exec_flg;
    }

    /**
     * Set exec_flg.
     *
     * @param integer $exec_flg
     *
     * @return RemiseACImportStack
     */
    public function setExecFlg($exec_flg)
    {
        $this->exec_flg = $exec_flg;

        return $this;
    }

    /**
     * Get complete_flg.
     *
     * @return integer
     */
    public function getCompleteFlg()
    {
        return $this->complete_flg;
    }

    /**
     * Set complete_flg.
     *
     * @param integer $complete_flg
     *
     * @return RemiseACImportStack
     */
    public function setCompleteFlg($complete_flg)
    {
        $this->complete_flg = $complete_flg;

        return $this;
    }

    /**
     * Get create_date.
     *
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * Set create_date.
     *
     * @param \DateTime $create_date
     *
     * @return RemiseACImportStack
     */
    public function setCreateDate($create_date)
    {
        $this->create_date = $create_date;

        return $this;
    }

    /**
     * Get update_date.
     *
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }

    /**
     * Set update_date.
     *
     * @param \DateTime $update_date
     *
     * @return RemiseACImportStack
     */
    public function setUpdateDate($update_date)
    {
        $this->update_date = $update_date;

        return $this;
    }
}