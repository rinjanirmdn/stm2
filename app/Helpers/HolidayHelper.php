<?php

namespace App\Helpers;

use Carbon\Carbon;

class HolidayHelper
{
    /**
     * Indonesian national holidays data (only official national holidays)
     */
    private static function getIndonesianHolidays(int $year): array
    {
        $holidays = [];

        // Official Indonesian National Holidays
        $nationalHolidays = [
            '01-01' => 'Tahun Baru',
            '05-01' => 'Hari Buruh Internasional',
            '06-01' => 'Hari Lahir Pancasila',
            '08-17' => 'Hari Kemerdekaan Republik Indonesia',
            '12-25' => 'Hari Raya Natal',
        ];

        foreach ($nationalHolidays as $date => $name) {
            $holidays[$year . '-' . $date] = $name;
        }

        // Religious holidays (official national holidays)
        $religiousHolidays = self::getReligiousHolidays($year);
        $holidays = array_merge($holidays, $religiousHolidays);

        return $holidays;
    }

    /**
     * Get official religious holidays (national holidays)
     */
    private static function getReligiousHolidays(int $year): array
    {
        // Simplified religious holidays - for production use proper calculation
        return [
            // Islamic holidays (official national holidays)
            self::formatDate($year, 3, 11) => 'Hari Raya Idul Fitri (perkiraan)',
            self::formatDate($year, 3, 12) => 'Hari Raya Idul Fitri (perkiraan)',
            self::formatDate($year, 6, 18) => 'Hari Raya Idul Adha (perkiraan)',
            self::formatDate($year, 6, 19) => 'Hari Raya Idul Adha (perkiraan)',

            // Hindu holiday (official national holiday)
            self::formatDate($year, 3, 11) => 'Hari Raya Nyepi (perkiraan)',

            // Buddhist holiday (official national holiday)
            self::formatDate($year, 5, 22) => 'Hari Raya Waisak (perkiraan)',

            // Christian holiday (official national holiday)
            self::formatDate($year, 3, 29) => 'Wafat Isa Al Masih (perkiraan)',
            self::formatDate($year, 8, 15) => 'Kenaikan Isa Al Masih (perkiraan)',
        ];
    }

    /**
     * Format date string
     */
    private static function formatDate(int $year, int $month, int $day): string
    {
        return sprintf('%04d-%02d-%02d', $year, $month, $day);
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
