<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MainController extends BaseController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(): Response
    {
        $store = $this->storeContext->get();

        /** @see templates/themes/{active}/home.html.twig */
        return $this->render('themes/' . $store->getTemplate()->getCode() . '/home.html.twig', [
            'store' => $store,
        ]);
    }
}
