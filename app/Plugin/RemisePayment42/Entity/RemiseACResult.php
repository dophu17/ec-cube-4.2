<?php

namespace Plugin\RemisePayment42\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ルミーズ定期購買結果情報
 *
 * @ORM\Table(name="plg_remise_payment4_remise_ac_result")
 * @ORM\Entity(repositoryClass="Plugin\RemisePayment42\Repository\RemiseACResultRepository")
 */
class RemiseACResult
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
     * @ORM\Column(name="result", type="integer", options={"unsigned":true})
     */
    private $result;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="charge_date", type="datetimetz")
     */
    private $charge_date;

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
     * Set result.
     *
     * @return int
     */
    public function setResult($result = null)
    {
        $this->result = $result;

        return $this;
    }

    /**
     * Get result.
     *
     * @return int
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Set chargeDate.
     *
     * @param \DateTime $chargeDate
     *
     * @return RemiseACType
     */
    public function setChargeDate($chargeDate)
    {
        $this->charge_date = $chargeDate;

        return $this;
    }

    /**
     * Get chargeDate.
     *
     * @return \DateTime
     */
    public function getChargeDate()
    {
        return $this->charge_date;
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
