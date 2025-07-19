<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Faculty\FacultyDashboardService;
use App\Services\Faculty\FacultyStatsService;
use App\Services\Faculty\FacultyScheduleService;
use App\Services\Faculty\FacultyClassService;
use App\Services\Faculty\FacultyActivityService;

final class FacultyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register Faculty services as singletons for better performance
        $this->app->singleton(FacultyStatsService::class);
        $this->app->singleton(FacultyScheduleService::class);
        $this->app->singleton(FacultyClassService::class);
        $this->app->singleton(FacultyActivityService::class);
        
        // Register the main dashboard service
        $this->app->singleton(FacultyDashboardService::class, function ($app) {
            return new FacultyDashboardService(
                $app->make(FacultyStatsService::class),
                $app->make(FacultyScheduleService::class),
                $app->make(FacultyClassService::class),
                $app->make(FacultyActivityService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
