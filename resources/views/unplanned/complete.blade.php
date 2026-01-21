@extends('layouts.app')

@section('title', 'Complete Slot - Slot Time Management')
@section('page_title', 'Complete Slot')

@section('content')
    <div class="st-card" style="margin-bottom:12px;">
        <div style="font-size:12px;color:#6b7280;">Slot #{{ $slot->id }}</div>
        <div style="font-weight:600;">PO: {{ $slot->truck_number ?? '-' }} | Warehouse: {{ $slot->warehouse_name ?? '-' }} | Planned: {{ $slot->planned_start ?? '-' }}</div>
    </div>

    <div class="st-card">
        <form method="POST" action="{{ route('unplanned.complete.store', ['slotId' => $slot->id]) }}">
            @csrf

            <div class="st-form-row" style="margin-bottom:12px;">
                <div class="st-form-field">
                    <label class="st-label">Material Document <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="mat_doc" class="st-input" required value="{{ old('mat_doc') }}">
                </div>
                <div class="st-form-field">
                    <label class="st-label">Surat Jalan Number (Final) <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="sj_number" class="st-input" required value="{{ old('sj_number', $slot->sj_start_number ?? '') }}">
                </div>
            </div>

            <div class="st-form-row" style="margin-bottom:12px;">
                <div class="st-form-field" style="position:relative;">
                    <label class="st-label">Truck Type <span style="color:#dc2626;">*</span></label>
                    <select name="truck_type" class="st-select" required>
                        <option value="">Select Truck Type</option>
                        @foreach($truckTypes as $type)
                            <option value="{{ $type }}" {{ (old('truck_type', $slot->truck_type ?? '') === $type) ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="st-form-field">
                    <label class="st-label">Vehicle Number <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="vehicle_number" class="st-input" required value="{{ old('vehicle_number') }}">
                </div>
            </div>

            <div class="st-form-row" style="margin-bottom:12px;">
                <div class="st-form-field">
                    <label class="st-label">Driver Name <span style="font-weight:400;color:#6b7280;">(optional)</span></label>
                    <input type="text" name="driver_name" class="st-input" value="{{ old('driver_name') }}">
                </div>
                <div class="st-form-field">
                    <label class="st-label">Driver Number <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="driver_number" class="st-input" required value="{{ old('driver_number') }}">
                </div>
            </div>

            <div class="st-form-row" style="margin-bottom:12px;">
                <div class="st-form-field">
                    <label class="st-label">Notes <span style="font-weight:400;color:#6b7280;">(optional)</span></label>
                    <textarea name="notes" class="st-textarea" rows="3">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div style="display:flex;gap:8px;">
                <button type="submit" class="st-btn">Complete Slot</button>
                <a href="{{ route('unplanned.index') }}" class="st-btn st-btn--secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script type="application/json" id="truck_types_json">{{ json_encode(array_values($truckTypes ?? [])) }}</script>

    <!-- Debug info -->
    <script>
    console.log('Unplanned complete - Truck types count:', {{ count($truckTypes ?? []) }});
    console.log('Unplanned complete - Using standard dropdown instead of autocomplete');
    </script>
@endsection
