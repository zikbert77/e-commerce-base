<?php

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Entity\Product;
use App\Entity\ProductInfo;
use App\Enum\BaseStatus;
use App\Enum\ContentLocale;
use App\Form\Admin\ProductInfoType;
use App\Form\Admin\ProductType;
use App\Repository\ProductRepository;
use App\Repository\StoreRepository;
use App\Store\StoreContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/products', host: '%admin_host%')]
class ProductController extends BaseController
{
    private const PER_PAGE = 15;

    public function __construct(
        StoreContext $storeContext,
        private readonly ProductRepository $productRepository,
        private readonly StoreRepository $storeRepository,
        private readonly EntityManagerInterface $entityManager,
    )
    {
        parent::__construct($storeContext);
    }

    #[Route('', name: 'admin_products', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $status = $request->query->get('status', 'all');
        $query = (string) $request->query->get('q', '');
        $page = max(1, $request->query->getInt('page', 1));

        $result = $this->productRepository->search(
            $status === 'all' ? null : (int) $status,
            $query,
            $page,
            self::PER_PAGE,
        );

        return $this->render('admin/product/index.html.twig', [
            'products' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'query' => $query,
            'status' => $status,
            'defaultLocale' => ContentLocale::default(),
        ]);
    }

    #[Route('/new', name: 'admin_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if (!$this->storeContext->isInitialized()) {
            $this->addFlash('error', 'Select a specific store from the switcher before creating a product.');

            return $this->redirectToRoute('admin_products');
        }

        $product = new Product();
        $product->setStatus(BaseStatus::ACTIVE->value);
        $product->setStore($this->storeRepository->find($this->storeContext->getId()));

        $mainForm = $this->createForm(ProductType::class, $product);
        $mainForm->handleRequest($request);

        if ($mainForm->isSubmitted() && $mainForm->isValid()) {
            $this->entityManager->persist($product);
            $this->entityManager->flush();

            $this->addFlash('success', 'Product created. Add a locale below to publish it.');

            return $this->redirectToRoute('admin_product_edit', ['id' => $product->getId()]);
        }

        return $this->render('admin/product/form.html.twig', [
            'mainForm' => $mainForm,
            'localeForm' => null,
            'info' => null,
            'product' => $product,
            'isNew' => true,
            'locale' => null,
            'locales' => ContentLocale::cases(),
            'localeStatus' => [],
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product): Response
    {
        $locale = $request->query->get('locale');

        $mainForm = $this->createForm(ProductType::class, $product);
        $mainForm->handleRequest($request);

        if ($mainForm->isSubmitted() && $mainForm->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Product updated.');

            return $this->redirectToRoute('admin_product_edit', ['id' => $product->getId()]);
        }

        $localeForm = null;
        $info = null;
        if ($locale !== null) {
            $info = $product->getProductInfoByLocale($locale) ?? (new ProductInfo())->setLocale($locale);

            $localeForm = $this->createForm(ProductInfoType::class, $info);
            $localeForm->handleRequest($request);

            if ($localeForm->isSubmitted() && $localeForm->isValid()) {
                if ($info->getProduct() === null) {
                    $product->addProductInfo($info);
                }

                $this->entityManager->persist($info);
                $this->entityManager->flush();

                $this->addFlash('success', 'Translation saved.');

                return $this->redirectToRoute('admin_product_edit', ['id' => $product->getId(), 'locale' => $locale]);
            }
        }

        return $this->render('admin/product/form.html.twig', [
            'mainForm' => $mainForm,
            'localeForm' => $localeForm,
            'info' => $info,
            'product' => $product,
            'isNew' => false,
            'locale' => $locale,
            'locales' => ContentLocale::cases(),
            'localeStatus' => $this->localeStatus($product),
        ]);
    }

    /**
     * @return array<string, string> locale code => 'missing'|'disabled'|'empty'|'translated'
     */
    private function localeStatus(Product $product): array
    {
        $status = [];
        foreach (ContentLocale::cases() as $l) {
            $info = $product->getProductInfoByLocale($l->value);
            $status[$l->value] = match (true) {
                $info === null => 'missing',
                !$info->isEnabled() => 'disabled',
                (bool) $info->getTitle() => 'translated',
                default => 'empty',
            };
        }

        return $status;
    }
}
