<?php

namespace App\Store\DTO;

readonly final class StoreSettingsDTO
{
    public function __construct(
        private string $template
    )
    {
    }

    public function getTemplate(): string
    {
        return $this->template;
    }
}
