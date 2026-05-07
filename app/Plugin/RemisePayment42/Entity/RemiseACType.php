<?php

namespace Plugin\RemisePayment42\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ルミーズ定期購買種別
 *
 * @ORM\Table(name="plg_remise_payment4_remise_ac_type")
 * @ORM\Entity(repositoryClass="Plugin\RemisePayment42\Repository\RemiseACTypeRepository")
 */
class RemiseACType
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
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var int
     *
     * @ORM\Column(name="count", type="integer", nullable=true, options={"unsigned":true})
     */
    private $count;

    /**
     * @var int
     *
     * @ORM\Column(name="limitless", type="boolean", options={"default":true})
     */
    private $limitless;

    /**
     * @var int
     *
     * @ORM\Column(name="interval_value", type="integer", options={"unsigned":true})
     */
    private $interval_value;

    /**
     * @var string
     *
     * @ORM\Column(name="interval_mark", type="string", length=255)
     */
    private $interval_mark;

    /**
     * @var int
     *
     * @ORM\Column(name="day_of_month", type="integer", nullable=true, options={"unsigned":true})
     */
    private $day_of_month;

    /**
     * @var int
     *
     * @ORM\Column(name="after_value", type="integer", options={"unsigned":true})
     */
    private $after_value;

    /**
     * @var string
     *
     * @ORM\Column(name="after_mark", type="string", length=255)
     */
    private $after_mark;

    /**
     * @var int
     *
     * @ORM\Column(name="skip", type="smallint", options={"unsigned":true})
     */
    private $skip;

    /**
     * @var int
     *
     * @ORM\Column(name="stop", type="smallint", options={"unsigned":true})
     */
    private $stop;

    /**
     * @var int
     *
     * @ORM\Column(name="usage_value", type="integer", nullable=true, options={"unsigned":true})
     */
    private $usage_value;

    /**
     * @var string
     *
     * @ORM\Column(name="usage_mark", type="string", nullable=true, length=255)
     */
    private $usage_mark;

    /**
     * @var int|null
     *
     * @ORM\Column(name="sort_no", type="smallint", nullable=true, options={"unsigned":true})
     */
    private $sort_no;

    /**
     * @var int
     *
     * @ORM\Column(name="visible", type="boolean", options={"default":true})
     */
    private $visible;

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
     * @var string|null
     *
     * @ORM\Column(name="stop_display", type="string", nullable=true, length=255)
     */
     private $stop_display;

    /**
     * @var string|null
     *
     * @ORM\Column(name="free_area", type="text", nullable=true)
     */
    private $free_area;

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
     * Set name.
     *
     * @param string $name
     *
     * @return RemiseACType
     */
    public function setName($name = null)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set count.
     *
     * @param int $count
     *
     * @return RemiseACType
     */
    public function setCount($count = null)
    {
        $this->count = $count;

        return $this;
    }

    /**
     * Get count.
     *
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * Set limitless.
     *
     * @param boolean $limitless
     *
     * @return RemiseACType
     */
    public function setLimitless($limitless = null)
    {
        $this->limitless = $limitless;

        return $this;
    }

    /**
     * Get limitless.
     *
     * @return integer
     */
    public function isLimitless()
    {
        return $this->limitless;
    }

    /**
     * Set interval_value.
     *
     * @param int $interval_value
     *
     * @return RemiseACType
     */
    public function setIntervalValue($interval_value = null)
    {
        $this->interval_value = $interval_value;

        return $this;
    }

    /**
     * Get interval_value.
     *
     * @return int
     */
    public function getIntervalValue()
    {
        return $this->interval_value;
    }

    /**
     * Set interval_mark.
     *
     * @param string $interval_mark
     *
     * @return RemiseACType
     */
    public function setIntervalMark($interval_mark = null)
    {
        $this->interval_mark = $interval_mark;

        return $this;
    }

    /**
     * Get interval_mark.
     *
     * @return string
     */
    public function getIntervalMark()
    {
        return $this->interval_mark;
    }

    /**
     * Set day_of_month.
     *
     * @param int $day_of_month
     *
     * @return RemiseACType
     */
    public function setDayOfMonth($day_of_month = null)
    {
        $this->day_of_month = $day_of_month;

        return $this;
    }

    /**
     * Get day_of_month.
     *
     * @return int
     */
    public function getDayOfMonth()
    {
        return $this->day_of_month;
    }

    /**
     * Set after_value.
     *
     * @param int $after_value
     *
     * @return RemiseACType
     */
    public function setAfterValue($after_value = null)
    {
        $this->after_value = $after_value;

        return $this;
    }

    /**
     * Get after_value.
     *
     * @return int
     */
    public function getAfterValue()
    {
        return $this->after_value;
    }

    /**
     * Set after_mark.
     *
     * @param string $after_mark
     *
     * @return RemiseACType
     */
    public function setAfterMark($after_mark = null)
    {
        $this->after_mark = $after_mark;

        return $this;
    }

    /**
     * Get after_mark.
     *
     * @return string
     */
    public function getAfterMark()
    {
        return $this->after_mark;
    }

    /**
     * Set skip.
     *
     * @param integer $skip
     *
     * @return RemiseACType
     */
    public function setSkip($skip = null)
    {
        $this->skip = $skip;

        return $this;
    }

    /**
     * Get skip.
     *
     * @return integer
     */
    public function getSkip()
    {
        return $this->skip;
    }

    /**
     * Set stop.
     *
     * @param int $stop
     *
     * @return RemiseACType
     */
    public function setStop($stop = null)
    {
        $this->stop = $stop;

        return $this;
    }

    /**
     * Get stop.
     *
     * @return int
     */
    public function getStop()
    {
        return $this->stop;
    }

    /**
     * Set usage_value.
     *
     * @param int $usage_value
     *
     * @return RemiseACType
     */
    public function setUsageValue($usage_value = null)
    {
        $this->usage_value = $usage_value;

        return $this;
    }

    /**
     * Get usage_value.
     *
     * @return int
     */
    public function getUsageValue()
    {
        return $this->usage_value;
    }

    /**
     * Set usage_mark.
     *
     * @param string $usage_mark
     *
     * @return RemiseACType
     */
    public function setUsageMark($usage_mark = null)
    {
        $this->usage_mark = $usage_mark;

        return $this;
    }

    /**
     * Get usage_mark.
     *
     * @return string
     */
    public function getUsageMark()
    {
        return $this->usage_mark;
    }

    /**
     * Set sortNo.
     *
     * @param int|null $sortNo
     *
     * @return RemiseACType
     */
    public function setSortNo($sortNo = null)
    {
        $this->sort_no = $sortNo;

        return $this;
    }

    /**
     * Get sortNo.
     *
     * @return int|null
     */
    public function getSortNo()
    {
        return $this->sort_no;
    }

    /**
     * @return integer
     */
    public function isVisible()
    {
        return $this->visible;
    }

    /**
     * @param boolean $visible
     *
     * @return RemiseACType
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
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

    /**
     * Set stopDisplay.
     *
     * @param string $stopDisplay
     *
     * @return RemiseACType
     */
    public function setStopDisplay($stopDisplay = null)
    {
        $this->stop_display = $stopDisplay;

        return $this;
    }

    /**
     * Get stopDisplay.
     *
     * @return string
     */
    public function getStopDisplay()
    {
        return $this->stop_display;
    }

    /**
     * Set freeArea.
     *
     * @param string|null $freeArea
     *
     * @return RemiseACType
     */
    public function setFreeArea($freeArea = null)
    {
        $this->free_area = $freeArea;

        return $this;
    }

    /**
     * Get freeArea.
     *
     * @return string|null
     */
    public function getFreeArea()
    {
        return $this->free_area;
    }
}
