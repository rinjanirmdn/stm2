@extends('layouts.app')

@section('title', 'View Slot - Slot Time Management')
@section('page_title', 'Slot Detail')

@php
    // Clear any old flash messages to prevent confusion
    session()->forget(['error', 'success', 'warning', 'info']);
@endphp

@section('content')
    @php
        $status = (string) ($slot->status ?? '');
        $slotTypeVal = (string) ($slot->slot_type ?? 'planned');
        $hasArrival = !empty($slot->arrival_time);

        $fmt = function ($v) {
            if (empty($v)) return '-';
            try {
                return \Carbon\Carbon::parse((string) $v)->format('d M Y H:i');
            } catch (\Throwable $e) {
                return (string) $v;
            }
        };

        $minutesLabel = function ($minutes) {
            if ($minutes === null) return '-';
            $m = (int) $minutes;
            $h = $m / 60;
            $out = $m . ' min';
            if ($h >= 1) {
                $out .= ' (' . rtrim(rtrim(number_format($h, 2), '0'), '.') . ' h)';
            }
            return $out;
        };

        $plannedDurationMinutes = isset($slot->planned_duration) ? (int) $slot->planned_duration : 0;
        $plannedDurationLabel = $plannedDurationMinutes > 0 ? $minutesLabel($plannedDurationMinutes) : '-';

        $plannedGateLabel = app(\App\Services\SlotService::class)->getGateDisplayName((string) ($slot->warehouse_code ?? ''), (string) ($slot->planned_gate_number ?? ''));
        $actualGateLabel = app(\App\Services\SlotService::class)->getGateDisplayName((string) ($slot->warehouse_code ?? ''), (string) ($slot->actual_gate_number ?? ''));

        $lateDisplay = null;
        if (! $isUnplanned) {
            if (!empty($slot->arrival_time)) {
                try {
                    $p = new \DateTime((string) $slot->planned_start);
                    $p->modify('+15 minutes');
                    $a = new \DateTime((string) $slot->arrival_time);
                    $lateDisplay = $a > $p ? 'late' : 'on_time';
                } catch (\Throwable $e) {
                    $lateDisplay = null;
                }
            } elseif ($status === 'completed') {
                $lateDisplay = !empty($slot->is_late) ? 'late' : 'on_time';
            }
        }

        $blockingRisk = isset($slot->blocking_risk) ? (int) $slot->blocking_risk : 0;
        $blockingLevel = $blockingRisk >= 2 ? 'High' : ($blockingRisk === 1 ? 'Medium' : 'Low');
    @endphp

    <div class="st-card" style="margin-bottom:12px;">
        <div style="padding:12px;">
            <div style="margin-bottom:10px;">
                <span class="st-table__status-badge {{ $isUnplanned ? 'st-status-on-time' : 'st-status-processing' }}">
                    {{ $isUnplanned ? 'Unplanned Slot' : 'Planned Slot' }}
                </span>
            </div>

            <div class="st-row" style="row-gap:14px;">
                <div class="st-col-6">
                    <h2 class="st-card__title" style="margin:0 0 10px;">General Info</h2>

                    <div style="display:grid;grid-template-columns:minmax(140px, 34%) 1fr;column-gap:12px;row-gap:8px;align-items:start;">
                        <div style="font-weight:600;">PO/DO Number</div>
                        <div style="font-size:16px; font-weight:700; color:#2563eb;">
                            {{ $slot->po_number ?? ($slot->po->po_number ?? '-') }}
                        </div>

                        <div style="font-weight:600;">MAT DOC</div>
                        <div>{{ !empty($slot->mat_doc) ? $slot->mat_doc : '-' }}</div>

                        <div style="font-weight:600;">SJ Start Number</div>
                        <div>{{ !empty($slot->sj_start_number) ? $slot->sj_start_number : '-' }}</div>

                        <div style="font-weight:600;">SJ Complete Number</div>
                        <div>{{ !empty($slot->sj_complete_number) ? $slot->sj_complete_number : '-' }}</div>

                        <div style="font-weight:600;">Ticket Number</div>
                        <div>{{ !empty($slot->ticket_number) ? $slot->ticket_number : '-' }}</div>

                        <div style="font-weight:600;">Vendor</div>
                        <div>{{ $slot->vendor_name ?? '-' }}</div>

                        <div style="font-weight:600;">Warehouse</div>
                        <div>{{ $slot->warehouse_name ?? '-' }}</div>

                        <div style="font-weight:600;">Direction</div>
                        <div>{{ strtoupper((string) ($slot->direction ?? '')) }}</div>

                        <div style="font-weight:600;">Truck Details</div>
                        <div>
                            @php
                                $truckParts = [];
                                if (!empty($slot->truck_type)) {
                                    $truckParts[] = 'Jenis: ' . $slot->truck_type;
                                }
                                if (!empty($slot->driver_number)) {
                                    $truckParts[] = 'Driver: ' . $slot->driver_number;
                                }
                                if (!empty($slot->vehicle_number_snap)) {
                                    $truckParts[] = 'No. Mobil: ' . $slot->vehicle_number_snap;
                                }
                            @endphp
                            @if (!empty($truckParts))
                                @foreach ($truckParts as $part)
                                    <div>{{ $part }}</div>
                                @endforeach
                            @else
                                -
                            @endif
                        </div>
                    </div>
                </div>

                <div class="st-col-6">
                    <h2 class="st-card__title" style="margin:0 0 10px;">Planning</h2>

                    <div style="display:grid;grid-template-columns:minmax(140px, 34%) 1fr;column-gap:12px;row-gap:8px;align-items:start;">
                        <div style="font-weight:600;">ETA</div>
                        <div>{{ $fmt($slot->planned_start ?? null) }}</div>

                        <div style="font-weight:600;">Planned Duration</div>
                        <div>{{ $plannedDurationLabel }}</div>

                        <div style="font-weight:600;">Est. Finish</div>
                        <div>{{ $plannedFinish ? $fmt($plannedFinish) : '-' }}</div>

                        <div style="font-weight:600;">Planned Gate</div>
                        <div>{{ !empty($slot->planned_gate_number) ? $plannedGateLabel : '-' }}</div>
                    </div>

                    <div style="height:1px;background:#e5e7eb;margin:12px 0;"></div>

                    <h2 class="st-card__title" style="margin:0 0 10px;">Actual &amp; Status</h2>

                    <div style="display:grid;grid-template-columns:minmax(140px, 34%) 1fr;column-gap:12px;row-gap:8px;align-items:start;">
                        <div style="font-weight:600;">Status</div>
                        <div>{{ strtoupper($status) }}</div>

                        <div style="font-weight:600;">Blocking Risk</div>
                        <div>
                            @if ($isUnplanned || $status === 'cancelled')
                                -
                            @else
                                @if ($blockingRisk >= 2)
                                    <span class="st-table__status-badge st-status-late">High</span>
                                @elseif ($blockingRisk === 1)
                                    <span class="st-table__status-badge st-status-processing">Medium</span>
                                @else
                                    <span class="st-table__status-badge st-status-on-time">Low</span>
                                @endif
                            @endif
                        </div>

                        <div style="font-weight:600;">Late</div>
                        <div>
                            @if ($isUnplanned)
                                -
                            @elseif ($lateDisplay === 'late')
                                Late
                            @elseif ($lateDisplay === 'on_time')
                                On Time
                            @else
                                -
                            @endif
                        </div>

                        @if (!empty($slot->late_reason))
                            <div style="font-weight:600;">Notes</div>
                            <div style="white-space:pre-wrap;">{{ $slot->late_reason }}</div>
                        @endif

                        <div style="font-weight:600;">Arrival Time</div>
                        <div>{{ $fmt($slot->arrival_time ?? null) }}</div>

                        <div style="font-weight:600;">Actual Start</div>
                        <div>{{ $fmt($slot->actual_start ?? null) }}</div>

                        <div style="font-weight:600;">Actual Finish</div>
                        <div>{{ $fmt($slot->actual_finish ?? null) }}</div>

                        <div style="font-weight:600;">Lead Time (Arrival → Start)</div>
                        <div>{{ $leadMinutes !== null ? $minutesLabel($leadMinutes) : '-' }}</div>

                        <div style="font-weight:600;">Process Time (Start → Finish)</div>
                        <div>{{ $processMinutes !== null ? $minutesLabel($processMinutes) : '-' }}</div>

                        <div style="font-weight:600;">Total Lead Time</div>
                        <div>{{ $totalLeadTimeMinutes !== null ? $minutesLabel($totalLeadTimeMinutes) : '-' }}</div>

                        <div style="font-weight:600;">Target Status</div>
                        <div>
                            @if ($targetStatus === 'achieve')
                                <span class="st-table__status-badge st-status-on-time">Achieve</span>
                            @elseif ($targetStatus === 'not_achieve')
                                <span class="st-table__status-badge st-status-late">Not Achieve</span>
                            @else
                                -
                            @endif
                        </div>

                        <div style="font-weight:600;">Actual Gate</div>
                        <div>{{ !empty($slot->actual_gate_number) ? $actualGateLabel : '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (! $isUnplanned && !empty($slot->ticket_number) && $status !== 'cancelled' && $status !== 'completed')
        @unless(optional(auth()->user())->hasRole('Operator'))
        @can('slots.ticket')
        <div style="margin-bottom:12px;display:flex;justify-content:flex-end;">
            <a href="{{ route('slots.ticket', ['slotId' => $slot->id]) }}" class="st-btn st-btn--secondary" onclick="event.preventDefault(); if (window.stPrintTicket) window.stPrintTicket(this.href);">
                Print Ticket
            </a>
        </div>
        @endcan
        @endunless
    @endif

    @if ($logs && count($logs) > 0)
        <div class="st-card" style="margin-bottom:12px;">
            <div class="st-card__header">
                <div>
                    <h2 class="st-card__title">Slot Log</h2>
                </div>
            </div>
            <div class="st-table-wrapper">
                <table class="st-table">
                    <thead>
                        <tr>
                            <th style="width:180px;">Time</th>
                            <th style="width:160px;">Type</th>
                            <th>Detail</th>
                            <th style="width:140px;">User</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            @php
                                $type = (string) ($log->activity_type ?? '');
                                $typeLabel = $type !== '' ? ucwords(str_replace('_', ' ', $type)) : '-';
                            @endphp
                            <tr>
                                <td>{{ $fmt($log->created_at ?? null) }}</td>
                                <td>{{ $typeLabel }}</td>
                                <td>{{ $log->description ?? '-' }}</td>
                                <td>{{ $log->username ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div style="margin-bottom:12px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        @if($status === 'pending_approval')
            @if(auth()->user()->hasRole(['Admin', 'admin', 'Super Administrator', 'Section Head']))
                <form action="{{ route('slots.approve', $slot->id) }}" method="POST" style="display:inline-block;">
                    @csrf
                    @method('POST')
                    <button type="submit" class="st-btn st-btn--primary" onclick="return confirm('Are you sure you want to Approve this booking?')">
                        <i class="fa-solid fa-check" style="margin-right:6px;"></i> Approve
                    </button>
                </form>

                <button type="button" class="st-btn st-btn--danger" onclick="document.getElementById('reject-dialog').style.display='flex'">
                    <i class="fa-solid fa-xmark" style="margin-right:6px;"></i> Reject
                </button>
            @else
                <button type="button" class="st-btn st-btn--secondary" disabled>Waiting for Approval</button>
            @endif
        @endif

        @if (! $isUnplanned)
            @if (in_array($status, ['scheduled'], true))
                <a href="{{ route('slots.arrival', ['slotId' => $slot->id]) }}" class="st-btn st-btn--secondary">Arrival</a>
            @elseif ($status === 'waiting')
                <a href="{{ route('slots.start', ['slotId' => $slot->id]) }}" class="st-btn st-btn--primary">Start Slot</a>
            @elseif ($status === 'in_progress')
                <a href="{{ route('slots.complete', ['slotId' => $slot->id]) }}" class="st-btn st-btn--primary">Complete Slot</a>
            @endif
        @else
            @if ($status === 'waiting')
                <a href="{{ route('slots.start', ['slotId' => $slot->id]) }}" class="st-btn st-btn--primary">Start Slot</a>
            @elseif ($status === 'in_progress')
                <a href="{{ route('slots.complete', ['slotId' => $slot->id]) }}" class="st-btn st-btn--primary">Complete Slot</a>
            @endif
        @endif

        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('slots.index') }}" class="st-btn st-btn--secondary">Back</a>
    </div>

    <!-- Reject Dialog -->
    <div id="reject-dialog" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;">
        <div class="st-card" style="width:100%; max-width:400px; padding:20px;">
            <h3 class="st-card__title">Reject Booking</h3>
            <form action="{{ route('slots.reject', $slot->id) }}" method="POST">
                @csrf
                <div class="st-form-field">
                    <label class="st-label">Reason</label>
                    <textarea name="reason" class="st-input" rows="3" required placeholder="Why is this booking rejected?"></textarea>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
                    <button type="button" class="st-btn st-btn--secondary" onclick="document.getElementById('reject-dialog').style.display='none'">Cancel</button>
                    <button type="submit" class="st-btn st-btn--danger">Confirm Reject</button>
                </div>
            </form>
        </div>
    </div>
@endsection
