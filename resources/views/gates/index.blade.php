@extends('layouts.app')

@section('title', 'Gates Management - Slot Time Management')
@section('page_title', 'Gates Management')

@section('content')
@php
    $paramDate = request('date_from', date('Y-m-d'));
    // Fetch slots for the date using 'planned_start' with vendor
    $daySlots = \App\Models\Slot::with('vendor')->whereDate('planned_start', $paramDate)->get();
    
    // Filter for Unassigned Slots (planned_gate_id is null)
    $unassignedSlots = $daySlots->filter(function($s) {
        return empty($s->planned_gate_id);
    });
@endphp
<div class="st-dock-layout">
    <!-- Left Sidebar -->
    <aside class="st-dock-sidebar">
        
        <!-- Mini Calendar (Interactive) -->
        <div id="dock_inline_calendar" style="width:100%;"></div>

        <!-- Unassigned Queue -->
        @if($unassignedSlots->isNotEmpty())
            <div style="padding: 12px; background: #fff1f2; border-radius: 8px; border: 1px dashed #f43f5e;">
                <div style="font-weight:600;color:#be123c;margin-bottom:8px;font-size:13px;">
                    Pending Assignment ({{ $unassignedSlots->count() }})
                </div>
                <div style="display:flex;flex-direction:column;gap:6px;max-height:200px;overflow-y:auto;">
                    @foreach($unassignedSlots as $us)
                        <div class="bg-{{ $us->status_badge_color }}" style="padding:6px;border-radius:4px;font-size:11px;cursor:pointer;border:1px solid #e5e7eb;box-shadow:0 1px 2px rgba(0,0,0,0.05);"
                             ondblclick="window.location.href='{{ route('slots.show', $us->id) }}'"
                             title="{{ $us->status }}">
                            <div style="display:flex;justify-content:space-between;">
                                <span style="font-weight:700;color:#374151;">{{ $us->ticket_number ?? '-' }}</span>
                                <span style="color:#6b7280;">{{ \Carbon\Carbon::parse($us->planned_start)->format('H:i') }}</span>
                            </div>
                            <div style="font-size:10px;color:#4b5563;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $us->vendor->name ?? '-' }}</div>
                            <div style="color:#ef4444;font-size:10px;margin-top:2px;">{{ $us->status_label ?? $us->status }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Bookings Legend -->
        <div>
            <div class="st-flex-between" style="margin-bottom:10px;">
                <div style="font-weight:600;font-size:14px;">Bookings for: <span id="selected_date_display">{{ \Carbon\Carbon::parse($paramDate)->format('d.m.Y') }}</span></div>
                <div class="st-dock-count">{{ $daySlots->count() }}</div>
            </div>
            
            <div class="st-dock-legend">
                <!-- Pre-Booking -->
                <div class="st-dock-legend-item bg-dock-pre-booking" style="padding:8px 12px;border-radius:20px;">
                    <div class="st-dock-legend-indicator">
                        <div class="st-dock-dot dot-dock-pre-booking"></div>
                        <span>Pre-Booking</span>
                    </div>
                    <span class="st-dock-count">3</span>
                </div>
                <!-- Confirmed -->
                <div class="st-dock-legend-item bg-dock-confirmed" style="padding:8px 12px;border-radius:20px;background-color:transparent;color:#374151;border:1px solid #e5e7eb;">
                    <div class="st-dock-legend-indicator">
                        <div class="st-dock-dot dot-dock-confirmed"></div>
                        <span>Confirmed</span>
                    </div>
                    <span class="st-dock-count">2</span>
                </div>
                <!-- In Progress -->
                <div class="st-dock-legend-item bg-dock-progress" style="padding:8px 12px;border-radius:20px;background-color:transparent;color:#374151;border:1px solid #e5e7eb;">
                    <div class="st-dock-legend-indicator">
                        <div class="st-dock-dot dot-dock-progress"></div>
                        <span>In progress</span>
                    </div>
                    <span class="st-dock-count">3</span>
                </div>
                <!-- Completed -->
                <div class="st-dock-legend-item bg-dock-completed" style="padding:8px 12px;border-radius:20px;background-color:transparent;color:#374151;border:1px solid #e5e7eb;">
                    <div class="st-dock-legend-indicator">
                        <div class="st-dock-dot dot-dock-completed"></div>
                        <span>Completed</span>
                    </div>
                    <span class="st-dock-count">3</span>
                </div>
                <!-- Cancelled/Problematic -->
                <div class="st-dock-legend-item bg-dock-rejected" style="padding:8px 12px;border-radius:20px;background-color:transparent;color:#374151;border:1px solid #e5e7eb;">
                    <div class="st-dock-legend-indicator">
                        <div class="st-dock-dot dot-dock-rejected"></div>
                        <span>Problematic</span>
                    </div>
                    <span class="st-dock-count">1</span>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="st-dock-main">
        <!-- Top Header: Search & Actions -->
        <header class="st-dock-topbar">
            <div class="st-dock-search">
                <i class="fa-solid fa-magnifying-glass st-dock-search-icon"></i>
                <input type="text" placeholder="Search for a booking">
            </div>
            
            <div style="display:flex;gap:12px;align-items:center;">
                <div style="font-size:14px;color:#6b7280;font-weight:500;">Filters</div>
                <button class="st-btn st-btn--secondary st-btn--sm">
                    <i class="fa-solid fa-filter"></i> More
                </button>
                <button class="st-btn st-btn--primary st-btn--sm">
                    New Booking
                </button>
            </div>
        </header>

        <!-- Scheduler Grid -->
        <div class="st-dock-scheduler">
            <!-- Grid Header -->
            <div class="st-dock-grid-header" style="grid-template-columns: 60px repeat({{ $gates->count() }}, 1fr);">
                <!-- Time Label Corner -->
                <div class="st-dock-col-header" style="justify-content:center;">
                    <i class="fa-regular fa-clock" style="color:#9ca3af;"></i>
                </div>
                <!-- Gate Columns -->
                @foreach($gates as $g)
                @php
                    $label = app(\App\Services\SlotService::class)->getGateDisplayName((string)($g->warehouse_code ?? ''), (string)($g->gate_number ?? ''));
                    $isBackup = (int)($g->is_backup ?? 0) === 1;
                    $isActive = (int)($g->is_active ?? 0) === 1;
                @endphp
                <div class="st-dock-col-header">
                    <span>{{ $label }}</span>
                    <!-- Active Toggle for Backup Gates Only -->
                    @if($isBackup)
                        <form method="POST" action="{{ route('gates.toggle', ['gateId' => $g->id]) }}" id="toggle-gate-{{ $g->id }}">
                            @csrf
                            <label class="st-dock-toggle">
                                <input type="checkbox" {{ $isActive ? 'checked' : '' }} onchange="document.getElementById('toggle-gate-{{ $g->id }}').submit()">
                                <span class="st-dock-toggle-slider"></span>
                            </label>
                        </form>
                    @endif
                </div>
                @endforeach
            </div>

            <!-- Grid Body (Scrollable) -->
            <div class="st-dock-grid-body" style="grid-template-columns: 60px repeat({{ $gates->count() }}, 1fr);">
                <!-- Time Column (07:00 - 23:00) -->
                <div class="st-dock-time-col">
                    @for($h = 7; $h <= 23; $h++)
                        <div class="st-dock-time-slot">
                            {{ sprintf('%02d:00', $h) }}
                        </div>
                    @endfor
                </div>

                <!-- Gate Columns -->
                <!-- Gate Columns -->
                @php
                    $paramDate = request('date_from', date('Y-m-d'));
                    // Fetch slots using 'planned_start' as requested by user
                    $daySlots = \App\Models\Slot::whereDate('planned_start', $paramDate)->get();
                @endphp

                @foreach($gates as $index => $g)
                <div class="st-dock-gate-col">
                    @php
                        // Filter slots for this gate using 'planned_gate_id'
                        $gateSlots = $daySlots->where('planned_gate_id', $g->id);
                    @endphp

                    @foreach($gateSlots as $slot)
                        @php
                            // Use planned_start and planned_end
                            $st = \Carbon\Carbon::parse($slot->planned_start);
                            
                            // Robust Duration Calculation
                            $durMinutes = 0;
                            $rawDur = $slot->planned_duration;

                            if (!empty($rawDur)) {
                                if (strpos((string)$rawDur, ':') !== false) {
                                    // Handle H:i format
                                    $parts = explode(':', $rawDur);
                                    $durMinutes = ((int)$parts[0] * 60) + ((int)($parts[1] ?? 0));
                                } else {
                                    // Handle integer minutes or hours
                                    $val = (float)$rawDur;
                                    if ($val > 0 && $val <= 8) { 
                                        // Assume hours if value is small (<= 8)
                                        $durMinutes = $val * 60;
                                    } else {
                                        $durMinutes = (int)$val;
                                    }
                                }
                            }

                            // Calculate ET based on duration
                            if ($durMinutes > 0) {
                                $et = $st->copy()->addMinutes($durMinutes);
                            } else {
                                // Fallback to planned_end if duration failed
                                // Check if planned_end exists and is valid
                                if (!empty($slot->planned_end)) {
                                     $et = \Carbon\Carbon::parse($slot->planned_end);
                                     // Verify it's not simply 'now' (parsing null)
                                     // Actually Eloquent returns null if column missing, verify raw attribute? 
                                     // We'll trust Carbon result if it's different enough from Now? No.
                                     // Just recalculate duration
                                     $durMinutes = $et->diffInMinutes($st);
                                }
                            }

                            // Final Safety Fallback
                            if ($durMinutes < 15) {
                                $durMinutes = 120; // Default to 2 hours if data missing/bad, matching user expectation
                                $et = $st->copy()->addMinutes($durMinutes);
                            }
                            
                            // Re-sync duration variable for height
                            $duration = $durMinutes;

                            // Calculate position relative to 07:00
                            // 1 hour = 60px. 1 minute = 1 px.
                            $minutesFrom7 = ($st->hour - 7) * 60 + $st->minute;
                            if ($minutesFrom7 < 0) $minutesFrom7 = 0; 
                            
                            // Ensure layout doesn't break
                            // $duration is already set above

                            // Status Colors
                            $bgClass = 'bg-dock-pre-booking';
                            // Use model's native badge color (Solid Original Colors)
                            $bgClass = 'bg-' . $slot->status_badge_color;
                            
                            // Ensure waiting/pending map to warning if not set
                            // (Already handled by helper, but explicit check if needed)
                            // Slot model: pending_approval -> warning, waiting -> warning. 
                            // scheduled -> success. in_progress -> primary. completed -> secondary (or success?)
                            // This matches User's "Solid" expectation.
                        @endphp
                        
                        <!-- Slot Card -->
                        <div class="st-dock-card {{ $bgClass }}" 
                             style="top:{{ $minutesFrom7 }}px; height:{{ $duration }}px;"
                             ondblclick="window.location.href='{{ route('slots.show', $slot->id) }}'"
                             title="{{ $slot->ticket_number }} ({{ $slot->status }})">
                            
                            <div class="st-dock-card-ticket">{{ $slot->ticket_number ?? '-' }}</div>
                            <div class="st-dock-card-time">{{ $st->format('H:i') }}-{{ $et->format('H:i') }}</div>
                            <div style="font-size:10px;color:#4b5563;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:2px;">
                                {{ $slot->vendor->name ?? '-' }}
                            </div>
                            
                            <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-top:auto;">
                                <div class="st-dock-card-cat">{{ $slot->status_label ?? $slot->status }}</div>
                                @if(in_array($slot->status, ['pending', 'pending_approval', 'pending_vendor_confirmation']))
                                    <button class="st-dock-btn-confirm" onclick="event.stopPropagation(); window.location.href='{{ route('slots.show', $slot->id) }}'">Action</button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                @endforeach

                <!-- Current Time Indicator (Red Line) -->
                @php
                    $now = now();
                    $startHr = 7;
                    $endHr = 23;
                    // Calculate pixels: (CurrentHour - 7) * 60 + CurrentMinute
                    $currentPx = ($now->hour - $startHr) * 60 + $now->minute;
                    // Only show if within the grid hours
                    $showLine = $now->hour >= $startHr && $now->hour <= $endHr;
                @endphp

                @if($showLine)
                    <div class="st-dock-time-line" style="top:{{ $currentPx }}px;" title="Current Time: {{ $now->format('H:i') }}"></div>
                @endif
            </div>
        </div>
    </main>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Initialize Sidebar Calendar
    if (typeof flatpickr !== 'undefined') {
        flatpickr("#dock_inline_calendar", {
            inline: true,
            dateFormat: "Y-m-d",
            defaultDate: "today",
            theme: "light", // ensure light theme
            onChange: function(selectedDates, dateStr, instance) {
                document.getElementById('selected_date_display').innerText = instance.formatDate(selectedDates[0], "d.m.Y");
            }
        });
    }

    // Interactive toggles demo
    const toggles = document.querySelectorAll('.st-dock-toggle input');
    toggles.forEach(t => {
        t.addEventListener('change', function() {
            const col = this.closest('.st-dock-col-header');
            const idx = Array.from(col.parentNode.children).indexOf(col);
            // In a real app, this would toggle visibility or status of the column
            console.log('Toggled gate column index:', idx);
        });
    });
});
</script>
<style>
/* Local override for specific calendar styling to match design if needed */
.flatpickr-calendar.inline {
    width: 100% !important;
    box-shadow: none !important;
}
</style>
@endpush
