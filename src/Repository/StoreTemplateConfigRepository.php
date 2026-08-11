<?php

namespace App\Repository;

use App\Entity\Store;
use App\Entity\StoreTemplateConfig;
use App\Entity\Template;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StoreTemplateConfig>
 */
class StoreTemplateConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreTemplateConfig::class);
    }

    public function findOneByStoreAndTemplate(Store $store, Template $template): ?StoreTemplateConfig
    {
        return $this->findOneBy([
            'store' => $store,
            'template' => $template,
        ]);
    }
}
