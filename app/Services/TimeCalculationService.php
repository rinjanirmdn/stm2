<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use DateTime;

class TimeCalculationService
{
    /**
     * Calculate minutes difference between two timestamps
     */
    public function minutesDiff(?string $start, ?string $end): ?int
    {
        if (!$start || !$end) {
            return null;
        }

        try {
            $s = new DateTime($start);
            $e = new DateTime($end);
            $diff = $s->diff($e);
            return ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Check if arrival is late by planned start time (15 minutes grace period)
     */
    public function isLateByPlannedStart(?string $plannedStart, string $actualTime): bool
    {
        if (!$plannedStart) {
            return false;
        }

        try {
            $p = new DateTime($plannedStart);
            $p->modify('+15 minutes');
            $a = new DateTime($actualTime);
            return $a > $p;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get planned duration for a slot based on truck type or planned duration
     */
    public function getPlannedDurationForStart(object $slot): int
    {
        $truckType = trim($slot->truck_type ?? '');

        if ($truckType !== '') {
            $duration = $this->getTruckTypeDuration($truckType);
            if ($duration > 0) {
                return $duration;
            }
        }

        $planned = (int) ($slot->planned_duration ?? 0);
        if ($planned > 0) {
            return $planned;
        }

        return 60; // Default 60 minutes
    }

    /**
     * Get target duration for truck type
     */
    public function getTruckTypeDuration(string $truckType): int
    {
        $row = DB::table('md_truck')
            ->where('truck_type', $truckType)
            ->select(['target_duration_minutes'])
            ->first();

        if ($row && isset($row->target_duration_minutes) && (int) $row->target_duration_minutes > 0) {
            return (int) $row->target_duration_minutes;
        }

        return 0;
    }

    /**
     * Calculate actual duration from start to finish
     */
    public function calculateActualDuration(?string $start, ?string $finish): ?int
    {
        return $this->minutesDiff($start, $finish);
    }

    /**
     * Calculate waiting time from arrival to start
     */
    public function calculateWaitingTime(?string $arrival, ?string $start): ?int
    {
        return $this->minutesDiff($arrival, $start);
    }

    /**
     * Calculate processing time from start to finish
     */
    public function calculateProcessingTime(?string $start, ?string $finish): ?int
    {
        return $this->minutesDiff($start, $finish);
    }

    /**
     * Calculate total lead time from arrival to finish
     */
    public function calculateLeadTime(?string $arrival, ?string $finish): ?int
    {
        return $this->minutesDiff($arrival, $finish);
    }

    /**
     * Check if target duration was achieved
     */
    public function isTargetAchieved(?string $arrival, ?string $start, ?string $finish, string $truckType): bool
    {
        $actualDuration = $this->calculateLeadTime($arrival, $finish);
        $targetDuration = $this->getTruckTypeDuration($truckType);

        if ($actualDuration === null || $targetDuration === 0) {
            return false;
        }

        // 15 minutes grace period
        return $actualDuration <= ($targetDuration + 15);
    }

    /**
     * Get performance status (achieve/not_achieve)
     */
    public function getPerformanceStatus(?string $arrival, ?string $start, ?string $finish, string $truckType): string
    {
        if ($this->isTargetAchieved($arrival, $start, $finish, $truckType)) {
            return 'achieve';
        }

        return 'not_achieve';
    }

    /**
     * Get late status (late/on_time)
     */
    public function getLateStatus(?string $plannedStart, ?string $arrival, ?string $isLateFlag): string
    {
        if ($arrival !== null && $plannedStart !== null) {
            return $this->isLateByPlannedStart($plannedStart, $arrival) ? 'late' : 'on_time';
        }

        // Fallback to is_late flag
        return !empty($isLateFlag) ? 'late' : 'on_time';
    }

    /**
     * Format duration for display
     */
    public function formatDuration(?int $minutes): string
    {
        if ($minutes === null) {
            return '-';
        }

        $m = (int) $minutes;
        $h = $m / 60;
        $out = $m . ' min';

        if ($h >= 1) {
            $out .= ' (' . rtrim(rtrim(number_format($h, 2), '0'), '.') . ' h)';
        }

        return $out;
    }

    /**
     * Calculate estimated finish time
     */
    public function calculateEstimatedFinish(?string $startTime, ?int $durationMinutes): ?string
    {
        if (!$startTime || !$durationMinutes) {
            return null;
        }

        try {
            $start = new DateTime($startTime);
            $start->modify('+' . $durationMinutes . ' minutes');
            return $start->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get time slot for timeline positioning
     */
    public function getTimeSlotPosition(?string $startTime, ?int $durationMinutes): array
    {
        $left = 0;
        $width = 15; // Default width
        $eta = '-';
        $estFinish = '-';

        if ($startTime) {
            try {
                $dt = new DateTime($startTime);
                $left = ((int) $dt->format('H')) * 60 + (int) $dt->format('i');
                $eta = $dt->format('H:i');

                if ($durationMinutes && $durationMinutes > 0) {
                    $width = $durationMinutes;
                    try {
                        $dtFinish = clone $dt;
                        $dtFinish->modify('+' . (int) $durationMinutes . ' minutes');
                        $estFinish = $dtFinish->format('H:i');
                    } catch (\Throwable $e) {
                        $estFinish = '-';
                    }
                }
            } catch (\Throwable $e) {
                $left = 0;
            }
        }

        return [
            'left' => max(0, $left),
            'width' => max(1, $width),
            'eta' => $eta,
            'est_finish' => $estFinish,
        ];
    }

    /**
     * Check if time range overlaps
     */
    public function isTimeOverlap(
        ?string $start1, ?string $end1,
        ?string $start2, ?string $end2
    ): bool {
        if (!$start1 || !$end1 || !$start2 || !$end2) {
            return false;
        }

        try {
            $s1 = new DateTime($start1);
            $e1 = new DateTime($end1);
            $s2 = new DateTime($start2);
            $e2 = new DateTime($end2);

            return $s1 < $e2 && $s2 < $e1;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Calculate average duration for truck type
     */
    public function getAverageDurationByTruckType(string $truckType): ?float
    {
        try {
            $avg = DB::table('slots')
                ->where('truck_type', $truckType)
                ->where('status', 'completed')
                ->whereNotNull('actual_start')
                ->whereNotNull('actual_finish')
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, actual_start, actual_finish)) as avg_duration')
                ->value('avg_duration');

            return $avg ? (float) $avg : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get truck type options with durations
     */
    public function getTruckTypeOptions(): array
    {
        $fromDb = DB::table('md_truck')->orderBy('truck_type')->pluck('truck_type')->all();

        if (!empty($fromDb)) {
            return array_values(array_filter(array_map('strval', $fromDb)));
        }

        // Default truck types
        return [
            'CDD/CDE',
            'Fuso',
            'Wingbox (Paletize)',
            'Wingbox (Loose)',
            'Container 20ft (Paletize)',
            'Container 20ft (Loose)',
            'Container 40ft (Paletize)',
            'Container 40ft (Loose)',
            'Cargo',
        ];
    }

    /**
     * Validate time format and convert to standard format
     */
    public function validateAndFormatTime(string $time): ?string
    {
        try {
            $dt = new DateTime($time);
            return $dt->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get current time in database format
     */
    public function getCurrentTime(): string
    {
        return now()->format('Y-m-d H:i:s');
    }

    /**
     * Add minutes to a time
     */
    public function addMinutes(?string $time, int $minutes): ?string
    {
        if (!$time) {
            return null;
        }

        try {
            $dt = new DateTime($time);
            $dt->modify('+' . $minutes . ' minutes');
            return $dt->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Check if time is within business hours (8:00 - 17:00)
     */
    public function isWithinBusinessHours(?string $time): bool
    {
        if (!$time) {
            return false;
        }

        try {
            $dt = new DateTime($time);
            $hour = (int) $dt->format('H');
            return $hour >= 8 && $hour < 17;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
