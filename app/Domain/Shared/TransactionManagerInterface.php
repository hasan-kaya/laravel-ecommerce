<?php

declare(strict_types=1);

namespace App\Domain\Shared;

interface TransactionManagerInterface
{
    /**
     * Execute callback within a database transaction
     * 
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed;
}
