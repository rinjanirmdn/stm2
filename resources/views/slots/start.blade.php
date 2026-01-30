@extends('layouts.app')

@section('title', 'Start Slot - Slot Time Management')
@section('page_title', 'Start Slot')

@section('content')
    @php
        $gateStatuses = $gateStatuses ?? [];
        $conflictDetails = $conflictDetails ?? [];
        $selectedGateId = old('actual_gate_id') !== null && (string) old('actual_gate_id') !== ''
            ? (int) old('actual_gate_id')
            : ($selectedGateId ?? null);
        $conflictLines = session('conflict_lines');
    @endphp

    <div class="st-card st-mb-12">
        <div class="st-text--sm st-text--muted">Slot #{{ $slot->id }}</div>
        <div class="st-font-semibold">PO: {{ $slot->truck_number ?? '-' }} | Warehouse: {{ $slot->warehouse_name ?? '-' }} | Planned: {{ $slot->planned_start ?? '-' }}</div>
        <div class="st-text--sm st-text--muted st-mt-4">
            Estimated Process Duration: {{ (int) $plannedDurationMinutes }} Minutes
        </div>
    </div>

    @if (is_array($conflictLines) && count($conflictLines) > 0)
        <div class="st-alert st-alert--error st-mb-12">
            <span class="st-alert__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
            <div class="st-alert__text">
                <div class="st-font-semibold st-mb-2">Lane Conflict</div>
                <div class="st-text--sm st-text--dark">
                    <div class="st-mb-6">Conflicting Active Slots:</div>
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
                                            $short = $row ? ('Slot #' . (int)$row->id . ' ' . (string)($row->ticket_number ?? '')) : ($firstId ? ('Slot #' . $firstId) : 'Occupied');
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
                                            $short = $row ? ('Slot #' . (int)$row->id . ' ' . (string)($row->ticket_number ?? '')) : ($firstId ? ('Slot #' . $firstId) : 'Occupied');
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

            <div class="st-form-actions">
                <button type="submit" class="st-btn">Start Slot</button>
                <a href="{{ route('slots.index') }}" class="st-btn st-btn--outline-primary">Cancel</a>
            </div>
        </form>
    </div>
@endsection
