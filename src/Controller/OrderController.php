<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/order')]
class OrderController extends BaseController
{
    #[Route('/checkout', name: 'app_order_checkout', methods: ['GET'])]
    public function checkout(): Response
    {
        $store = $this->storeContext->get();

        /** @see templates/themes/{active}/checkout.html.twig */
        return $this->render('themes/' . $store->getTemplate()->getCode() . '/checkout.html.twig', [
            'store' => $store,
        ]);
    }
}
