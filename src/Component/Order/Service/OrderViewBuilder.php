<?php

declare(strict_types=1);

namespace App\Component\Order\Service;

use App\Component\Currency\CurrencyConverter;
use App\Component\Order\Entity\Order;

class OrderViewBuilder
{
    public function __construct(
        private readonly CurrencyConverter $converter,
    ) {}

    public function build(Order $order, ?string $currency = null): array
    {
        $currency = $this->converter->normalize($currency);
        $convert = fn (int $v): int => $this->converter->convert($v, $currency);

        $items = [];
        foreach ($order->getItems() as $item) {
            $product = $item->getProduct();
            $items[] = [
                'id' => $item->getId(),
                'product' => [
                    'id' => $product->getId(),
                    'code' => $product->getCode(),
                    'name' => $product->getName(),
                    'type' => $product->getType(),
                    'price' => $convert($product->getPrice()),
                    'taxRate' => $convert($product->getTaxRate()),
                ],
                'unitPrice' => $convert($item->getUnitPrice()),
                'quantity' => $item->getQuantity(),
                'discount' => $convert($item->getDiscount() ?? 0),
                'discountValue' => $convert($item->getDiscountValue()),
                'distributedOrderDiscountValue' => $convert($item->getDistributedOrderDiscountValue()),
                'discountedUnitPrice' => $convert($item->getDiscountedUnitPrice()),
                'total' => $convert($item->getTotal()),
                'taxValue' => $convert($item->getTaxValue()),
            ];
        }

        return [
            'id' => $order->getId(),
            'currency' => $currency,
            'itemsTotal' => $convert($order->getItemsTotal()),
            'adjustmentsTotal' => $convert($order->getAdjustmentsTotal()),
            'taxTotal' => $convert($order->getTaxTotal()),
            'total' => $convert($order->getTotal()),
            'items' => $items,
        ];
    }
}
