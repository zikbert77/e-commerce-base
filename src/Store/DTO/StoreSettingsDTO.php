<?php

namespace App\Store\DTO;

final class StoreSettingsDTO
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

    public function setTemplate(string $template): StoreSettingsDTO
    {
        $this->template = $template;

        return $this;
    }
}
