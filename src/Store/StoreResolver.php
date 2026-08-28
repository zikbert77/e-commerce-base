<?php

namespace App\Store;

use App\Enum\CacheLifetime;
use App\Repository\StoreDomainRepository;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class StoreResolver
{
    public function __construct(
        private TagAwareCacheInterface $cache,
        private StoreDomainRepository  $domainRepository,
    )
    {
    }

    public function resolveStoreIdByHost(string $host): ?int
    {
        $host = $this->normalizeHost($host);
        $cacheKey = 'store_domain_' . md5($host);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($host) {
            $item->expiresAfter(CacheLifetime::STORE_RESOLVER->value);
            $item->tag(['store_domains']); // tag for mass invalidation

            $domain = $this->domainRepository->findByHost($host);

            // cache null to prevent multiple DB requests
            return $domain?->getStore()?->getId() ?? null;
        });
    }

    public function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host));
        $host = preg_replace('/:\d+$/', '', $host);
        return preg_replace('/^www\./', '', $host);
    }
}
