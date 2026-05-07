<?php
namespace Plugin\RemisePayment42\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\ProductClass")
 */
trait ProductClassTrait
{
    /**
     * @var int
     *
     * @ORM\Column(name="remise_payment4_ac_actype_id", type="integer", nullable=true, options={"unsigned":true})
     */
    private $remise_payment4_ac_actype_id;

    /**
     * @var int
     *
     * @ORM\Column(name="remise_payment4_ac_amount", type="integer", nullable=true, options={"unsigned":true})
     */
    private $remise_payment4_ac_amount;

    /**
     * @var int
     *
     * @ORM\Column(name="remise_payment4_ac_point_flg", type="boolean", nullable=true, options={"default":true})
     */
    private $remise_payment4_ac_point_flg;

    /**
     * Set ac_type_id.
     *
     * @param int $ac_type_id
     *
     * @return ProductClass
     */
    public function setRemisePayment4AcAcTypeId($remise_payment4_ac_actype_id = null)
    {
        $this->remise_payment4_ac_actype_id = $remise_payment4_ac_actype_id;

        return $this;
    }

    /**
     * Get ac_type_id.
     *
     * @return int
     */
    public function getRemisePayment4AcAcTypeId()
    {
        return $this->remise_payment4_ac_actype_id;
    }

    /**
     * Set amount.
     *
     * @param int $amount
     *
     * @return ProductClass
     */
    public function setRemisePayment4AcAmount($remise_payment4_ac_amount = null)
    {
        $this->remise_payment4_ac_amount = $remise_payment4_ac_amount;

        return $this;
    }

    /**
     * Get amount.
     *
     * @return int
     */
    public function getRemisePayment4AcAmount()
    {
        return $this->remise_payment4_ac_amount;
    }

    /**
     * Set point_flg.
     *
     * @param int $point_flg
     *
     * @return ProductClass
     */
    public function setRemisePayment4AcPointFlg($remise_payment4_ac_point_flg = null)
    {
        $this->remise_payment4_ac_point_flg = $remise_payment4_ac_point_flg;

        return $this;
    }

    /**
     * Get point_flg.
     *
     * @return int
     */
    public function getRemisePayment4AcPointFlg()
    {
        return $this->remise_payment4_ac_point_flg;
    }
}
