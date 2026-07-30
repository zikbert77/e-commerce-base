<?php

namespace App\Doctrine;

use App\Entity\Interface\StoreScopedInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

final class StoreFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        if (!$targetEntity->reflClass->implementsInterface(StoreScopedInterface::class)) {
            return '';
        }

        $storeId = $this->getParameter('storeId');

        return sprintf('%s.store_id = %s', $targetTableAlias, $storeId);
    }
}
