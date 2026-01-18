<?php

declare(strict_types=1);

namespace App\Controller;

use App\Component\Order\Service\OrderViewBuilder;
use App\Component\Promotion\Service\PromotionService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class PromotionController
{
    public function __construct(
        private readonly PromotionService $promotionService,
        private readonly OrderViewBuilder $viewBuilder,
    ) {}

    #[Route('/order/{orderId}/promotion/{promotionId}', name: 'order_apply_promotion', methods: ['POST'])]
    public function applyPromotion(int $orderId, int $promotionId): Response
    {
        $order = $this->promotionService->applyPromotion($orderId, $promotionId);

        return new JsonResponse([
            'data' => $this->viewBuilder->build($order),
        ]);
    }
}
