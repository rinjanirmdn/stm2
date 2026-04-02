<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class HolidayHelper
{
    /**
     * Indonesian national holidays data sourced from Google Calendar ICS.
     */
    private static function getIndonesianHolidays(int $year): array
    {
        $allHolidays = self::getGoogleCalendarHolidays();

        if (! $allHolidays) {
            return [];
        }

        $filtered = [];
        foreach ($allHolidays as $date => $name) {
            if (str_starts_with($date, (string) $year)) {
                $filtered[$date] = $name;
            }
        }

        return $filtered;
    }

    private static function getGoogleCalendarHolidays(): array
    {
        $cached = Cache::get('indonesia_holidays_ics');
        if (is_array($cached) && count($cached) > 0) {
            return $cached;
        }

        try {
            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->get('https://calendar.google.com/calendar/ical/en.indonesian%23holiday%40group.v.calendar.google.com/public/basic.ics');

            if (! $response->ok()) {
                return self::getFallbackHolidays();
            }

            $ics = (string) $response->body();
            if ($ics === '') {
                return self::getFallbackHolidays();
            }

            $lines = preg_split("/\r\n|\n|\r/", $ics);
            if (! $lines) {
                return self::getFallbackHolidays();
            }

            $unfolded = [];
            foreach ($lines as $line) {
                if ($line === '') {
                    $unfolded[] = $line;

                    continue;
                }

                if (isset($unfolded[count($unfolded) - 1]) && str_starts_with($line, ' ')) {
                    $unfolded[count($unfolded) - 1] .= ltrim($line);
                } else {
                    $unfolded[] = $line;
                }
            }

            $holidays = [];
            $currentDate = null;
            $currentName = null;

            foreach ($unfolded as $line) {
                if ($line === 'BEGIN:VEVENT') {
                    $currentDate = null;
                    $currentName = null;

                    continue;
                }

                if ($line === 'END:VEVENT') {
                    if ($currentDate && $currentName) {
                        $holidays[$currentDate] = $currentName;
                    }
                    $currentDate = null;
                    $currentName = null;

                    continue;
                }

                if (str_starts_with($line, 'DTSTART')) {
                    $parts = explode(':', $line, 2);
                    $value = $parts[1] ?? '';
                    if (preg_match('/^(\d{4})(\d{2})(\d{2})/', $value, $matches)) {
                        $currentDate = sprintf('%04d-%02d-%02d', (int) $matches[1], (int) $matches[2], (int) $matches[3]);
                    }

                    continue;
                }

                if (str_starts_with($line, 'SUMMARY:')) {
                    $currentName = trim(substr($line, 8));

                    continue;
                }
            }

            // Only cache if we actually got holidays — never cache empty results
            if (count($holidays) > 0) {
                Cache::put('indonesia_holidays_ics', $holidays, 86400);
            }

            return count($holidays) > 0 ? $holidays : self::getFallbackHolidays();
        } catch (\Throwable $e) {
            return self::getFallbackHolidays();
        }
    }

    /**
     * Hardcoded fallback holidays for Indonesia (2025-2027).
     * Used when Google Calendar ICS fetch fails.
     */
    private static function getFallbackHolidays(): array
    {
        return [
            // 2025
            '2025-01-01' => 'Tahun Baru Masehi',
            '2025-01-27' => 'Tahun Baru Imlek',
            '2025-01-29' => 'Isra Mi\'raj Nabi Muhammad SAW',
            '2025-03-29' => 'Hari Suci Nyepi',
            '2025-03-31' => 'Hari Raya Idul Fitri',
            '2025-04-01' => 'Hari Raya Idul Fitri',
            '2025-04-18' => 'Wafat Isa Al Masih',
            '2025-05-01' => 'Hari Buruh Internasional',
            '2025-05-12' => 'Hari Raya Waisak',
            '2025-05-29' => 'Kenaikan Isa Al Masih',
            '2025-06-01' => 'Hari Lahir Pancasila',
            '2025-06-07' => 'Hari Raya Idul Adha',
            '2025-06-27' => 'Tahun Baru Islam',
            '2025-08-17' => 'Hari Kemerdekaan RI',
            '2025-09-05' => 'Maulid Nabi Muhammad SAW',
            '2025-12-25' => 'Hari Raya Natal',

            // 2026
            '2026-01-01' => 'Tahun Baru Masehi',
            '2026-01-17' => 'Tahun Baru Imlek',
            '2026-02-17' => 'Isra Mi\'raj Nabi Muhammad SAW',
            '2026-03-19' => 'Hari Suci Nyepi',
            '2026-03-20' => 'Hari Raya Idul Fitri',
            '2026-03-21' => 'Hari Raya Idul Fitri',
            '2026-04-03' => 'Wafat Isa Al Masih',
            '2026-05-01' => 'Hari Buruh Internasional',
            '2026-05-14' => 'Kenaikan Isa Al Masih',
            '2026-05-16' => 'Hari Raya Waisak',
            '2026-05-27' => 'Hari Raya Idul Adha',
            '2026-06-01' => 'Hari Lahir Pancasila',
            '2026-06-17' => 'Tahun Baru Islam',
            '2026-08-17' => 'Hari Kemerdekaan RI',
            '2026-08-26' => 'Maulid Nabi Muhammad SAW',
            '2026-12-25' => 'Hari Raya Natal',

            // 2027
            '2027-01-01' => 'Tahun Baru Masehi',
            '2027-02-06' => 'Tahun Baru Imlek',
            '2027-02-08' => 'Isra Mi\'raj Nabi Muhammad SAW',
            '2027-03-09' => 'Hari Raya Idul Fitri',
            '2027-03-10' => 'Hari Raya Idul Fitri',
            '2027-03-26' => 'Wafat Isa Al Masih',
            '2027-03-28' => 'Hari Suci Nyepi',
            '2027-05-01' => 'Hari Buruh Internasional',
            '2027-05-06' => 'Kenaikan Isa Al Masih',
            '2027-05-06' => 'Hari Raya Waisak',
            '2027-05-17' => 'Hari Raya Idul Adha',
            '2027-06-01' => 'Hari Lahir Pancasila',
            '2027-06-07' => 'Tahun Baru Islam',
            '2027-08-16' => 'Maulid Nabi Muhammad SAW',
            '2027-08-17' => 'Hari Kemerdekaan RI',
            '2027-12-25' => 'Hari Raya Natal',
        ];
    }

    /**
     * Check if a date is a holiday in Indonesia
     *
     * @param  \DateTime|string  $date
     */
    public static function isHoliday($date): bool
    {
        try {
            $date = is_string($date) ? Carbon::parse($date) : Carbon::instance($date);
            $year = $date->year;
            $dateStr = $date->format('Y-m-d');

            $holidays = self::getIndonesianHolidays($year);

            return isset($holidays[$dateStr]);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get holiday name for a specific date
     *
     * @param  \DateTime|string  $date
     */
    public static function getHolidayName($date): ?string
    {
        try {
            $date = is_string($date) ? Carbon::parse($date) : Carbon::instance($date);
            $year = $date->year;
            $dateStr = $date->format('Y-m-d');

            $holidays = self::getIndonesianHolidays($year);

            return $holidays[$dateStr] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get all holidays for a year
     */
    public static function getHolidaysByYear(int $year): array
    {
        try {
            $holidays = self::getIndonesianHolidays($year);
            $result = [];

            foreach ($holidays as $date => $name) {
                $result[] = [
                    'name' => $name,
                    'date' => $date,
                    'type' => 'national',
                ];
            }

            // Sort by date
            usort($result, function ($a, $b) {
                return strcmp($a['date'], $b['date']);
            });

            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get holiday map (date => name) for the year of a given date.
     * Consolidates logic previously duplicated in multiple controllers.
     */
    public static function getHolidayMap(string $date): array
    {
        try {
            $year = (int) date('Y', strtotime($date));
            $holidayData = self::getHolidaysByYear($year);

            return collect($holidayData)->pluck('name', 'date')->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Check if date is weekend (Saturday/Sunday)
     *
     * @param  \DateTime|string  $date
     */
    public static function isWeekend($date): bool
    {
        $date = is_string($date) ? Carbon::parse($date) : Carbon::instance($date);

        return in_array($date->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]);
    }

    /**
     * Check if date is working day (not weekend and not holiday)
     *
     * @param  \DateTime|string  $date
     */
    public static function isWorkingDay($date): bool
    {
        return ! self::isWeekend($date) && ! self::isHoliday($date);
    }
}
