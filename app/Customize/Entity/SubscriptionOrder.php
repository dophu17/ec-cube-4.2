<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;

/**
 * @ORM\Table(
 *     name="dtb_subscription_order",
 *     indexes={
 *         @ORM\Index(name="idx_subscription_order_lookup", columns={"subscription_id", "billing_status"})
 *     },
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="uniq_subscription_cycle", columns={"billing_cycle_key"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="Customize\Repository\SubscriptionOrderRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class SubscriptionOrder extends AbstractEntity
{
    public const BILLING_STATUS_PENDING = 'pending';
    public const BILLING_STATUS_SUCCESS = 'success';
    public const BILLING_STATUS_FAILED = 'failed';

    /**
     * @var int|null
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned": true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Subscription
     *
     * @ORM\ManyToOne(targetEntity="Customize\Entity\Subscription")
     * @ORM\JoinColumn(name="subscription_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $Subscription;

    /**
     * @var int|null
     *
     * @ORM\Column(name="order_id", type="integer", options={"unsigned": true}, nullable=true)
     */
    private $order_id;

    /**
     * @var string
     *
     * @ORM\Column(name="billing_cycle_key", type="string", length=128)
     */
    private $billing_cycle_key;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="billing_scheduled_at", type="datetimetz")
     */
    private $billing_scheduled_at;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="billing_executed_at", type="datetimetz", nullable=true)
     */
    private $billing_executed_at;

    /**
     * @var string
     *
     * @ORM\Column(name="billing_status", type="string", length=32)
     */
    private $billing_status;

    /**
     * @var string|null
     *
     * @ORM\Column(name="gmo_order_id", type="string", length=255, nullable=true)
     */
    private $gmo_order_id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="gmo_access_id", type="string", length=255, nullable=true)
     */
    private $gmo_access_id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="gmo_access_pass", type="string", length=255, nullable=true)
     */
    private $gmo_access_pass;

    /**
     * @var string|null
     *
     * @ORM\Column(name="gmo_tran_id", type="string", length=255, nullable=true)
     */
    private $gmo_tran_id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="gmo_approve", type="string", length=255, nullable=true)
     */
    private $gmo_approve;

    /**
     * @var string|null
     *
     * @ORM\Column(name="gmo_status", type="string", length=64, nullable=true)
     */
    private $gmo_status;

    /**
     * @var string|null
     *
     * @ORM\Column(name="error_code", type="string", length=255, nullable=true)
     */
    private $error_code;

    /**
     * @var string|null
     *
     * @ORM\Column(name="error_message", type="text", nullable=true)
     */
    private $error_message;

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
     * @var string
     *
     * @ORM\Column(name="discriminator_type", type="string", length=255)
     */
    private $discriminator_type = 'subscriptionorder';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubscription(): Subscription
    {
        return $this->Subscription;
    }

    public function setSubscription(Subscription $subscription): self
    {
        $this->Subscription = $subscription;

        return $this;
    }

    public function getOrderId(): ?int
    {
        return null === $this->order_id ? null : (int) $this->order_id;
    }

    public function setOrderId(?int $orderId): self
    {
        $this->order_id = $orderId;

        return $this;
    }

    public function getBillingCycleKey(): string
    {
        return (string) $this->billing_cycle_key;
    }

    public function setBillingCycleKey(string $billingCycleKey): self
    {
        $this->billing_cycle_key = $billingCycleKey;

        return $this;
    }

    public function getBillingScheduledAt(): \DateTime
    {
        return $this->billing_scheduled_at;
    }

    public function setBillingScheduledAt(\DateTime $billingScheduledAt): self
    {
        $this->billing_scheduled_at = $billingScheduledAt;

        return $this;
    }

    public function getBillingExecutedAt(): ?\DateTime
    {
        return $this->billing_executed_at;
    }

    public function setBillingExecutedAt(?\DateTime $billingExecutedAt): self
    {
        $this->billing_executed_at = $billingExecutedAt;

        return $this;
    }

    public function getBillingStatus(): string
    {
        return (string) $this->billing_status;
    }

    public function setBillingStatus(string $billingStatus): self
    {
        $this->billing_status = $billingStatus;

        return $this;
    }

    public function getGmoTranId(): ?string
    {
        return $this->gmo_tran_id;
    }

    public function setGmoTranId(?string $gmoTranId): self
    {
        $this->gmo_tran_id = $gmoTranId;

        return $this;
    }

    public function getGmoApprove(): ?string
    {
        return $this->gmo_approve;
    }

    public function setGmoApprove(?string $gmoApprove): self
    {
        $this->gmo_approve = $gmoApprove;

        return $this;
    }

    public function getGmoStatus(): ?string
    {
        return $this->gmo_status;
    }

    public function setGmoStatus(?string $gmoStatus): self
    {
        $this->gmo_status = $gmoStatus;

        return $this;
    }

    public function getErrorCode(): ?string
    {
        return $this->error_code;
    }

    public function setErrorCode(?string $errorCode): self
    {
        $this->error_code = $errorCode;

        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->error_message;
    }

    public function setErrorMessage(?string $errorMessage): self
    {
        $this->error_message = $errorMessage;

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
