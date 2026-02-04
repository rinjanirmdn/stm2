@extends('layouts.app')

@section('title', 'Gates Management - Slot Time Management')
@section('page_title', 'Gates Management')

@section('content')
<div class="st-dock-layout">
    <!-- Left Sidebar -->
    <aside class="st-dock-sidebar">

        <!-- Mini Calendar (Interactive) -->
        <div class="av-calendar" id="dock_inline_calendar">
            <div class="av-calendar__header">
                <span id="dock_calendar_month">{{ \Carbon\Carbon::parse($paramDate)->format('F Y') }}</span>
                <div class="av-calendar__nav">
                    <button type="button" class="av-calendar__nav-btn" id="dock_calendar_prev"><i class="fas fa-chevron-left"></i></button>
                    <button type="button" class="av-calendar__nav-btn" id="dock_calendar_next"><i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
            <div class="av-calendar__grid">
                <div class="av-calendar__day-header">Mon</div>
                <div class="av-calendar__day-header">Tue</div>
                <div class="av-calendar__day-header">Wed</div>
                <div class="av-calendar__day-header">Thu</div>
                <div class="av-calendar__day-header">Fri</div>
                <div class="av-calendar__day-header">Sat</div>
                <div class="av-calendar__day-header">Sun</div>
            </div>
            <div class="av-calendar__grid" id="dock_calendar_days"></div>
        </div>



        <!-- Bookings Legend -->
        <div>
            <div class="st-flex-between st-mb-10">
                <div class="st-font-semibold st-text--sm st-text--slate-600">Bookings for: <span id="selected_date_display">{{ \Carbon\Carbon::parse($paramDate)->format('d.m.Y') }}</span></div>
                <div class="st-dock-count st-text--sm">{{ $daySlots->count() }}</div>
            </div>
            <div class="st-dock-legend">
                <!-- Scheduled -->
                <div class="st-legend-group">
                    <div class="st-legend-item st-legend-item--scheduled">
                        <div class="st-dock-legend-indicator">
                            <div class="st-dock-dot"></div>
                            <span>Scheduled</span>
                        </div>
                        <span class="st-dock-count">{{ $scheduledSlots->count() }}</span>
                    </div>
                    @if($scheduledSlots->count() > 0)
                    <div class="st-dock-legend-list">
                        @foreach($scheduledSlots as $ss)
                        <div class="st-dock-legend-card">
                            <div class="st-dock-legend-card-header st-justify-between st-w-full">
                                <span class="st-dock-legend-card-ticket">{{ $ss->ticket_number }}</span>
                                <div class="st-dock-legend-card-actions">
                                    <div class="st-dock-legend-card-btn" onclick="focusSlot({{ $ss->id }})" title="View on grid">
                                        <i class="fas fa-eye"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>

                <!-- Waiting -->
                <div class="st-legend-group">
                    <div class="st-legend-item st-legend-item--waiting">
                        <div class="st-dock-legend-indicator">
                            <div class="st-dock-dot"></div>
                            <span>Waiting</span>
                        </div>
                        <span class="st-dock-count">{{ $waitingSlots->count() }}</span>
                    </div>
                    @if($waitingSlots->count() > 0)
                    <div class="st-dock-legend-list">
                        @foreach($waitingSlots as $ws)
                        <div class="st-dock-legend-card">
                            <div class="st-dock-legend-card-header st-justify-between st-w-full">
                                <span class="st-dock-legend-card-ticket">{{ $ws->ticket_number }}</span>
                                <div class="st-dock-legend-card-actions">
                                    <div class="st-dock-legend-card-btn" onclick="focusSlot({{ $ws->id }})" title="View on grid">
                                        <i class="fas fa-eye"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>

                <!-- In Progress -->
                <div class="st-legend-group">
                    <div class="st-legend-item st-legend-item--in_progress">
                        <div class="st-dock-legend-indicator">
                            <div class="st-dock-dot"></div>
                            <span>In Progress</span>
                        </div>
                        <span class="st-dock-count">{{ $inProgressSlots->count() }}</span>
                    </div>
                    @if($inProgressSlots->count() > 0)
                    <div class="st-dock-legend-list">
                        @foreach($inProgressSlots as $is)
                        <div class="st-dock-legend-card">
                            <div class="st-dock-legend-card-header st-justify-between st-w-full">
                                <span class="st-dock-legend-card-ticket">{{ $is->ticket_number }}</span>
                                <div class="st-dock-legend-card-actions">
                                    <div class="st-dock-legend-card-btn" onclick="focusSlot({{ $is->id }})" title="View on grid">
                                        <i class="fas fa-eye"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>

                <!-- Completed -->
                <div class="st-legend-group">
                    <div class="st-legend-item st-legend-item--completed">
                        <div class="st-dock-legend-indicator">
                            <div class="st-dock-dot"></div>
                            <span>Completed</span>
                        </div>
                        <span class="st-dock-count">{{ $completedSlots->count() }}</span>
                    </div>
                    @if($completedSlots->count() > 0)
                    <div class="st-dock-legend-list">
                        @foreach($completedSlots as $cs)
                        <div class="st-dock-legend-card">
                            <div class="st-dock-legend-card-header st-justify-between st-w-full">
                                <span class="st-dock-legend-card-ticket">{{ $cs->ticket_number }}</span>
                                <div class="st-dock-legend-card-actions">
                                    <div class="st-dock-legend-card-btn" onclick="focusSlot({{ $cs->id }})" title="View on grid">
                                        <i class="fas fa-eye"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
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
                <input type="text" id="gate_search_input" placeholder="Search for a Booking (Ticket or Vendor)">
            </div>

            <div class="st-flex st-gap-12 st-align-center">
                <button class="st-btn st-btn--primary st-btn--sm" onclick="window.location.href='{{ route('slots.create') }}'">
                    Create Slots
                </button>
            </div>
        </header>

        <!-- Scheduler Grid -->
        <div class="st-dock-scheduler">
            <!-- Grid Header -->
            <div class="st-dock-grid-header">
                <!-- Time Label Corner -->
                <div class="st-dock-col-header st-justify-center">
                    <i class="fa-regular fa-clock st-text--gray-400"></i>
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
            <div class="st-dock-grid-body">
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
                    // Fetch slots with the same filters as the sidebar to ensure consistency
                    $daySlots = \App\Models\Slot::whereDate('planned_start', $paramDate)
                        ->whereNotIn('status', ['cancelled', 'rejected'])
                        ->where(function($q) {
                            $q->whereNull('slot_type')->orWhere('slot_type', 'planned');
                        })
                        ->get();
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

                            // Time Label Logic
                            // Default: Planned Start (ETA) - Calculated End
                            $startTime = $st;
                            $endTime = $et;

                            if ($slot->status === 'completed') {
                                // Use Actual Arrival and Actual Finish
                                $actualStart = $slot->arrival_time ? \Carbon\Carbon::parse($slot->arrival_time) : $st;
                                $actualEnd = $slot->actual_finish ? \Carbon\Carbon::parse($slot->actual_finish) : $et;
                                $timeLabel = $actualStart->format('H:i') . '-' . $actualEnd->format('H:i');
                            } else {
                                $timeLabel = $startTime->format('H:i') . '-' . $endTime->format('H:i');
                            }

                            // Status Colors
                            $bgClass = 'bg-' . $slot->status_badge_color;

                            // Calculate dynamic font sizes based on duration
                            // Flexible scaling:
                            // Very Small (<30m): minimal info (Ticket + Status Icon)
                            // Small (30-50m): Compact (Ticket, Vendor, Status Line)
                            // Normal (>50m): Full info

                            $h = $duration;

                            $paddingClass = 'st-dock-card--normal';
                            if ($h < 35) {
                                $paddingClass = 'st-dock-card--micro';
                            } elseif ($h < 60) {
                                $paddingClass = 'st-dock-card--compact';
                            }

                            $titleClass = $h < 35 ? 'st-text-9' : ($h < 60 ? 'st-text-10' : 'st-text-12');
                            $vendorClass = $h < 35 ? 'st-text-8' : ($h < 60 ? 'st-text-9' : 'st-text-10');
                            $statusClass = $h < 35 ? 'st-text-8' : ($h < 60 ? 'st-text-9' : 'st-text-10');
                        @endphp

                        @php
                            $targetRoute = in_array($slot->status, ['pending_approval', 'pending'])
                                ? route('bookings.show', $slot->id)
                                : route('slots.show', $slot->id);
                        @endphp

                        <!-- Slot Card -->
                        <div id="slot-{{ $slot->id }}" class="st-dock-card {{ $bgClass }} {{ $paddingClass }}"
                             data-top="{{ $minutesFrom7 }}"
                             data-height="{{ $duration }}"
                             ondblclick="window.location.href='{{ $targetRoute }}'"
                             title="{{ $slot->ticket_number }} ({{ $slot->status }}) - {{ $slot->vendor->name ?? '' }}">

                            <!-- Header: Ticket & Time -->
                            <div class="st-dock-card__header">
                                <div class="st-dock-card__title {{ $titleClass }}">
                                    {{ $slot->ticket_number ?? '-' }}
                                </div>
                                <div class="st-dock-card__time {{ $vendorClass }}">
                                    {{ $timeLabel }}
                                </div>
                            </div>

                            <!-- Body: Vendor (Hide if too small) -->
                            @if($h >= 35)
                            <div class="st-dock-card__vendor {{ $vendorClass }} st-mt-auto st-mb-auto">
                                {{ $slot->vendor->name ?? '-' }}

                                @php
                                    $activeStatuses = ['scheduled', 'arrived', 'waiting', 'in_progress', 'completed'];
                                @endphp
                                @if($slot->original_planned_start && !empty($slot->approval_notes) && $h >= 60 && in_array($slot->status, $activeStatuses))
                                <div class="st-dock-approval-note">
                                    <i class="fas fa-sticky-note st-dock-approval-note__icon"></i>
                                    <span class="st-truncate">{{ $slot->approval_notes }}</span>
                                </div>
                                @endif
                            </div>
                            @endif

                            <!-- Footer: Status & Actions -->
                            <div class="st-dock-card__footer">
                                <div class="st-dock-card-cat st-dock-card-cat--tight {{ $statusClass }}">
                                    {{ $slot->status_label ?? $slot->status }}
                                    @if($slot->original_planned_start)
                                        <span class="st-dock-card-cat__note">(RESCHEDULED)</span>
                                    @endif
                                </div>

                                <!-- Dynamic Status Icons -->
                                @if($slot->status === 'scheduled')
                                    <div class="st-dock-icon-circle st-dock-icon-circle--green" onclick="event.stopPropagation(); window.location.href='{{ route('slots.arrival', $slot->id) }}'" title="Click to mark arrival">
                                        <i class="fas fa-sign-in-alt st-text-10"></i>
                                    </div>
                                @elseif($slot->status === 'waiting')
                                    <div class="st-dock-icon-circle st-dock-icon-circle--orange" onclick="event.stopPropagation(); window.location.href='{{ route('slots.start', $slot->id) }}'" title="Click to start">
                                        <i class="fas fa-play st-text-10"></i>
                                    </div>
                                @elseif($slot->status === 'in_progress')
                                    <div class="st-dock-icon-circle st-dock-icon-circle--teal" onclick="event.stopPropagation(); window.location.href='{{ route('slots.complete', $slot->id) }}'" title="Click to complete">
                                        <i class="fas fa-check st-text-10"></i>
                                    </div>
                                @endif

                                @if($slot->status === 'pending_approval')
                                    <div class="st-dock-action-group">
                                        <button type="button" class="st-dock-action-btn st-dock-action-btn--approve" onclick="event.stopPropagation(); openApproveModal({{ $slot->id }}, '{{ $slot->ticket_number }}')" title="Confirm Booking">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <a href="{{ route('bookings.reschedule', $slot->id) }}" class="st-dock-action-btn st-dock-action-btn--reschedule" onclick="event.stopPropagation();" title="Reschedule">
                                            <i class="fas fa-calendar-alt"></i>
                                        </a>
                                        <button type="button" class="st-dock-action-btn st-dock-action-btn--reject" onclick="event.stopPropagation(); openRejectModal({{ $slot->id }}, '{{ $slot->ticket_number }}')" title="Reject Booking">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                @elseif(in_array($slot->status, ['pending']))
                                    @if($h >= 40)
                                    <button class="st-dock-btn-confirm st-dock-btn-confirm--sm" onclick="event.stopPropagation(); window.location.href='{{ route('slots.show', $slot->id) }}'">Act</button>
                                    @else
                                    <button class="st-dock-btn-confirm st-dock-btn-confirm--xs" onclick="event.stopPropagation(); window.location.href='{{ route('slots.show', $slot->id) }}'">!</button>
                                    @endif
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
                    <div class="st-dock-time-line" data-top="{{ $currentPx }}" title="Current Time: {{ $now->format('H:i') }}"></div>
                @endif
            </div>
        </div>
    </main>
</div>

<!-- Custom Confirmation Modal -->
<div id="approveModal" class="st-custom-modal">
    <div class="st-custom-modal-overlay" onclick="closeApproveModal()"></div>
    <div class="st-custom-modal-container">
        <div class="st-custom-modal-header">
            <h3>Confirm Approval</h3>
            <button type="button" class="st-custom-modal-close" onclick="closeApproveModal()">&times;</button>
        </div>
        <form id="approveForm" method="POST" action="">
            @csrf
            <div class="st-custom-modal-body text-center">
                <p>Are you sure you want to approve booking <strong id="modalTicketNumber"></strong>?</p>
            </div>
            <div class="st-custom-modal-footer">
                <button type="submit" class="st-btn st-btn--primary st-btn--approve">Yes, Approve</button>
                <button type="button" class="st-btn st-btn--outline-primary" onclick="closeApproveModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div id="reject-modal" class="st-custom-modal">
    <div class="st-custom-modal-overlay" onclick="closeRejectModal()"></div>
    <div class="st-custom-modal-container">
        <div class="st-custom-modal-header">
            <h3>Reject Booking</h3>
            <button type="button" class="st-custom-modal-close" onclick="closeRejectModal()">&times;</button>
        </div>
        <form method="POST" id="reject-form">
            @csrf
            <div class="st-custom-modal-body">
                <p>Are you sure you want to reject booking <strong id="reject-ticket"></strong>?</p>
                <div class="st-form-group st-form-group--mt-15">
                    <label class="st-label st-label--strong">Reason for Rejection <span class="st-required">*</span></label>
                    <textarea name="reason" class="st-textarea st-textarea--md" rows="3" required placeholder="Please Provide a Reason for Rejection..."></textarea>
                </div>
            </div>
            <div class="st-custom-modal-footer">
                <button type="submit" class="st-btn st-btn--primary st-btn--reject">Reject Booking</button>
                <button type="button" class="st-btn st-btn--outline-primary" onclick="closeRejectModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script type="application/json" id="gates_index_config">{!! json_encode([
    'paramDate' => $paramDate,
    'gatesIndexUrl' => route('gates.index'),
    'bookingsBaseUrl' => url('/bookings'),
]) !!}</script>
@vite(['resources/js/pages/gates-index.js'])
@endpush

