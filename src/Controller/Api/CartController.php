<?php

namespace App\Controller\Api;

use App\Repository\ProductRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
    public function add(Request $request, ProductRepository $productRepository, CartService $cartService): JsonResponse
    {
        $productId = $request->request->get('productId');
        $qty = $request->request->get('qty');
        if (empty($productId) || empty($qty)) {
            throw new BadRequestHttpException();
        }

        $product = $productRepository->find($productId);
        if (empty($product)) {
            throw new NotFoundHttpException();
        }

        $cartService->addToCart($product, $qty);

        return $this->json($cartService->getCartInfo());
    }
}
