<?php

declare(strict_types=1);

namespace App\Domain\Shared\Search;

interface SearchEngineInterface
{
    /**
     * Search documents with query
     */
    public function search(array $query): array;

    /**
     * Index a document
     */
    public function indexDocument(string $id, array $document): bool;

    /**
     * Bulk index documents
     */
    public function bulkIndex(array $documents): bool;

    /**
     * Delete a document
     */
    public function deleteDocument(string $id): bool;

    /**
     * Create index with mapping
     */
    public function createIndex(): bool;
}
