<?php

declare(strict_types=1);

namespace App\Controller;

use App\Component\Product\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * For behat example purposes only (can be removed)
 */
final class ProductController
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    #[Route('/products', name: 'productList', methods: ['GET'])]
    public function productList(): Response
    {
        $products = $this->em->getRepository(Product::class)->findAll();

        $data = [];
        foreach ($products as $product) {
            $data[] = [
                'id' => $product->getId(),
                'code' => $product->getCode(),
                'name' => $product->getName(),
                'type' => $product->getType(),
                'price' => $product->getPrice(),
                'taxRate' => $product->getTaxRate(),
            ];
        }

        return new JsonResponse(['data' => $data]);
    }
}
