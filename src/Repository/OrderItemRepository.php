<?php

namespace App\Repository;

use App\Entity\OrderItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderItem>
 */
class OrderItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderItem::class);
    }

    /**
     * Best-selling products (by units) within a date range.
     *
     * @return list<array{product: \App\Entity\Product, units: int, revenue: int}>
     */
    public function topSellers(\DateTimeImmutable $from, \DateTimeImmutable $to, int $limit): array
    {
        $rows = $this->createQueryBuilder('oi')
            ->select('IDENTITY(oi.product) AS productId', 'SUM(oi.qty) AS units', 'SUM(oi.totalAmount) AS revenue')
            ->innerJoin('oi.relatedOrder', 'o')
            ->andWhere('o.createdAt BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->groupBy('oi.product')
            ->orderBy('units', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();

        if ($rows === []) {
            return [];
        }

        $productRepository = $this->getEntityManager()->getRepository(\App\Entity\Product::class);
        $products = $productRepository->findBy(['id' => array_column($rows, 'productId')]);
        $byId = [];
        foreach ($products as $product) {
            $byId[$product->getId()] = $product;
        }

        return array_values(array_filter(array_map(
            static fn (array $row) => isset($byId[$row['productId']]) ? [
                'product' => $byId[$row['productId']],
                'units' => (int) $row['units'],
                'revenue' => (int) $row['revenue'],
            ] : null,
            $rows,
        )));
    }
}
