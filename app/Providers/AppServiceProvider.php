<?php

namespace App\Providers;

use App\Helpers\HolidayHelper;
use App\Models\Slot;
use App\Observers\SlotObserver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
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
        // Load holidays lazily — only when views that use them are rendered
        View::composer(['layouts.app', 'vendor.layouts.vendor', 'vendor.bookings.availability'], function ($view) {
            try {
                $holidays = Cache::remember('public_holidays_v2', 86400, function () {
                    $years = [now()->year, now()->year + 1];
                    $holidayMap = [];

                    foreach ($years as $year) {
                        $holidayData = HolidayHelper::getHolidaysByYear($year);
                        foreach ($holidayData as $holiday) {
                            $date = $holiday['date'] ?? null;
                            if (! $date) {
                                continue;
                            }
                            $holidayMap[$date] = $holiday['name'] ?? 'Holiday';
                        }
                    }

                    return $holidayMap;
                });
                $view->with('holidays', $holidays);
            } catch (\Exception $e) {
                $view->with('holidays', []);
            }
        });

        // Register Slot observer
        Slot::observe(SlotObserver::class);

        // BroadcastNotification listener is auto-discovered by Laravel 11.
        // Do NOT manually register it here — it would cause double broadcasts.
    }
}
