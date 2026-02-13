@extends('vendor.layouts.vendor')

@section('title', 'Create Booking - Vendor Portal')

@section('page_class', 'vendor-page--layout vendor-page--bookings-create')

@push('styles')
    @vite(['resources/css/bookings.css'])
@endpush

@section('content')

<div class="cb-container">
    <div class="cb-scroll-container">
        <div class="cb-content-container">
            <!-- Header -->
            <div class="cb-header">
                <h1 class="cb-header__title">
                    <i class="fas fa-plus-circle"></i>
                    Create New Booking
                </h1>
            </div>

            <form method="POST" action="{{ route('vendor.bookings.store') }}" enctype="multipart/form-data" id="booking-form">
                @csrf

                <div class="cb-form-layout">
                    <!-- Row 1: PO Selection + Schedule -->
                    <div class="cb-form-row">
                        <!-- PO Selection -->
                        <div class="cb-section">
                            <h3 class="cb-section__title">
                                <i class="fas fa-file-invoice"></i>
                                PO/DO Selection
                            </h3>
                            <div class="cb-field">
                                <label class="cb-label cb-label--required">PO/DO Number</label>
                                <div class="cb-po-search">
                                    <input type="text"
                                           id="po-search"
                                           class="cb-input cb-input--pr-40"
                                           placeholder="Search PO/DO number..."
                                           autocomplete="off"
                                           value="{{ old('po_number') }}">
                                    <input type="hidden" name="po_number" id="po-number-hidden" value="{{ old('po_number') }}">
                                    <span class="cb-input-status" id="po-status" aria-hidden="true"></span>
                                </div>
                                <div class="cb-po-message" id="po-message"></div>
                                @error('po_number')
                                    <div class="cb-hint cb-hint--error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Schedule -->
                        <div class="cb-section">
                            <h3 class="cb-section__title">
                                <i class="fas fa-calendar-alt"></i>
                                Schedule
                            </h3>
                            <div class="cb-field-row">
                                <div class="cb-field cb-field--half">
                                    <label class="cb-label cb-label--required">Date</label>
                                    <input type="text"
                                           name="planned_date"
                                           class="cb-input cb-input--date"
                                           id="planned-date"
                                           autocomplete="off"
                                           readonly
                                           value="{{ old('planned_date') }}"
                                           required>
                                    <div class="cb-hint">Min 4 hours from now</div>
                                    @error('planned_date')
                                        <div class="cb-hint cb-hint--error">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="cb-field cb-field--half">
                                    <label class="cb-label cb-label--required">Time</label>
                                    <input type="text"
                                           name="planned_time"
                                           class="cb-input cb-input--time"
                                           id="planned-time"
                                           inputmode="none"
                                           readonly
                                           value="{{ old('planned_time', '08:00') }}"
                                           required>
                                    <div class="cb-hint">07:00 - 19:00</div>
                                    <div class="cb-hint cb-hint--error" id="time-error" hidden></div>
                                    @error('planned_time')
                                        <div class="cb-hint cb-hint--error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <input type="hidden" name="planned_duration" id="planned-duration" value="{{ old('planned_duration', 60) }}">
                            <input type="hidden" name="planned_start" id="planned-start" value="{{ old('planned_start') }}">
                        </div>
                    </div>

                    <!-- Row 2: Vehicle & Driver + Live Availability -->
                    <div class="cb-form-row">
                        <!-- Vehicle & Driver -->
                        <div class="cb-section">
                            <h3 class="cb-section__title">
                                <i class="fas fa-truck"></i>
                                Vehicle & Driver
                            </h3>
                            <div class="cb-field-grid-2x2">
                                <div class="cb-field">
                                    <label class="cb-label cb-label--required">Truck Type</label>
                                    <select name="truck_type" class="cb-select" required>
                                        <option value="">-- Select Truck Type --</option>
                                        @foreach($truckTypes as $type)
                                            <option value="{{ $type->truck_type }}" data-duration="{{ $type->target_duration_minutes }}" {{ old('truck_type') == $type->truck_type ? 'selected' : '' }}>
                                                {{ $type->truck_type }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('truck_type')
                                        <div class="cb-hint cb-hint--error">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="cb-field">
                                    <label class="cb-label">Vehicle Number</label>
                                    <input type="text"
                                           name="vehicle_number"
                                           class="cb-input"
                                           placeholder="e.g., B 1234 ABC"
                                           value="{{ old('vehicle_number') }}"
                                           maxlength="50">
                                    @error('vehicle_number')
                                        <div class="cb-hint cb-hint--error">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="cb-field">
                                    <label class="cb-label">Driver Name</label>
                                    <input type="text"
                                           name="driver_name"
                                           class="cb-input"
                                           placeholder="Driver's full name"
                                           value="{{ old('driver_name') }}"
                                           maxlength="50">
                                    @error('driver_name')
                                        <div class="cb-hint cb-hint--error">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="cb-field">
                                    <label class="cb-label">Driver Phone</label>
                                    <input type="text"
                                           name="driver_number"
                                           class="cb-input"
                                           placeholder="e.g., 08123456789"
                                           value="{{ old('driver_number') }}"
                                           maxlength="50">
                                    @error('driver_number')
                                        <div class="cb-hint cb-hint--error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Live Availability -->
                        <div class="cb-section">
                            <h3 class="cb-section__title">
                                <i class="fas fa-clock"></i>
                                Live Availability
                            </h3>
                            <div class="cb-availability-mini" id="mini-availability">
                                <div class="cb-availability-mini__placeholder">Select date to see available hours</div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 3: Additional Notes (Full Width) -->
                    <div class="cb-form-row cb-form-row--full">
                        <div class="cb-section cb-section--notes">
                            <h3 class="cb-section__title">
                                <i class="fas fa-sticky-note"></i>
                                Additional Notes
                            </h3>
                            <div class="cb-field">
                                <textarea name="notes"
                                          class="cb-textarea cb-textarea--large"
                                          placeholder="Any additional information..."
                                          maxlength="500">{{ old('notes') }}</textarea>
                                <div class="cb-hint">Maximum 500 characters</div>
                                @error('notes')
                                    <div class="cb-hint cb-hint--error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions (Bottom) -->
                    <div class="cb-form-row cb-form-row--full">
                        <div class="cb-form-actions">
                            <a href="{{ route('vendor.bookings.index') }}" class="cb-btn cb-btn--secondary">
                                <i class="fas fa-times"></i>
                                Cancel
                            </a>
                            <button type="submit" class="cb-btn cb-btn--primary" id="submit-btn">
                                <i class="fas fa-paper-plane"></i>
                                Submit Booking Request
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script type="application/json" id="vendor_booking_create_config">{!! json_encode([
    'poSearchUrl' => route('vendor.ajax.po_search'),
    'poDetailUrl' => url('vendor/ajax/po'),
    'availableSlotsUrl' => route('vendor.ajax.available_slots'),
]) !!}</script>
@endpush
@endsection

