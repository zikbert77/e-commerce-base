<?php

namespace App\Store\DTO;

use App\Enum\BaseStatus;

final readonly class StoreDTO
{
    public function __construct(
        private int              $id,
        private string           $title,
        private BaseStatus       $status,
        private StoreSettingsDTO $settings,
    )
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getStatus(): BaseStatus
    {
        return $this->status;
    }

    public function getSettings(): StoreSettingsDTO
    {
        return $this->settings;
    }
}
