<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Entity\ProductInfo;
use App\Form\Admin\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ProductController extends AbstractController
{
    private const SUPPORTED_LOCALES = ['en', 'uk', 'de', 'fr', 'es', 'pl'];

    private function resolveLocale(Request $request): string
    {
        $locale = $request->query->get('locale', 'en');
        return in_array($locale, self::SUPPORTED_LOCALES, true) ? $locale : 'en';
    }

    #[Route('/admin/products', name: 'admin_product_index')]
    public function index(ProductRepository $repo): Response
    {
        return $this->render('admin/product/index.html.twig', [
            'products' => $repo->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/admin/products/new', name: 'admin_product_create')]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $locale = $this->resolveLocale($request);
        $product = new Product();
        $productInfo = new ProductInfo();
        $productInfo->setLocale($locale);

        $form = $this->createForm(ProductType::class, $product);
        $form->get('productInfo')->setData($productInfo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ProductInfo $productInfo */
            $productInfo = $form->get('productInfo')->getData();
            $productInfo->setLocale($locale);
            $product->addProductInfo($productInfo);
            $product->setCreator($this->getUser());

            $em->persist($product);
            $em->persist($productInfo);
            $em->flush();

            $this->addFlash('success', 'Product created successfully.');

            return $this->redirectToRoute('admin_product_index');
        }

        return $this->render('admin/product/create.html.twig', [
            'form' => $form,
            'locale' => $locale,
        ]);
    }

    #[Route('/admin/products/{id}/edit', name: 'admin_product_edit')]
    public function edit(Product $product, Request $request, EntityManagerInterface $em): Response
    {
        $locale = $this->resolveLocale($request);

        $productInfo = $product->getProductInfoByLocale($locale);
        $isNew = $productInfo === null;

        if ($isNew) {
            $productInfo = new ProductInfo();
            $productInfo->setLocale($locale);
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->get('productInfo')->setData($productInfo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ProductInfo $productInfo */
            $productInfo = $form->get('productInfo')->getData();

            if ($isNew) {
                $productInfo->setLocale($locale);
                $product->addProductInfo($productInfo);
                $em->persist($productInfo);
            }

            $em->flush();

            $this->addFlash('success', 'Product updated successfully.');

            return $this->redirectToRoute('admin_product_edit', ['id' => $product->getId(), 'locale' => $locale]);
        }

        $availableLocales = $product->getProductInfos()
            ->map(fn(ProductInfo $info) => $info->getLocale())
            ->toArray();

        return $this->render('admin/product/edit.html.twig', [
            'form' => $form,
            'product' => $product,
            'locale' => $locale,
            'availableLocales' => $availableLocales,
        ]);
    }

    #[Route('/admin/products/{id}/delete', name: 'admin_product_delete', methods: ['POST'])]
    public function delete(Product $product, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_product_' . $product->getId(), $request->request->get('_token'))) {
            $em->remove($product);
            $em->flush();
            $this->addFlash('success', 'Product deleted.');
        } else {
            $this->addFlash('error', 'Invalid security token. Please try again.');
        }

        return $this->redirectToRoute('admin_product_index');
    }
}
