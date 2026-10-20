<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\TelegramNotificationService::class);
        $this->app->singleton(\App\Services\HosxpSyncService::class);
        $this->app->singleton(\App\Services\MealOrderService::class);
        $this->app->singleton(\App\Services\DailySummaryService::class);
    }

    public function boot(): void
    {
        //
    }
}
