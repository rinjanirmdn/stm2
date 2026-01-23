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
        if ($status === 'rejected') {
            $status = 'cancelled';
        }
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
            $out = $m . ' Min';
            if ($h >= 1) {
                $out .= ' (' . rtrim(rtrim(number_format($h, 2), '0'), '.') . ' Hours)';
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

    <div class="st-card st-mb-3">
        <div class="st-p-3">
            <div class="st-mb-2">
                <span class="st-table__status-badge {{ $isUnplanned ? 'st-status-on-time' : 'st-status-processing' }}">
                    {{ $isUnplanned ? 'Unplanned Slot' : 'Planned Slot' }}
                </span>
            </div>

            <div class="st-row" style="row-gap:14px;">
                <div class="st-col-6">
                    <h2 class="st-card__title st-mb-2">General Info</h2>

                    <div class="st-detail-grid">
                        <div class="st-detail-item">
                            <div class="st-detail-label">PO/DO Number</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value st-detail-value--primary">{{ $slot->po_number ?? ($slot->po->po_number ?? '-') }}</div>
                        </div>

                        <div class="st-detail-item">
                            <div class="st-detail-label">MAT DOC</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ !empty($slot->mat_doc) ? $slot->mat_doc : '-' }}</div>
                        </div>

                        <div class="st-detail-item">
                            <div class="st-detail-label">SJ Start Number</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ !empty($slot->sj_start_number) ? $slot->sj_start_number : '-' }}</div>
                        </div>

                        <div class="st-detail-item">
                            <div class="st-detail-label">SJ Complete Number</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ !empty($slot->sj_complete_number) ? $slot->sj_complete_number : '-' }}</div>
                        </div>

                        <div class="st-detail-item">
                            <div class="st-detail-label">Ticket Number</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ !empty($slot->ticket_number) ? $slot->ticket_number : '-' }}</div>
                        </div>

                        <div class="st-detail-item">
                            <div class="st-detail-label">COA</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">
                            @if(!empty($slot->coa_path))
                                <a href="{{ asset('storage/' . $slot->coa_path) }}" target="_blank" rel="noopener">View / Download</a>
                            @else
                                -
                            @endif
                            </div>
                        </div>

                        <div class="st-detail-item">
                            <div class="st-detail-label">Surat Jalan</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">
                            @if(!empty($slot->surat_jalan_path))
                                <a href="{{ asset('storage/' . $slot->surat_jalan_path) }}" target="_blank" rel="noopener">View / Download</a>
                            @else
                                -
                            @endif
                            </div>
                        </div>

                        <div class="st-detail-item">
                            <div class="st-detail-label">Vendor</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ $slot->vendor_name ?? '-' }}</div>
                        </div>

                        <div class="st-detail-item">
                            <div class="st-detail-label">Warehouse</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ $slot->warehouse_name ?? '-' }}</div>
                        </div>

                        <div class="st-detail-item">
                            <div class="st-detail-label">Direction</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">
                            @php $dir = strtolower($slot->direction ?? ''); @endphp
                            @if($dir === 'inbound')
                                <span class="st-badge-modern st-badge-modern--inbound">
                                    Inbound
                                </span>
                            @elseif($dir === 'outbound')
                                <span class="st-badge-modern st-badge-modern--outbound">
                                    Outbound
                                </span>
                            @else
                                {{ strtoupper((string) ($slot->direction ?? '')) }}
                            @endif
                            </div>
                        </div>

                        <div class="st-detail-item">
                            <div class="st-detail-label">Truck Details</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">
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
                </div>

                <div class="st-col-6">
                    <h2 class="st-card__title st-mb-2">Planning</h2>

                    <div class="st-detail-grid">
                        <div class="st-detail-item">
                            <div class="st-detail-label">ETA</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ $fmt($slot->planned_start ?? null) }}</div>
                        </div>

                        <div class="st-detail-item">
                            <div class="st-detail-label">Planned Duration</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ $plannedDurationLabel }}</div>
                        </div>

                        <div class="st-detail-item">
                            <div class="st-detail-label">Est. Finish</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ $plannedFinish ? $fmt($plannedFinish) : '-' }}</div>
                        </div>

                        <div class="st-detail-item">
                            <div class="st-detail-label">Planned Gate</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ !empty($slot->planned_gate_number) ? $plannedGateLabel : '-' }}</div>
                        </div>
                    </div>

                    <div class="st-detail-divider"></div>

                    <h2 class="st-card__title st-mb-2">Actual &amp; Status</h2>

                    <div class="st-detail-grid">
                        <div class="st-detail-item">
                            <div class="st-detail-label">Status</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ strtoupper($status) }}</div>
                        </div>

                        <div class="st-detail-item">
                            <div class="st-detail-label">Blocking Risk</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">
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
                        </div>

                        <div class="st-detail-item">
                            <div class="st-detail-label">Late</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">
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
                        </div>

                        @if (!empty($slot->late_reason))
                            <div class="st-detail-item">
                                <div class="st-detail-label">Notes</div>
                                <div class="st-detail-colon">:</div>
                                <div class="st-detail-value st-detail-value--prewrap">{{ $slot->late_reason }}</div>
                            </div>
                        @endif

                        <div class="st-detail-item">
                            <div class="st-detail-label">Arrival Time</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ $fmt($slot->arrival_time ?? null) }}</div>
                        </div>

                        <div class="st-detail-item">
                            <div class="st-detail-label">Actual Start</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ $fmt($slot->actual_start ?? null) }}</div>
                        </div>

                        <div class="st-detail-item">
                            <div class="st-detail-label">Actual Finish</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ $fmt($slot->actual_finish ?? null) }}</div>
                        </div>

                        <div class="st-detail-item">
                            <div class="st-detail-label">Waiting Time (Arrival → Start)</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ $leadMinutes !== null ? $minutesLabel($leadMinutes) : '-' }}</div>
                        </div>

                        <div class="st-detail-item">
                            <div class="st-detail-label">Process Time (Start → Finish)</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ $processMinutes !== null ? $minutesLabel($processMinutes) : '-' }}</div>
                        </div>

                        <div class="st-detail-item">
                            <div class="st-detail-label">Total Lead Time</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ $totalLeadTimeMinutes !== null ? $minutesLabel($totalLeadTimeMinutes) : '-' }}</div>
                        </div>

                        <div class="st-detail-item">
                            <div class="st-detail-label">Target Status</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">
                            @if ($targetStatus === 'achieve')
                                <span class="st-table__status-badge st-status-on-time">Achieve</span>
                            @elseif ($targetStatus === 'not_achieve')
                                <span class="st-table__status-badge st-status-late">Not Achieve</span>
                            @else
                                -
                            @endif
                            </div>
                        </div>

                        <div class="st-detail-item">
                            <div class="st-detail-label">Actual Gate</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ !empty($slot->actual_gate_number) ? $actualGateLabel : '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (! $isUnplanned && !empty($slot->ticket_number) && in_array($status, ['scheduled', 'waiting', 'in_progress'], true))
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
                @can('bookings.reschedule')
                @if (!empty($slot->requested_by) && in_array($status, ['pending_approval', 'pending_vendor_confirmation', 'scheduled'], true))
                    <a href="{{ route('bookings.reschedule', ['id' => $slot->id]) }}" class="st-btn st-btn--secondary">
                        <i class="fa-solid fa-calendar" style="margin-right:6px;"></i> Reschedule
                    </a>
                @endif
                @endcan

                <form action="{{ route('slots.approve', $slot->id) }}" method="POST" style="display:inline-block;">
                    @csrf
                    @method('POST')
                    <button type="submit" class="st-btn st-btn--primary" onclick="return confirm('Are You Sure You Want to Approve This Booking?')">
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

        @if($status === 'pending_vendor_confirmation')
            @can('bookings.reschedule')
            @if (!empty($slot->requested_by))
                <a href="{{ route('bookings.reschedule', ['id' => $slot->id]) }}" class="st-btn st-btn--secondary">
                    <i class="fa-solid fa-calendar" style="margin-right:6px;"></i> Reschedule
                </a>
            @endif
            @endcan
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
                    <textarea name="reason" class="st-input" rows="3" required placeholder="Why Is This Booking Rejected?"></textarea>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
                    <button type="button" class="st-btn st-btn--secondary" onclick="document.getElementById('reject-dialog').style.display='none'">Cancel</button>
                    <button type="submit" class="st-btn st-btn--danger">Confirm Reject</button>
                </div>
            </form>
        </div>
    </div>
@endsection
