<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Faculty\FacultyDashboardService;
use App\Services\Faculty\FacultyStatsService;
use App\Services\Faculty\FacultyScheduleService;
use App\Services\Faculty\FacultyClassService;
use App\Services\Faculty\FacultyActivityService;
use App\Services\GeneralSettingsService;

final class FacultyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register Faculty services as singletons for better performance
        $this->app->singleton(FacultyStatsService::class, function ($app) {
            return new FacultyStatsService(
                $app->make(GeneralSettingsService::class)
            );
        });

        $this->app->singleton(FacultyScheduleService::class, function ($app) {
            return new FacultyScheduleService(
                $app->make(GeneralSettingsService::class)
            );
        });

        $this->app->singleton(FacultyClassService::class, function ($app) {
            return new FacultyClassService(
                $app->make(GeneralSettingsService::class)
            );
        });

        $this->app->singleton(FacultyActivityService::class);

        // Register the main dashboard service
        $this->app->singleton(FacultyDashboardService::class, function ($app) {
            return new FacultyDashboardService(
                $app->make(FacultyStatsService::class),
                $app->make(FacultyScheduleService::class),
                $app->make(FacultyClassService::class),
                $app->make(FacultyActivityService::class),
                $app->make(GeneralSettingsService::class)
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
