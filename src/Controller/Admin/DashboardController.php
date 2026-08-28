<?php

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Repository\OrderItemRepository;
use App\Repository\OrderRepository;
use App\Store\StoreContext;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin', host: '%admin_host%')]
class DashboardController extends BaseController
{
    private const PERIOD_DAYS = 30;

    public function __construct(
        StoreContext $storeContext,
        private readonly OrderRepository $orderRepository,
        private readonly OrderItemRepository $orderItemRepository,
    )
    {
        parent::__construct($storeContext);
    }

    #[Route('', name: 'admin_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        $now = new \DateTimeImmutable();
        $periodStart = $now->modify(sprintf('-%d days', self::PERIOD_DAYS));
        $previousStart = $periodStart->modify(sprintf('-%d days', self::PERIOD_DAYS));

        $current = $this->orderRepository->getPeriodStats($periodStart, $now);
        $previous = $this->orderRepository->getPeriodStats($previousStart, $periodStart);

        $aov = $current['orders'] > 0 ? intdiv($current['revenue'], $current['orders']) : 0;
        $previousAov = $previous['orders'] > 0 ? intdiv($previous['revenue'], $previous['orders']) : 0;

        $kpis = [
            ['label' => 'Revenue', 'value' => $this->money($current['revenue']), 'delta' => $this->delta($current['revenue'], $previous['revenue'])],
            ['label' => 'Orders', 'value' => (string) $current['orders'], 'delta' => $this->delta($current['orders'], $previous['orders'])],
            ['label' => 'Average order value', 'value' => $this->money($aov), 'delta' => $this->delta($aov, $previousAov)],
            ['label' => 'Cancelled', 'value' => (string) $current['cancelled'], 'delta' => $this->delta($current['cancelled'], $previous['cancelled'], lowerIsBetter: true)],
        ];

        $topSellers = $this->orderItemRepository->topSellers($periodStart, $now, 5);
        $topRevenue = array_sum(array_column($topSellers, 'revenue')) ?: 1;

        $topProducts = array_map(function (array $row) use ($topRevenue) {
            /** @var \App\Entity\Product $product */
            $product = $row['product'];
            $info = $product->getProductInfoByLocale('en');

            return [
                'title' => $info?->getTitle() ?? ('#'.$product->getId()),
                'category' => $product->getCategories()->first()
                    ?->getCategoryInfos()->first()?->getTitle() ?? '—',
                'units' => $row['units'],
                'revenue' => $this->money($row['revenue']),
                'share' => (int) round($row['revenue'] / $topRevenue * 100).'%',
            ];
        }, $topSellers);

        return $this->render('admin/dashboard.html.twig', [
            'kpis' => $kpis,
            'topProducts' => $topProducts,
            'periodDays' => self::PERIOD_DAYS,
        ]);
    }

    private function money(int $cents): string
    {
        return '$'.number_format($cents / 100, 2);
    }

    private function delta(int $current, int $previous, bool $lowerIsBetter = false): array
    {
        if ($previous === 0) {
            $percent = $current === 0 ? 0.0 : 100.0;
        } else {
            $percent = ($current - $previous) / abs($previous) * 100;
        }

        $improved = $lowerIsBetter ? $percent <= 0 : $percent >= 0;

        return [
            'label' => sprintf('%+.1f%%', $percent),
            'class' => $improved ? 'positive' : 'negative',
        ];
    }
}
