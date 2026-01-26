@extends('layouts.app')

@section('title', 'Edit Unplanned Transaction - Slot Time Management')
@section('page_title', 'Edit Unplanned Transaction')

@section('content')
    @php
        $urlPoSearch = route('slots.ajax.po_search');
        $urlPoDetailTemplate = route('slots.ajax.po_detail', ['poNumber' => '__PO__']);
    @endphp

    <div class="st-card" style="margin-bottom:12px;">
        <div class="st-flex-between" style="gap:8px;flex-wrap:wrap;">
            <h1 class="st-page-title" style="margin:0;">Edit Unplanned Transaction</h1>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <a href="{{ route('unplanned.show', ['slotId' => $slot->id]) }}" class="st-btn st-btn--secondary st-btn--sm">Back</a>
            </div>
        </div>
        @if ($errors->any())
            <div class="st-alert st-alert--error" style="margin-top:8px;">
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
    </div>

    <div class="st-card">
        <form method="POST" action="{{ route('unplanned.update', ['slotId' => $slot->id]) }}">
            @csrf

            <div class="st-form-row" style="margin-bottom:12px;">
                <div class="st-form-field">
                    <label class="st-label">PO/DO Number <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="po_number" maxlength="12" autocomplete="off" class="st-input{{ $errors->has('po_number') ? ' st-input--invalid' : '' }}" required value="{{ old('po_number', $slot->truck_number ?? '') }}">
                    @error('po_number')
                        <div style="font-size:11px;color:#b91c1c;margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Direction</label>
                    <select name="direction" class="st-select{{ $errors->has('direction') ? ' st-input--invalid' : '' }}">
                        <option value="">Choose...</option>
                        <option value="inbound" {{ old('direction', $slot->direction ?? '') === 'inbound' ? 'selected' : '' }}>Inbound</option>
                        <option value="outbound" {{ old('direction', $slot->direction ?? '') === 'outbound' ? 'selected' : '' }}>Outbound</option>
                    </select>
                    @error('direction')
                        <div style="font-size:11px;color:#b91c1c;margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="st-form-row" style="margin-bottom:12px;display:none;">
                <div class="st-form-field">
                    <label class="st-label">Warehouse</label>
                    <select name="warehouse_id" id="unplanned-warehouse" class="st-select">
                        <option value="">Choose...</option>
                        @foreach ($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ (string) old('warehouse_id', $slot->warehouse_id ?? '') === (string) $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="st-form-row" style="margin-bottom:12px;">
                <div class="st-form-field">
                    <label class="st-label">Gate (Actual) <span style="color:#dc2626;">*</span></label>
                    <select name="actual_gate_id" id="unplanned-gate" class="st-select{{ $errors->has('actual_gate_id') ? ' st-input--invalid' : '' }}" required>
                        <option value="">Choose Gate...</option>
                        @foreach ($gates as $gate)
                            @php
                                $gateLabel = app(\App\Services\SlotService::class)->getGateDisplayName($gate->warehouse_code ?? '', $gate->gate_number ?? '');
                            @endphp
                            <option value="{{ $gate->id }}" data-warehouse-id="{{ $gate->warehouse_id }}" {{ (string) old('actual_gate_id', $slot->actual_gate_id ?? '') === (string) $gate->id ? 'selected' : '' }}>
                                {{ $gateLabel }}
                            </option>
                        @endforeach
                    </select>
                    @error('actual_gate_id')
                        <div style="font-size:11px;color:#b91c1c;margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Arrival Time <span style="color:#dc2626;">*</span></label>
                    @php
                        $arrivalValue = old('arrival_time');
                        if ($arrivalValue === null || (string) $arrivalValue === '') {
                            $arrivalValue = !empty($slot->arrival_time) ? \Carbon\Carbon::parse((string) $slot->arrival_time)->format('Y-m-d H:i') : '';
                        }
                    @endphp
                    <input type="text" name="arrival_time" id="arrival_time_input" class="st-input{{ $errors->has('arrival_time') ? ' st-input--invalid' : '' }}" required value="{{ $arrivalValue }}" placeholder="Select Date and Time">
                    @error('arrival_time')
                        <div style="font-size:11px;color:#b91c1c;margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="st-form-row" style="margin-bottom:12px;">
                <label class="st-label" style="font-weight:600;">Queue Status</label>
                <div style="display:flex;gap:12px;align-items:center;">
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                        <input type="checkbox" name="set_waiting" value="1" {{ old('set_waiting', (($slot->status ?? '') === 'waiting') ? '1' : '') === '1' ? 'checked' : '' }} style="margin:0;">
                        <span>Set to Waiting</span>
                    </label>
                </div>
            </div>

            <div class="st-form-row" style="margin-bottom:12px;">
                <div class="st-form-field">
                    <label class="st-label">MAT DOC <span style="font-weight:400;color:#6b7280;">(Optional)</span></label>
                    <input type="text" name="mat_doc" class="st-input" value="{{ old('mat_doc', $slot->mat_doc ?? '') }}">
                </div>
                <div class="st-form-field">
                    <label class="st-label">SJ Number <span style="font-weight:400;color:#6b7280;">(Optional)</span></label>
                    <input type="text" name="sj_number" class="st-input" value="{{ old('sj_number', $slot->sj_complete_number ?? '') }}">
                </div>
            </div>

            <div class="st-form-row" style="margin-bottom:12px;">
                <div class="st-form-field">
                    <label class="st-label">Truck Type <span style="font-weight:400;color:#6b7280;">(Optional)</span></label>
                    <select name="truck_type" id="truck_type" class="st-select">
                        <option value="">-</option>
                        @foreach ($truckTypes as $tt)
                            <option value="{{ $tt }}" {{ old('truck_type', $slot->truck_type ?? '') === $tt ? 'selected' : '' }}>{{ $tt }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="st-form-field">
                    <label class="st-label">Vehicle Number <span style="font-weight:400;color:#6b7280;">(Optional)</span></label>
                    <input type="text" name="vehicle_number_snap" class="st-input" value="{{ old('vehicle_number_snap', $slot->vehicle_number_snap ?? '') }}">
                </div>
            </div>

            <div class="st-form-row" style="margin-bottom:12px;">
                <div class="st-form-field">
                    <label class="st-label">Driver Number <span style="font-weight:400;color:#6b7280;">(Optional)</span></label>
                    <input type="text" name="driver_number" class="st-input" value="{{ old('driver_number', $slot->driver_number ?? '') }}">
                </div>
                <div class="st-form-field">
                    <label class="st-label">Notes <span style="font-weight:400;color:#6b7280;">(Optional)</span></label>
                    <input type="text" name="notes" class="st-input" value="{{ old('notes', $slot->late_reason ?? '') }}">
                </div>
            </div>

            <div style="margin-top:4px;display:flex;gap:8px;">
                <button type="submit" class="st-btn">Save</button>
                <a href="{{ route('unplanned.show', ['slotId' => $slot->id]) }}" class="st-btn st-btn--secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var arrivalInput = document.getElementById('arrival_time_input');
        var gateSelect = document.getElementById('unplanned-gate');
        var warehouseSelect = document.getElementById('unplanned-warehouse');

        function syncWarehouseFromGate() {
            if (!warehouseSelect || !gateSelect) return;
            var selected = gateSelect.options[gateSelect.selectedIndex];
            if (!selected) return;
            warehouseSelect.value = selected.getAttribute('data-warehouse-id') || '';
        }
        
        function initFlatpickrForArrival() {
            if (!arrivalInput) return;
            if (arrivalInput._flatpickr) return;
            
            if (typeof window.flatpickr !== 'function') {
                setTimeout(initFlatpickrForArrival, 100);
                return;
            }

            var holidayData = typeof window.getIndonesiaHolidays === 'function' ? window.getIndonesiaHolidays() : {};

            window.flatpickr(arrivalInput, {
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

        initFlatpickrForArrival();

        if (gateSelect) {
            gateSelect.addEventListener('change', syncWarehouseFromGate);
            if (gateSelect.value) {
                syncWarehouseFromGate();
            }
        }
    });
    </script>
@endsection
