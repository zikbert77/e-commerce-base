<?php

namespace App\Entity\Interface;

use App\Entity\Store;

interface StoreScopedInterface
{
    public function getStore(): ?Store;

    public function setStore(?Store $store): static;
}
