<?php

declare(strict_types=1);

namespace App\Controller;

use App\Component\Order\Entity\Order;
use App\Component\Order\Service\OrderViewBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class OrderController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OrderViewBuilder $viewBuilder,
    )
    {
    }

    #[Route('/order/{orderId}', name: 'order_details', methods: ['GET'])]
    public function details(int $orderId): Response
    {
        $order = $this->em->find(Order::class, $orderId);

        if (!$order) {
            throw new NotFoundHttpException('Order not found.');
        }

        return new JsonResponse([
            'data' => $this->viewBuilder->build($order),
        ]);
    }
}