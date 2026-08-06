<?php

namespace App\Store;

use App\Entity\Store;
use App\Enum\CacheLifetime;
use App\Store\DTO\StoreDTO;
use App\Store\Factory\StoreDTOFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use App\Store\Exception\StoreNotFoundException;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class StoreConfigProvider
{
    public function __construct(
        private TagAwareCacheInterface $cache,
        private EntityManagerInterface $em,
    ) {}

    public function getConfig(int $storeId): StoreDto
    {
        return $this->cache->get(
            sprintf('store_config_%d', $storeId),
            function (ItemInterface $item) use ($storeId) {
                $item->tag(sprintf('store_%d', $storeId));
                $item->expiresAfter(CacheLifetime::ONE_HOUR->value);

                $store = $this->em->getRepository(Store::class)->find($storeId);
                if (!$store) {
                    throw new StoreNotFoundException(
                        sprintf('Store with id %d not found', $storeId)
                    );
                }

                return StoreDtoFactory::fromEntity($store);
            }
        );
    }
}
