<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\CategoryInfo;
use App\Form\Admin\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class CategoryController extends AbstractController
{
    private const SUPPORTED_LOCALES = ['en', 'uk', 'de', 'fr', 'es', 'pl'];

    private function resolveLocale(Request $request): string
    {
        $locale = $request->query->get('locale', 'en');
        return in_array($locale, self::SUPPORTED_LOCALES, true) ? $locale : 'en';
    }

    #[Route('/admin/categories', name: 'admin_category_index')]
    public function index(CategoryRepository $repo): Response
    {
        return $this->render('admin/category/index.html.twig', [
            'categories' => $repo->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/admin/categories/new', name: 'admin_category_create')]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $locale = $this->resolveLocale($request);
        $category = new Category();
        $categoryInfo = new CategoryInfo();
        $categoryInfo->setLocale($locale);

        $form = $this->createForm(CategoryType::class, $category);
        $form->get('categoryInfo')->setData($categoryInfo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var CategoryInfo $categoryInfo */
            $categoryInfo = $form->get('categoryInfo')->getData();
            $categoryInfo->setLocale($locale);
            $category->addCategoryInfo($categoryInfo);
            $category->setCreator($this->getUser());

            $em->persist($category);
            $em->persist($categoryInfo);
            $em->flush();

            $this->addFlash('success', 'Category created successfully.');

            return $this->redirectToRoute('admin_category_index');
        }

        return $this->render('admin/category/create.html.twig', [
            'form' => $form,
            'locale' => $locale,
        ]);
    }

    #[Route('/admin/categories/{id}/edit', name: 'admin_category_edit')]
    public function edit(Category $category, Request $request, EntityManagerInterface $em): Response
    {
        $locale = $this->resolveLocale($request);

        $categoryInfo = $this->getCategoryInfoByLocale($category, $locale);
        $isNew = $categoryInfo === null;

        if ($isNew) {
            $categoryInfo = new CategoryInfo();
            $categoryInfo->setLocale($locale);
        }

        $form = $this->createForm(CategoryType::class, $category, [
            'exclude_category' => $category->getId(),
        ]);
        $form->get('categoryInfo')->setData($categoryInfo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var CategoryInfo $categoryInfo */
            $categoryInfo = $form->get('categoryInfo')->getData();

            if ($isNew) {
                $categoryInfo->setLocale($locale);
                $category->addCategoryInfo($categoryInfo);
                $em->persist($categoryInfo);
            }

            $em->flush();

            $this->addFlash('success', 'Category updated successfully.');

            return $this->redirectToRoute('admin_category_edit', ['id' => $category->getId(), 'locale' => $locale]);
        }

        $availableLocales = $category->getCategoryInfos()
            ->map(fn(CategoryInfo $info) => $info->getLocale())
            ->toArray();

        return $this->render('admin/category/edit.html.twig', [
            'form' => $form,
            'category' => $category,
            'locale' => $locale,
            'availableLocales' => $availableLocales,
        ]);
    }

    #[Route('/admin/categories/{id}/delete', name: 'admin_category_delete', methods: ['POST'])]
    public function delete(Category $category, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_category_' . $category->getId(), $request->request->get('_token'))) {
            $em->remove($category);
            $em->flush();
            $this->addFlash('success', 'Category deleted.');
        } else {
            $this->addFlash('error', 'Invalid security token. Please try again.');
        }

        return $this->redirectToRoute('admin_category_index');
    }

    private function getCategoryInfoByLocale(Category $category, string $locale): ?CategoryInfo
    {
        $result = $category->getCategoryInfos()->filter(
            fn(CategoryInfo $info) => $info->getLocale() === $locale
        )->first();

        return $result === false ? null : $result;
    }
}
