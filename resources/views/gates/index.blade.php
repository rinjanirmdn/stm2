@extends('layouts.app')

@section('title', 'Gates Management - Slot Time Management')
@section('page_title', 'Gates Management')

@section('content')
@php
    $paramDate = request('date_from', date('Y-m-d'));
    // Fetch active slots for the date (exclude cancelled and rejected)
    $daySlots = \App\Models\Slot::with('vendor')
        ->whereDate('planned_start', $paramDate)
        ->whereNotIn('status', ['cancelled', 'rejected'])
        ->get();
    
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



        <!-- Bookings Legend -->
        <div>
            <div class="st-flex-between" style="margin-bottom:10px;">
                <div style="font-weight:600; font-size:12px; color: #4b5563;">Bookings for: <span id="selected_date_display">{{ \Carbon\Carbon::parse($paramDate)->format('d.m.Y') }}</span></div>
                <div class="st-dock-count" style="font-size:12px;">{{ $daySlots->count() }}</div>
            </div>
            
            @php
                // Calculate counts for valuable statuses
                $countPending = $daySlots->whereIn('status', ['pending_approval', 'pending'])->count();
                $countVendor = $daySlots->where('status', 'pending_vendor_confirmation')->count();
                // Count slots that have been rescheduled (have an original plan)
                $countRescheduled = $daySlots->filter(function($slot) {
                    return !empty($slot->original_planned_start);
                })->count();
            @endphp
            <div class="st-dock-legend">
                <!-- Pending Approval -->
                <div class="st-legend-item st-legend-item--pending_approval">
                    <div class="st-dock-legend-indicator">
                        <div class="st-dock-dot"></div>
                        <span>Pending Approval</span>
                    </div>
                    <span class="st-dock-count">{{ $countPending }}</span>
                </div>
                
                <!-- Awaiting Vendor -->
                <div class="st-legend-item st-legend-item--awaiting_vendor">
                    <div class="st-dock-legend-indicator">
                        <div class="st-dock-dot"></div>
                        <span>Awaiting Vendor</span>
                    </div>
                    <span class="st-dock-count">{{ $countVendor }}</span>
                </div>
                
                <!-- Rescheduled -->
                <div class="st-legend-item st-legend-item--rescheduled">
                    <div class="st-dock-legend-indicator">
                        <div class="st-dock-dot"></div>
                        <span>Rescheduled</span>
                    </div>
                    <span class="st-dock-count">{{ $countRescheduled }}</span>
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
            
            <div style="display:flex;gap:12px;align-items:center;">
                <button class="st-btn st-btn--primary st-btn--sm" onclick="window.location.href='{{ route('slots.create') }}'">
                    Create Slots
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
                    // Fetch slots with the same filters as the sidebar to ensure consistency
                    $daySlots = \App\Models\Slot::whereDate('planned_start', $paramDate)
                        ->whereNotIn('status', ['cancelled', 'rejected'])
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
                            
                            // Base styles
                            $cardStyle = "top:{$minutesFrom7}px; height:{$duration}px; display:flex; flex-direction:column; justify-content:space-between; overflow:hidden;";
                            
                            if ($h < 35) {
                                // Micro View (Very short duration)
                                $cardStyle .= "padding: 1px 4px;";
                                $titleSize = "9px";
                                $vendorSize = "8px";
                                $statusSize = "8px";
                            } elseif ($h < 60) {
                                // Compact View
                                $cardStyle .= "padding: 3px 6px;";
                                $titleSize = "10px";
                                $vendorSize = "9px";
                                $statusSize = "9px";
                            } else {
                                // Normal View
                                $cardStyle .= "padding: 6px 8px;";
                                $titleSize = "12px";
                                $vendorSize = "10px";
                                $statusSize = "10px";
                            }
                        @endphp
                        
                        @php
                            $targetRoute = in_array($slot->status, ['pending_approval', 'pending_vendor_confirmation', 'pending'])
                                ? route('bookings.show', $slot->id)
                                : route('slots.show', $slot->id);
                        @endphp
                        
                        <!-- Slot Card -->
                        <div class="st-dock-card {{ $bgClass }}" 
                             style="{{ $cardStyle }}"
                             ondblclick="window.location.href='{{ $targetRoute }}'"
                             title="{{ $slot->ticket_number }} ({{ $slot->status }}) - {{ $slot->vendor->name ?? '' }}">
                            
                            <!-- Header: Ticket & Time -->
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; line-height:1.1;">
                                <div style="font-weight:700; font-size:{{ $titleSize }}; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    {{ $slot->ticket_number ?? '-' }}
                                </div>
                                    <div style="font-size:{{ $vendorSize }}; opacity:0.8; margin-left:4px; white-space:nowrap;">
                                        {{ $timeLabel }}
                                    </div>
                            </div>

                            <!-- Body: Vendor (Hide if too small) -->
                            @if($h >= 35)
                            <div style="font-size:{{ $vendorSize }}; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; opacity:0.9; margin-top: auto; margin-bottom: auto;">
                                {{ $slot->vendor->name ?? '-' }}
                                
                                @php
                                    $activeStatuses = ['scheduled', 'arrived', 'waiting', 'in_progress', 'completed'];
                                @endphp
                                @if($slot->original_planned_start && !empty($slot->approval_notes) && $h >= 60 && in_array($slot->status, $activeStatuses))
                                <div style="font-size: 9px; font-style: italic; opacity: 0.8; margin-top: 1px; color: #fff; font-weight: 400; display: flex; align-items: center; gap: 4px;">
                                    <i class="fas fa-sticky-note" style="font-size: 8px;"></i> 
                                    <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $slot->approval_notes }}</span>
                                </div>
                                @endif
                            </div>
                            @endif

                            <!-- Footer: Status & Actions -->
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:1px;">
                                <div class="st-dock-card-cat" style="font-size:{{ $statusSize }}; font-style:normal; font-weight:600; text-transform:uppercase; letter-spacing:0.3px; line-height:1; opacity: 0.7;">
                                    {{ $slot->status_label ?? $slot->status }}
                                    @if($slot->original_planned_start)
                                        <span style="font-size: 0.8em; margin-left: 4px; opacity: 0.8;">(RESCHEDULED)</span>
                                    @endif
                                </div>

                                @if($slot->status === 'pending_approval')
                                    <div style="display:flex; gap:3px; transform: scale(0.85); transform-origin: right center;">
                                        <button type="button" class="st-dock-action-btn" style="background:#fff; color:#166534; box-shadow:0 1px 2px rgba(0,0,0,0.1);" onclick="event.stopPropagation(); openApproveModal({{ $slot->id }}, '{{ $slot->ticket_number }}')" title="Confirm Booking">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <a href="{{ route('bookings.reschedule', $slot->id) }}" class="st-dock-action-btn" style="background:#fff; color:#ea580c; box-shadow:0 1px 2px rgba(0,0,0,0.1);" onclick="event.stopPropagation();" title="Reschedule">
                                            <i class="fas fa-calendar-alt"></i>
                                        </a>
                                        <button type="button" class="st-dock-action-btn" style="background:#fff; color:#dc2626; box-shadow:0 1px 2px rgba(0,0,0,0.1);" onclick="event.stopPropagation(); openRejectModal({{ $slot->id }}, '{{ $slot->ticket_number }}')" title="Reject Booking">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                @elseif(in_array($slot->status, ['pending', 'pending_vendor_confirmation']))
                                    @if($h >= 40)
                                    <button class="st-dock-btn-confirm" style="font-size:9px; padding:1px 6px; height:auto; min-height:16px;" onclick="event.stopPropagation(); window.location.href='{{ route('slots.show', $slot->id) }}'">Act</button>
                                    @else
                                    <button class="st-dock-btn-confirm" style="font-size:8px; padding:0px 4px; height:14px; min-height:0;" onclick="event.stopPropagation(); window.location.href='{{ route('slots.show', $slot->id) }}'">!</button>
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
                    <div class="st-dock-time-line" style="top:{{ $currentPx }}px;" title="Current Time: {{ $now->format('H:i') }}"></div>
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
                <button type="submit" class="st-btn st-btn--primary" style="background-color: #166534; border-color: #166534;">Yes, Approve</button>
                <button type="button" class="st-btn st-btn--secondary" onclick="closeApproveModal()">Cancel</button>
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
                <div class="st-form-group" style="margin-top: 15px;">
                    <label class="st-label" style="font-weight: 600; display: block; margin-bottom: 5px;">Reason for Rejection <span class="st-required">*</span></label>
                    <textarea name="reason" class="st-textarea" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-family: inherit;" rows="3" required placeholder="Please Provide a Reason for Rejection..."></textarea>
                </div>
            </div>
            <div class="st-custom-modal-footer">
                <button type="submit" class="st-btn st-btn--primary" style="background-color: #dc2626; border-color: #dc2626; color: #fff;">Reject Booking</button>
                <button type="button" class="st-btn st-btn--secondary" onclick="closeRejectModal()">Cancel</button>
            </div>
        </form>
    </div>
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
            defaultDate: "{{ $paramDate }}",
            theme: "light", // ensure light theme
            onChange: function(selectedDates, dateStr, instance) {
                document.getElementById('selected_date_display').innerText = instance.formatDate(selectedDates[0], "d.m.Y");
                // Reload page with new date param
                window.location.href = "{{ route('gates.index') }}?date_from=" + dateStr;
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

function openApproveModal(id, ticket) {
    const modal = document.getElementById('approveModal');
    const ticketSpan = document.getElementById('modalTicketNumber');
    const form = document.getElementById('approveForm');
    
    ticketSpan.innerText = ticket;
    form.action = "{{ url('/bookings') }}/" + id + "/approve";
    modal.classList.add('active');
}

function closeApproveModal() {
    const modal = document.getElementById('approveModal');
    modal.classList.remove('active');
}

function openRejectModal(bookingId, ticketNumber) {
    const modal = document.getElementById('reject-modal');
    document.getElementById('reject-ticket').textContent = ticketNumber;
    document.getElementById('reject-form').action = '/bookings/' + bookingId + '/reject';
    modal.classList.add('active');
}

function closeRejectModal() {
    document.getElementById('reject-modal').classList.remove('active');
}

// Search Logic: Trigger on Enter, Reset on Clear
const searchInput = document.getElementById('gate_search_input');
if (searchInput) {
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            performSearch(this.value);
        }
    });

    searchInput.addEventListener('input', function() {
        if (this.value === '') {
            performSearch('');
        }
    });
}

function performSearch(query) {
    query = query.toLowerCase().trim();
    const cards = document.querySelectorAll('.st-dock-card');
    
    cards.forEach(card => {
        // Find ticket number 
        const ticketElement = card.querySelector('div[style*="font-weight:700"]');
        const ticket = ticketElement ? ticketElement.innerText.toLowerCase() : '';
        
        // Find vendor name
        const vendorElement = card.querySelector('div[style*="opacity:0.9"]');
        const vendor = vendorElement ? vendorElement.innerText.toLowerCase() : '';
        
        if (query === '' || ticket.includes(query) || vendor.includes(query)) {
            card.style.opacity = '1';
            card.style.pointerEvents = 'auto';
            card.style.filter = 'none';
        } else {
            card.style.opacity = '0.1';
            card.style.pointerEvents = 'none';
            card.style.filter = 'grayscale(100%)';
        }
    });
}
</script>
<style>
/* Local override for specific calendar styling to match design if needed */
.flatpickr-calendar.inline {
    margin: 0 auto;
    width: 100% !important;
    max-width: 100% !important;
    box-shadow: none !important;
    transform: scale(0.95);
    transform-origin: top center;
}
</style>
@endpush
