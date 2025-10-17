<?php

namespace App\Infrastructure\Providers;

use App\Domain\Address\Repository\AddressRepositoryInterface;
use App\Domain\Auth\TokenServiceInterface;
use App\Domain\Order\Contract\StockReservationJobDispatcherInterface;
use App\Domain\Order\Repository\OrderRepositoryInterface;
use App\Domain\Payment\Enums\PaymentMethod;
use App\Domain\Payment\Repository\PaymentRepositoryInterface;
use App\Domain\Payment\Contract\PaymentServiceFactoryInterface;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use App\Domain\Product\Repository\StockReservationRepositoryInterface;
use App\Domain\Shared\Search\SearchEngineInterface;
use App\Domain\Shared\TransactionManagerInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Address\Repository\EloquentAddressRepository;
use App\Infrastructure\Auth\PassportTokenService;
use App\Infrastructure\Database\DatabaseTransactionManager;
use App\Infrastructure\Order\Jobs\StockReservationJobDispatcher;
use App\Infrastructure\Order\Repository\EloquentOrderRepository;
use App\Infrastructure\Payment\IyzicoPaymentService;
use App\Infrastructure\Payment\PaymentServiceFactory;
use App\Infrastructure\Payment\Repository\EloquentPaymentRepository;
use App\Infrastructure\Product\Repository\EloquentProductRepository;
use App\Infrastructure\Product\Repository\EloquentStockReservationRepository;
use App\Infrastructure\Search\ElasticsearchClient;
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
        $this->app->singleton(ProductRepositoryInterface::class, EloquentProductRepository::class);
        $this->app->singleton(StockReservationRepositoryInterface::class, EloquentStockReservationRepository::class);
        $this->app->singleton(OrderRepositoryInterface::class, EloquentOrderRepository::class);
        $this->app->singleton(PaymentRepositoryInterface::class, EloquentPaymentRepository::class);
        $this->app->singleton(TransactionManagerInterface::class, DatabaseTransactionManager::class);
        $this->app->singleton(StockReservationJobDispatcherInterface::class, StockReservationJobDispatcher::class);

        // Search Engine (Dependency Inversion Principle)
        $this->app->singleton(SearchEngineInterface::class, ElasticsearchClient::class);

        // Payment Service Factory (Strategy Pattern)
        $this->app->singleton(PaymentServiceFactoryInterface::class, function ($app) {
            $factory = new PaymentServiceFactory();

            // Register available payment services
            $factory->register(PaymentMethod::IYZICO, new IyzicoPaymentService());

            // Future payment methods can be registered here:
            // $factory->register(PaymentMethod::PAYTR, new FakePayTRPaymentService());

            return $factory;
        });
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
