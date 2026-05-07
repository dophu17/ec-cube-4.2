<?php

namespace Plugin\RemisePayment42\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 決済履歴
 *
 * @ORM\Table(name="plg_remise_payment4_order_result")
 * @ORM\Entity(repositoryClass="Plugin\RemisePayment42\Repository\OrderResultRepository")
 */
class OrderResult
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
     * @ORM\Column(name="kind", type="string", length=2)
     */
    private $kind;

    /**
     * @var int
     *
     * @ORM\Column(name="payment_id", type="integer", options={"unsigned":true})
     */
    private $payment_id;

    /**
     * @var string
     *
     * @ORM\Column(name="payment_total", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0})
     */
    private $payment_total = 0;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="request_date", type="datetimetz", nullable=true)
     */
    private $request_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="complete_date", type="datetimetz", nullable=true)
     */
    private $complete_date;

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
     * @var \Eccube\Entity\Order
     *
     */
    private $Order;

    /**
     * @var \Plugin\RemisePayment42\Entity\OrderResultCard
     *
     */
    private $OrderResultCard;

    /**
     * @var \Plugin\RemisePayment42\Entity\OrderResultCvs
     *
     */
    private $OrderResultCvs;

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
    public function getKind()
    {
        return $this->kind;
    }

    /**
     * @param string $kind
     *
     * @return $this;
     */
    public function setKind($kind)
    {
        $this->kind = $kind;

        return $this;
    }

    /**
     * @return int
     */
    public function getPaymentId()
    {
        return $this->payment_id;
    }

    /**
     * @param int $payment_id
     *
     * @return $this;
     */
    public function setPaymentId($payment_id)
    {
        $this->payment_id = $payment_id;

        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentTotal()
    {
        return $this->payment_total;
    }

    /**
     * @param string $payment_total
     *
     * @return $this;
     */
    public function setPaymentTotal($payment_total)
    {
        $this->payment_total = $payment_total;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getRequestDate()
    {
        return $this->request_date;
    }

    /**
     * @param \DateTime $request_date
     *
     * @return $this;
     */
    public function setRequestDate($request_date)
    {
        $this->request_date = $request_date;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCompleteDate()
    {
        return $this->complete_date;
    }

    /**
     * @param \DateTime $complete_date
     *
     * @return $this;
     */
    public function setCompleteDate($complete_date)
    {
        $this->complete_date = $complete_date;

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

    /**
     * Set order.
     *
     * @param \Eccube\Entity\Order|null $order
     *
     * @return OrderItem
     */
    public function setOrder(\Eccube\Entity\Order $order = null)
    {
        $this->Order = $order;

        return $this;
    }

    /**
     * Get order.
     *
     * @return \Eccube\Entity\Order|null
     */
    public function getOrder()
    {
        return $this->Order;
    }

    public function getOrderId()
    {
        if (is_object($this->getOrder())) {
            return $this->getOrder()->getId();
        }

        return null;
    }

    /**
     * Set OrderResultCard.
     *
     * @param \Plugin\RemisePayment42\Entity\OrderResultCard|null $OrderResultCard
     *
     * @return OrderResultCard
     */
    public function setOrderResultCard(\Plugin\RemisePayment42\Entity\OrderResultCard $OrderResultCard = null)
    {
        $this->OrderResultCard = $OrderResultCard;

        return $this;
    }

    /**
     * Get OrderResultCard.
     *
     * @return \Plugin\RemisePayment42\Entity\OrderResultCard|null
     */
    public function getOrderResultCard()
    {
        return $this->OrderResultCard;
    }

    /**
     * Set OrderResultCvs.
     *
     * @param \Plugin\RemisePayment42\Entity\OrderResultCvs|null $OrderResultCvs
     *
     * @return OrderResultCvs
     */
    public function setOrderResultCvs(\Plugin\RemisePayment42\Entity\OrderResultCvs $OrderResultCvs = null)
    {
        $this->OrderResultCvs = $OrderResultCvs;

        return $this;
    }

    /**
     * Get OrderResultCvs.
     *
     * @return \Plugin\RemisePayment42\Entity\OrderResultCvs|null
     */
    public function getOrderResultCvs()
    {
        return $this->OrderResultCvs;
    }
}
