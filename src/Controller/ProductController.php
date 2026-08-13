<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/product')]
class ProductController extends BaseController
{
    #[Route('/{slug}', name: 'app_product_view', methods: ['GET'])]
    public function view(string $slug): Response
    {
        $store = $this->storeContext->get();

        /** @see templates/themes/{active}/product.html.twig */
        return $this->render('themes/' . $store->getTemplate()->getCode() . '/product.html.twig', [
            'store' => $store,
            'slug' => $slug,
        ]);
    }
}
