<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers;

use App\Infrastructure\Console\Commands\ElasticsearchIndexCommand;
use App\Infrastructure\Console\Commands\ExpireStockReservationsCommand;
use Illuminate\Support\ServiceProvider;

class ConsoleServiceProvider extends ServiceProvider
{
    /**
     * The commands to register
     */
    protected array $commands = [
        ExpireStockReservationsCommand::class,
        ElasticsearchIndexCommand::class,
    ];

    /**
     * Register console commands
     */
    public function register(): void
    {
        $this->commands($this->commands);
    }
}
