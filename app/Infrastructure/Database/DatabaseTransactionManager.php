<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use App\Domain\Shared\TransactionManagerInterface;
use Illuminate\Support\Facades\DB;

final readonly class DatabaseTransactionManager implements TransactionManagerInterface
{
    public function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
