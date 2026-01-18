<?php

declare(strict_types=1);

namespace App\Component\Order\Service;

use App\Component\Order\Entity\Order;

class OrderCalculator
{
    public function recalculate(Order $order): void
    {
        $itemsTotal = 0;
        $itemsTaxTotal = 0;
        $items = $order->getItems()->toArray();

        foreach ($items as $item) {
            $itemTotal = $item->getUnitPrice() * $item->getQuantity();
            $item->setTotal($itemTotal);

            $taxRate = $item->getProduct()?->getTaxRate() ?? 0;
            $taxValue = $taxRate > 0 ? intdiv($itemTotal * $taxRate, 100) : 0;
            $item->setTaxValue($taxValue);

            $itemsTaxTotal += $taxValue;
            $itemsTotal += $itemTotal;
        }

        $order->setItemsTotal($itemsTotal);
        $order->setTaxTotal($itemsTaxTotal);
        $order->setTotal($itemsTotal);
    }
}
