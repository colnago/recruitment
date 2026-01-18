<?php

declare(strict_types=1);

namespace App\Tests\PhpUnit\App\Unit;

use App\Component\Order\Entity\Order;
use App\Component\Order\Service\CartService;
use App\Component\Order\Service\OrderCalculator;
use App\Component\OrderItem\Entity\OrderItem;
use App\Component\Product\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class CartServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private OrderCalculator $calculator;
    private CartService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->calculator = $this->createMock(OrderCalculator::class);
        $this->service = new CartService($this->em, $this->calculator);
    }

    public function testThrowsBadRequestWhenQuantityIsZero(): void
    {
        $this->expectException(BadRequestHttpException::class);

        $this->service->addProduct(null, null, 1, 0);
    }

    public function testThrowsNotFoundWhenProductNotExists(): void
    {
        $this->em->expects(self::once())
            ->method('find')
            ->with(Product::class, 999)
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);

        $this->service->addProduct(null, null, 999, 1);
    }

    public function testCreatesOrderAndItem(): void
    {
        $product = $this->createProduct(10, 500);

        $this->em->expects(self::once())
            ->method('find')
            ->with(Product::class, 10)
            ->willReturn($product);

        $this->em->expects(self::exactly(2))->method('persist');
        $this->em->expects(self::once())->method('flush');

        $this->calculator->expects(self::once())
            ->method('recalculate')
            ->with(self::isInstanceOf(Order::class));

        $order = $this->service->addProduct(null, null, 10, 2);

        self::assertCount(1, $order->getItems());

        $item = $order->getItems()->first();
        self::assertSame(2, $item->getQuantity());
        self::assertSame(500, $item->getUnitPrice());
        self::assertSame($product, $item->getProduct());
    }

    public function testIncrementsExistingItemQuantity(): void
    {
        $product = $this->createProduct(10, 1000);

        $order = new Order();
        $item = new OrderItem();
        $item->setProduct($product);
        $item->setQuantity(3);
        $item->setUnitPrice(500);
        $order->addItem($item);

        $this->em->expects(self::exactly(2))
            ->method('find')
            ->willReturnMap([
                [Product::class, 10, $product],
                [Order::class,   1,  $order],
            ]);

        $this->em->expects(self::never())->method('persist');
        $this->em->expects(self::once())->method('flush');

        $this->calculator->expects(self::once())->method('recalculate');

        $this->service->addProduct(1, null, 10, 2);

        self::assertSame(5, $item->getQuantity());
        self::assertSame(1000, $item->getUnitPrice());
    }

    public function testThrowsWhenMaxDistinctProductsExceeded(): void
    {
        $order = new Order();

        for ($i = 1; $i <= CartService::MAX_DISTINCT_PRODUCTS; $i++) {
            $p = $this->createProduct($i, 100);
            $it = new OrderItem();
            $it->setProduct($p);
            $it->setQuantity(1);
            $it->setUnitPrice(100);
            $order->addItem($it);
        }

        $newProduct = $this->createProduct(999, 200);

        $this->em->expects(self::exactly(2))
            ->method('find')
            ->willReturnMap([
                [Product::class, 999, $newProduct],
                [Order::class,   1,   $order],
            ]);

        $this->expectException(UnprocessableEntityHttpException::class);

        $this->service->addProduct(1, null, 999, 1);
    }

    public function testThrowsWhenQuantityExceedsMax(): void
    {
        $product = $this->createProduct(1, 100);

        $order = new Order();
        $item = new OrderItem();
        $item->setProduct($product);
        $item->setQuantity(CartService::MAX_QTY);
        $item->setUnitPrice(100);
        $order->addItem($item);

        $this->em->expects(self::exactly(2))
            ->method('find')
            ->willReturnMap([
                [Product::class, 1, $product],
                [Order::class,   1, $order],
            ]);

        $this->expectException(UnprocessableEntityHttpException::class);

        $this->service->addProduct(1, null, 1, 1);
    }

    private function createProduct(int $id, int $price): Product
    {
        $product = new Product();

        $ref = new \ReflectionProperty(Product::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($product, $id);

        $product->setCode('P-' . $id);
        $product->setName('Product '.$id);
        $product->setType(Product::TYPE_BOOK);
        $product->setPrice($price);
        $product->setTaxRate(23);

        return $product;
    }
}