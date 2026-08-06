<?php

namespace App\Store\DTO;

use App\Enum\BaseStatus;

final class StoreDTO
{
    public function __construct(
        private int $id,
        private string $title,
        private BaseStatus $status,
        private StoreSettingsDTO $settings,
    )
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getStatus(): BaseStatus
    {
        return $this->status;
    }

    public function setStatus(BaseStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getSettings(): StoreSettingsDTO
    {
        return $this->settings;
    }

    public function setSettings(StoreSettingsDTO $settings): static
    {
        $this->settings = $settings;

        return $this;
    }
}
