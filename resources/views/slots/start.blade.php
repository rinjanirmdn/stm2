@extends('layouts.app')

@section('title', 'Start Planned - e-Docking Control System')
@section('page_title', 'Start Planned')

@section('content')
    @php
        $gateStatuses = $gateStatuses ?? [];
        $conflictDetails = $conflictDetails ?? [];
        $selectedGateId = old('actual_gate_id') !== null && (string) old('actual_gate_id') !== ''
            ? (int) old('actual_gate_id')
            : ($selectedGateId ?? null);
        $conflictLines = session('conflict_lines');
    @endphp

    <div class="st-card st-mb-16 st-border-l-4 st-card--primary-accent">
        <div class="st-flex st-justify-between st-align-center st-mb-12">
            <h3 class="st-m-0 st-text-16">Start Process</h3>
            <span class="st-badge st-badge--primary st-text--sm">Ref #{{ $slot->id }}</span>
        </div>
        <div class="st-form-row--grid-3 st-text--sm">
            <div class="st-flex st-align-center st-gap-8">
                <div class="st-icon-circle st-bg-slate-100 st-text--slate"><i class="fas fa-truck"></i></div>
                <div>
                    <div class="st-text--xs st-text--muted">PO / DO</div>
                    <div class="st-font-semibold">{{ $slot->truck_number ?? '-' }}</div>
                </div>
            </div>
            <div class="st-flex st-align-center st-gap-8">
                <div class="st-icon-circle st-bg-slate-100 st-text--slate"><i class="fas fa-warehouse"></i></div>
                <div>
                    <div class="st-text--xs st-text--muted">Warehouse</div>
                    <div class="st-font-semibold">{{ $slot->warehouse_name ?? '-' }}</div>
                </div>
            </div>
            <div class="st-flex st-align-center st-gap-8">
                <div class="st-icon-circle st-bg-slate-100 st-text--slate"><i class="fas fa-clock"></i></div>
                <div>
                    <div class="st-text--xs st-text--muted">Planned ETA</div>
                    <div class="st-flex st-flex-col st-gap-2 st-mt-2">
                        @if(isset($slot->planned_start))
                            @php $eta = \Carbon\Carbon::parse($slot->planned_start); @endphp
                            <div class="st-font-semibold st-flex st-align-center st-gap-6"><i class="far fa-calendar-alt st-text--slate st-text-12"></i> {{ $eta->format('d-m-Y') }}</div>
                            <div class="st-font-semibold st-flex st-align-center st-gap-6"><i class="far fa-clock st-text--slate st-text-12"></i> {{ $eta->format('H:i') }}</div>
                        @else
                            <div class="st-font-semibold">-</div>
                        @endif
                        <div class="st-text--xs st-text--muted st-mt-4">
                            Duration: {{ (int) $plannedDurationMinutes }} Min
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (is_array($conflictLines) && count($conflictLines) > 0)
        <div class="st-alert st-alert--error st-mb-12">
            <span class="st-alert__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
            <div class="st-alert__text">
                <div class="st-font-semibold st-mb-2">Lane Conflict</div>
                <div class="st-text--sm st-text--dark">
                    <div class="st-mb-6">Conflicting Active Bookings:</div>
                    <ul class="st-list">
                        @foreach ($conflictLines as $line)
                            <li>{{ $line }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <div class="st-card">
        <form method="POST" action="{{ route('slots.start.store', ['slotId' => $slot->id]) }}">
            @csrf

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field">
                    <label class="st-label">Actual Gate <span class="st-text--danger-dark">*</span></label>
                    <select name="actual_gate_id" class="st-select" required>
                        <option value="">Choose Gate...</option>
                        @php
                            $slotWhId = (int) ($slot->warehouse_id ?? 0);
                            $sameWh = [];
                            $otherWh = [];
                            foreach ($gates as $g) {
                                if ((int)($g->warehouse_id ?? 0) === $slotWhId) {
                                    $sameWh[] = $g;
                                } else {
                                    $otherWh[] = $g;
                                }
                            }
                        @endphp
                        @if (!empty($sameWh))
                            <optgroup label="Same Warehouse">
                                @foreach ($sameWh as $gate)
                                    @php
                                        $gid = (int) ($gate->id ?? 0);
                                        $st = $gateStatuses[$gid] ?? ['is_conflict' => false, 'overlapping_slots' => []];
                                        $isConflict = !empty($st['is_conflict']);
                                        $label = app(\App\Services\SlotService::class)->getGateDisplayName($gate->warehouse_code ?? '', $gate->gate_number ?? '');
                                        $text = trim(($gate->warehouse_name ?? '') . ' - ' . $label);
                                        if ($isConflict) {
                                            $firstId = !empty($st['overlapping_slots']) ? (int) $st['overlapping_slots'][0] : 0;
                                            $row = $firstId ? ($conflictDetails[$firstId] ?? null) : null;
                                            $short = $row ? ('Planned #' . (int)$row->id . ' ' . (string)($row->ticket_number ?? '')) : ($firstId ? ('Planned #' . $firstId) : 'Occupied');
                                            $text .= ' (In Use: ' . $short . ')';
                                        } else {
                                            $text .= ' (Available)';
                                        }
                                    @endphp
                                    <option value="{{ $gid }}" {{ (int)$selectedGateId === $gid ? 'selected' : '' }}>{{ $text }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                        @if (!empty($otherWh))
                            <optgroup label="Other Warehouses">
                                @foreach ($otherWh as $gate)
                                    @php
                                        $gid = (int) ($gate->id ?? 0);
                                        $st = $gateStatuses[$gid] ?? ['is_conflict' => false, 'overlapping_slots' => []];
                                        $isConflict = !empty($st['is_conflict']);
                                        $label = app(\App\Services\SlotService::class)->getGateDisplayName($gate->warehouse_code ?? '', $gate->gate_number ?? '');
                                        $text = trim(($gate->warehouse_name ?? '') . ' - ' . $label);
                                        if ($isConflict) {
                                            $firstId = !empty($st['overlapping_slots']) ? (int) $st['overlapping_slots'][0] : 0;
                                            $row = $firstId ? ($conflictDetails[$firstId] ?? null) : null;
                                            $short = $row ? ('Planned #' . (int)$row->id . ' ' . (string)($row->ticket_number ?? '')) : ($firstId ? ('Planned #' . $firstId) : 'Occupied');
                                            $text .= ' (In Use: ' . $short . ')';
                                        } else {
                                            $text .= ' (Available)';
                                        }
                                    @endphp
                                    <option value="{{ $gid }}" {{ (int)$selectedGateId === $gid ? 'selected' : '' }}>{{ $text }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>

                    @if ($recommendedGateId)
                        <div class="st-text--sm st-text--muted st-mt-6">
                            Rekomendasi: {{ $recommendedGateId }}
                        </div>
                    @endif
                </div>
            </div>

            @php
                $waitingMinutes = $waitingMinutes ?? 0;
                $requireReason = $waitingMinutes > 60;
            @endphp

            @if ($requireReason)
                <div class="st-alert st-alert--warning st-mb-12">
                    <span class="st-alert__icon"><i class="fa-solid fa-clock"></i></span>
                    <span class="st-alert__text">
                        The truck has been waiting for <strong>{{ $waitingMinutes }} minutes</strong> (more than 60 minutes).
                        Please provide the reason for the long wait before starting the process.
                    </span>
                </div>

                <div class="st-form-row st-form-field--mb-12">
                    <div class="st-form-field">
                        <label class="st-label">Long Waiting Reason <span class="st-text--danger-dark">*</span></label>
                        <textarea name="waiting_reason" class="st-input" rows="3" required
                                  placeholder="Explain why the truck waited more than 60 minutes...">{{ old('waiting_reason') }}</textarea>
                        <div class="st-text--sm st-text--muted st-mt-4">
                            Example: Gate occupied, previous unloading delay, document issue, etc.
                        </div>
                    </div>
                </div>
            @endif

            <div class="st-form-actions">
                <button type="submit" class="st-btn st-btn--pad-lg">
                    <i class="fas fa-play"></i>
                    <span class="st-ml-6">Start Process</span>
                </button>
                <a href="{{ route('slots.index') }}" class="st-btn st-btn--outline-primary st-btn--pad-lg">
                    <i class="fas fa-times"></i>
                    <span class="st-ml-6">Cancel</span>
                </a>
            </div>
        </form>
    </div>
@endsection
