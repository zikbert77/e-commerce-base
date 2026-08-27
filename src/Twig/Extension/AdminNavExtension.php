<?php

namespace App\Twig\Extension;

use App\Repository\CategoryRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class AdminNavExtension extends AbstractExtension
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly OrderRepository $orderRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('admin_nav_counts', $this->counts(...)),
        ];
    }

    /**
     * @return array{products: int, categories: int, orders: int, customers: int, newOrders: int}
     */
    public function counts(): array
    {
        return [
            'products' => $this->productRepository->count([]),
            'categories' => $this->categoryRepository->count([]),
            'orders' => $this->orderRepository->count([]),
            'customers' => $this->userRepository->count([]),
            'newOrders' => $this->orderRepository->countNew(),
        ];
    }
}
