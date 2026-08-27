<?php

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Entity\Category;
use App\Entity\CategoryInfo;
use App\Enum\BaseStatus;
use App\Enum\ContentLocale;
use App\Form\Admin\CategoryInfoType;
use App\Form\Admin\CategoryType;
use App\Repository\CategoryRepository;
use App\Repository\StoreRepository;
use App\Store\StoreContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/categories')]
class CategoryController extends BaseController
{
    public function __construct(
        StoreContext $storeContext,
        private readonly CategoryRepository $categoryRepository,
        private readonly StoreRepository $storeRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct($storeContext);
    }

    #[Route('', name: 'admin_categories', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $locale = $request->query->get('locale', ContentLocale::EN->value);
        $status = $request->query->get('status', 'all');
        $query = (string) $request->query->get('q', '');

        $rows = $this->categoryRepository->search(
            $locale,
            $status === 'all' ? null : (int) $status,
            $query,
        );

        return $this->render('admin/category/index.html.twig', [
            'rows' => $rows,
            'query' => $query,
            'status' => $status,
            'locale' => $locale,
            'locales' => ContentLocale::cases(),
        ]);
    }

    #[Route('/new', name: 'admin_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $category = new Category();
        $category->setStatus(BaseStatus::ACTIVE->value);
        $category->setStore($this->storeRepository->find($this->storeContext->getId()));

        $locale = $request->query->get('locale', ContentLocale::EN->value);
        $info = new CategoryInfo();
        $info->setLocale($locale);

        return $this->handleForm($request, $category, $info, isNew: true);
    }

    #[Route('/{id}/edit', name: 'admin_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category): Response
    {
        $locale = $request->query->get('locale', ContentLocale::EN->value);
        $info = $category->getCategoryInfos()->filter(
            fn (CategoryInfo $i) => $i->getLocale() === $locale
        )->first() ?: null;

        if ($info === null) {
            $info = new CategoryInfo();
            $info->setLocale($locale);
        }

        return $this->handleForm($request, $category, $info, isNew: false);
    }

    private function handleForm(Request $request, Category $category, CategoryInfo $info, bool $isNew): Response
    {
        $locale = $info->getLocale() ?? ContentLocale::EN->value;

        $form = $this->createFormBuilder()
            ->add('category', CategoryType::class, ['data' => $category, 'mapped' => false, 'editing_category' => $isNew ? null : $category])
            ->add('info', CategoryInfoType::class, ['data' => $info, 'mapped' => false])
            ->add('save', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($info->getCategory() === null) {
                $category->addCategoryInfo($info);
            }

            $this->entityManager->persist($category);
            $this->entityManager->persist($info);
            $this->entityManager->flush();

            $this->addFlash('success', $isNew ? 'Category created.' : 'Category updated.');

            return $this->redirectToRoute('admin_categories', ['locale' => $locale]);
        }

        return $this->render('admin/category/form.html.twig', [
            'form' => $form,
            'category' => $category,
            'info' => $info,
            'isNew' => $isNew,
            'locale' => $locale,
            'locales' => ContentLocale::cases(),
        ]);
    }
}
