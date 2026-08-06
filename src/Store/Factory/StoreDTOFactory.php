<?php

namespace App\Store\Factory;

use App\Entity\Store;
use App\Store\DTO\StoreDTO;
use App\Store\DTO\StoreSettingsDto;

final class StoreDTOFactory
{
    public static function fromEntity(Store $store): StoreDTO
    {
        return new StoreDTO(
            id: $store->getId(),
            title: $store->getTitle(),
            status: $store->getStatus(),
            settings: self::buildSettings($store),
        );
    }

    private static function buildSettings(Store $store): StoreSettingsDto
    {
        return new StoreSettingsDto(
            template: 'test'
        );
    }
}
