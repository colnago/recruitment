<?php

namespace App\Component\User\Entity;

use App\Component\Order\Entity\Order;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class User
{
    protected int $id;
    protected string $name;
    /**
     * @var Collection<array-key, Order>
     */
    protected Collection $orders;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return Collection<array-key, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }
}
