<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;

/**
 * @ORM\Table(name="dtb_subscription_event_log", indexes={
 *     @ORM\Index(name="idx_subscription_event_sub", columns={"subscription_id"})
 * })
 * @ORM\Entity(repositoryClass="Customize\Repository\SubscriptionEventLogRepository")
 */
class SubscriptionEventLog extends AbstractEntity
{
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
     * @var string
     *
     * @ORM\Column(name="event_type", type="string", length=64)
     */
    private $event_type;

    /**
     * @var string|null
     *
     * @ORM\Column(name="payload", type="text", nullable=true)
     */
    private $payload;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetime")
     */
    private $create_date;

    /**
     * @var string
     *
     * @ORM\Column(name="discriminator_type", type="string", length=255)
     */
    private $discriminator_type = 'subscriptioneventlog';

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

    public function getEventType(): string
    {
        return (string) $this->event_type;
    }

    public function setEventType(string $eventType): self
    {
        $this->event_type = $eventType;

        return $this;
    }

    public function getPayload(): ?string
    {
        return $this->payload;
    }

    public function setPayload(?string $payload): self
    {
        $this->payload = $payload;

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
}
