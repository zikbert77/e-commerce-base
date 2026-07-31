<?php

namespace App\Entity\Trait;

use App\Entity\Store;
use Doctrine\ORM\Mapping as ORM;

trait StoreScopedTrait
{
    #[ORM\JoinColumn(nullable: false)]
    private ?Store $store = null;

    public function getStore(): ?Store
    {
        return $this->store;
    }

    public function setStore(?Store $store): static
    {
        $this->store = $store;

        return $this;
    }
}
