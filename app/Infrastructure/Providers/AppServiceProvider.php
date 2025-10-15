<?php

namespace App\Infrastructure\Providers;

use App\Domain\Address\Repository\AddressRepositoryInterface;
use App\Domain\Auth\TokenServiceInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Address\Repository\EloquentAddressRepository;
use App\Infrastructure\Auth\PassportTokenService;
use App\Infrastructure\User\Repository\EloquentUserRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind Domain interfaces to Infrastructure implementations
        $this->app->singleton(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->singleton(TokenServiceInterface::class, PassportTokenService::class);
        $this->app->singleton(AddressRepositoryInterface::class, EloquentAddressRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load migrations from Infrastructure layer
        $this->loadMigrationsFrom(app_path('Infrastructure/Database/Migrations'));
    }
}
