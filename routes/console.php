<?php

use App\Jobs\RecalculateBlockingRiskJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Recalculate blocking risk every 5 minutes in background
Schedule::job(new RecalculateBlockingRiskJob())->everyFiveMinutes()->withoutOverlapping();

// Manual command to trigger blocking risk recalculation
Artisan::command('slots:recalculate-risk', function () {
    $this->info('Dispatching RecalculateBlockingRiskJob...');
    RecalculateBlockingRiskJob::dispatch();
    $this->info('Job dispatched successfully.');
})->purpose('Recalculate blocking risk for all active slots');
