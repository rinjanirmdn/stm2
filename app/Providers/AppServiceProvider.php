<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Slot;
use App\Observers\SlotObserver;

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
            // Use HolidayHelper instead of database table
            try {
                $holidays = \Illuminate\Support\Facades\Cache::remember('public_holidays_v2', 86400, function () {
                    $years = [now()->year, now()->year + 1];
                    $holidayMap = [];

                    foreach ($years as $year) {
                        $holidayData = \App\Helpers\HolidayHelper::getHolidaysByYear($year);
                        foreach ($holidayData as $holiday) {
                            $date = $holiday['date'] ?? null;
                            if (!$date) {
                                continue;
                            }
                            $holidayMap[$date] = $holiday['name'] ?? 'Holiday';
                        }
                    }

                    return $holidayMap;
                });
                $view->with('holidays', $holidays);
            } catch (\Exception $e) {
                // If holiday helper fails, use empty array
                $view->with('holidays', []);
            }
        });

        // Register Slot observer
        Slot::observe(SlotObserver::class);
    }
}
