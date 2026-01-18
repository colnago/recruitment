<?php

declare(strict_types=1);

namespace App\Tests\PhpUnit\App\Api;

use App\Component\Product\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CartControllerTest extends WebTestCase
{
    private EntityManagerInterface $em;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::getContainer()->get(KernelBrowser::class);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testCannotAddMoreThanFiveDistinctProducts(): void
    {
        $seed = $this->createProduct(10, 500);
        $resp = $this->postJson('/cart/items', [
            'productId' => $seed->getId(),
            'quantity' => 1,
        ]);
        self::assertSame(200, $resp['status']);
        $orderId = (int) $resp['json']['data']['id'];

        for ($i = 1; $i <= 4; $i++) {
            $p = $this->createProduct($i, 100 + $i);
            $r = $this->postJson('/cart/items', [
                'orderId' => $orderId,
                'productId' => $p->getId(),
                'quantity' => 1,
            ]);
            self::assertSame(200, $r['status']);
        }

        $sixth = $this->createProduct(20, 999);
        $r = $this->postJson('/cart/items', [
            'orderId' => $orderId,
            'productId' => $sixth->getId(),
            'quantity' => 1,
        ]);

        self::assertSame(422, $r['status']);
        self::assertArrayHasKey('error', $r['json']);
        self::assertStringContainsString('more than 5', strtolower((string) $r['json']['error']['message']));
    }

    public function testCannotExceedMaxQuantityPerProduct(): void
    {
        $p = $this->createProduct(30, 1000);

        $resp = $this->postJson('/cart/items', [
            'productId' => $p->getId(),
            'quantity' => 10,
        ]);
        self::assertSame(200, $resp['status']);
        $orderId = (int) $resp['json']['data']['id'];

        $r = $this->postJson('/cart/items', [
            'orderId' => $orderId,
            'productId' => $p->getId(),
            'quantity' => 1,
        ]);

        self::assertSame(422, $r['status']);
        self::assertArrayHasKey('error', $r['json']);
        self::assertStringContainsString('between', strtolower((string) $r['json']['error']['message']));
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

        $this->em->persist($product);
        $this->em->flush();

        return $product;
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
