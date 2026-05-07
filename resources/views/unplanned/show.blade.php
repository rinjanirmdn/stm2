@extends('layouts.app')

@section('title', 'View Unplanned - e-Docking Control System')
@section('page_title', 'Unplanned Detail')

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
                return \Carbon\Carbon::parse((string) $v)->format('d-m-Y H:i');
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
        if (!$isUnplanned) {
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

            <div class="st-row st-row-gap-14">
                <div class="st-col-6">
                    <h2 class="st-card__title st-mb-2 st-text--md-14">Planning</h2>

                    <div class="st-detail-grid st-detail-grid--sm">
                        <div class="st-detail-item st-detail-item--compact">
                            <div class="st-detail-label">ETA</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ $fmt($slot->planned_start ?? null) }}</div>
                        </div>

                        <div class="st-detail-item st-detail-item--compact">
                            <div class="st-detail-label">Planned Duration</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ $plannedDurationLabel }}</div>
                        </div>

                        <div class="st-detail-item st-detail-item--compact">
                            <div class="st-detail-label">Est. Finish</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ $plannedFinish ? $fmt($plannedFinish) : '-' }}</div>
                        </div>

                        <div class="st-detail-item st-detail-item--compact">
                            <div class="st-detail-label">Planned Gate</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ !empty($slot->planned_gate_number) ? $plannedGateLabel : '-' }}</div>
                        </div>
                    </div>

                    <div class="st-detail-divider st-detail-divider--compact"></div>

                    <h2 class="st-card__title st-mb-2 st-text--md-14">Actual &amp; Status</h2>

                    <div class="st-detail-grid st-detail-grid--sm">
                        <div class="st-detail-item st-detail-item--compact">
                            <div class="st-detail-label">Status</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">
                                @php
                                    $stClass = $status;
                                    $stLabel = ucwords(str_replace('_', ' ', $status));
                                    if ($status === 'arrived') { $stClass = 'waiting'; $stLabel = 'Waiting'; }
                                    $badgeMap = [
                                        'scheduled' => 'bg-scheduled',
                                        'waiting' => 'bg-waiting',
                                        'in_progress' => 'bg-in_progress',
                                        'completed' => 'bg-completed',
                                        'cancelled' => 'bg-danger',
                                        'pending_approval' => 'bg-pending_approval',
                                    ];
                                    $badgeClass = $badgeMap[$stClass] ?? 'bg-secondary';
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $stLabel }}</span>
                            </div>
                        </div>

                        <div class="st-detail-item st-detail-item--compact">
                            <div class="st-detail-label">Blocking Risk</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">
                            @if ($isUnplanned || $status === 'cancelled')
                                -
                            @else
                                @if ($blockingRisk >= 2)
                                    <span class="st-table__status-badge st-status-late st-status-badge--xs">High</span>
                                @elseif ($blockingRisk === 1)
                                    <span class="st-table__status-badge st-status-processing st-status-badge--xs">Medium</span>
                                @else
                                    <span class="st-table__status-badge st-status-on-time st-status-badge--xs">Low</span>
                                @endif
                            @endif
                            </div>
                        </div>

                        <div class="st-detail-item st-detail-item--compact">
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
                            <div class="st-detail-item st-detail-item--compact">
                                <div class="st-detail-label">Notes</div>
                                <div class="st-detail-colon">:</div>
                                <div class="st-detail-value st-detail-value--prewrap">{{ $slot->late_reason }}</div>
                            </div>
                        @endif

                        <div class="st-detail-item st-detail-item--compact">
                            <div class="st-detail-label">Arrival Time</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ $fmt($slot->arrival_time ?? null) }}</div>
                        </div>

                        <div class="st-detail-item st-detail-item--compact">
                            <div class="st-detail-label">Actual Start</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ $fmt($slot->actual_start ?? null) }}</div>
                        </div>

                        <div class="st-detail-item st-detail-item--compact">
                            <div class="st-detail-label">Actual Finish</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ $fmt($slot->actual_finish ?? null) }}</div>
                        </div>

                        <div class="st-detail-item st-detail-item--compact">
                            <div class="st-detail-label">Waiting Time (Arrival → Start)</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ $leadMinutes !== null ? $minutesLabel($leadMinutes) : '-' }}</div>
                        </div>

                        <div class="st-detail-item st-detail-item--compact">
                            <div class="st-detail-label">Process Time (Start → Finish)</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ $processMinutes !== null ? $minutesLabel($processMinutes) : '-' }}</div>
                        </div>

                        <div class="st-detail-item st-detail-item--compact">
                            <div class="st-detail-label">Total Lead Time</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ $totalLeadTimeMinutes !== null ? $minutesLabel($totalLeadTimeMinutes) : '-' }}</div>
                        </div>

                        <div class="st-detail-item st-detail-item--compact">
                            <div class="st-detail-label">Target Status</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">
                            @if ($targetStatus === 'achieve')
                                <span class="st-table__status-badge st-status-on-time st-status-badge--xs">Achieve</span>
                            @elseif ($targetStatus === 'not_achieve')
                                <span class="st-table__status-badge st-status-late st-status-badge--xs">Not Achieve</span>
                            @else
                                -
                            @endif
                            </div>
                        </div>

                        <div class="st-detail-item st-detail-item--compact">
                            <div class="st-detail-label">Actual Gate</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ !empty($slot->actual_gate_number) ? $actualGateLabel : '-' }}</div>
                        </div>
                    </div>
                </div>

                <div class="st-col-6">
                    <h2 class="st-card__title st-mb-2 st-text--md-14">General Info</h2>

                    <div class="st-detail-grid st-detail-grid--sm">
                        <div class="st-detail-item st-detail-item--compact">
                            <div class="st-detail-label">PO/SO Number</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value st-detail-value--primary st-detail-value--primary-sm">{{ $slot->po_number ?? '-' }}</div>
                        </div>

                        <div class="st-detail-item st-detail-item--compact">
                            <div class="st-detail-label">SJ</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ !empty($slot->mat_doc) ? $slot->mat_doc : '-' }}</div>
                        </div>

                        <div class="st-detail-item st-detail-item--compact">
                            <div class="st-detail-label">Ticket Number</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ !empty($slot->ticket_number) ? $slot->ticket_number : '-' }}</div>
                        </div>

                        <div class="st-detail-item st-detail-item--compact">
                            <div class="st-detail-label">Vendor</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ $slot->vendor_name ?? '-' }}</div>
                        </div>

                        <div class="st-detail-item st-detail-item--compact">
                            <div class="st-detail-label">Warehouse</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ $slot->warehouse_name ?? '-' }}</div>
                        </div>

                        <div class="st-detail-item st-detail-item--compact">
                            <div class="st-detail-label">Direction</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">
                            @php $dir = strtolower($slot->direction ?? ''); @endphp
                            @if($dir === 'inbound')
                                <span class="st-badge-modern st-badge-modern--inbound st-status-badge--xs">
                                    Inbound
                                </span>
                            @elseif($dir === 'outbound')
                                <span class="st-badge-modern st-badge-modern--outbound st-status-badge--xs">
                                    Outbound
                                </span>
                            @else
                                {{ strtoupper((string) ($slot->direction ?? '')) }}
                            @endif
                            </div>
                        </div>

                        <div class="st-detail-item st-detail-item--compact">
                            <div class="st-detail-label">Truck Details</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">
                            @php
                                $truckParts = [];
                                if (!empty($slot->truck_type)) {
                                    $truckParts[] = 'Jenis: ' . $slot->truck_type;
                                }
                                if (!empty($slot->driver_name)) {
                                    $truckParts[] = 'Driver Name: ' . $slot->driver_name;
                                }
                                if (!empty($slot->driver_number)) {
                                    $truckParts[] = 'Driver Phone: ' . $slot->driver_number;
                                }
                                if (!empty($slot->vehicle_number_snap)) {
                                    $truckParts[] = 'No. Mobil: ' . $slot->vehicle_number_snap;
                                }
                                if (!empty($slot->seal_number)) {
                                    $truckParts[] = 'Seal: ' . $slot->seal_number;
                                }
                            @endphp
                            @if (!empty($truckParts))
                                @foreach ($truckParts as $part)
                                    <div class="st-text--xs-10">{{ $part }}</div>
                                @endforeach
                            @else
                                -
                            @endif
                            </div>
                        </div>

                        @if(!empty($slot->destination))
                        <div class="st-detail-item st-detail-item--compact">
                            <div class="st-detail-label">Destination</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value">{{ $slot->destination }}</div>
                        </div>
                        @endif

                        @if(!empty($slot->late_reason))
                        <div class="st-detail-item st-detail-item--compact">
                            <div class="st-detail-label">Notes</div>
                            <div class="st-detail-colon">:</div>
                            <div class="st-detail-value st-detail-value--prewrap">{{ $slot->late_reason }}</div>
                        </div>
                        @endif
                    </div>

                    @if(!empty($slot->start_photos) || !empty($slot->complete_photos))
                        <div class="st-detail-divider st-detail-divider--compact"></div>
                        <h2 class="st-card__title st-mb-2 st-text--md-14">Photo Documentation</h2>
                        <div class="st-flex st-gap-16 st-flex-wrap st-mt-8">
                            @if(!empty($slot->start_photos))
                                <div class="st-flex st-gap-8 st-flex-wrap">
                                    <div class="st-w-full st-font-semibold st-text--sm st-mb-4 st-text--slate">Start Process</div>
                                    @foreach($slot->start_photos as $idx => $photo)
                                        @php
                                            $imgUrl = !empty($photo->id) ? route('slot-photos.show', $photo->id) : (!empty($photo->legacy_path) ? Storage::disk('public')->url($photo->legacy_path) : '');
                                            $dlUrl = !empty($photo->id) ? route('slot-photos.download', $photo->id) : $imgUrl;
                                        @endphp
                                        <div class="st-photo-preview st-text-center">
                                            <img src="{{ $imgUrl }}" alt="Start Photo {{ $idx + 1 }}" loading="lazy" style="width: 120px; height: 120px; cursor: zoom-in; border-radius: 8px; border: 1px solid #e2e8f0; object-fit: cover;" onclick="openPhotoModal(this.src)" onerror="this.closest('.st-photo-preview').style.display='none'">
                                            <div class="st-mt-4">
                                                <a href="{{ $dlUrl }}" download="Start_Photo_{{ $slot->id }}_{{ $idx + 1 }}.jpg" class="st-btn st-btn--secondary st-btn--xs">
                                                    <i class="fas fa-download"></i> Save
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if(!empty($slot->complete_photos))
                                <div class="st-flex st-gap-8 st-flex-wrap">
                                    <div class="st-w-full st-font-semibold st-text--sm st-mb-4 st-text--slate">Complete Process</div>
                                    @foreach($slot->complete_photos as $idx => $photo)
                                        @php
                                            $imgUrl = !empty($photo->id) ? route('slot-photos.show', $photo->id) : (!empty($photo->legacy_path) ? Storage::disk('public')->url($photo->legacy_path) : '');
                                            $dlUrl = !empty($photo->id) ? route('slot-photos.download', $photo->id) : $imgUrl;
                                        @endphp
                                        <div class="st-photo-preview st-text-center">
                                            <img src="{{ $imgUrl }}" alt="Complete Photo {{ $idx + 1 }}" loading="lazy" style="width: 120px; height: 120px; cursor: zoom-in; border-radius: 8px; border: 1px solid #e2e8f0; object-fit: cover;" onclick="openPhotoModal(this.src)" onerror="this.closest('.st-photo-preview').style.display='none'">
                                            <div class="st-mt-4">
                                                <a href="{{ $dlUrl }}" download="Complete_Photo_{{ $slot->id }}_{{ $idx + 1 }}.jpg" class="st-btn st-btn--secondary st-btn--xs">
                                                    <i class="fas fa-download"></i> Save
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif

                </div>
            </div>

            <div class="st-flex st-gap-6 st-flex-wrap st-align-center st-justify-end st-mb-8">
                @if(auth()->user() && (auth()->user()->can('slots.edit') || auth()->user()->hasAnyRole(['Super Account', 'Section Head', 'super account', 'section head', 'Super Admin', 'super admin', 'Admin', 'admin'])))
                    <a href="{{ route('unplanned.edit', ['slotId' => $slot->id]) }}" class="st-btn st-btn--outline-primary st-btn--xs">
                        <i class="fa-solid fa-pen st-mr-4"></i> Edit
                    </a>
                @endif

                @if ($status === 'waiting')
                    @can('slots.start')
                    <a href="{{ route('unplanned.start', ['slotId' => $slot->id]) }}" class="st-btn st-btn--primary st-btn--xs" onclick="event.preventDefault(); openGlobalAjaxModal('Start Unplanned', this.href);">Start</a>
                    @endcan
                @elseif ($status === 'in_progress')
                    @can('slots.complete')
                    <a href="{{ route('unplanned.complete', ['slotId' => $slot->id]) }}" class="st-btn st-btn--primary st-btn--xs" onclick="event.preventDefault(); openGlobalAjaxModal('Complete Unplanned', this.href);">Complete</a>
                    @endcan
                @endif

                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('unplanned.index') }}" class="st-btn st-btn--outline-primary st-btn--xs">Back</a>
            </div>
        </div>
    </div>



    @if ($logs && count($logs) > 0)
        <div class="st-card st-mb-12">
            <div class="st-card__header">
                <div>
                    <h2 class="st-card__title">Activity Log</h2>
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
                            <tr class="st-table-row">
                                <td class="st-table-cell">{{ $fmt($log->created_at ?? null) }}</td>
                                <td class="st-table-cell">{{ $typeLabel }}</td>
                                <td class="st-table-cell" style="line-height:1.5;">{{ $log->description ?? '-' }}</td>
                                <td class="st-table-cell">{{ $log->username ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Photo Modal -->
    <div id="photo-zoom-dialog" style="position:fixed;inset:0;z-index:9999;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,0.85);padding:16px;" onclick="this.style.display='none'">
        <img id="zoomed-photo" src="" alt="Zoomed Photo" style="max-width:95vw;max-height:90vh;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,0.5);object-fit:contain;">
    </div>
    <script>
        function openPhotoModal(src) {
            var img = document.getElementById('zoomed-photo');
            img.src = src;
            document.getElementById('photo-zoom-dialog').style.display = 'flex';
        }
    </script>
@endsection