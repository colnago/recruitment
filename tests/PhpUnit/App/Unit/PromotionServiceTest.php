<?php

namespace App\Tests\PhpUnit\App\Unit;

use App\Component\Order\Entity\Order;
use App\Component\Order\Service\OrderCalculator;
use App\Component\OrderItem\Entity\OrderItem;
use App\Component\Product\Entity\Product;
use App\Component\Promotion\Entity\Promotion;
use App\Component\Promotion\Service\PromotionService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class PromotionServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private OrderCalculator $calculator;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->calculator = $this->createMock(OrderCalculator::class);
    }

    public function testThrowsNotFoundWhenOrderMissing(): void
    {
        $this->em->expects(self::once())
            ->method('find')
            ->with(Order::class, 1)
            ->willReturn(null);

        $service = new PromotionService($this->em, $this->calculator);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Order not found.');

        $service->applyPromotion(1, 10);
    }

    public function testOrderPromotionCannotBeAppliedTwice(): void
    {
        $order = new Order();
        $existingPromo = new Promotion();
        $order->setOrderPromotion($existingPromo);

        $promo = new Promotion();
        $promo->setType(Promotion::TYPE_ORDER);
        $promo->setPercentageDiscount(10);

        $this->em->expects(self::exactly(2))
            ->method('find')
            ->willReturnMap([
                [Order::class, 1, $order],
                [Promotion::class, 10, $promo],
            ]);

        $service = new PromotionService($this->em, $this->calculator);

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage('Order already has order-level promotion.');

        $service->applyPromotion(1, 10);
    }

    public function testItemPromotionAppliedOnlyToMatchingTypes(): void
    {
        $order = new Order();

        $book = new Product();
        $book->setType(Product::TYPE_BOOK);

        $audio = new Product();
        $audio->setType(Product::TYPE_AUDIO);

        $bookItem = new OrderItem();
        $bookItem->setProduct($book);
        $order->addItem($bookItem);

        $audioItem = new OrderItem();
        $audioItem->setProduct($audio);
        $order->addItem($audioItem);

        $promo = new Promotion();
        $promo->setType(Promotion::TYPE_ITEM);
        $promo->setPercentageDiscount(15);
        $promo->setProductTypesFilter([Product::TYPE_BOOK]);

        $this->em->expects(self::exactly(2))
            ->method('find')
            ->willReturnMap([
                [Order::class, 1, $order],
                [Promotion::class, 10, $promo],
            ]);

        $this->calculator->expects(self::once())->method('recalculate')->with($order);
        $this->em->expects(self::once())->method('flush');

        $service = new PromotionService($this->em, $this->calculator);
        $service->applyPromotion(1, 10);

        self::assertSame(15, $bookItem->getDiscount());
        self::assertSame($promo, $bookItem->getItemPromotion());

        self::assertNull($audioItem->getDiscount());
        self::assertNull($audioItem->getItemPromotion());
    }

    public function testThrowsNotFoundWhenPromotionMissing(): void
    {
        $order = new Order();

        $this->em->expects(self::exactly(2))
            ->method('find')
            ->willReturnMap([
                [Order::class, 1, $order],
                [Promotion::class, 10, null],
            ]);

        $service = new PromotionService($this->em, $this->calculator);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Promotion not found.');

        $service->applyPromotion(1, 10);
    }

    public function testAppliesOrderPromotion(): void
    {
        $order = new Order();

        $promo = new Promotion();
        $promo->setType(Promotion::TYPE_ORDER);
        $promo->setPercentageDiscount(10);

        $this->em->expects(self::exactly(2))
            ->method('find')
            ->willReturnMap([
                [Order::class, 1, $order],
                [Promotion::class, 10, $promo],
            ]);

        $this->calculator->expects(self::once())->method('recalculate')->with($order);
        $this->em->expects(self::once())->method('flush');

        $service = new PromotionService($this->em, $this->calculator);
        $service->applyPromotion(1, 10);

        self::assertSame($promo, $order->getOrderPromotion());
        self::assertSame(10, $order->getOrderDiscount());
    }
}