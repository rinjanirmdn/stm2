<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class FetchIndonesiaHolidays extends Command
{
    protected $signature = 'holidays:fetch {year?}';
    protected $description = 'Fetch Indonesia public holidays from API';

    public function handle()
    {
        $year = $this->argument('year') ?: date('Y');
        $this->info("Fetching holidays for year: {$year}...");

        try {
            // Using api-harilibur.vercel.app as a reliable source for Indonesia holidays
            $response = Http::get("https://api-harilibur.vercel.app/api?year={$year}");

            if ($response->successful()) {
                $holidays = $response->json();
                
                foreach ($holidays as $holiday) {
                    if (isset($holiday['holiday_date'])) {
                        DB::table('holidays')->updateOrInsert(
                            ['holiday_date' => $holiday['holiday_date']],
                            [
                                'description' => $holiday['holiday_name'],
                                'is_national' => $holiday['is_national_holiday'] ?? true,
                                'updated_at' => now(),
                                'created_at' => now(),
                            ]
                        );
                    }
                }
                
                $this->info("Successfully synced " . count($holidays) . " holidays.");
            } else {
                $this->error("Failed to fetch holidays from API.");
            }
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }
    }
}
