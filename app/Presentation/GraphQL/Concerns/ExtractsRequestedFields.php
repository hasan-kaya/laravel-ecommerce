<?php

declare(strict_types=1);

namespace App\Presentation\GraphQL\Concerns;

use GraphQL\Type\Definition\ResolveInfo;

trait ExtractsRequestedFields
{
    /**
     * Extract requested fields from GraphQL ResolveInfo
     * 
     * @return array<string>
     */
    protected function extractRequestedFields(ResolveInfo $resolveInfo): array
    {
        $fieldSelection = $resolveInfo->getFieldSelection();
        
        return array_keys($fieldSelection);
    }
}
