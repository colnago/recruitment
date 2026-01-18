<?php

declare(strict_types=1);

namespace App\Component\Order\Service;

use App\Component\Order\Entity\Order;
use App\Component\OrderItem\Entity\OrderItem;
use App\Component\Product\Entity\Product;
use App\Component\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class CartService
{
    public const MIN_QTY = 1;
    public const MAX_QTY = 10;
    public const MAX_DISTINCT_PRODUCTS = 5;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OrderCalculator $calculator,
    ) {}

    public function addProduct(?int $orderId, ?int $userId, int $productId, int $quantity): Order
    {
        if ($quantity < self::MIN_QTY) {
            throw new BadRequestHttpException('Quantity is below min value.');
        }

        $product = $this->em->find(Product::class, $productId);
        if (!$product) {
            throw new NotFoundHttpException('Product not found.');
        }

        $order = $orderId ? $this->em->find(Order::class, $orderId) : null;
        if ($orderId && !$order) {
            throw new NotFoundHttpException('Order not found.');
        }

        if (!$order) {
            $order = new Order();

            if ($userId !== null) {
                $user = $this->em->find(User::class, $userId);
                if (!$user) {
                    throw new NotFoundHttpException('User not found.');
                }
                $order->setUser($user);
            }

            $this->em->persist($order);
        }

        $existingItem = null;
        foreach ($order->getItems() as $item) {
            if ($item->getProduct() === $product) {
                $existingItem = $item;
                break;
            }
        }

        if (!$existingItem) {
            $distinct = 0;
            foreach ($order->getItems() as $item) {
                if ($item->getProduct() !== null) {
                    $distinct++;
                }
            }

            if ($distinct >= self::MAX_DISTINCT_PRODUCTS) {
                throw new UnprocessableEntityHttpException('Cart cannot contain more than 5 different products.');
            }

            $existingItem = new OrderItem();
            $existingItem->setProduct($product);
            $existingItem->setUnitPrice($product->getPrice());
            $existingItem->setQuantity(0);
            $order->addItem($existingItem);
            $this->em->persist($existingItem);
        }

        $newQty = $existingItem->getQuantity() + $quantity;

        if ($newQty < self::MIN_QTY || $newQty > self::MAX_QTY) {
            throw new UnprocessableEntityHttpException(sprintf('Quantity for a product must be between %d and %d.', self::MIN_QTY, self::MAX_QTY));
        }

        $existingItem->setQuantity($newQty);
        $existingItem->setUnitPrice($product->getPrice());
        $existingItem->setTaxValue($product->getTaxRate());
        $this->calculator->recalculate($order);
        $this->em->flush();

        return $order;
    }
}
