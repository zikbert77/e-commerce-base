<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/catalog')]
class CatalogController extends BaseController
{
    #[Route('', name: 'app_catalog', methods: ['GET'])]
    public function catalog(): Response
    {
        $store = $this->storeContext->get();

        /** @see templates/themes/{active}/catalog.html.twig */
        return $this->render('themes/' . $store->getTemplate()->getCode() . '/catalog.html.twig', [
            'store' => $store,
        ]);
    }

    #[Route('/search', name: 'app_search', methods: ['GET'])]
    public function search(): Response
    {
        $store = $this->storeContext->get();

        /** @see templates/themes/{active}/search.html.twig */
        return $this->render('themes/' . $store->getTemplate()->getCode() . '/search.html.twig', [
            'store' => $store,
        ]);
    }

    #[Route('/category/{slug}', name: 'app_category', methods: ['GET'])]
    public function category(string $slug): Response
    {
        $store = $this->storeContext->get();

        /** @see templates/themes/{active}/category.html.twig */
        return $this->render('themes/' . $store->getTemplate()->getCode() . '/category.html.twig', [
            'store' => $store,
            'slug' => $slug,
        ]);
    }
}
