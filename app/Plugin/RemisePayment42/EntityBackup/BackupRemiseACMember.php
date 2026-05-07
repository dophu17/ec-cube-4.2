<?php

namespace Plugin\RemisePayment42\EntityBackup;

use Doctrine\ORM\Mapping as ORM;

/**
 * ルミーズ定期購買情報
 *
 * @ORM\Table(name="backup_plg_remise_payment4_remise_ac_member")
 * @ORM\Entity(repositoryClass="Plugin\RemisePayment42\RepositoryBackup\BackupRemiseACMemberRepository")
 */
class BackupRemiseACMember
{
    /**
     * @var string
     *
     * @ORM\Column(name="id", type="string", length=14)
     * @ORM\Id
      */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="total", type="integer", options={"unsigned":true})
     */
    private $total;

    /**
     * @var int
     *
     * @ORM\Column(name="count", type="integer", nullable=true, options={"unsigned":true})
     */
    private $count;

    /**
     * @var int
     *
     * @ORM\Column(name="limitless", type="boolean")
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
     * @var string
     *
     * @ORM\Column(name="cardparts", type="string", nullable=true, length=255)
     */
    private $cardparts;

    /**
     * @var string
     *
     * @ORM\Column(name="expire", type="string", nullable=true, length=255)
     */
    private $expire;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="next_date", type="datetimetz", nullable=true)
     */
    private $next_date;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="boolean", options={"default":true})
     */
    private $status;

    /**
     * @var int
     *
     * @ORM\Column(name="skipped", type="boolean", nullable=true)
     */
    private $skipped;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="skipped_date", type="datetimetz", nullable=true)
     */
    private $skipped_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="skipped_next_date", type="datetimetz", nullable=true)
     */
    private $skipped_next_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_date", type="datetimetz", nullable=true)
     */
    private $start_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="stop_date", type="datetimetz", nullable=true)
     */
    private $stop_date;

    /**
     * @var string
     *
     * @ORM\Column(name="note", type="text", nullable=true)
     */
    private $note;

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
     * @return string
     */
    public function setId($id = null)
    {
        $this->id = $id;

        return $this;
    }

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
     * Set cardparts.
     *
     * @param string $cardparts
     *
     * @return RemiseACType
     */
    public function setCardparts($cardparts = null)
    {
        $this->cardparts = $cardparts;

        return $this;
    }

    /**
     * Get cardparts.
     *
     * @return string
     */
    public function getCardparts()
    {
        return $this->cardparts;
    }

    /**
     * Set expire.
     *
     * @param string $expire
     *
     * @return RemiseACType
     */
    public function setExpire($expire = null)
    {
        $this->expire = $expire;

        return $this;
    }

    /**
     * Get expire.
     *
     * @return string
     */
    public function getExpire()
    {
        return $this->expire;
    }

    /**
     * Set next_date.
     *
     * @param \DateTime $next_date
     *
     * @return RemiseACType
     */
    public function setNextDate($next_date = null)
    {
        $this->next_date = $next_date;

        return $this;
    }

    /**
     * Get next_date.
     *
     * @return \DateTime
     */
    public function getNextDate()
    {
        return $this->next_date;
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return RemiseACType
     */
    public function setStatus($status = null)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function isStatus()
    {
        return $this->status;
    }

    /**
     * Set skipped.
     *
     * @param boolean $skipped
     *
     * @return RemiseACType
     */
    public function setSkipped($skipped = null)
    {
        $this->skipped = $skipped;

        return $this;
    }

    /**
     * Get skipped.
     *
     * @return integer
     */
    public function isSkipped()
    {
        return $this->skipped;
    }

    /**
     * Set skipped_date.
     *
     * @param \DateTime $skipped_date
     *
     * @return RemiseACType
     */
    public function setSkippedDate($skipped_date)
    {
        $this->skipped_date = $skipped_date;

        return $this;
    }

    /**
     * Get skipped_date.
     *
     * @return \DateTime
     */
    public function getSkippedDate()
    {
        return $this->skipped_date;
    }

    /**
     * Set skipped_next_date.
     *
     * @param \DateTime $skipped_next_date
     *
     * @return RemiseACType
     */
    public function setSkippedNextDate($skipped_next_date)
    {
        $this->skipped_next_date = $skipped_next_date;

        return $this;
    }

    /**
     * Get skipped_next_date.
     *
     * @return \DateTime
     */
    public function getSkippedNextDate()
    {
        return $this->skipped_next_date;
    }

    /**
     * Set start_date.
     *
     * @param \DateTime $start_date
     *
     * @return RemiseACType
     */
    public function setStartDate($start_date)
    {
        $this->start_date = $start_date;

        return $this;
    }

    /**
     * Get start_date.
     *
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->start_date;
    }

    /**
     * Set stop_date.
     *
     * @param \DateTime $stop_date
     *
     * @return RemiseACType
     */
    public function setStopDate($stop_date)
    {
        $this->stop_date = $stop_date;

        return $this;
    }

    /**
     * Get stop_date.
     *
     * @return \DateTime
     */
    public function getStopDate()
    {
        return $this->stop_date;
    }

    /**
     * Set note.
     *
     * @param string $note
     *
     * @return RemiseACType
     */
    public function setNote($note = null)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * Get note.
     *
     * @return string
     */
    public function getNote()
    {
        return $this->note;
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
