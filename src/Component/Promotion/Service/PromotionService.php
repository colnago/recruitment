<?php

declare(strict_types=1);

namespace App\Component\Promotion\Service;

use App\Component\Order\Entity\Order;
use App\Component\Order\Service\OrderCalculator;
use App\Component\Promotion\Entity\Promotion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class PromotionService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OrderCalculator $calculator,
    ) {}

    public function applyPromotion(int $orderId, int $promotionId): Order
    {
        $order = $this->em->find(Order::class, $orderId);
        if (!$order) {
            throw new NotFoundHttpException('Order not found.');
        }

        $promotion = $this->em->find(Promotion::class, $promotionId);
        if (!$promotion) {
            throw new NotFoundHttpException('Promotion not found.');
        }

        if ($promotion->getType() === Promotion::TYPE_ORDER) {
            if ($order->getOrderPromotion()) {
                throw new UnprocessableEntityHttpException('Order already has order-level promotion.');
            }

            $order->setOrderPromotion($promotion);
            $order->setOrderDiscount($promotion->getPercentageDiscount());
        } elseif ($promotion->getType() === Promotion::TYPE_ITEM) {
            $filter = $promotion->getProductTypesFilter() ?? [];

            foreach ($order->getItems() as $item) {
                $productType = $item->getProduct()?->getType();

                if (!$productType) {
                    continue;
                }

                if (!in_array($productType, $filter, true)) {
                    continue;
                }

                if ($item->getItemPromotion()) {
                    continue;
                }

                $item->setItemPromotion($promotion);
                $item->setDiscount($promotion->getPercentageDiscount());
            }
        } else {
            throw new BadRequestHttpException('Unknown promotion type.');
        }

        $this->calculator->recalculate($order);
        $this->em->flush();

        return $order;
    }
}
