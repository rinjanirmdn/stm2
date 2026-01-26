@extends('layouts.app')

@section('title', 'Edit Slot - Slot Time Management')
@section('page_title', 'Edit Slot (Scheduled)')

@section('content')
    <div class="st-card" style="margin-bottom:12px;">
        <div class="st-flex-between" style="gap:8px;flex-wrap:wrap;">
            <div>
                <h2 class="st-page-title" style="margin:0;">Edit Slot #{{ $slot->id }}</h2>
                <div style="font-size:12px;color:#6b7280;">Only Scheduled Planned Slots Can Be Edited</div>
            </div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <a href="{{ route('slots.show', ['slotId' => $slot->id]) }}" class="st-btn st-btn--secondary st-btn--sm">Back</a>
            </div>
        </div>
    </div>

    <div class="st-card">
        <form method="POST" action="{{ route('slots.update', ['slotId' => $slot->id]) }}">
            @csrf

            @if ($errors->any())
                <div class="st-alert st-alert--error">
                    <span class="st-alert__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                    <div class="st-alert__text">
                        <div style="font-weight:600;margin-bottom:2px;">Validation Error</div>
                        <div style="font-size:12px;">
                            <ul style="margin:0;padding-left:16px;">
                                @foreach ($errors->all() as $msg)
                                    <li>{{ $msg }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <div class="st-form-row" style="margin-bottom:12px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
                <div class="st-form-field">
                    <label class="st-label">PO/DO Number <span class="st-text--danger-dark">*</span></label>
                    <div style="position:relative;">
                        <input type="text" id="po_number" autocomplete="off" name="po_number" maxlength="12" class="st-input{{ $errors->has('po_number') ? ' st-input--invalid' : '' }}" required value="{{ old('po_number', $slot->truck_number ?? '') }}">
                        <div id="po_suggestions" class="st-suggestions st-suggestions--po" style="display:none;"></div>
                    </div>
                    @error('po_number')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Direction <span class="st-text--danger-dark">*</span></label>
                    <select name="direction" id="direction" class="st-select{{ $errors->has('direction') ? ' st-input--invalid' : '' }}" required>
                        <option value="">Choose Direction...</option>
                        <option value="inbound" {{ old('direction', $slot->direction ?? '') === 'inbound' ? 'selected' : '' }}>Inbound</option>
                        <option value="outbound" {{ old('direction', $slot->direction ?? '') === 'outbound' ? 'selected' : '' }}>Outbound</option>
                    </select>
                    @error('direction')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Truck Type <span class="st-text--danger-dark">*</span></label>
                    <select name="truck_type" id="truck_type" class="st-select{{ $errors->has('truck_type') ? ' st-input--invalid' : '' }}" required>
                        <option value="">Choose...</option>
                        @foreach ($truckTypes as $tt)
                            <option value="{{ $tt }}" {{ old('truck_type', $slot->truck_type ?? '') === $tt ? 'selected' : '' }}>{{ $tt }}</option>
                        @endforeach
                    </select>
                    @error('truck_type')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="st-form-row" style="margin-bottom:12px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
                <div class="st-form-field" style="position:relative;">
                    <label class="st-label">Vendor <span class="st-text--optional">(Optional)</span></label>
                    <input
                        type="text"
                        id="vendor_search"
                        class="st-input{{ $errors->has('vendor_id') ? ' st-input--invalid' : '' }}"
                        placeholder="Choose Direction First..."
                        style="margin-bottom:4px;"
                        value="{{ old('vendor_search') }}"
                        {{ old('direction', $slot->direction ?? '') ? '' : 'disabled' }}
                    >
                    <input type="hidden" name="vendor_id" id="vendor_id" value="{{ old('vendor_id', $slot->vendor_id ?? '') }}">
                    <div id="vendor_suggestions" class="st-suggestions" style="display:none;"></div>
                    @error('vendor_id')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field" style="display:none;">
                    <label class="st-label">Warehouse</label>
                    <select name="warehouse_id" id="warehouse_id" class="st-select">
                        <option value="">Choose Warehouse...</option>
                        @foreach ($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ old('warehouse_id', $slot->warehouse_id ?? '') === (string) $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="st-form-field">
                    <label class="st-label">Planned Gate <span class="st-text--danger-dark">*</span></label>
                    <select name="planned_gate_id" id="planned_gate_id" class="st-select{{ $errors->has('planned_gate_id') ? ' st-input--invalid' : '' }}" required>
                        <option value="">Choose Gate...</option>
                        @foreach ($gates as $gate)
                            @php
                                $gateLabel = app(\App\Services\SlotService::class)->getGateDisplayName($gate->warehouse_code ?? '', $gate->gate_number ?? '');
                            @endphp
                            <option
                                value="{{ $gate->id }}"
                                data-warehouse-id="{{ $gate->warehouse_id }}"
                                {{ old('planned_gate_id', $slot->planned_gate_id ?? '') === (string) $gate->id ? 'selected' : '' }}
                            >
                                {{ $gateLabel }}
                            </option>
                        @endforeach
                    </select>
                    @error('planned_gate_id')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="st-form-row" style="margin-bottom:12px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;align-items:end;">
                <div class="st-form-field">
                    <label class="st-label">ETA <span class="st-text--danger-dark">*</span></label>
                    <input type="text" name="planned_start" id="planned_start_input" class="st-input{{ $errors->has('planned_start') ? ' st-input--invalid' : '' }}" required {{ old('warehouse_id', $slot->warehouse_id ?? '') ? '' : 'disabled' }} value="{{ old('planned_start', $slot->planned_start ?? '') }}" placeholder="Select Date and Time">
                    @error('planned_start')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Planned Duration <span class="st-text--optional">(Optional)</span></label>
                    <div style="display:flex;gap:4px;">
                        <input type="number" name="planned_duration" class="st-input{{ $errors->has('planned_duration') ? ' st-input--invalid' : '' }}" value="{{ old('planned_duration', $slot->planned_duration ?? '') }}" min="1" style="flex:1;">
                        <span class="st-text--small st-text--muted" style="align-self:center;white-space:nowrap;">Min</span>
                    </div>
                    @error('planned_duration')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Risk & Schedule</label>
                    <div style="display:flex;gap:4px;align-items:start;">
                        <div style="flex:1;">
                            <div id="risk_preview" class="st-text--muted" style="font-size:11px;">Risk Not Calculated.</div>
                            <div id="time_warning" class="st-text--small st-text--danger" style="margin-top:2px;"></div>
                        </div>
                        <button type="button" id="btn_schedule_preview" class="st-btn" style="padding:4px 8px;font-size:11px;white-space:nowrap;flex-shrink:0;" {{ old('warehouse_id', $slot->warehouse_id ?? '') ? '' : 'disabled' }}>View Schedule</button>
                    </div>
                </div>
            </div>

            <div class="st-form-row" style="margin-bottom:12px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
                <div class="st-form-field">
                    <label class="st-label">Vehicle Number <span class="st-text--optional">(optional)</span></label>
                    <input type="text" name="vehicle_number_snap" class="st-input{{ $errors->has('vehicle_number_snap') ? ' st-input--invalid' : '' }}" value="{{ old('vehicle_number_snap', $slot->vehicle_number_snap ?? '') }}" placeholder="e.g., B 1234 ABC">
                    @error('vehicle_number_snap')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Driver Name <span class="st-text--optional">(optional)</span></label>
                    <input type="text" name="driver_name" class="st-input{{ $errors->has('driver_name') ? ' st-input--invalid' : '' }}" value="{{ old('driver_name', $slot->driver_name ?? '') }}" placeholder="e.g., Budi">
                    @error('driver_name')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Driver Number <span class="st-text--optional">(optional)</span></label>
                    <input type="text" name="driver_number" class="st-input{{ $errors->has('driver_number') ? ' st-input--invalid' : '' }}" value="{{ old('driver_number', $slot->driver_number ?? '') }}" placeholder="e.g., 08xxxxxxxxxx">
                    @error('driver_number')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="st-form-row" style="margin-bottom:12px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
                <div class="st-form-field">
                    <label class="st-label">Notes <span class="st-text--optional">(optional)</span></label>
                    <input type="text" name="notes" class="st-input{{ $errors->has('notes') ? ' st-input--invalid' : '' }}" value="{{ old('notes', $slot->notes ?? '') }}" placeholder="Any special notes...">
                    @error('notes')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <!-- Empty field for balance -->
                </div>
                <div class="st-form-field">
                    <!-- Empty field for balance -->
                </div>
            </div>

            <div style="margin-top:4px;display:flex;gap:8px;">
                <button type="submit" class="st-btn" id="save_button">Save</button>
                <a href="{{ route('slots.index') }}" class="st-btn st-btn--secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script type="application/json" id="truck_types_json">{{ json_encode(array_values($truckTypes)) }}</script>
    <script type="application/json" id="truck_type_durations_json">{{ json_encode($truckTypeDurations) }}</script>
    <script type="application/json" id="slot_routes_json">{!! json_encode([
        'check_risk' => route('slots.ajax.check_risk'),
        'check_slot_time' => route('slots.ajax.check_slot_time'),
        'recommend_gate' => route('slots.ajax.recommend_gate'),
        'schedule_preview' => route('slots.ajax.schedule_preview'),
        'po_search' => route('slots.ajax.po_search'),
        'po_detail_template' => route('slots.ajax.po_detail', ['poNumber' => '__PO__']),
        'vendor_search' => route('api.sap.vendor.search'),
    ]) !!}</script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var warehouseSelect = document.getElementById('warehouse_id');
        var plannedStartInput = document.getElementById('planned_start_input');
        var plannedDurationInput = document.querySelector('input[name="planned_duration"]');
        var durationUnitSelect = document.querySelector('select[name="duration_unit"]');
        var truckTypeSelect = document.getElementById('truck_type');
        var gateSelect = document.getElementById('planned_gate_id');

        function syncWarehouseFromGate() {
            if (!warehouseSelect || !gateSelect) return;
            var selected = gateSelect.options[gateSelect.selectedIndex];
            if (!selected) return;
            var wh = selected.getAttribute('data-warehouse-id') || '';
            warehouseSelect.value = wh;
        }

        function initFlatpickrForETA() {
            if (!plannedStartInput) return;
            if (plannedStartInput._flatpickr) return;

            if (typeof window.flatpickr !== 'function') {
                setTimeout(initFlatpickrForETA, 100);
                return;
            }

            var holidayData = typeof window.getIndonesiaHolidays === 'function' ? window.getIndonesiaHolidays() : {};

            window.flatpickr(plannedStartInput, {
                enableTime: true,
                minDate: "today",
                time_24hr: true,
                allowInput: true,
                disableMobile: true,
                minuteIncrement: 1,
                dateFormat: 'Y-m-d H:i',
                clickOpens: true,
                closeOnSelect: false,
                onDayCreate: function(dObj, dStr, fp, dayElem) {
                    const dateStr = fp.formatDate(dayElem.dateObj, "Y-m-d");
                    if (holidayData[dateStr]) {
                        dayElem.classList.add('is-holiday');
                        dayElem.title = holidayData[dateStr];
                    }
                }
            });
        }

        if (gateSelect && gateSelect.value) {
            syncWarehouseFromGate();
            initFlatpickrForETA();
        }

        if (gateSelect) {
            gateSelect.addEventListener('change', function() {
                syncWarehouseFromGate();
                if (gateSelect.value) {
                    plannedStartInput.disabled = false;
                    initFlatpickrForETA();
                } else {
                    plannedStartInput.disabled = true;
                    if (plannedStartInput._flatpickr) {
                        plannedStartInput._flatpickr.clear();
                    }
                    plannedStartInput.value = '';
                }
            });
        }

        // Logic for Truck Type Durations
        var typeDurations = {};
        try {
            typeDurations = JSON.parse(document.getElementById('truck_type_durations_json').textContent);
        } catch(e) {}

        if (truckTypeSelect && plannedDurationInput) {
            truckTypeSelect.addEventListener('change', function() {
                var val = truckTypeSelect.value;
                if (val && typeDurations[val]) {
                    plannedDurationInput.value = typeDurations[val];
                    if (durationUnitSelect) durationUnitSelect.value = 'minutes';
                }
            });
        }
    });
    </script>
@endsection
