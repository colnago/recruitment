<?php

declare(strict_types=1);

namespace App\Component\Product\Entity;

class Product
{
    protected int $id;
    protected string $code;
    protected string $name;
    protected int $price;
    /** null means that product is tax-free  */
    protected ?int $taxRate;

    public function getId(): int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): void
    {
        $this->price = $price;
    }

    public function getTaxRate(): ?int
    {
        return $this->taxRate;
    }

    public function setTaxRate(?int $taxRate): void
    {
        $this->taxRate = $taxRate;
    }
}
