<?php

declare(strict_types=1);

namespace App\Controller;

use App\Component\Order\Service\CartService;
use App\Component\Order\Service\OrderViewBuilder;
use App\Http\Dto\Cart\CartAddItemDto;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CartController
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly OrderViewBuilder $viewBuilder,
    ) {}

    #[Route('/cart/items', name: 'cart_add_item', methods: ['POST'])]
    public function addItem(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
    ): JsonResponse {
        try {
            $dto = $serializer->deserialize(
                $request->getContent() ?: '{}',
                CartAddItemDto::class,
                'json'
            );
        } catch (\Throwable) {
            return new JsonResponse(['error' => 'Invalid JSON'], 422);
        }

        $violations = $validator->validate($dto);

        if (count($violations) > 0) {
            return new JsonResponse([
                'errors' => array_map(
                    static fn($v) => [
                        'field' => $v->getPropertyPath(),
                        'message' => $v->getMessage(),
                    ],
                    iterator_to_array($violations)
                ),
            ], 422);
        }

        $order = $this->cartService->addProduct(
            $dto->orderId,
            $dto->userId,
            $dto->productId,
            $dto->quantity
        );

        return new JsonResponse([
            'data' => $this->viewBuilder->build($order),
        ]);
    }
}
