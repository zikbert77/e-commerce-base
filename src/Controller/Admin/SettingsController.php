<?php

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Store\StoreContext;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * "Settings" is the current store's own profile — the same page as
 * editing a store from the Stores & domains list, just addressed
 * without needing to know its id.
 */
#[Route('/admin/settings', host: '%admin_host%')]
class SettingsController extends BaseController
{
    public function __construct(StoreContext $storeContext)
    {
        parent::__construct($storeContext);
    }

    #[Route('', name: 'admin_settings', methods: ['GET'])]
    public function index(): Response
    {
        if (!$this->storeContext->isInitialized()) {
            $this->addFlash('error', 'Select a specific store from the switcher to view its settings.');

            return $this->redirectToRoute('admin_stores');
        }

        return $this->redirectToRoute('admin_store_edit', ['id' => $this->storeContext->getId()]);
    }
}
