<?php

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Repository\StoreRepository;
use App\Repository\TemplateRepository;
use App\Store\StoreContext;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/themes')]
class ThemeController extends BaseController
{
    public function __construct(
        StoreContext $storeContext,
        private readonly TemplateRepository $templateRepository,
        private readonly StoreRepository $storeRepository,
    ) {
        parent::__construct($storeContext);
    }

    #[Route('', name: 'admin_themes', methods: ['GET'])]
    public function index(): Response
    {
        $templates = $this->templateRepository->findBy([], ['id' => 'ASC']);
        $stores = $this->storeRepository->findAll();

        $storeCountByTemplate = [];
        foreach ($stores as $store) {
            $templateId = $store->getTemplate()?->getId();
            if ($templateId !== null) {
                $storeCountByTemplate[$templateId] = ($storeCountByTemplate[$templateId] ?? 0) + 1;
            }
        }

        return $this->render('admin/theme/index.html.twig', [
            'templates' => $templates,
            'storeCountByTemplate' => $storeCountByTemplate,
        ]);
    }
}
