<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;
use Eccube\Entity\Customer;

/**
 * @ORM\Table(
 *     name="dtb_subscription",
 *     indexes={
 *         @ORM\Index(name="idx_subscription_status_next", columns={"status", "next_billing_at"}),
 *         @ORM\Index(name="idx_subscription_customer_status", columns={"customer_id", "status"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="Customize\Repository\SubscriptionRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Subscription extends AbstractEntity
{
    public const STATUS_PENDING_ACTIVATION = 'pending_activation';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_PAST_DUE = 'past_due';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';

    public const PLAN_TEST_MINUTE = 'test_minute';
    public const PLAN_WEEKLY = 'weekly';
    public const PLAN_MONTHLY = 'monthly';

    /**
     * @var int|null
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned": true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Customer|null
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Customer")
     * @ORM\JoinColumn(name="customer_id", referencedColumnName="id", nullable=false)
     */
    private $Customer;

    /**
     * @var int
     *
     * @ORM\Column(name="base_order_id", type="integer", options={"unsigned": true})
     */
    private $base_order_id;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=32)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="plan_type", type="string", length=32)
     */
    private $plan_type;

    /**
     * @var int
     *
     * @ORM\Column(name="interval_count", type="smallint", options={"unsigned": true})
     */
    private $interval_count = 1;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="next_billing_at", type="datetime")
     */
    private $next_billing_at;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="last_billed_at", type="datetime", nullable=true)
     */
    private $last_billed_at;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="cancelled_at", type="datetime", nullable=true)
     */
    private $cancelled_at;

    /**
     * @var int
     *
     * @ORM\Column(name="retry_count", type="smallint", options={"unsigned": true})
     */
    private $retry_count = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="max_retry", type="smallint", options={"unsigned": true})
     */
    private $max_retry = 3;

    /**
     * @var int
     *
     * @ORM\Column(name="subtotal_amount", type="integer")
     */
    private $subtotal_amount = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="discount_amount", type="integer")
     */
    private $discount_amount = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="shipping_fee", type="integer")
     */
    private $shipping_fee = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="tax_amount", type="integer")
     */
    private $tax_amount = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="total_amount", type="integer")
     */
    private $total_amount = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="payment_gateway", type="string", length=32)
     */
    private $payment_gateway = 'gmo';

    /**
     * @var string|null
     *
     * @ORM\Column(name="gmo_member_id", type="string", length=255, nullable=true)
     */
    private $gmo_member_id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="gmo_card_seq", type="string", length=64, nullable=true)
     */
    private $gmo_card_seq;

    /**
     * @var string|null
     *
     * @ORM\Column(name="gmo_last_order_id", type="string", length=255, nullable=true)
     */
    private $gmo_last_order_id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetime")
     */
    private $create_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_date", type="datetime")
     */
    private $update_date;

    /**
     * @var string
     *
     * @ORM\Column(name="discriminator_type", type="string", length=255)
     */
    private $discriminator_type = 'subscription';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?Customer
    {
        return $this->Customer;
    }

    public function setCustomer(?Customer $Customer): self
    {
        $this->Customer = $Customer;

        return $this;
    }

    public function getBaseOrderId(): int
    {
        return (int) $this->base_order_id;
    }

    public function setBaseOrderId(int $baseOrderId): self
    {
        $this->base_order_id = $baseOrderId;

        return $this;
    }

    public function getStatus(): string
    {
        return (string) $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getPlanType(): string
    {
        return (string) $this->plan_type;
    }

    public function setPlanType(string $planType): self
    {
        $this->plan_type = $planType;

        return $this;
    }

    public function getIntervalCount(): int
    {
        return (int) $this->interval_count;
    }

    public function setIntervalCount(int $intervalCount): self
    {
        $this->interval_count = $intervalCount;

        return $this;
    }

    public function getNextBillingAt(): \DateTime
    {
        return $this->next_billing_at;
    }

    public function setNextBillingAt(\DateTime $nextBillingAt): self
    {
        $this->next_billing_at = $nextBillingAt;

        return $this;
    }

    public function getLastBilledAt(): ?\DateTime
    {
        return $this->last_billed_at;
    }

    public function setLastBilledAt(?\DateTime $lastBilledAt): self
    {
        $this->last_billed_at = $lastBilledAt;

        return $this;
    }

    public function getCancelledAt(): ?\DateTime
    {
        return $this->cancelled_at;
    }

    public function setCancelledAt(?\DateTime $cancelledAt): self
    {
        $this->cancelled_at = $cancelledAt;

        return $this;
    }

    public function getRetryCount(): int
    {
        return (int) $this->retry_count;
    }

    public function setRetryCount(int $retryCount): self
    {
        $this->retry_count = $retryCount;

        return $this;
    }

    public function incrementRetryCount(): self
    {
        ++$this->retry_count;

        return $this;
    }

    public function getMaxRetry(): int
    {
        return (int) $this->max_retry;
    }

    public function setMaxRetry(int $maxRetry): self
    {
        $this->max_retry = $maxRetry;

        return $this;
    }

    public function getSubtotalAmount(): int
    {
        return (int) $this->subtotal_amount;
    }

    public function setSubtotalAmount(int $subtotalAmount): self
    {
        $this->subtotal_amount = $subtotalAmount;

        return $this;
    }

    public function getDiscountAmount(): int
    {
        return (int) $this->discount_amount;
    }

    public function setDiscountAmount(int $discountAmount): self
    {
        $this->discount_amount = $discountAmount;

        return $this;
    }

    public function getShippingFee(): int
    {
        return (int) $this->shipping_fee;
    }

    public function setShippingFee(int $shippingFee): self
    {
        $this->shipping_fee = $shippingFee;

        return $this;
    }

    public function getTaxAmount(): int
    {
        return (int) $this->tax_amount;
    }

    public function setTaxAmount(int $taxAmount): self
    {
        $this->tax_amount = $taxAmount;

        return $this;
    }

    public function getTotalAmount(): int
    {
        return (int) $this->total_amount;
    }

    public function setTotalAmount(int $totalAmount): self
    {
        $this->total_amount = $totalAmount;

        return $this;
    }

    public function getPaymentGateway(): string
    {
        return (string) $this->payment_gateway;
    }

    public function setPaymentGateway(string $paymentGateway): self
    {
        $this->payment_gateway = $paymentGateway;

        return $this;
    }

    public function getGmoMemberId(): ?string
    {
        return $this->gmo_member_id;
    }

    public function setGmoMemberId(?string $gmoMemberId): self
    {
        $this->gmo_member_id = $gmoMemberId;

        return $this;
    }

    public function getGmoCardSeq(): ?string
    {
        return $this->gmo_card_seq;
    }

    public function setGmoCardSeq(?string $gmoCardSeq): self
    {
        $this->gmo_card_seq = $gmoCardSeq;

        return $this;
    }

    public function getGmoLastOrderId(): ?string
    {
        return $this->gmo_last_order_id;
    }

    public function setGmoLastOrderId(?string $gmoLastOrderId): self
    {
        $this->gmo_last_order_id = $gmoLastOrderId;

        return $this;
    }

    public function getCreateDate(): \DateTime
    {
        return $this->create_date;
    }

    public function setCreateDate(\DateTime $createDate): self
    {
        $this->create_date = $createDate;

        return $this;
    }

    public function getUpdateDate(): \DateTime
    {
        return $this->update_date;
    }

    public function setUpdateDate(\DateTime $updateDate): self
    {
        $this->update_date = $updateDate;

        return $this;
    }
}
