<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Production only: surface slow queries without drowning logs
        if ($this->app->isProduction()) {
            DB::listen(function ($query) {
                if ($query->time > 100) {
                    logger()->warning('slow query', [
                        'sql' => $query->sql,
                        'time_ms' => $query->time,
                    ]);
                }
            });
        }
    }
}
