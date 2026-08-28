<?php

namespace App\Repository;

use App\Entity\Enum\OrderStatus;
use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * @return array{items: Order[], total: int}
     */
    public function search(?OrderStatus $status, string $query, int $page, int $perPage): array
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.store', 'store')
            ->addSelect('store')
            ->orderBy('o.createdAt', 'DESC');

        if ($status !== null) {
            $qb->andWhere('o.status = :status')->setParameter('status', $status);
        }

        $query = trim($query);
        if ($query !== '') {
            $qb->andWhere('LOWER(o.uid) LIKE :query OR LOWER(o.customerFirstName) LIKE :query OR LOWER(o.customerLastName) LIKE :query OR LOWER(o.customerEmail) LIKE :query')
                ->setParameter('query', '%'.mb_strtolower($query).'%');
        }

        $paginator = new Paginator($qb->getQuery(), true);
        $total = count($paginator);

        $items = $qb
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        return ['items' => $items, 'total' => $total];
    }

    /**
     * @return array{revenue: int, orders: int, cancelled: int}
     */
    public function getPeriodStats(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $row = $this->createQueryBuilder('o')
            ->select(
                'COALESCE(SUM(CASE WHEN o.status != :cancelled THEN o.totalAmount ELSE 0 END), 0) AS revenue',
                'SUM(CASE WHEN o.status != :cancelled THEN 1 ELSE 0 END) AS orders',
                'SUM(CASE WHEN o.status = :cancelled THEN 1 ELSE 0 END) AS cancelled',
            )
            ->andWhere('o.createdAt BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('cancelled', OrderStatus::CANCELLED)
            ->getQuery()
            ->getSingleResult();

        return [
            'revenue' => (int) $row['revenue'],
            'orders' => (int) $row['orders'],
            'cancelled' => (int) $row['cancelled'],
        ];
    }

    /**
     * @param list<int> $userIds
     * @return array<int, int> userId => order count
     */
    public function countsByCustomers(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('o')
            ->select('IDENTITY(o.relatedUser) AS userId', 'COUNT(o.id) AS total')
            ->andWhere('o.relatedUser IN (:userIds)')
            ->setParameter('userIds', $userIds)
            ->groupBy('o.relatedUser')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['userId']] = (int) $row['total'];
        }

        return $counts;
    }

    public function countNew(): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->andWhere('o.status = :status')
            ->setParameter('status', OrderStatus::NEW)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
