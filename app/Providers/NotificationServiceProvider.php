<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\NotificationService;
use App\Helpers\NotificationHelper;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the notification service
        $this->app->singleton(NotificationService::class, function () {
            return new NotificationService();
        });

        // Register the notification helper
        $this->app->singleton(NotificationHelper::class, function () {
            return new NotificationHelper();
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
