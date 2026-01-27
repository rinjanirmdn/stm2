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
                <span class="st-table__status-badge {{ $isUnplanned ? 'st-status-unplanned' : 'st-status-in_progress' }}">
                    {{ $isUnplanned ? 'Unplanned' : 'Planned' }}
                </span>
            </div>

            <div class="st-row" style="row-gap:14px;">
                <div class="st-col-6">
                    <h2 class="st-card__title st-mb-2" style="font-size:14px;">Planning</h2>

                    <div class="st-detail-grid" style="font-size:12px;">
                        <div class="st-detail-item" style="padding:6px 8px;">
                            <div class="st-detail-label" style="font-size:11px;">ETA</div>
                            <div class="st-detail-colon" style="font-size:11px;">:</div>
                            <div class="st-detail-value" style="font-size:11px;">{{ $fmt($slot->planned_start ?? null) }}</div>
                        </div>

                        <div class="st-detail-item" style="padding:6px 8px;">
                            <div class="st-detail-label" style="font-size:11px;">Planned Duration</div>
                            <div class="st-detail-colon" style="font-size:11px;">:</div>
                            <div class="st-detail-value" style="font-size:11px;">{{ $plannedDurationLabel }}</div>
                        </div>

                        <div class="st-detail-item" style="padding:6px 8px;">
                            <div class="st-detail-label" style="font-size:11px;">Est. Finish</div>
                            <div class="st-detail-colon" style="font-size:11px;">:</div>
                            <div class="st-detail-value" style="font-size:11px;">{{ $plannedFinish ? $fmt($plannedFinish) : '-' }}</div>
                        </div>

                        <div class="st-detail-item" style="padding:6px 8px;">
                            <div class="st-detail-label" style="font-size:11px;">Planned Gate</div>
                            <div class="st-detail-colon" style="font-size:11px;">:</div>
                            <div class="st-detail-value" style="font-size:11px;">{{ !empty($slot->planned_gate_number) ? $plannedGateLabel : '-' }}</div>
                        </div>
                    </div>

                    <div class="st-detail-divider" style="margin:8px 0;"></div>

                    <h2 class="st-card__title st-mb-2" style="font-size:14px;">Actual &amp; Status</h2>

                    <div class="st-detail-grid" style="font-size:12px;">
                        <div class="st-detail-item" style="padding:6px 8px;">
                            <div class="st-detail-label" style="font-size:11px;">Status</div>
                            <div class="st-detail-colon" style="font-size:11px;">:</div>
                            <div class="st-detail-value" style="font-size:11px;">{{ strtoupper($status) }}</div>
                        </div>

                        <div class="st-detail-item" style="padding:6px 8px;">
                            <div class="st-detail-label" style="font-size:11px;">Blocking Risk</div>
                            <div class="st-detail-colon" style="font-size:11px;">:</div>
                            <div class="st-detail-value" style="font-size:11px;">
                            @if ($isUnplanned || $status === 'cancelled')
                                -
                            @else
                                @if ($blockingRisk >= 2)
                                    <span class="st-table__status-badge st-status-late" style="font-size:10px;">High</span>
                                @elseif ($blockingRisk === 1)
                                    <span class="st-table__status-badge st-status-processing" style="font-size:10px;">Medium</span>
                                @else
                                    <span class="st-table__status-badge st-status-on-time" style="font-size:10px;">Low</span>
                                @endif
                            @endif
                            </div>
                        </div>

                        <div class="st-detail-item" style="padding:6px 8px;">
                            <div class="st-detail-label" style="font-size:11px;">Late</div>
                            <div class="st-detail-colon" style="font-size:11px;">:</div>
                            <div class="st-detail-value" style="font-size:11px;">
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
                            <div class="st-detail-item" style="padding:6px 8px;">
                                <div class="st-detail-label" style="font-size:11px;">Notes</div>
                                <div class="st-detail-colon" style="font-size:11px;">:</div>
                                <div class="st-detail-value st-detail-value--prewrap" style="font-size:11px;">{{ $slot->late_reason }}</div>
                            </div>
                        @endif

                        <div class="st-detail-item" style="padding:6px 8px;">
                            <div class="st-detail-label" style="font-size:11px;">Arrival Time</div>
                            <div class="st-detail-colon" style="font-size:11px;">:</div>
                            <div class="st-detail-value" style="font-size:11px;">{{ $fmt($slot->arrival_time ?? null) }}</div>
                        </div>

                        <div class="st-detail-item" style="padding:6px 8px;">
                            <div class="st-detail-label" style="font-size:11px;">Actual Start</div>
                            <div class="st-detail-colon" style="font-size:11px;">:</div>
                            <div class="st-detail-value" style="font-size:11px;">{{ $fmt($slot->actual_start ?? null) }}</div>
                        </div>

                        <div class="st-detail-item" style="padding:6px 8px;">
                            <div class="st-detail-label" style="font-size:11px;">Actual Finish</div>
                            <div class="st-detail-colon" style="font-size:11px;">:</div>
                            <div class="st-detail-value" style="font-size:11px;">{{ $fmt($slot->actual_finish ?? null) }}</div>
                        </div>

                        <div class="st-detail-item" style="padding:6px 8px;">
                            <div class="st-detail-label" style="font-size:11px;">Waiting Time (Arrival → Start)</div>
                            <div class="st-detail-colon" style="font-size:11px;">:</div>
                            <div class="st-detail-value" style="font-size:11px;">{{ $leadMinutes !== null ? $minutesLabel($leadMinutes) : '-' }}</div>
                        </div>

                        <div class="st-detail-item" style="padding:6px 8px;">
                            <div class="st-detail-label" style="font-size:11px;">Process Time (Start → Finish)</div>
                            <div class="st-detail-colon" style="font-size:11px;">:</div>
                            <div class="st-detail-value" style="font-size:11px;">{{ $processMinutes !== null ? $minutesLabel($processMinutes) : '-' }}</div>
                        </div>

                        <div class="st-detail-item" style="padding:6px 8px;">
                            <div class="st-detail-label" style="font-size:11px;">Total Lead Time</div>
                            <div class="st-detail-colon" style="font-size:11px;">:</div>
                            <div class="st-detail-value" style="font-size:11px;">{{ $totalLeadTimeMinutes !== null ? $minutesLabel($totalLeadTimeMinutes) : '-' }}</div>
                        </div>

                        <div class="st-detail-item" style="padding:6px 8px;">
                            <div class="st-detail-label" style="font-size:11px;">Target Status</div>
                            <div class="st-detail-colon" style="font-size:11px;">:</div>
                            <div class="st-detail-value" style="font-size:11px;">
                            @if ($targetStatus === 'achieve')
                                <span class="st-table__status-badge st-status-on-time" style="font-size:10px;">Achieve</span>
                            @elseif ($targetStatus === 'not_achieve')
                                <span class="st-table__status-badge st-status-late" style="font-size:10px;">Not Achieve</span>
                            @else
                                -
                            @endif
                            </div>
                        </div>

                        <div class="st-detail-item" style="padding:6px 8px;">
                            <div class="st-detail-label" style="font-size:11px;">Actual Gate</div>
                            <div class="st-detail-colon" style="font-size:11px;">:</div>
                            <div class="st-detail-value" style="font-size:11px;">{{ !empty($slot->actual_gate_number) ? $actualGateLabel : '-' }}</div>
                        </div>
                    </div>
                </div>

                <div class="st-col-6">
                    <h2 class="st-card__title st-mb-2" style="font-size:14px;">General Info</h2>

                    <div class="st-detail-grid" style="font-size:12px;">
                        <div class="st-detail-item" style="padding:6px 8px;">
                            <div class="st-detail-label" style="font-size:11px;">PO/DO Number</div>
                            <div class="st-detail-colon" style="font-size:11px;">:</div>
                            <div class="st-detail-value st-detail-value--primary" style="font-size:12px;">{{ $slot->po_number ?? ($slot->po->po_number ?? '-') }}</div>
                        </div>

                        <div class="st-detail-item" style="padding:6px 8px;">
                            <div class="st-detail-label" style="font-size:11px;">MAT DOC</div>
                            <div class="st-detail-colon" style="font-size:11px;">:</div>
                            <div class="st-detail-value" style="font-size:11px;">{{ !empty($slot->mat_doc) ? $slot->mat_doc : '-' }}</div>
                        </div>

                        <div class="st-detail-item" style="padding:6px 8px;">
                            <div class="st-detail-label" style="font-size:11px;">SJ Start Number</div>
                            <div class="st-detail-colon" style="font-size:11px;">:</div>
                            <div class="st-detail-value" style="font-size:11px;">{{ !empty($slot->sj_start_number) ? $slot->sj_start_number : '-' }}</div>
                        </div>

                        <div class="st-detail-item" style="padding:6px 8px;">
                            <div class="st-detail-label" style="font-size:11px;">SJ Complete Number</div>
                            <div class="st-detail-colon" style="font-size:11px;">:</div>
                            <div class="st-detail-value" style="font-size:11px;">{{ !empty($slot->sj_complete_number) ? $slot->sj_complete_number : '-' }}</div>
                        </div>

                        <div class="st-detail-item" style="padding:6px 8px;">
                            <div class="st-detail-label" style="font-size:11px;">Ticket Number</div>
                            <div class="st-detail-colon" style="font-size:11px;">:</div>
                            <div class="st-detail-value" style="font-size:11px;">{{ !empty($slot->ticket_number) ? $slot->ticket_number : '-' }}</div>
                        </div>

                        <div class="st-detail-item" style="padding:6px 8px;">
                            <div class="st-detail-label" style="font-size:11px;">COA</div>
                            <div class="st-detail-colon" style="font-size:11px;">:</div>
                            <div class="st-detail-value" style="font-size:11px;">
                            @if(!empty($slot->coa_path))
                                <a href="{{ asset('storage/' . $slot->coa_path) }}" target="_blank" rel="noopener" style="font-size:10px;color:var(--primary);text-decoration:underline;cursor:pointer;">
                                    <i class="fa-solid fa-file-pdf" style="margin-right:4px;"></i>View / Download
                                </a>
                            @else
                                -
                            @endif
                            </div>
                        </div>

                        <div class="st-detail-item" style="padding:6px 8px;">
                            <div class="st-detail-label" style="font-size:11px;">Surat Jalan</div>
                            <div class="st-detail-colon" style="font-size:11px;">:</div>
                            <div class="st-detail-value" style="font-size:11px;">
                            @if(!empty($slot->surat_jalan_path))
                                <a href="{{ asset('storage/' . $slot->surat_jalan_path) }}" target="_blank" rel="noopener" style="font-size:10px;color:var(--primary);text-decoration:underline;cursor:pointer;">
                                    <i class="fa-solid fa-file-pdf" style="margin-right:4px;"></i>View / Download
                                </a>
                            @else
                                -
                            @endif
                            </div>
                        </div>

                        <div class="st-detail-item" style="padding:6px 8px;">
                            <div class="st-detail-label" style="font-size:11px;">Vendor</div>
                            <div class="st-detail-colon" style="font-size:11px;">:</div>
                            <div class="st-detail-value" style="font-size:11px;">{{ $slot->vendor_name ?? '-' }}</div>
                        </div>

                        <div class="st-detail-item" style="padding:6px 8px;">
                            <div class="st-detail-label" style="font-size:11px;">Warehouse</div>
                            <div class="st-detail-colon" style="font-size:11px;">:</div>
                            <div class="st-detail-value" style="font-size:11px;">{{ $slot->warehouse_name ?? '-' }}</div>
                        </div>

                        <div class="st-detail-item" style="padding:6px 8px;">
                            <div class="st-detail-label" style="font-size:11px;">Direction</div>
                            <div class="st-detail-colon" style="font-size:11px;">:</div>
                            <div class="st-detail-value" style="font-size:11px;">
                            @php $dir = strtolower($slot->direction ?? ''); @endphp
                            @if($dir === 'inbound')
                                <span class="st-badge-modern st-badge-modern--inbound" style="font-size:10px;">
                                    Inbound
                                </span>
                            @elseif($dir === 'outbound')
                                <span class="st-badge-modern st-badge-modern--outbound" style="font-size:10px;">
                                    Outbound
                                </span>
                            @else
                                {{ strtoupper((string) ($slot->direction ?? '')) }}
                            @endif
                            </div>
                        </div>

                        <div class="st-detail-item" style="padding:6px 8px;">
                            <div class="st-detail-label" style="font-size:11px;">Truck Details</div>
                            <div class="st-detail-colon" style="font-size:11px;">:</div>
                            <div class="st-detail-value" style="font-size:11px;">
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
                                    <div style="font-size:10px;">{{ $part }}</div>
                                @endforeach
                            @else
                                -
                            @endif
                            </div>
                        </div>
                    </div>

                    <div class="st-detail-divider" style="margin:10px 0;"></div>

                    <h2 class="st-card__title st-mb-2" style="font-size:14px;">Item &amp; Qty (Slot)</h2>

                    @if (!empty($slotItems) && $slotItems->count() > 0)
                        <div class="st-table-wrapper" style="margin-top:6px;">
                            <table class="st-table" style="font-size:12px;">
                                <thead>
                                    <tr>
                                        <th style="width:70px;">Item</th>
                                        <th>Material</th>
                                        <th style="width:120px;text-align:right;">Qty</th>
                                        <th style="width:90px;">UOM</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($slotItems as $item)
                                        <tr>
                                            <td><strong>{{ $item->item_no }}</strong></td>
                                            <td>{{ $item->material_code ?? '-' }}{{ $item->material_name ? ' - ' . $item->material_name : '' }}</td>
                                            <td style="text-align:right;">{{ number_format((float) ($item->qty_booked ?? 0), 3) }}</td>
                                            <td>{{ $item->uom ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="st-text--small st-text--muted">Tidak ada item detail pada slot ini.</div>
                    @endif
                </div>
            </div>

            <div style="margin-bottom:8px;display:flex;gap:6px;flex-wrap:wrap;align-items:center;justify-content:flex-end;">
                @if (! $isUnplanned && !empty($slot->ticket_number) && in_array($status, ['scheduled', 'waiting', 'in_progress'], true))
                    @unless(optional(auth()->user())->hasRole('Operator'))
                    @can('slots.ticket')
                    <a href="{{ route('slots.ticket', ['slotId' => $slot->id]) }}" class="st-btn st-btn--primary" style="font-size:11px;padding:4px 8px;" onclick="event.preventDefault(); if (window.stPrintTicket) window.stPrintTicket(this.href);">
                        Print Ticket
                    </a>
                    @endcan
                    @endunless
                @endif

                @if($status === 'pending_approval')
                    @if(auth()->user()->hasRole(['Admin', 'admin', 'Super Administrator', 'Section Head']))
                        @can('bookings.reschedule')
                        @if (!empty($slot->requested_by) && in_array($status, ['pending_approval', 'scheduled'], true))
                            <a href="{{ route('bookings.reschedule', ['id' => $slot->id]) }}" class="st-btn" style="background:transparent;color:var(--primary);border:1px solid var(--primary);" style="font-size:11px;padding:4px 8px;">
                                <i class="fa-solid fa-calendar" style="margin-right:4px;"></i> Reschedule
                            </a>
                        @endif
                        @endcan

                        <form action="{{ route('slots.approve', $slot->id) }}" method="POST" style="display:inline-block;">
                            @csrf
                            @method('POST')
                            <button type="submit" class="st-btn st-btn--primary" style="font-size:11px;padding:4px 8px;" onclick="return confirm('Are You Sure You Want to Approve This Booking?')">
                                <i class="fa-solid fa-check" style="margin-right:4px;"></i> Approve
                            </button>
                        </form>

                        <button type="button" class="st-btn st-btn--danger" style="font-size:11px;padding:4px 8px;" onclick="document.getElementById('reject-dialog').style.display='flex'">
                            <i class="fa-solid fa-xmark" style="margin-right:4px;"></i> Reject
                        </button>
                    @else
                        <button type="button" class="st-btn" style="background:transparent;color:var(--primary);border:1px solid var(--primary);" style="font-size:11px;padding:4px 8px;" disabled>Waiting for Approval</button>
                    @endif
                @endif

                @if (! $isUnplanned)
                    @if (in_array($status, ['scheduled'], true))
                        <a href="{{ route('slots.arrival', ['slotId' => $slot->id]) }}" class="st-btn" style="font-size:11px;padding:4px 8px;background:transparent;color:var(--primary);border:1px solid var(--primary);">Arrival</a>
                    @elseif ($status === 'waiting')
                        <a href="{{ route('slots.start', ['slotId' => $slot->id]) }}" class="st-btn st-btn--primary" style="font-size:11px;padding:4px 8px;">Start Slot</a>
                    @elseif ($status === 'in_progress')
                        <a href="{{ route('slots.complete', ['slotId' => $slot->id]) }}" class="st-btn st-btn--primary" style="font-size:11px;padding:4px 8px;">Complete Slot</a>
                    @endif
                @else
                    @if ($status === 'waiting')
                        <a href="{{ route('slots.start', ['slotId' => $slot->id]) }}" class="st-btn st-btn--primary" style="font-size:11px;padding:4px 8px;">Start Slot</a>
                    @elseif ($status === 'in_progress')
                        <a href="{{ route('slots.complete', ['slotId' => $slot->id]) }}" class="st-btn st-btn--primary" style="font-size:11px;padding:4px 8px;">Complete Slot</a>
                    @endif
                @endif

                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('slots.index') }}" class="st-btn" style="background:transparent;color:var(--primary);border:1px solid var(--primary);" style="font-size:11px;padding:4px 8px;">Back</a>
            </div>
        </div>
    </div>

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
                    <button type="button" class="st-btn" style="background:transparent;color:var(--primary);border:1px solid var(--primary);" onclick="document.getElementById('reject-dialog').style.display='none'">Cancel</button>
                    <button type="submit" class="st-btn st-btn--danger">Confirm Reject</button>
                </div>
            </form>
        </div>
    </div>
@endsection
