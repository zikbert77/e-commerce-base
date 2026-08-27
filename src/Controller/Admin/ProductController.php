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
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/products')]
class ProductController extends BaseController
{
    private const PER_PAGE = 15;

    public function __construct(
        StoreContext $storeContext,
        private readonly ProductRepository $productRepository,
        private readonly StoreRepository $storeRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct($storeContext);
    }

    #[Route('', name: 'admin_products', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $locale = $request->query->get('locale', ContentLocale::EN->value);
        $status = $request->query->get('status', 'all');
        $query = (string) $request->query->get('q', '');
        $page = max(1, $request->query->getInt('page', 1));

        $result = $this->productRepository->search(
            $locale,
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
            'locale' => $locale,
            'locales' => ContentLocale::cases(),
        ]);
    }

    #[Route('/new', name: 'admin_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $product = new Product();
        $product->setStatus(BaseStatus::ACTIVE->value);
        $product->setStore($this->storeRepository->find($this->storeContext->getId()));

        $locale = $request->query->get('locale', ContentLocale::EN->value);
        $info = new ProductInfo();
        $info->setLocale($locale);

        return $this->handleForm($request, $product, $info, isNew: true);
    }

    #[Route('/{id}/edit', name: 'admin_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product): Response
    {
        $locale = $request->query->get('locale', ContentLocale::EN->value);
        $info = $product->getProductInfoByLocale($locale);
        if ($info === null) {
            $info = new ProductInfo();
            $info->setLocale($locale);
        }

        return $this->handleForm($request, $product, $info, isNew: false);
    }

    private function handleForm(Request $request, Product $product, ProductInfo $info, bool $isNew): Response
    {
        $locale = $info->getLocale() ?? ContentLocale::EN->value;

        $form = $this->createFormBuilder()
            ->add('product', ProductType::class, ['data' => $product, 'mapped' => false])
            ->add('info', ProductInfoType::class, ['data' => $info, 'mapped' => false])
            ->add('save', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($info->getProduct() === null) {
                $product->addProductInfo($info);
            }

            $this->entityManager->persist($product);
            $this->entityManager->persist($info);
            $this->entityManager->flush();

            $this->addFlash('success', $isNew ? 'Product created.' : 'Product updated.');

            return $this->redirectToRoute('admin_products', ['locale' => $locale]);
        }

        return $this->render('admin/product/form.html.twig', [
            'form' => $form,
            'product' => $product,
            'info' => $info,
            'isNew' => $isNew,
            'locale' => $locale,
            'locales' => ContentLocale::cases(),
        ]);
    }
}
