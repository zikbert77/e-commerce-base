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

        if (!$this->hasParameter('storeIds')) {
            return '';
        }

        $list = $this->getParameterList('storeIds');

        // No accessible stores (e.g. an admin linked to zero stores in
        // aggregate mode) must yield zero rows, not the invalid `IN ()`.
        if ($list === '') {
            return '1 = 0';
        }

        return sprintf('%s.store_id IN (%s)', $targetTableAlias, $list);
    }
}
