<?php

namespace App\Service\Store;

use App\Entity\Store;
use Symfony\Contracts\Service\ResetInterface;

final class StoreContext implements ResetInterface
{
    private ?Store $store = null;

    public function set(Store $store): void
    {
        $this->store = $store;
    }

    public function get(): Store
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
