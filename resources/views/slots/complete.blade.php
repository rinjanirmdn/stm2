@extends('layouts.app')

@section('title', 'Complete - e-Docking Control System')
@section('page_title', 'Complete Slot')

@section('content')
    <div class="st-card st-mb-16 st-border-l-4" style="border-left-color: var(--primary);">
        <div class="st-flex st-justify-between st-align-center st-mb-12">
            <h3 class="st-m-0 st-text-16">Complete Registration</h3>
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="st-card">
        <form method="POST" action="{{ route('slots.complete.store', ['slotId' => $slot->id]) }}">
            @csrf

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field">
                    <label class="st-label">Nomor Surat Jalan <span class="st-text--danger-dark">*</span></label>
                    <input type="text" name="mat_doc" class="st-input" required value="{{ old('mat_doc') }}">
                </div>
            </div>

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field">
                    <label class="st-label">Vehicle Number <span class="st-text--danger-dark">*</span></label>
                    <input type="text" name="vehicle_number" class="st-input" required value="{{ old('vehicle_number') }}">
                </div>
            </div>

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field">
                    <label class="st-label">Driver Name <span class="st-text--danger-dark">*</span></label>
                    <input type="text" name="driver_name" class="st-input" required value="{{ old('driver_name') }}">
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
                <button type="submit" class="st-btn st-btn--pad-lg">
                    <i class="fas fa-check-circle"></i>
                    <span class="st-ml-6">Complete Registration</span>
                </button>
                <a href="{{ route('slots.index') }}" class="st-btn st-btn--outline-primary st-btn--pad-lg">
                    <i class="fas fa-times"></i>
                    <span class="st-ml-6">Cancel</span>
                </a>
            </div>
        </form>
    </div>

    <script type="application/json" id="truck_types_json">{{ json_encode(array_values($truckTypes)) }}</script>

    @push('scripts')
@vite(['resources/js/pages/slots-complete.js'])
@endpush
@endsection

