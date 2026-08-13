<?php

namespace App\Twig\Extension;

use App\Store\StoreContext;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class StoreGlobalsExtension  extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly StoreContext $storeContext,
    )
    {
    }

    public function getGlobals(): array
    {
        return [
            'store' => $this->storeContext->get(),
        ];
    }
}
