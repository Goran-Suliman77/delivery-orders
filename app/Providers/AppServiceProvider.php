<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
