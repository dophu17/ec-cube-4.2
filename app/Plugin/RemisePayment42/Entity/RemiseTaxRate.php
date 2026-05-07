<?php

namespace Plugin\RemisePayment42\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ルミーズ消費税率設定情報
 *
 * @ORM\Table(name="plg_remise_payment4_remise_tax_rate")
 * @ORM\Entity(repositoryClass="Plugin\RemisePayment42\Repository\RemiseTaxRateRepository")
 */
class RemiseTaxRate
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
     * @var int
     *
     * @ORM\Column(name="tax_rate", type="integer", options={"unsigned":true})
     */
    private $tax_rate;

    /**
     * @var int
     *
     * @ORM\Column(name="calc_rule", type="integer", options={"unsigned":true})
     */
    private $calc_rule;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="apply_date", type="datetimetz")
     */
    private $apply_date;

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
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
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
     * Set tax_rate.
     *
     * @param int $tax_rate
     *
     * @return $this
     */
    public function setTaxRate($tax_rate)
    {
        $this->tax_rate = $tax_rate;

        return $this;
    }

    /**
     * Get tax_rate.
     *
     * @return int
     */
    public function getTaxRate()
    {
        return $this->tax_rate;
    }

    /**
     * Set calc_rule.
     *
     * @param int $calc_rule
     *
     * @return $this
     */
    public function setCalcRule($calc_rule)
    {
        $this->calc_rule = $calc_rule;

        return $this;
    }

    /**
     * Get calc_rule.
     *
     * @return int
     */
    public function getCalcRule()
    {
        return $this->calc_rule;
    }

    /**
     * @param \DateTime $apply_date
     *
     * @return $this;
     */
    public function setApplyDate($apply_date)
    {
        $this->apply_date = $apply_date;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getApplyDate()
    {
        return $this->apply_date;
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
    public function getCreateDate()
    {
        return $this->create_date;
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

    /**
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }
}
