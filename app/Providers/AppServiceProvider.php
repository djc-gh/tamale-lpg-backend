<?php

namespace App\Providers;

use App\Services\AuthService;
use App\Services\StationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register service classes for dependency injection
        $this->app->singleton(StationService::class, function ($app) {
            return new StationService();
        });

        $this->app->singleton(AuthService::class, function ($app) {
            return new AuthService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
