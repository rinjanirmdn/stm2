<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            if (\Illuminate\Support\Facades\Schema::hasTable('holidays')) {
                $holidays = \Illuminate\Support\Facades\Cache::remember('public_holidays', 86400, function () {
                    return \Illuminate\Support\Facades\DB::table('holidays')->pluck('description', 'holiday_date')->toArray();
                });
                $view->with('holidays', $holidays);
            } else {
                $view->with('holidays', []);
            }
        });
    }
}
