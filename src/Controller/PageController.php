<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PageController extends BaseController
{
    #[Route('/about', name: 'app_page_about', methods: ['GET'])]
    public function about(): Response
    {
        /** @see templates/themes/{active}/about.html.twig */
        return $this->render('@theme/about.html.twig');
    }

    #[Route('/contacts', name: 'app_page_contacts', methods: ['GET'])]
    public function contacts(): Response
    {
        /** @see templates/themes/{active}/contacts.html.twig */
        return $this->render('@theme/contacts.html.twig');
    }
}
