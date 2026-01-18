<?php

declare(strict_types=1);

namespace App\Http\Dto\Cart;

use Symfony\Component\Validator\Constraints as Assert;

class CartAddItemDto
{
    public function __construct(
        #[Assert\Positive]
        public readonly int $productId,

        #[Assert\Positive]
        public readonly int $quantity,

        #[Assert\Positive]
        public readonly ?int $orderId = null,

        #[Assert\Positive]
        public readonly ?int $userId = null,
    ) {}
}