@extends('layouts.app')

@section('title', 'Complete Slot - Slot Time Management')
@section('page_title', 'Complete Slot')

@section('content')
    <div class="st-card st-mb-12">
        <div class="st-text--sm st-text--muted">Slot #{{ $slot->id }}</div>
        <div class="st-font-semibold">PO: {{ $slot->truck_number ?? '-' }} | Warehouse: {{ $slot->warehouse_name ?? '-' }} | Planned: {{ $slot->planned_start ?? '-' }}</div>
    </div>

    <div class="st-card">
        <form method="POST" action="{{ route('unplanned.complete.store', ['slotId' => $slot->id]) }}">
            @csrf

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field">
                    <label class="st-label">Material Document <span class="st-text--danger-dark">*</span></label>
                    <input type="text" name="mat_doc" class="st-input" required value="{{ old('mat_doc') }}">
                </div>
            </div>

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field st-form-field--relative">
                    <label class="st-label">Truck Type <span class="st-text--danger-dark">*</span></label>
                    <select name="truck_type" class="st-select" required>
                        <option value="">Select Truck Type</option>
                        @foreach($truckTypes as $type)
                            <option value="{{ $type }}" {{ (old('truck_type', $slot->truck_type ?? '') === $type) ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="st-form-field">
                    <label class="st-label">Vehicle Number <span class="st-text--danger-dark">*</span></label>
                    <input type="text" name="vehicle_number" class="st-input" required value="{{ old('vehicle_number') }}">
                </div>
            </div>

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field">
                    <label class="st-label">Driver Name <span class="st-text--optional">(optional)</span></label>
                    <input type="text" name="driver_name" class="st-input" value="{{ old('driver_name') }}">
                </div>
                <div class="st-form-field">
                    <label class="st-label">Driver Number <span class="st-text--danger-dark">*</span></label>
                    <input type="text" name="driver_number" class="st-input" required value="{{ old('driver_number') }}">
                </div>
            </div>

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field">
                    <label class="st-label">Notes <span class="st-text--optional">(Optional)</span></label>
                    <textarea name="notes" class="st-textarea" rows="3">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="st-form-actions">
                <button type="submit" class="st-btn">Complete Slot</button>
                <a href="{{ route('unplanned.index') }}" class="st-btn st-btn--outline-primary">Cancel</a>
            </div>
        </form>
    </div>

    <script type="application/json" id="truck_types_json">{{ json_encode(array_values($truckTypes ?? [])) }}</script>

    @push('scripts')
@vite(['resources/js/pages/unplanned-complete.js'])
@endpush
@endsection

