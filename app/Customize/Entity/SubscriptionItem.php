<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;

/**
 * @ORM\Table(name="dtb_subscription_item", indexes={
 *     @ORM\Index(name="idx_subscription_item_sub", columns={"subscription_id"})
 * })
 * @ORM\Entity(repositoryClass="Customize\Repository\SubscriptionItemRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class SubscriptionItem extends AbstractEntity
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
     * @var Product|null
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $Product;

    /**
     * @var ProductClass|null
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\ProductClass")
     * @ORM\JoinColumn(name="product_class_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $ProductClass;

    /**
     * @var string
     *
     * @ORM\Column(name="product_name_snapshot", type="string", length=255)
     */
    private $product_name_snapshot;

    /**
     * @var string|null
     *
     * @ORM\Column(name="product_code_snapshot", type="string", length=255, nullable=true)
     */
    private $product_code_snapshot;

    /**
     * @var int
     *
     * @ORM\Column(name="quantity", type="integer")
     */
    private $quantity = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="unit_price_snapshot", type="integer")
     */
    private $unit_price_snapshot = 0;

    /**
     * @var string|null
     *
     * @ORM\Column(name="tax_rate_snapshot", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $tax_rate_snapshot;

    /**
     * @var int
     *
     * @ORM\Column(name="is_combo", type="smallint", options={"unsigned": true})
     */
    private $is_combo = 0;

    /**
     * @var string|null
     *
     * @ORM\Column(name="combo_code", type="string", length=64, nullable=true)
     */
    private $combo_code;

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
    private $discriminator_type = 'subscriptionitem';

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

    public function getProduct(): ?Product
    {
        return $this->Product;
    }

    public function setProduct(?Product $product): self
    {
        $this->Product = $product;

        return $this;
    }

    public function getProductClass(): ?ProductClass
    {
        return $this->ProductClass;
    }

    public function setProductClass(?ProductClass $productClass): self
    {
        $this->ProductClass = $productClass;

        return $this;
    }

    public function getProductNameSnapshot(): string
    {
        return (string) $this->product_name_snapshot;
    }

    public function setProductNameSnapshot(string $name): self
    {
        $this->product_name_snapshot = $name;

        return $this;
    }

    public function getProductCodeSnapshot(): ?string
    {
        return $this->product_code_snapshot;
    }

    public function setProductCodeSnapshot(?string $code): self
    {
        $this->product_code_snapshot = $code;

        return $this;
    }

    public function getQuantity(): int
    {
        return (int) $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getUnitPriceSnapshot(): int
    {
        return (int) $this->unit_price_snapshot;
    }

    public function setUnitPriceSnapshot(int $unitPrice): self
    {
        $this->unit_price_snapshot = $unitPrice;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTaxRateSnapshot()
    {
        return null === $this->tax_rate_snapshot ? null : (string) $this->tax_rate_snapshot;
    }

    /**
     * @param string|null $rate
     */
    public function setTaxRateSnapshot($rate): self
    {
        $this->tax_rate_snapshot = $rate;

        return $this;
    }

    public function isCombo(): bool
    {
        return (int) $this->is_combo === 1;
    }

    public function setIsCombo(bool $isCombo): self
    {
        $this->is_combo = $isCombo ? 1 : 0;

        return $this;
    }

    public function getComboCode(): ?string
    {
        return $this->combo_code;
    }

    public function setComboCode(?string $comboCode): self
    {
        $this->combo_code = $comboCode;

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
