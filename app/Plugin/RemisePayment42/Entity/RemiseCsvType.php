<?php

namespace Plugin\RemisePayment42\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ルミーズCSV種別
 *
 * @ORM\Table(name="plg_remise_payment4_remise_csv_type")
 * @ORM\Entity(repositoryClass="Plugin\RemisePayment42\Repository\RemiseCsvTypeRepository")
 */
class RemiseCsvType
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="smallint", options={"unsigned":true})
     * @ORM\Id
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
     * @ORM\Column(name="csv_type", type="smallint", options={"unsigned":true})
     */
    private $csv_type;

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
     * Set name.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
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
     * Set csv_type.
     *
     * @param int $csv_type
     *
     * @return $this
     */
    public function setCsvType($csv_type)
    {
        $this->csv_type = $csv_type;

        return $this;
    }

    /**
     * Get csv_type.
     *
     * @return int
     */
    public function getCsvType()
    {
        return $this->csv_type;
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
