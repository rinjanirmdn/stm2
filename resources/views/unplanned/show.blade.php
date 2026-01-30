@extends('layouts.app')

@section('title', 'View Slot - Slot Time Management')
@section('page_title', 'Unplanned Details')

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
                    $lateDisplay = $a > $p ? 'Late' : 'On Time';
                } catch (\Throwable $e) {
                    $lateDisplay = null;
                }
            } elseif ($status === 'completed') {
                $lateDisplay = !empty($slot->is_late) ? 'Late' : 'On Time';
            }
        }

        $blockingRisk = isset($slot->blocking_risk) ? (int) $slot->blocking_risk : 0;
        $blockingLevel = $blockingRisk >= 2 ? 'High' : ($blockingRisk === 1 ? 'Medium' : 'Low');
    @endphp

    <div class="st-card st-mb-12">
        <div class="st-p-12">
            <div class="st-mb-10">
                <span class="st-table__status-badge {{ $isUnplanned ? 'st-status-on-time' : 'st-status-processing' }}">
                    {{ $isUnplanned ? 'Unplanned Slot' : 'Planned Slot' }}
                </span>
            </div>

            <div class="st-row st-row-gap-14">
                <div class="st-col-6">
                    <h2 class="st-card__title st-mb-10">General Info</h2>

                    <div class="st-info-grid">
                        <div class="st-font-semibold">PO/DO Number</div>
                        <div>{{ $slot->truck_number ?? '-' }}</div>

                        <div class="st-font-semibold">MAT DOC</div>
                        <div>{{ !empty($slot->mat_doc) ? $slot->mat_doc : '-' }}</div>

                        <div class="st-font-semibold">Ticket Number</div>
                        <div>{{ !empty($slot->ticket_number) ? $slot->ticket_number : '-' }}</div>

                        <div class="st-font-semibold">COA</div>
                        <div>
                            @if(!empty($slot->coa_path))
                                <a href="{{ asset('storage/' . $slot->coa_path) }}" target="_blank" rel="noopener">View / Download</a>
                            @else
                                -
                            @endif
                        </div>

                        <div class="st-font-semibold">Vendor</div>
                        <div>{{ $slot->vendor_name ?? '-' }}</div>

                        <div class="st-font-semibold">Warehouse</div>
                        <div>{{ $slot->warehouse_name ?? '-' }}</div>

                        <div class="st-font-semibold">Direction</div>
                        <div>{{ strtoupper((string) ($slot->direction ?? '')) }}</div>

                        <div class="st-font-semibold">Truck</div>
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
                    <h2 class="st-card__title st-mb-10">Planning</h2>

                    <div class="st-info-grid">
                        <div class="st-font-semibold">ETA</div>
                        <div>{{ $fmt($slot->planned_start ?? null) }}</div>

                        <div class="st-font-semibold">Planned Duration</div>
                        <div>{{ $plannedDurationLabel }}</div>

                        <div class="st-font-semibold">Est. Finish</div>
                        <div>{{ $plannedFinish ? $fmt($plannedFinish) : '-' }}</div>

                        <div class="st-font-semibold">Planned Gate</div>
                        <div>{{ !empty($slot->planned_gate_number) ? $plannedGateLabel : '-' }}</div>
                    </div>

                    <div class="st-divider--my-12"></div>

                    <h2 class="st-card__title st-mb-10">Actual &amp; Status</h2>

                    <div class="st-info-grid">
                        <div class="st-font-semibold">Status</div>
                        <div>{{ strtoupper($status) }}</div>

                        <div class="st-font-semibold">Blocking Risk</div>
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

                        <div class="st-font-semibold">Late</div>
                        <div>
                            @if ($isUnplanned)
                                -
                            @elseif ($lateDisplay === 'Late')
                                Late
                            @elseif ($lateDisplay === 'On Time')
                                On Time
                            @else
                                -
                            @endif
                        </div>

                        @if (!empty($slot->late_reason))
                            <div class="st-font-semibold">Notes</div>
                            <div class="st-prewrap">{{ $slot->late_reason }}</div>
                        @endif

                        <div class="st-font-semibold">Arrival Time</div>
                        <div>{{ $fmt($slot->arrival_time ?? null) }}</div>

                        <div class="st-font-semibold">Actual Start</div>
                        <div>{{ $fmt($slot->actual_start ?? null) }}</div>

                        <div class="st-font-semibold">Actual Finish</div>
                        <div>{{ $fmt($slot->actual_finish ?? null) }}</div>

                        <div class="st-font-semibold">Lead Time (Arrival → Start)</div>
                        <div>{{ $leadMinutes !== null ? $minutesLabel($leadMinutes) : '-' }}</div>

                        <div class="st-font-semibold">Process Time (Start → Finish)</div>
                        <div>{{ $processMinutes !== null ? $minutesLabel($processMinutes) : '-' }}</div>

                        <div class="st-font-semibold">Actual Gate</div>
                        <div>{{ !empty($slot->actual_gate_number) ? $actualGateLabel : '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (! $isUnplanned && !empty($slot->ticket_number) && in_array($status, ['scheduled', 'waiting', 'in_progress'], true))
        @unless(optional(auth()->user())->hasRole('Operator'))
        @can('slots.ticket')
        <div class="st-form-actions st-justify-end st-mb-12">
            <a href="{{ route('slots.ticket', ['slotId' => $slot->id]) }}" class="st-btn st-btn--outline-primary" onclick="event.preventDefault(); if (window.stPrintTicket) window.stPrintTicket(this.href);">
                Print Ticket
            </a>
        </div>
        @endcan
        @endunless
    @endif

    @if ($logs && count($logs) > 0)
        <div class="st-card st-mb-12">
            <div class="st-card__header">
                <div>
                    <h2 class="st-card__title">Slot Log</h2>
                </div>
            </div>
            <div class="st-table-wrapper">
                <table class="st-table">
                    <thead>
                        <tr>
                            <th class="st-table-col-180">Time</th>
                            <th class="st-table-col-160">Type</th>
                            <th>Detail</th>
                            <th class="st-table-col-140">User</th>
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

    <div class="st-form-actions st-flex-wrap st-mb-12">
        @if ($status === 'waiting')
            <a href="{{ route('unplanned.start', ['slotId' => $slot->id]) }}" class="st-btn st-btn--primary">Start Slot</a>
        @elseif ($status === 'in_progress')
            <a href="{{ route('unplanned.complete', ['slotId' => $slot->id]) }}" class="st-btn st-btn--primary">Complete Slot</a>
        @endif

        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('unplanned.index') }}" class="st-btn st-btn--outline-primary">Back</a>
    </div>
@endsection
