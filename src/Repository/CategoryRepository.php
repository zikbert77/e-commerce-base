<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * Returns every category for the given locale, ordered depth-first
     * (root categories followed by their children), each entry carrying
     * its tree depth. Status/title filters are then applied to that
     * ordered list.
     *
     * @return list<array{category: Category, depth: int}>
     */
    public function search(string $locale, ?int $status, string $query): array
    {
        /** @var Category[] $all */
        $all = $this->createQueryBuilder('c')
            ->leftJoin('c.categoryInfos', 'info', 'WITH', 'info.locale = :locale')
            ->addSelect('info')
            ->leftJoin('c.parent', 'parent')
            ->addSelect('parent')
            ->leftJoin('c.store', 'store')
            ->addSelect('store')
            ->setParameter('locale', $locale)
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();

        $byParent = [];
        foreach ($all as $category) {
            $parentId = $category->getParent()?->getId() ?? 0;
            $byParent[$parentId][] = $category;
        }

        $ordered = [];
        $this->flatten($byParent, 0, 0, $ordered);

        $query = mb_strtolower(trim($query));

        return array_values(array_filter($ordered, function (array $row) use ($status, $query) {
            if ($status !== null && $row['category']->getStatus() !== $status) {
                return false;
            }

            if ($query !== '') {
                $title = mb_strtolower($row['category']->getCategoryInfos()->first()?->getTitle() ?? '');

                return str_contains($title, $query);
            }

            return true;
        }));
    }

    /**
     * @param array<int, Category[]> $byParent
     * @param list<array{category: Category, depth: int}> $ordered
     */
    private function flatten(array $byParent, int $parentId, int $depth, array &$ordered): void
    {
        foreach ($byParent[$parentId] ?? [] as $category) {
            $ordered[] = ['category' => $category, 'depth' => $depth];
            $this->flatten($byParent, $category->getId(), $depth + 1, $ordered);
        }
    }
}
