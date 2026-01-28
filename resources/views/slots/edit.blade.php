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
                <a href="{{ route('slots.show', ['slotId' => $slot->id]) }}" class="st-btn" style="background:transparent;color:var(--primary);border:1px solid var(--primary); st-btn--sm">Back</a>
            </div>
        </div>
    </div>

    <div class="st-card">
        <form method="POST" action="{{ route('slots.update', ['slotId' => $slot->id]) }}" enctype="multipart/form-data">
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
                <div class="st-form-field">
                    <label class="st-label">ETA <span class="st-text--danger-dark">*</span></label>
                    <input type="hidden" name="planned_start" id="planned_start_input" value="{{ old('planned_start', $slot->planned_start ?? '') }}">
                    <div style="display:flex;gap:8px;">
                        <input type="text" id="planned_start_date_input" class="st-input" placeholder="Select Date" autocomplete="off" {{ old('warehouse_id', $slot->warehouse_id ?? '') ? '' : 'disabled' }}>
                        <input type="text" id="planned_start_time_input" class="st-input" placeholder="Select Time" autocomplete="off" inputmode="none" readonly {{ old('warehouse_id', $slot->warehouse_id ?? '') ? '' : 'disabled' }}>
                    </div>
                    @error('planned_start')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
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

            <div class="st-form-row" style="margin-bottom:12px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;align-items:end;">
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
            </div>

            <div class="st-form-row" style="margin-bottom:12px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;align-items:end;">
                <div class="st-form-field">
                    <label class="st-label">Driver Number <span class="st-text--optional">(optional)</span></label>
                    <input type="text" name="driver_number" class="st-input{{ $errors->has('driver_number') ? ' st-input--invalid' : '' }}" value="{{ old('driver_number', $slot->driver_number ?? '') }}" placeholder="e.g., 08xxxxxxxxxx">
                    @error('driver_number')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">COA (PDF) <span class="st-text--danger-dark">*</span></label>
                    <input type="file" name="coa_pdf" class="st-input{{ $errors->has('coa_pdf') ? ' st-input--invalid' : '' }}" accept="application/pdf" {{ empty($slot->coa_path ?? '') ? 'required' : '' }}>
                    @error('coa_pdf')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <div style="display:grid;grid-template-columns:1fr 160px;gap:16px;align-items:end;">
                        <div>
                            <label class="st-label">Risk &amp; Schedule</label>
                            <div id="risk_preview" class="st-text--muted" style="font-size:11px;">Risk Not Calculated.</div>
                            <div id="time_warning" class="st-text--small st-text--danger" style="margin-top:2px;"></div>
                        </div>
                        <div>
                            <label class="st-label">View Schedule</label>
                            <button type="button" id="btn_schedule_preview" class="st-btn" style="padding:4px 8px;font-size:11px;white-space:nowrap;" {{ old('warehouse_id', $slot->warehouse_id ?? '') ? '' : 'disabled' }}>View Schedule</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="st-form-row" style="margin-bottom:12px;display:grid;grid-template-columns:1fr;gap:16px;">
                <div class="st-form-field">
                    <label class="st-label">Notes <span class="st-text--optional">(optional)</span></label>
                    <input type="text" name="notes" class="st-input{{ $errors->has('notes') ? ' st-input--invalid' : '' }}" value="{{ old('notes', $slot->late_reason ?? '') }}" placeholder="Any special notes...">
                    @error('notes')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div style="margin-top:4px;display:flex;gap:8px;">
                <button type="submit" class="st-btn" id="save_button">Save</button>
                <a href="{{ route('slots.index') }}" class="st-btn" style="background:transparent;color:var(--primary);border:1px solid var(--primary);">Cancel</a>
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
        var plannedStartDateInput = document.getElementById('planned_start_date_input');
        var plannedStartTimeInput = document.getElementById('planned_start_time_input');
        var plannedDurationInput = document.querySelector('input[name="planned_duration"]');
        var durationUnitSelect = document.querySelector('select[name="duration_unit"]');
        var truckTypeSelect = document.getElementById('truck_type');
        var gateSelect = document.getElementById('planned_gate_id');

        var holidayData = typeof window.getIndonesiaHolidays === 'function' ? window.getIndonesiaHolidays() : {};

        function syncWarehouseFromGate() {
            if (!warehouseSelect || !gateSelect) return;
            var selected = gateSelect.options[gateSelect.selectedIndex];
            if (!selected) return;
            var wh = selected.getAttribute('data-warehouse-id') || '';
            warehouseSelect.value = wh;
        }

        function applyDatepickerTooltips(inst) {
            if (!inst || !inst.dpDiv) return;
            const dp = window.jQuery(inst.dpDiv);

            dp.find('td.is-holiday').each(function() {
                const cell = window.jQuery(this);
                const dayText = cell.find('a, span').first().text();
                if (!dayText) return;
                const fallbackYear = inst.drawYear ?? inst.selectedYear;
                const fallbackMonth = inst.drawMonth ?? inst.selectedMonth;
                const year = cell.data('year') ?? fallbackYear;
                const month = cell.data('month') ?? fallbackMonth;
                if (year === undefined || month === undefined) return;
                const ds = `${year}-${String(month + 1).padStart(2, '0')}-${String(dayText).padStart(2, '0')}`;
                const title = holidayData[ds] || '';
                if (title) {
                    cell.attr('data-st-tooltip', title);
                    cell.find('a, span').attr('data-st-tooltip', title);
                }
                cell.removeAttr('title');
                cell.find('a, span').removeAttr('title');
            });
        }

        function bindDatepickerHover(inst) {
            if (!inst || !inst.dpDiv) return;
            const dp = window.jQuery(inst.dpDiv);
            let hideTimer = null;
            let tooltip = document.getElementById('st-datepicker-tooltip');
            if (!tooltip) {
                tooltip = document.createElement('div');
                tooltip.id = 'st-datepicker-tooltip';
                tooltip.className = 'st-datepicker-tooltip';
                document.body.appendChild(tooltip);
            }

            dp.off('mouseenter.st-tooltip mousemove.st-tooltip mouseleave.st-tooltip', 'td.is-holiday');
            dp.on('mouseenter.st-tooltip', 'td.is-holiday', function(event) {
                const text = window.jQuery(this).attr('data-st-tooltip') || '';
                if (!text) return;
                if (hideTimer) {
                    clearTimeout(hideTimer);
                    hideTimer = null;
                }
                tooltip.textContent = text;
                tooltip.classList.add('st-datepicker-tooltip--visible');
                tooltip.style.left = `${event.clientX + 12}px`;
                tooltip.style.top = `${event.clientY + 12}px`;
            });
            dp.on('mousemove.st-tooltip', 'td.is-holiday', function(event) {
                tooltip.style.left = `${event.clientX + 12}px`;
                tooltip.style.top = `${event.clientY + 12}px`;
            });
            dp.on('mouseleave.st-tooltip', 'td.is-holiday', function() {
                hideTimer = setTimeout(function() {
                    tooltip.classList.remove('st-datepicker-tooltip--visible');
                }, 300);
            });
        }

        function syncPlannedStart() {
            if (!plannedStartInput || !plannedStartDateInput || !plannedStartTimeInput) return;
            var dateVal = (plannedStartDateInput.value || '').trim();
            var timeVal = (plannedStartTimeInput.value || '').trim();
            if (dateVal && timeVal) {
                plannedStartInput.value = dateVal + ' ' + timeVal;
            } else if (dateVal) {
                plannedStartInput.value = dateVal;
            } else {
                plannedStartInput.value = '';
            }
        }

        function initEtaDatepicker() {
            if (!plannedStartDateInput) return;
            if (plannedStartDateInput.getAttribute('data-st-datepicker') === '1') return;
            if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.datepicker !== 'function') return;
            plannedStartDateInput.setAttribute('data-st-datepicker', '1');

            window.jQuery(plannedStartDateInput).datepicker({
                dateFormat: 'yy-mm-dd',
                minDate: 0,
                beforeShowDay: function(date) {
                    var ds = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
                    if (holidayData[ds]) {
                        return [true, 'is-holiday', holidayData[ds]];
                    }
                    return [true, '', ''];
                },
                beforeShow: function(input, inst) {
                    setTimeout(function() {
                        applyDatepickerTooltips(inst);
                        bindDatepickerHover(inst);
                    }, 0);
                },
                onChangeMonthYear: function(year, month, inst) {
                    setTimeout(function() {
                        applyDatepickerTooltips(inst);
                        bindDatepickerHover(inst);
                    }, 0);
                },
                onSelect: function() {
                    syncPlannedStart();
                    window.jQuery(plannedStartDateInput).datepicker('hide');
                }
            });
        }

        function initEtaTimepicker() {
            if (!plannedStartTimeInput) return;
            if (plannedStartTimeInput.getAttribute('data-st-timepicker') === '1') return;
            if (typeof window.mdtimepicker !== 'function') return;
            plannedStartTimeInput.setAttribute('data-st-timepicker', '1');

            plannedStartTimeInput.addEventListener('keydown', function (event) { event.preventDefault(); });
            plannedStartTimeInput.addEventListener('paste', function (event) { event.preventDefault(); });

            window.mdtimepicker('#planned_start_time_input', {
                format: 'hh:mm',
                is24hour: true,
                theme: 'cyan',
                hourPadding: true
            });

            plannedStartTimeInput.addEventListener('change', function () {
                syncPlannedStart();
            });
        }

        if (gateSelect && gateSelect.value) {
            syncWarehouseFromGate();
            initEtaDatepicker();
            initEtaTimepicker();
        }

        if (gateSelect) {
            gateSelect.addEventListener('change', function() {
                syncWarehouseFromGate();
                var enabled = !!gateSelect.value;
                if (plannedStartDateInput) plannedStartDateInput.disabled = !enabled;
                if (plannedStartTimeInput) plannedStartTimeInput.disabled = !enabled;
                if (enabled) {
                    initEtaDatepicker();
                    initEtaTimepicker();
                } else {
                    plannedStartInput.value = '';
                    if (plannedStartDateInput) plannedStartDateInput.value = '';
                    if (plannedStartTimeInput) plannedStartTimeInput.value = '';
                }
            });
        }

        if (plannedStartInput && plannedStartInput.value) {
            var parts = plannedStartInput.value.split(' ');
            if (plannedStartDateInput) plannedStartDateInput.value = parts[0] || '';
            if (plannedStartTimeInput) plannedStartTimeInput.value = (parts[1] || '').slice(0, 5);
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
