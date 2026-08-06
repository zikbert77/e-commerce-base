<?php

namespace App\Store;

use App\Store\DTO\StoreDTO;
use Symfony\Contracts\Service\ResetInterface;

final class StoreContext implements ResetInterface
{
    private ?StoreDto $store = null;

    public function set(StoreDto $store): void
    {
        $this->store = $store;
    }

    public function get(): StoreDto
    {
        if ($this->store === null) {
            throw new \RuntimeException('Store context not initialized.');
        }
        return $this->store;
    }

    public function getId(): int
    {
        return $this->get()->getId();
    }

    public function isInitialized(): bool
    {
        return $this->store !== null;
    }

    public function reset(): void
    {
        $this->store = null;
    }
}
