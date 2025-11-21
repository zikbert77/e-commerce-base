<?php

namespace App\Service;

use App\Entity\Enum\CartStatus;
use App\Entity\User;
use App\Repository\CartRepository;
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
        private CartRepository $cartRepository,
    )
    {
        $this->locale = $requestStack->getCurrentRequest()?->getLocale();
        $this->user = $security->getUser();
        $this->userSessionId = $permanentUserIdentifierService->getUserSessionId();
    }

    public function getCartInfo(): array
    {
        $cart = null;
        if (!empty($this->user)) {
            $cart = $this->cartRepository->findOneBy(['user' => $this->user, 'status' => CartStatus::Active->value]);
        } else if (!empty($this->userSessionId)) {
            $cart = $this->cartRepository->findOneBy(['sessionId' => $this->userSessionId, 'status' => CartStatus::Active->value]);
        }

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
}
