<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(125);
        Paginator::useBootstrapFive();

        // Share hospital config to all views
        view()->share('hospitalConfig', config('hospital'));
    }
}
