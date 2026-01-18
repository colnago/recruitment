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

        $subtotalAfterItemDiscounts = 0;
        foreach ($items as $item) {
            $unitPrice = $item->getUnitPrice();
            $qty = $item->getQuantity();

            $itemPercent = $item->getDiscount() ?? 0;
            $itemDiscountValue = $itemPercent > 0 ? intdiv($unitPrice * $itemPercent, 100) : 0;

            $item->setDiscountValue($itemDiscountValue);
            $item->setDistributedOrderDiscountValue(0);

            $unitAfterItemDiscount = $unitPrice - $itemDiscountValue;
            if ($unitAfterItemDiscount < 0) {
                $unitAfterItemDiscount = 0;
            }

            $subtotalAfterItemDiscounts += $unitAfterItemDiscount * $qty;
        }

        $orderDiscountPercent = $order->getOrderDiscount() ?? 0;
        $orderDiscountTotal = 0;

        if ($orderDiscountPercent > 0 && $subtotalAfterItemDiscounts > 0) {
            $orderDiscountTotal = intdiv($subtotalAfterItemDiscounts * $orderDiscountPercent, 100);
        }

        $distributedTotal = $this->distributeOrderDiscountPerUnit($items, $subtotalAfterItemDiscounts, $orderDiscountTotal);

        foreach ($items as $item) {
            $unitAfterItemDiscount = $item->getUnitPrice() - $item->getDiscountValue();
            if ($unitAfterItemDiscount < 0) {
                $unitAfterItemDiscount = 0;
            }

            $unitAfterAllDiscounts = $unitAfterItemDiscount - $item->getDistributedOrderDiscountValue();
            if ($unitAfterAllDiscounts < 0) {
                $unitAfterAllDiscounts = 0;
            }

            $item->setDiscountedUnitPrice($unitAfterAllDiscounts);

            $itemTotal = $unitAfterAllDiscounts * $item->getQuantity();
            $item->setTotal($itemTotal);

            $taxRate = $item->getProduct()?->getTaxRate() ?? 0;
            $taxValue = $taxRate > 0 ? intdiv($itemTotal * $taxRate, 100) : 0;
            $item->setTaxValue($taxValue);

            $itemsTaxTotal += $taxValue;
            $itemsTotal += $itemTotal;
        }

        $order->setAdjustmentsTotal(-$distributedTotal);
        $order->setItemsTotal($itemsTotal);
        $order->setTaxTotal($itemsTaxTotal);

        $order->recalculateTotalPublic();
    }

    private function distributeOrderDiscountPerUnit(array $items, int $subtotalAfterItemDiscounts, int $orderDiscountTotal): int
    {
        if ($orderDiscountTotal <= 0 || $subtotalAfterItemDiscounts <= 0) {
            return 0;
        }

        $rows = [];
        $used = 0;

        foreach ($items as $idx => $item) {
            $qty = $item->getQuantity();
            if ($qty <= 0) {
                $rows[] = ['idx' => $idx, 'qty' => 0, 'fraction' => 0.0];
                continue;
            }

            $unitAfterItemDiscount = $item->getUnitPrice() - $item->getDiscountValue();
            if ($unitAfterItemDiscount < 0) {
                $unitAfterItemDiscount = 0;
            }

            $lineTotal = $unitAfterItemDiscount * $qty;
            $rawLine = ($orderDiscountTotal * $lineTotal) / $subtotalAfterItemDiscounts;

            $baseLine = (int) floor($rawLine);

            $baseLine -= ($baseLine % $qty);

            $perUnit = intdiv($baseLine, $qty);
            if ($perUnit < 0) {
                $perUnit = 0;
            }

            $item->setDistributedOrderDiscountValue($perUnit);
            $used += $perUnit * $qty;

            $rows[] = ['idx' => $idx, 'qty' => $qty, 'fraction' => $rawLine - $baseLine];
        }

        $remainder = $orderDiscountTotal - $used;
        if ($remainder <= 0) {
            return $used;
        }

        usort($rows, static fn(array $a, array $b) => $b['fraction'] <=> $a['fraction']);

        $progress = true;
        while ($remainder > 0 && $progress) {
            $progress = false;

            foreach ($rows as $row) {
                $qty = (int) $row['qty'];
                if ($qty <= 0) {
                    continue;
                }
                if ($remainder < $qty) {
                    continue;
                }

                $item = $items[$row['idx']];
                $item->setDistributedOrderDiscountValue($item->getDistributedOrderDiscountValue() + 1);

                $used += $qty;
                $remainder -= $qty;
                $progress = true;

                if ($remainder <= 0) {
                    break;
                }
            }
        }

        return $used;
    }
}
