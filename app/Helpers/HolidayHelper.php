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

        if (!$allHolidays) {
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
        return Cache::remember('indonesia_holidays_ics', 86400, function () {
            try {
                $response = Http::timeout(15)->get('https://calendar.google.com/calendar/ical/en.indonesian%23holiday%40group.v.calendar.google.com/public/basic.ics');

                if (!$response->ok()) {
                    return [];
                }

                $ics = (string) $response->body();
                if ($ics === '') {
                    return [];
                }

                $lines = preg_split("/\r\n|\n|\r/", $ics);
                if (!$lines) {
                    return [];
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

                return $holidays;
            } catch (\Throwable $e) {
                return [];
            }
        });
    }

    /**
     * Check if a date is a holiday in Indonesia
     *
     * @param \DateTime|string $date
     * @return bool
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
     * @param \DateTime|string $date
     * @return string|null
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
     *
     * @param int $year
     * @return array
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
                    'type' => 'national'
                ];
            }

            // Sort by date
            usort($result, function($a, $b) {
                return strcmp($a['date'], $b['date']);
            });

            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Check if date is weekend (Saturday/Sunday)
     *
     * @param \DateTime|string $date
     * @return bool
     */
    public static function isWeekend($date): bool
    {
        $date = is_string($date) ? Carbon::parse($date) : Carbon::instance($date);
        return in_array($date->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]);
    }

    /**
     * Check if date is working day (not weekend and not holiday)
     *
     * @param \DateTime|string $date
     * @return bool
     */
    public static function isWorkingDay($date): bool
    {
        return !self::isWeekend($date) && !self::isHoliday($date);
    }
}
