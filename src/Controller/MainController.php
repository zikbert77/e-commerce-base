<?php

namespace App\Controller;

use App\Store\StoreContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MainController extends AbstractController
{
    #[Route('/main', name: 'app_main')]
    public function index(StoreContext $storeContext): Response
    {
        $store = $storeContext->get();

        /** @see templates/main/index.html.twig */
        return $this->render('main/index.html.twig', [
            'store' => $store,
        ]);
    }
}
