<?php

namespace App\Store\Factory;

use App\Entity\Store;
use App\Entity\StoreTemplateConfig;
use App\Entity\Template;
use App\Store\DTO\StoreDTO;
use App\Store\DTO\TemplateDTO;

final class StoreDTOFactory
{
    public static function fromEntity(
        Store $store,
        Template $template,
        ?StoreTemplateConfig $templateConfig,
    ): StoreDTO
    {
        return new StoreDTO(
            id: $store->getId(),
            title: $store->getTitle(),
            status: $store->getStatus(),
            template: self::buildTemplate($template, $templateConfig),
        );
    }

    private static function buildTemplate(
        Template $template,
        ?StoreTemplateConfig $templateConfig,
    ): TemplateDto
    {
        return new TemplateDto(
            code: $template->getCode(),
            title: $template->getTitle(),
            config: array_replace_recursive(
                $template->getDefaultConfig(),
                $templateConfig?->getConfig() ?? [],
            ),
        );
    }
}
