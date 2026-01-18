<?php

declare(strict_types=1);

namespace App\Tests\PhpUnit\App\Api;

use App\Component\Product\Entity\Product;
use App\Component\Promotion\Entity\Promotion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OrderControllerTest extends WebTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();

        $this->client = static::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        if (!$this->em->getRepository(Product::class)->findOneBy(['code' => 'p1'])) {
            $p1 = new Product();
            $p1->setCode('p1');
            $p1->setName('P1');
            $p1->setType(Product::TYPE_BOOK);
            $p1->setPrice(100);
            $p1->setTaxRate(23);

            $p2 = new Product();
            $p2->setCode('p2');
            $p2->setName('P2');
            $p2->setType(Product::TYPE_AUDIO);
            $p2->setPrice(200);
            $p2->setTaxRate(8);

            $orderPromo = new Promotion();
            $orderPromo->setType(Promotion::TYPE_ORDER);
            $orderPromo->setPercentageDiscount(10);
            $orderPromo->setProductTypesFilter(null);

            $itemPromo = new Promotion();
            $itemPromo->setType(Promotion::TYPE_ITEM);
            $itemPromo->setPercentageDiscount(20);
            $itemPromo->setProductTypesFilter([Product::TYPE_BOOK]);

            $this->em->persist($p1);
            $this->em->persist($p2);
            $this->em->persist($orderPromo);
            $this->em->persist($itemPromo);
            $this->em->flush();
        }
    }

    public function testAddItemIncreasesQuantityAndAppliesPromotions(): void
    {
        $p1 = $this->em->getRepository(Product::class)->findOneBy(['code' => 'p1']);
        self::assertNotNull($p1);

        $resp = $this->postJson('/cart/items', [
            'productId' => $p1->getId(),
            'quantity' => 2,
        ]);
        self::assertSame(200, $resp['status']);

        $data = $resp['json']['data'] ?? null;
        self::assertIsArray($data);

        $orderId = (int) $data['id'];
        self::assertSame(2, $data['items'][0]['quantity']);

        $resp = $this->postJson('/cart/items', [
            'orderId' => $orderId,
            'productId' => $p1->getId(),
            'quantity' => 1,
        ]);
        self::assertSame(200, $resp['status']);

        $data = $resp['json']['data'];
        self::assertSame(3, $data['items'][0]['quantity']);

        $itemPromo = $this->em->getRepository(Promotion::class)->findOneBy([
            'type' => Promotion::TYPE_ITEM,
            'percentageDiscount' => 20,
        ]);
        self::assertNotNull($itemPromo);

        $resp = $this->postJson(sprintf('/order/%d/promotion/%d', $orderId, $itemPromo->getId()), []);
        self::assertSame(200, $resp['status']);

        $data = $resp['json']['data'];
        self::assertSame(100, $data['items'][0]['unitPrice']);
        self::assertSame(20, $data['items'][0]['discountValue']);

        $orderPromo = $this->em->getRepository(Promotion::class)->findOneBy([
            'type' => Promotion::TYPE_ORDER,
            'percentageDiscount' => 10,
        ]);
        self::assertNotNull($orderPromo);

        $resp = $this->postJson(sprintf('/order/%d/promotion/%d', $orderId, $orderPromo->getId()), []);
        self::assertSame(200, $resp['status']);

        $data = $resp['json']['data'];
        self::assertArrayHasKey('taxTotal', $data);
        self::assertArrayHasKey('adjustmentsTotal', $data);
        self::assertArrayHasKey('total', $data);

        $this->client->request('GET', sprintf('/order/%d', $orderId));
        self::assertResponseIsSuccessful();
    }

    private function postJson(string $uri, array $payload): array
    {
        $this->client->request('POST', $uri, server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], content: json_encode($payload, JSON_THROW_ON_ERROR));

        $content = $this->client->getResponse()->getContent() ?: '';

        return [
            'status' => $this->client->getResponse()->getStatusCode(),
            'json' => $content !== '' ? json_decode($content, true) : null,
        ];
    }
}
