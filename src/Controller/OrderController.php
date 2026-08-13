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
        /** @see templates/themes/{active}/checkout.html.twig */
        return $this->render('@theme/checkout.html.twig');
    }
}
