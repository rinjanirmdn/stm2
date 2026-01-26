<?php

namespace App\Observers;

use App\Models\Slot;
use Illuminate\Support\Facades\Cache;

class SlotObserver
{
    /**
     * Handle the Slot "created" event.
     */
    public function created(Slot $slot): void
    {
        $this->clearAvailabilityCache($slot->planned_start);
    }

    /**
     * Handle the Slot "updated" event.
     */
    public function updated(Slot $slot): void
    {
        $this->clearAvailabilityCache($slot->planned_start);
        
        // Also clear old date if planned_start changed
        if ($slot->isDirty('planned_start')) {
            $this->clearAvailabilityCache($slot->getOriginal('planned_start'));
        }
    }

    /**
     * Handle the Slot "deleted" event.
     */
    public function deleted(Slot $slot): void
    {
        $this->clearAvailabilityCache($slot->planned_start);
    }

    /**
     * Clear availability cache for a given date
     */
    private function clearAvailabilityCache($date): void
    {
        $dateStr = \Carbon\Carbon::parse($date)->format('Y-m-d');
        Cache::forget("vendor_availability_{$dateStr}");
    }
}
