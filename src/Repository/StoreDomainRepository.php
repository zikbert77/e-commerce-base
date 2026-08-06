<?php

namespace App\Repository;

use App\Enum\BaseStatus;
use App\Entity\StoreDomain;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<StoreDomain>
 */
class StoreDomainRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreDomain::class);
    }

    public function findByHost(string $host): ?StoreDomain
    {
        return $this->createQueryBuilder('sd')
            ->innerJoin('sd.store', 's')
            ->andWhere('sd.domain = :domain')
            ->andWhere('s.status = :status')
            ->setParameter('domain', $host)
            ->setParameter('status', BaseStatus::ACTIVE)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
