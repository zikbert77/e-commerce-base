<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return array{items: Product[], total: int}
     */
    public function search(string $locale, ?int $status, string $query, int $page, int $perPage): array
    {
        $qb = $this->createQueryBuilder('p')
            ->innerJoin('p.productInfos', 'info', 'WITH', 'info.locale = :locale')
            ->addSelect('info')
            ->leftJoin('p.categories', 'category')
            ->addSelect('category')
            ->setParameter('locale', $locale)
            ->orderBy('p.id', 'DESC');

        if ($status !== null) {
            $qb->andWhere('p.status = :status')->setParameter('status', $status);
        }

        $query = trim($query);
        if ($query !== '') {
            $qb->andWhere('LOWER(info.title) LIKE :query OR LOWER(info.slug) LIKE :query')
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
}
