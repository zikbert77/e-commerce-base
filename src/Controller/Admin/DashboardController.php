<?php

namespace App\Controller\Admin;

use App\Repository\CategoryRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(
        ProductRepository $productRepo,
        CategoryRepository $categoryRepo,
        UserRepository $userRepo,
        OrderRepository $orderRepo,
    ): Response {
        return $this->render('admin/dashboard/index.html.twig', [
            'productCount' => $productRepo->count([]),
            'categoryCount' => $categoryRepo->count([]),
            'userCount' => $userRepo->count([]),
            'orderCount' => $orderRepo->count([]),
            'recentOrders' => $orderRepo->findBy([], ['createdAt' => 'DESC'], 5),
        ]);
    }
}
