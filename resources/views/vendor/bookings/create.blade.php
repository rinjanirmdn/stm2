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

            <form method="POST" action="{{ route('vendor.bookings.store') }}" enctype="multipart/form-data" id="booking-form" novalidate>
                @csrf

                <div class="vendor-alert vendor-alert--warning" id="booking-form-alert" hidden></div>

                <div class="cb-form-layout">
                    <!-- Row 1: PO Selection + Schedule -->
                    <div class="cb-form-row">
                        <!-- PO Selection -->
                        <div class="cb-section">
                            <h3 class="cb-section__title">
                                <i class="fas fa-file-invoice"></i>
                                PO{{ auth()->user()->isInternalVendor() ? '/SO' : '' }} Selection
                            </h3>
                            <div class="cb-field">
                                <label class="cb-label cb-label--required">PO{{ auth()->user()->isInternalVendor() ? '/SO' : '' }} Number</label>
                                <div id="po_entries_container">
                                    @php
                                        $oldPoNumbers = old('po_number', ['']);
                                        if (!is_array($oldPoNumbers)) $oldPoNumbers = explode(',', $oldPoNumbers);
                                    @endphp
                                    @foreach($oldPoNumbers as $index => $oldPo)
                                    <div class="po-entry cb-mb-8" data-po-index="{{ $index }}" style="margin-bottom: 12px;">
                                        <div style="display: flex; gap: 8px; align-items: center; position: relative;">
                                            <div class="cb-po-search" style="flex: 1; position: relative;">
                                                <input type="text"
                                                       class="cb-input cb-input--pr-40 po-search-input"
                                                       placeholder="Search PO{{ auth()->user()->isInternalVendor() ? '/SO' : '' }} number..."
                                                       autocomplete="off"
                                                       value="{{ trim($oldPo) }}">
                                                <input type="hidden" name="po_number[]" class="po-number-hidden" value="{{ trim($oldPo) }}">
                                                <span class="cb-input-status po-status" aria-hidden="true"></span>
                                            </div>
                                            @if($index === 0)
                                            <button type="button" id="btn_add_po" class="cb-btn cb-btn--primary" title="Add Another PO/SO" style="background: #3b82f6; color: white; border: 1px solid #3b82f6; border-radius: 6px; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#2563eb'; this.style.borderColor='#2563eb';" onmouseout="this.style.background='#3b82f6'; this.style.borderColor='#3b82f6';">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                            @else
                                            <button type="button" class="btn-remove-po" title="Delete" style="background: transparent; color: #6b7280; border: 1px solid #d1d5db; border-radius: 6px; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#fee2e2'; this.style.color='#ef4444'; this.style.borderColor='#fca5a5';" onmouseout="this.style.background='transparent'; this.style.color='#6b7280'; this.style.borderColor='#d1d5db';">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                            @endif
                                        </div>
                                        <div class="cb-po-message po-message" style="margin-top: 4px;"></div>
                                    </div>
                                    @endforeach
                                </div>

                                <div class="cb-hint" style="font-style: italic; color: #9ca3af; margin-top: 8px;">Only displays released PO{{ auth()->user()->isInternalVendor() ? '/SO' : '' }} numbers from SAP.</div>
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
                                           value="{{ old('planned_date', request()->query('date')) }}"
                                           placeholder="Select date"
                                           required>
                                    <div class="cb-hint">H-2 Booking</div>
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
                                           value="{{ old('planned_time', request()->query('time')) }}"
                                           placeholder="Select time"
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
                                    <select name="truck_type" class="cb-select" id="truck-type-select" required>
                                        <option value="">-- Select Truck Type --</option>
                                        @foreach($truckTypes as $type)
                                            <option value="{{ $type->truck_type }}" data-duration="{{ $type->target_duration_minutes }}" {{ old('truck_type') == $type->truck_type ? 'selected' : '' }}>
                                                {{ $type->truck_type }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="cb-hint"></div>
                                    @error('truck_type')
                                        <div class="cb-hint cb-hint--error">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="cb-field">
                                    <label class="cb-label">Vehicle Number <span class="cb-text--optional">(Optional)</span></label>
                                    <input type="text"
                                           name="vehicle_number"
                                           class="cb-input"
                                           placeholder="e.g., B 1234 ABC"
                                           value="{{ old('vehicle_number') }}"
                                           maxlength="20"
                                           oninput="formatVehicleNumber(this); checkVehicleNumber(this);"
                                           onblur="checkVehicleNumber(this)">
                                    <div class="cb-hint">Format: B 1234 ABC (with spaces)</div>
                                    <div class="cb-hint cb-hint--warning" id="vehicle_number_warning" style="display:none; color: #d97706; margin-top: 4px;">
                                        <i class="fas fa-exclamation-triangle"></i> Vehicle number format is invalid (e.g., B 1234 ABC)
                                    </div>
                                    @error('vehicle_number')
                                        <div class="cb-hint cb-hint--error">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="cb-field">
                                    <label class="cb-label">Driver Name <span class="cb-text--optional">(Optional)</span></label>
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
                                    <label class="cb-label">Driver Phone <span class="cb-text--optional">(Optional)</span></label>
                                    <input type="tel"
                                           name="driver_number"
                                           class="cb-input"
                                           placeholder="e.g., 08123456789"
                                           value="{{ old('driver_number') }}"
                                           maxlength="15"
                                           oninput="checkDriverPhone(this)"
                                           onblur="checkDriverPhone(this)">
                                    <div class="cb-hint">Format: 08xxxxxxxxxx (10-13 digits)</div>
                                    <div class="cb-hint cb-hint--warning" id="driver_phone_warning" style="display:none; color: #d97706; margin-top: 4px;">
                                        <i class="fas fa-exclamation-triangle"></i> Driver phone number must start with 08
                                    </div>
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
                                <div class="cb-availability-mini__placeholder">Select truck type first, then date to see available hours</div>
                            </div>
                            <div class="cb-hint cb-hint--warning" id="availability-warning" hidden>
                                <i class="fas fa-exclamation-triangle"></i>
                                Please select truck type first to check accurate availability
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
                                <div class="cb-hint cb-hint--warning" id="submit-warning" hidden style="margin-top: 8px;">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Please select truck type and ensure time is available before submitting
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions (Bottom) -->
                    <div class="cb-form-row cb-form-row--full">
                        <div class="cb-form-actions">
                            <a href="{{ route('vendor.bookings.index') }}" class="cb-btn cb-btn--secondary">
                                Cancel
                            </a>
                            <button type="submit" class="cb-btn cb-btn--primary" id="submit-btn" disabled>
                                Submit
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
    'poSearchUrl' => auth()->user()->can('vendor.ajax.po_search') ? route('vendor.ajax.po_search') : null,
    'poDetailUrl' => auth()->user()->can('vendor.ajax.po_detail') ? url('vendor/ajax/po') : null,
    'availableSlotsUrl' => auth()->user()->can('vendor.ajax.available_slots') ? route('vendor.ajax.available_slots') : null,
    'forcedHolidayDatesUrl' => auth()->user()->can('vendor.ajax.available_slots') ? route('vendor.ajax.forced_holiday_dates') : null,
    'isInternalVendor' => auth()->user()->isInternalVendor(),
]) !!}</script>

<script>
    function formatVehicleNumber(input) {
        let val = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        let formatted = '';
        let matchArea = val.match(/^[A-Z]{1,2}/);
        if (matchArea) {
            formatted += matchArea[0];
            let rest = val.substring(matchArea[0].length);
            let matchNumber = rest.match(/^[0-9]{1,4}/);
            if (matchNumber) {
                formatted += ' ' + matchNumber[0];
                let rest2 = rest.substring(matchNumber[0].length);
                let letters = rest2.replace(/[^A-Z]/g, '').substring(0, 3);
                if (letters) {
                    formatted += ' ' + letters;
                }
            } else if (rest.length > 0) {
                // If there are letters right after the area code (invalid), keep just the area code
            }
        } else {
            formatted = val.replace(/[^A-Z]/g, '').substring(0, 2);
        }
        if (val === '') formatted = '';
        input.value = formatted;
    }

    function checkDriverPhone(input) {
        let val = input.value;
        let warning = document.getElementById('driver_phone_warning');
        if (val.length > 0) {
            if (!val.startsWith('08')) {
                warning.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Driver phone number must start with 08';
                warning.style.display = 'block';
            } else if (val.length < 10 || val.length > 13) {
                warning.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Phone number must be between 10-13 digits';
                warning.style.display = 'block';
            } else {
                warning.style.display = 'none';
            }
        } else {
            warning.style.display = 'none';
        }
    }

    function checkVehicleNumber(input) {
        let val = input.value.trim();
        let warning = document.getElementById('vehicle_number_warning');
        if (val.length > 0) {
            let regex = /^[A-Z]{1,2}\s\d{1,4}\s[A-Z]{1,3}$/;
            if (!regex.test(val)) {
                warning.style.display = 'block';
            } else {
                warning.style.display = 'none';
            }
        } else {
            warning.style.display = 'none';
        }
    }
</script>

@endpush
@endsection
