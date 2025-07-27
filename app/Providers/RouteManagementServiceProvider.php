<?php

namespace App\Providers;

use App\Services\RouteManagementService;
use App\Services\NavigationService;
use App\Helpers\NavigationHelper;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class RouteManagementServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(RouteManagementService::class);
        $this->app->singleton(NavigationService::class);
        $this->app->singleton(NavigationHelper::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Share navigation helper with all views
        View::composer('*', function ($view) {
            $navigationHelper = app(NavigationHelper::class);
            $view->with('navigationHelper', $navigationHelper);
        });

        // Add global middleware for route access checking
        $this->app['router']->pushMiddlewareToGroup('web', \App\Http\Middleware\CheckRouteAccess::class);
    }
}
