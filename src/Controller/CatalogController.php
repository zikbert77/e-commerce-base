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
        /** @see templates/themes/{active}/catalog.html.twig */
        return $this->render('@theme/catalog.html.twig');
    }

    #[Route('/search', name: 'app_search', methods: ['GET'])]
    public function search(): Response
    {
        /** @see templates/themes/{active}/search.html.twig */
        return $this->render('@theme/search.html.twig');
    }

    #[Route('/category/{slug}', name: 'app_category', methods: ['GET'])]
    public function category(string $slug): Response
    {
        /** @see templates/themes/{active}/category.html.twig */
        return $this->render('@theme/category.html.twig', [
            'slug' => $slug,
        ]);
    }
}
