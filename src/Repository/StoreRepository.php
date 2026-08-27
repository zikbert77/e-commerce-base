<?php

namespace App\Repository;

use App\Entity\Store;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Store>
 */
class StoreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Store::class);
    }

    /**
     * @return Store[]
     */
    public function findAllWithDomains(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.storeDomains', 'domain')
            ->addSelect('domain')
            ->leftJoin('s.template', 'template')
            ->addSelect('template')
            ->orderBy('s.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
