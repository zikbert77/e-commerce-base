<?php

namespace App\Store\DTO;

readonly final class TemplateDTO
{
    public function __construct(
        private string $code,
        private string $title,
        private array $config,
    )
    {
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}
