<?php

declare(strict_types=1);

namespace App\Component\Order\Entity;

use App\Component\OrderItem\Entity\OrderItem;
use App\Component\Promotion\Entity\Promotion;
use App\Component\User\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Order
{
    protected int $id;
    protected ?User $user = null;
    protected int $itemsTotal = 0;
    protected int $adjustmentsTotal = 0;
    /**
     * Items total + adjustments total.
     */
    protected int $taxTotal = 0;
    protected int $total = 0;
    private ?int $orderDiscount = null;
    private ?Promotion $orderPromotion = null;
    /**
     * @var Collection<array-key, OrderItem>
     */
    protected Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getItemsTotal(): int
    {
        return $this->itemsTotal;
    }

    public function setItemsTotal(int $itemsTotal): void
    {
        $this->itemsTotal = $itemsTotal;
    }

    public function getAdjustmentsTotal(): int
    {
        return $this->adjustmentsTotal;
    }

    public function setAdjustmentsTotal(int $adjustmentsTotal): void
    {
        $this->adjustmentsTotal = $adjustmentsTotal;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): void
    {
        $this->total = $total;
    }

    public function getTaxTotal(): int
    {
        return $this->taxTotal;
    }

    public function setTaxTotal(int $taxTotal): void
    {
        $this->taxTotal = $taxTotal;
    }

    public function getOrderPromotion(): ?Promotion
    {
        return $this->orderPromotion;
    }

    public function setOrderPromotion(?Promotion $promotion): void
    {
        $this->orderPromotion = $promotion;
    }

    public function getOrderDiscount(): ?int
    {
        return $this->orderDiscount;
    }

    public function setOrderDiscount(?int $orderDiscount): void
    {
        $this->orderDiscount = $orderDiscount;
    }

    /**
     * @return Collection<array-key, OrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function clearItems(): void
    {
        $this->items->clear();

        $this->recalculateItemsTotal();
    }

    public function addItem(OrderItem $item): void
    {
        if ($this->hasItem($item)) {
            return;
        }

        $this->items->add($item);
        $item->setOrder($this);

        $this->recalculateItemsTotal();
    }

    public function removeItem(OrderItem $item): void
    {
        if (!$this->hasItem($item)) {
            return;
        }

        $this->items->removeElement($item);
        $item->setOrder(null);

        $this->recalculateItemsTotal();
    }

    public function hasItem(OrderItem $item): bool
    {
        return $this->items->contains($item);
    }

    /**
     * Items total + Adjustments total.
     */
    protected function recalculateTotal(): void
    {
        $this->total = $this->itemsTotal + $this->adjustmentsTotal;

        if ($this->total < 0) {
            $this->total = 0;
        }
    }

    protected function recalculateItemsTotal(): void
    {
        $this->itemsTotal = 0;
        foreach ($this->items as $item) {
            $this->itemsTotal += $item->getTotal();
        }

        $this->recalculateTotal();
    }

    public function recalculateTotalPublic(): void
    {
        $this->recalculateTotal();
    }
}
