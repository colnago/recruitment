<?php

declare(strict_types=1);

namespace App\Component\Order\Service;

use App\Component\Order\Entity\Order;

class OrderViewBuilder
{
    public function build(Order $order): array
    {
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
                    'price' => $product->getPrice(),
                    'taxRate' => $product->getTaxRate(),
                ],
                'unitPrice' => $item->getUnitPrice(),
                'quantity' => $item->getQuantity(),
                'discount' => $item->getDiscount(),
                'discountValue' => $item->getDiscountValue(),
                'distributedOrderDiscountValue' => $item->getDistributedOrderDiscountValue(),
                'discountedUnitPrice' => $item->getDiscountedUnitPrice(),
                'total' => $item->getTotal(),
                'taxValue' => $item->getTaxValue(),
            ];
        }

        return [
            'id' => $order->getId(),
            'itemsTotal' => $order->getItemsTotal(),
            'adjustmentsTotal' => $order->getAdjustmentsTotal(),
            'taxTotal' => $order->getTaxTotal(),
            'total' => $order->getTotal(),
            'items' => $items,
        ];
    }
}
