<?php

namespace App\Controller\Api;

use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_api_cart_get', methods: ['GET'])]
    public function get(CartService $cartService): JsonResponse
    {
        return $this->json($cartService->getCartInfo());
    }

    #[Route('/cart', name: 'app_api_cart_add', methods: ['POST'])]
    public function add(): JsonResponse
    {
        return $this->json([]);
    }
}
