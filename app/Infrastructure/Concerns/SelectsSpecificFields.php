<?php

declare(strict_types=1);

namespace App\Infrastructure\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait SelectsSpecificFields
{
    /**
     * Apply field selection to query
     * Always includes 'id' field for model identification
     * 
     * @param Builder $query
     * @param array<string> $fields
     * @return Builder
     */
    protected function applyFieldSelection(Builder $query, array $fields): Builder
    {
        if (empty($fields)) {
            return $query;
        }

        // Always include 'id' for proper model identification
        if (!in_array('id', $fields, true)) {
            $fields[] = 'id';
        }

        return $query->select($fields);
    }
}
