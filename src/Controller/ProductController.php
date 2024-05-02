<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * For behat example purposes only (can be removed)
 */
final class ProductController
{
    #[Route('/products', 'productList')]
    public function productList(): Response
    {
        return new JsonResponse([
            'data' => [
                ['Product one'],
                ['Product two'],
                ['Product three'],
            ],
        ]);
    }
}
