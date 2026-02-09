<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Enum\CartStatus;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class CartService
{
    private string $locale;
    private ?User $user;
    private ?string $userSessionId;

    public function __construct(
        RequestStack $requestStack,
        Security $security,
        PermanentUserIdentifierService $permanentUserIdentifierService,
        private EntityManagerInterface $entityManager,
        private CartRepository $cartRepository,
    )
    {
        $this->locale = $requestStack->getCurrentRequest()?->getLocale();
        $this->user = $security->getUser();
        $this->userSessionId = $permanentUserIdentifierService->getUserSessionId();
    }

    public function getCart(): ?Cart
    {
        if (!empty($this->user)) {
            return $this->cartRepository->findOneBy(['user' => $this->user, 'status' => CartStatus::Active->value]);
        } else if (!empty($this->userSessionId)) {
            return $this->cartRepository->findOneBy(['sessionId' => $this->userSessionId, 'status' => CartStatus::Active->value]);
        }

        return null;
    }

    public function createCart(): Cart
    {
        $cart = new Cart();
        $cart->setUser($this->user);
        $cart->setStatus(CartStatus::Active);
        $cart->setSessionId($this->userSessionId);

        $this->entityManager->persist($cart);

        return $cart;
    }

    public function getCartInfo(): array
    {
        $cart = $this->getCart();
        if (empty($cart)) {
            return [];
        }

        $items = [];
        foreach ($cart->getCartItems() as $cartItem) {
            $productInfo = $cartItem->getProduct()->getProductInfoByLocale($this->locale);
            if (empty($productInfo)) {
                continue;
            }

            $items[] = [
                'id' => $cartItem->getId(),
                'qty' => $cartItem->getQty(),
                'product' => [
                    'id' => $cartItem->getProduct()->getId(),
                    'title' => $productInfo->getTitle(),
                ]
            ];
        }

        return [
            'id' => $cart->getId(),
            'items' => $items,
            'totalItems' => $cart->getCartItems()->count(),
        ];
    }

    public function addToCart(Product $product, int $qty): void
    {
        $cart = $this->getCart();
        if (empty($cart)) {
            $cart = $this->createCart();
        }
        $cartItem = $cart->getCartItems()->findFirst(function ($key, CartItem $cartItem) use ($product) {
            return $cartItem->getProduct()->getId() === $product->getId();
        });
        if (!empty($cartItem)) {
            $cartItem->setQty($cartItem->getQty() + $qty);
        } else {
            $cartItem = new CartItem();
            $cartItem->setProduct($product);
            $cartItem->setQty($qty);
            $cartItem->setCart($cart);
        }

        $this->entityManager->persist($cartItem);
        $this->entityManager->flush();
    }
}
