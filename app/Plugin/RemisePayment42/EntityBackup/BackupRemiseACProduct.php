<?php

namespace Plugin\RemisePayment42\EntityBackup;

use Doctrine\ORM\Mapping as ORM;

/**
 * ルミーズ定期購買商品情報
 *
 * @ORM\Table(name="backup_plg_remise_payment4_remise_ac_product")
 * @ORM\Entity(repositoryClass="Plugin\RemisePayment42\RepositoryBackup\BackupRemiseACProductRepository")
 */
class BackupRemiseACProduct
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
     * @ORM\Column(name="ac_type_id", type="integer", nullable=true, options={"unsigned":true})
     */
    private $ac_type_id;

    /**
     * @var int
     *
     * @ORM\Column(name="amount", type="integer", nullable=true, options={"unsigned":true})
     */
    private $amount;

    /**
     * @var int
     *
     * @ORM\Column(name="point_flg", type="boolean", nullable=true)
     */
    private $point_flg;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", nullable=true, type="datetimetz")
     */
    private $create_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_date", nullable=true, type="datetimetz")
     */
    private $update_date;

    /**
     * Set id.
     *
     * @return int
     */
    public function setId($id = null)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set ac_type_id.
     *
     * @param int $ac_type_id
     *
     * @return BackupRemiseACProduct
     */
    public function setAcTypeId($ac_type_id = null)
    {
        $this->ac_type_id = $ac_type_id;

        return $this;
    }

    /**
     * Get ac_type_id.
     *
     * @return int
     */
    public function getAcTypeId()
    {
        return $this->ac_type_id;
    }

    /**
     * Set amount.
     *
     * @param int $amount
     *
     * @return BackupRemiseACProduct
     */
    public function setAmount($amount = null)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount.
     *
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set point_flg.
     *
     * @param int $point_flg
     *
     * @return BackupRemiseACProduct
     */
    public function setPointFlg($point_flg = null)
    {
        $this->point_flg = $point_flg;

        return $this;
    }

    /**
     * Get point_flg.
     *
     * @return int
     */
    public function getPointFlg()
    {
        return $this->point_flg;
    }

    /**
     * Set createDate.
     *
     * @param \DateTime $createDate
     *
     * @return RemiseACType
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
     * @return RemiseACType
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
