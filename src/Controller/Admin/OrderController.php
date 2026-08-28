<?php

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Entity\Enum\OrderStatus;
use App\Repository\OrderRepository;
use App\Store\StoreContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/orders', host: '%admin_host%')]
class OrderController extends BaseController
{
    private const PER_PAGE = 20;

    public function __construct(
        StoreContext $storeContext,
        private readonly OrderRepository $orderRepository,
    )
    {
        parent::__construct($storeContext);
    }

    #[Route('', name: 'admin_orders', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $tab = $request->query->get('tab', 'all');
        $query = (string) $request->query->get('q', '');
        $page = max(1, $request->query->getInt('page', 1));

        $status = $tab === 'all' ? null : OrderStatus::from((int) $tab);

        $result = $this->orderRepository->search($status, $query, $page, self::PER_PAGE);

        return $this->render('admin/order/index.html.twig', [
            'orders' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'query' => $query,
            'tab' => $tab,
            'newCount' => $this->orderRepository->countNew(),
            'statuses' => OrderStatus::cases(),
        ]);
    }
}
