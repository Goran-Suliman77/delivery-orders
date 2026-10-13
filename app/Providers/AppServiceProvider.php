<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use App\Contracts\OrderQueryServiceInterface;
use App\Services\OrderQueryService;
use App\Contracts\OrderServiceInterface;
use App\Contracts\OrderStatusServiceInterface;
use App\Contracts\DriverAssignmentServiceInterface;
use App\Services\OrderService;
use App\Services\OrderStatusService;
use App\Services\DriverAssignmentService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->bind(
            OrderQueryServiceInterface::class,
            OrderQueryService::class
        );
        $this->app->bind(
            \App\Contracts\StoreQueryServiceInterface::class,
            \App\Services\StoreQueryService::class
        );

        $this->app->bind(
            \App\Contracts\ProductQueryServiceInterface::class,
            \App\Services\ProductQueryService::class
        );

        $this->app->bind(
            \App\Contracts\StoreManagementServiceInterface::class,
            \App\Services\StoreManagementService::class
        );

        $this->app->bind(
            \App\Contracts\ProductManagementServiceInterface::class,
            \App\Services\ProductManagementService::class
        );

        $this->app->bind(
            OrderServiceInterface::class,
            OrderService::class
        );

        $this->app->bind(
            OrderStatusServiceInterface::class,
            OrderStatusService::class
        );

        $this->app->bind(
            DriverAssignmentServiceInterface::class,
            DriverAssignmentService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        if (app()->environment('local')) {
            DB::listen(function (QueryExecuted $query) {
                if ($query->time >= 100) {
                    logger()->warning('Slow SQL query', [
                        'sql' => $query->sql,
                        'time_ms' => $query->time,
                    ]);
                }
            });
        }
        Gate::define('viewReports', function (User $user): bool {
            return $user->hasPermissionTo('reports.view');
        });
    }
}
