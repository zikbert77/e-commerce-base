<?php

namespace App\Service\Store;

use App\Entity\Store;
use App\Entity\StoreDomain;
use App\Enum\BaseStatus;
use App\Enum\CacheLifetime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class StoreResolver
{
    public function __construct(
        private EntityManagerInterface $em,
        private TagAwareCacheInterface $storeCache,
    )
    {

    }

    public function resolveByHost(string $host): ?Store
    {
        $host = $this->normalizeHost($host);
        $cacheKey = 'store_domain_' . md5($host);

        $storeId = $this->storeCache->get($cacheKey, function (ItemInterface $item) use ($host) {
            $item->expiresAfter(CacheLifetime::STORE_RESOLVER->value);
            $item->tag(['store_domains']); // tag for mass invalidation

            $domain = $this->em->getRepository(StoreDomain::class)
                ->createQueryBuilder('sd')
                ->select('IDENTITY(sd.store) AS storeId')
                ->innerJoin('sd.store', 's')
                ->andWhere('sd.domain = :domain')
                ->andWhere('s.status = :status')
                ->setParameter('domain', $host)
                ->setParameter('status', BaseStatus::ACTIVE)
                ->getQuery()
                ->getOneOrNullResult();

            // cache null to prevent multiple DB requests
            return $domain['storeId'] ?? null;
        });

        if ($storeId === null) {
            return null;
        }

        // @todo: In the future make a DTO to prevent multiple DB requests
        return $this->em->getRepository(Store::class)->find($storeId);
    }

    private function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host));
        $host = preg_replace('/:\d+$/', '', $host);
        return preg_replace('/^www\./', '', $host);
    }
}
