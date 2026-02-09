<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Enum\CartStatus;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\CartRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
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
        $this->entityManager->flush();

        return $cart;
    }

    /**
     * Gets existing cart or creates a new one.
     * Handles race conditions where multiple concurrent requests might try to create a cart.
     */
    public function getOrCreateCart(): Cart
    {
        $cart = $this->getCart();
        if ($cart !== null) {
            return $cart;
        }

        try {
            return $this->createCart();
        } catch (UniqueConstraintViolationException) {
            // Another concurrent request created the cart, fetch it
            $cart = $this->getCart();
            if ($cart !== null) {
                return $cart;
            }
            // If still null, re-throw the original exception
            throw new \RuntimeException('Unable to create or retrieve cart after constraint violation');
        }
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

    /**
     * @throws \Throwable
     */
    public function addToCart(Product $product, int $qty): void
    {
        $this->entityManager->beginTransaction();
        try {
            $cart = $this->getOrCreateCart();

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
            $this->entityManager->commit();
        } catch (\Throwable $exception) {
            $this->entityManager->rollback();
            throw $exception;
        }
    }
}
