@extends('vendor.layouts.vendor')

@section('title', 'Create Booking - Vendor Portal')

@section('content')
<div class="vendor-card">
    <div class="vendor-card__header">
        <h1 class="vendor-card__title">
            <i class="fas fa-plus-circle"></i>
            Create New Booking
        </h1>
        <a href="{{ route('vendor.bookings.index') }}" class="vendor-btn vendor-btn--secondary">
            <i class="fas fa-arrow-left"></i>
            Back
        </a>
    </div>

    <form method="POST" action="{{ route('vendor.bookings.store') }}" id="booking-form">
        @csrf
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
            <!-- Left Column -->
            <div>
                <h3 style="margin-bottom: 1rem; color: #374151; font-size: 1rem; font-weight: 600;">
                    <i class="fas fa-warehouse"></i>
                    Location & Direction
                </h3>
                
                <div class="vendor-form-group">
                    <label class="vendor-form-label">Warehouse <span style="color: #ef4444;">*</span></label>
                    <select name="warehouse_id" class="vendor-form-select" required id="warehouse_id">
                        <option value="">Select Warehouse</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ old('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->wh_code }} - {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('warehouse_id')
                        <small style="color: #ef4444;">{{ $message }}</small>
                    @enderror
                </div>

                <div class="vendor-form-group">
                    <label class="vendor-form-label">PO/DO Number <span style="color: #ef4444;">*</span></label>
                    <div style="position: relative;">
                        <input
                            type="text"
                            name="po_number"
                            id="po_number"
                            class="vendor-form-input"
                            autocomplete="off"
                            placeholder="Type PO/DO..."
                            required
                            value="{{ old('po_number') }}"
                        >
                        <div id="po_suggestions" style="display:none; position:absolute; top:100%; left:0; right:0; z-index:50; background:#fff; border:1px solid #e5e7eb; border-radius:10px; margin-top:6px; max-height:260px; overflow:auto;"></div>
                        <div id="po_preview" style="margin-top:8px;"></div>
                    </div>
                    @error('po_number')
                        <small style="color: #ef4444;">{{ $message }}</small>
                    @enderror
                    <small style="color: #64748b;">Suggestions Only Show PO/DO Assigned to Your Vendor.</small>
                </div>

                <div class="vendor-form-group" id="po_items_group" style="display:none;">
                    <label class="vendor-form-label">PO Items & Quantity for This Booking <span style="color: #ef4444;">*</span></label>
                    <div id="po_items_box"></div>
                    <small style="color: #64748b;">Input Quantity to Deliver Now. Remaining Quantity Will Stay Available for Future Bookings.</small>
                </div>

                <div class="vendor-form-group">
                    <label class="vendor-form-label">Direction <span style="color: #ef4444;">*</span></label>
                    <select name="direction" class="vendor-form-select" required id="direction">
                        <option value="">Select Direction</option>
                        <option value="inbound" {{ old('direction', $defaultDirection ?? '') === 'inbound' ? 'selected' : '' }}>Inbound (Delivery)</option>
                        <option value="outbound" {{ old('direction', $defaultDirection ?? '') === 'outbound' ? 'selected' : '' }}>Outbound (Pickup)</option>
                    </select>
                    @error('direction')
                        <small style="color: #ef4444;">{{ $message }}</small>
                    @enderror
                </div>

                <div class="vendor-form-group">
                    <label class="vendor-form-label">Preferred Gate (Optional)</label>
                    <select name="planned_gate_id" class="vendor-form-select" id="gate_select">
                        <option value="">Auto-assign by Admin</option>
                        <!-- Gates will be loaded via JavaScript based on warehouse -->
                    </select>
                    <small style="color: #64748b;">Leave Empty for Admin to Assign the Best Available Gate</small>
                </div>

                <h3 style="margin-bottom: 1rem; margin-top: 2rem; color: #374151; font-size: 1rem; font-weight: 600;">
                    <i class="fas fa-truck"></i>
                    Vehicle Information
                </h3>

                <div class="vendor-form-group">
                    <label class="vendor-form-label">Truck Type</label>
                    <select name="truck_type" class="vendor-form-select" id="truck_type">
                        <option value="">Select Truck Type</option>
                        @foreach($truckTypes as $type)
                            <option value="{{ $type->truck_type }}" 
                                    data-duration="{{ $type->target_duration_minutes }}"
                                    {{ old('truck_type') === $type->truck_type ? 'selected' : '' }}>
                                {{ $type->truck_type }} ({{ $type->target_duration_minutes }} Min)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="vendor-form-group">
                    <label class="vendor-form-label">Vehicle Number (Optional)</label>
                    <input type="text" name="vehicle_number" class="vendor-form-input" 
                           placeholder="e.g., B 1234 ABC" value="{{ old('vehicle_number') }}">
                    <small style="color: #64748b;">Can Be Provided Later Before Arrival</small>
                </div>
            </div>

            <!-- Right Column -->
            <div>
                <h3 style="margin-bottom: 1rem; color: #374151; font-size: 1rem; font-weight: 600;">
                    <i class="fas fa-clock"></i>
                    Schedule
                </h3>

                <div class="vendor-form-group">
                    <label class="vendor-form-label">Date <span style="color: #ef4444;">*</span></label>
                    <input type="date" name="planned_date" class="vendor-form-input" required
                           min="{{ date('Y-m-d') }}" value="{{ old('planned_date', date('Y-m-d')) }}" id="planned_date">
                    @error('planned_date')
                        <small style="color: #ef4444;">{{ $message }}</small>
                    @enderror
                </div>

                <div class="vendor-form-group">
                    <label class="vendor-form-label">Time <span style="color: #ef4444;">*</span></label>
                    <input type="time" name="planned_time" class="vendor-form-input" required
                           min="07:00" max="22:00" value="{{ old('planned_time', '09:00') }}" id="planned_time">
                    <small style="color: #64748b;">Operating hours: 07:00 - 23:00</small>
                    @error('planned_time')
                        <small style="color: #ef4444;">{{ $message }}</small>
                    @enderror
                </div>

                <div class="vendor-form-group">
                    <label class="vendor-form-label">Duration (Minutes) <span style="color: #ef4444;">*</span></label>
                    <input type="number" name="planned_duration" class="vendor-form-input" required
                           min="30" max="480" step="30" value="{{ old('planned_duration', 60) }}" id="planned_duration">
                    <small style="color: #64748b;">Duration Is Auto-suggested Based on Truck Type</small>
                    @error('planned_duration')
                        <small style="color: #ef4444;">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Availability Check -->
                <div id="availability-check" style="padding: 1rem; border-radius: 10px; background: #f8fafc; margin-top: 1rem; display: none;">
                    <div id="availability-result"></div>
                </div>

                <div class="vendor-form-group" style="margin-top: 1.5rem;">
                    <label class="vendor-form-label">Notes (Optional)</label>
                    <textarea name="notes" class="vendor-form-textarea" rows="3" 
                              placeholder="Any Special Requests or Notes...">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; display: flex; gap: 1rem; justify-content: flex-end;">
            <a href="{{ route('vendor.bookings.index') }}" class="vendor-btn vendor-btn--secondary">
                Cancel
            </a>
            <button type="submit" class="vendor-btn vendor-btn--primary" id="submit-btn">
                <i class="fas fa-paper-plane"></i>
                Submit Booking Request
            </button>
        </div>
    </form>
</div>

<!-- Availability Calendar Preview -->
<div class="vendor-card">
    <div class="vendor-card__header">
        <h2 class="vendor-card__title">
            <i class="fas fa-calendar-alt"></i>
            Slot Availability Preview
        </h2>
    </div>
    
    <div id="calendar-preview" style="min-height: 200px;">
        <p style="text-align: center; color: #64748b; padding: 2rem;">
            Select a Warehouse and Date to See Availability
        </p>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const warehouseSelect = document.getElementById('warehouse_id');
    const gateSelect = document.getElementById('gate_select');
    const truckTypeSelect = document.getElementById('truck_type');
    const durationInput = document.getElementById('planned_duration');
    const dateInput = document.getElementById('planned_date');
    const timeInput = document.getElementById('planned_time');
    const availabilityCheck = document.getElementById('availability-check');
    const availabilityResult = document.getElementById('availability-result');
    const calendarPreview = document.getElementById('calendar-preview');

    const poInput = document.getElementById('po_number');
    const poSuggestions = document.getElementById('po_suggestions');
    const poPreview = document.getElementById('po_preview');
    const poItemsGroup = document.getElementById('po_items_group');
    const poItemsBox = document.getElementById('po_items_box');
    const directionSelect = document.getElementById('direction');
    const vendorType = '{{ $vendorType ?? '' }}';
    const defaultDirection = '{{ $defaultDirection ?? '' }}';

    const urlPoSearch = '{{ route('vendor.ajax.po_search') }}';
    const urlPoDetailTemplate = '{{ route('vendor.ajax.po_detail', ['poNumber' => '__PO__']) }}';

    let poDebounceTimer = null;
    let directionTouched = false;

    function escapeHtml(str) {
        return String(str || '').replace(/[&<>"']/g, function (m) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[m]);
        });
    }

    function closePoSuggestions() {
        if (!poSuggestions) return;
        poSuggestions.style.display = 'none';
        poSuggestions.innerHTML = '';
    }

    function showPoSuggestionsMessage(html) {
        if (!poSuggestions) return;
        poSuggestions.innerHTML = html;
        poSuggestions.style.display = 'block';
    }

    function renderPoSuggestions(items) {
        if (!poSuggestions) return;
        if (!items || items.length === 0) {
            showPoSuggestionsMessage(
                '<div style="padding:12px; color:#64748b; font-size:13px;">No PO/DO Found for Your Vendor</div>'
            );
            return;
        }
        let html = '';
        items.forEach(function (it) {
            const po = it.po_number || '';
            const vendorName = it.vendor_name || '';
            const dir = it.direction || '';
            html += '<div class="po-item" data-po="' + escapeHtml(po) + '" data-dir="' + escapeHtml(dir) + '" '
                + 'style="padding:10px 12px; cursor:pointer; border-bottom:1px solid #f1f5f9;">'
                + '<div style="font-weight:600;">' + escapeHtml(po) + '</div>'
                + '<div style="font-size:12px; color:#64748b;">' + escapeHtml(vendorName) + (dir ? (' | ' + escapeHtml(dir)) : '') + '</div>'
                + '</div>';
        });
        poSuggestions.innerHTML = html;
        poSuggestions.style.display = 'block';
    }

    function setPoPreview(data) {
        if (!poPreview) return;
        if (!data) {
            poPreview.innerHTML = '';
            if (poItemsGroup) poItemsGroup.style.display = 'none';
            if (poItemsBox) poItemsBox.innerHTML = '';
            return;
        }
        const vendorName = data.vendor_name || '-';
        const vendorCode = data.vendor_code || '';
        const direction = data.direction || '';
        poPreview.innerHTML = ''
            + '<div style="padding:10px 12px; border:1px solid #e5e7eb; border-radius:10px; background:#f8fafc;">'
            + '<div style="font-size:12px; color:#64748b; margin-bottom:4px;">PO Preview</div>'
            + '<div style="font-weight:600;">' + escapeHtml(data.po_number || '') + '</div>'
            + '<div style="font-size:12px; color:#475569;">Vendor: ' + escapeHtml(vendorName) + (vendorCode ? (' (' + escapeHtml(vendorCode) + ')') : '') + '</div>'
            + (direction ? ('<div style="font-size:12px; color:#475569;">Direction: ' + escapeHtml(direction) + '</div>') : '')
            + '</div>';
    }

    function renderPoItems(items) {
        if (!poItemsGroup || !poItemsBox) return;
        if (!Array.isArray(items) || items.length === 0) {
            poItemsGroup.style.display = 'none';
            poItemsBox.innerHTML = '';
            return;
        }

        function formatQty(val) {
            const n = Number(val);
            if (!isFinite(n)) return String(val ?? '');
            const isInt = Math.abs(n - Math.round(n)) < 1e-9;
            const fmt = new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: isInt ? 0 : 3,
            });
            return fmt.format(n);
        }

        let html = '';
        html += '<div style="border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">';
        html += '<div style="display:grid; grid-template-columns: 90px 1fr 110px 110px; gap:0; background:#f8fafc; padding:10px 12px; font-weight:600; font-size:12px; color:#475569;">'
            + '<div>Item</div><div>Material</div><div style="text-align:right;">Remaining</div><div>Qty Now</div>'
            + '</div>';

        items.forEach(function(it, idx) {
            const itemNo = it.item_no || '';
            const mat = (it.material || '') + (it.description ? (' - ' + it.description) : '');
            const uom = it.uom || '';
            const remaining = (it.remaining_qty != null ? it.remaining_qty : 0);

            html += '<div style="display:grid; grid-template-columns: 90px 1fr 110px 110px; gap:0; padding:10px 12px; border-top:1px solid #f1f5f9; align-items:center;">'
                + '<div style="font-weight:600;">' + escapeHtml(itemNo) + '</div>'
                + '<div style="font-size:12px; color:#475569;">' + escapeHtml(mat) + '</div>'
                + '<div style="text-align:right; font-size:12px; color:#0f172a;">' + escapeHtml(formatQty(remaining)) + ' ' + escapeHtml(uom) + '</div>'
                + '<div>'
                + '<input type="number" min="0" step="0.001" name="po_items[' + escapeHtml(itemNo) + '][qty]"'
                + ' style="width:100%; padding:8px 10px; border:2px solid #e5e7eb; border-radius:10px;"'
                + ' max="' + escapeHtml(remaining) + '" />'
                + '</div>'
                + '</div>';
        });

        html += '</div>';
        poItemsBox.innerHTML = html;
        poItemsGroup.style.display = 'block';
    }

    function getJson(url) {
        return fetch(url, { headers: { 'Accept': 'application/json' } }).then(r => r.json());
    }

    function fetchPoDetail(poNumber) {
        if (!poNumber) {
            setPoPreview(null);
            return;
        }
        const url = String(urlPoDetailTemplate || '').replace('__PO__', encodeURIComponent(poNumber));
        getJson(url)
            .then(function (resp) {
                if (!resp || !resp.success) {
                    setPoPreview(null);
                    if (poItemsGroup && poItemsBox) {
                        const msg = (resp && resp.message) ? String(resp.message) : 'PO Detail Not Found or Not Assigned to Your Vendor.';
                        poItemsBox.innerHTML = '<div style="padding:10px 12px; border:1px solid #fecaca; background:#fef2f2; color:#991b1b; border-radius:10px; font-size:13px;">' + escapeHtml(msg) + '</div>';
                        poItemsGroup.style.display = 'block';
                    }
                    return;
                }
                const data = resp.data || null;
                setPoPreview(data);
                renderPoItems((data && data.items) ? data.items : []);
                if (!directionTouched && directionSelect && data && data.direction) {
                    directionSelect.value = String(data.direction);
                }
            })
            .catch(function () {
                setPoPreview(null);
                if (poItemsGroup && poItemsBox) {
                    poItemsBox.innerHTML = '<div style="padding:10px 12px; border:1px solid #fecaca; background:#fef2f2; color:#991b1b; border-radius:10px; font-size:13px;">Failed to Load PO Detail. Please Try Again.</div>';
                    poItemsGroup.style.display = 'block';
                }
            });
    }

    if (directionSelect) {
        directionSelect.addEventListener('change', function () {
            directionTouched = true;
        });
    }

    if (directionSelect && !directionSelect.value && defaultDirection) {
        directionSelect.value = defaultDirection;
    }

    if (poInput) {
        poInput.addEventListener('input', function () {
            const q = (poInput.value || '').trim();
            setPoPreview(null);
            if (poDebounceTimer) clearTimeout(poDebounceTimer);
            poDebounceTimer = setTimeout(function () {
                if (!q) {
                    closePoSuggestions();
                    return;
                }
                showPoSuggestionsMessage(
                    '<div style="padding:12px; color:#64748b; font-size:13px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>'
                );
                getJson(urlPoSearch + '?q=' + encodeURIComponent(q))
                    .then(function (resp) {
                        if (!resp || !resp.success) {
                            showPoSuggestionsMessage(
                                '<div style="padding:12px; color:#ef4444; font-size:13px;">Failed to Load Suggestions</div>'
                            );
                            return;
                        }
                        renderPoSuggestions(resp.data || []);
                    })
                    .catch(function () {
                        showPoSuggestionsMessage(
                            '<div style="padding:12px; color:#ef4444; font-size:13px;">Failed to Load Suggestions</div>'
                        );
                    });
            }, 250);
        });

        poInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const q = (poInput.value || '').trim();
                if (q) {
                    closePoSuggestions();
                    fetchPoDetail(q);
                }
            }
        });

        poInput.addEventListener('blur', function () {
            const q = (poInput.value || '').trim();
            if (q) {
                fetchPoDetail(q);
            }
        });

        poInput.addEventListener('focus', function () {
            const q = (poInput.value || '').trim();
            if (!q) return;
            showPoSuggestionsMessage(
                '<div style="padding:12px; color:#64748b; font-size:13px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>'
            );
            getJson(urlPoSearch + '?q=' + encodeURIComponent(q))
                .then(function (resp) {
                    if (!resp || !resp.success) {
                        showPoSuggestionsMessage(
                            '<div style="padding:12px; color:#ef4444; font-size:13px;">Failed to Load Suggestions</div>'
                        );
                        return;
                    }
                    renderPoSuggestions(resp.data || []);
                })
                .catch(function () {
                    showPoSuggestionsMessage(
                        '<div style="padding:12px; color:#ef4444; font-size:13px;">Failed to Load Suggestions</div>'
                    );
                });
        });
    }

    if (poSuggestions) {
        poSuggestions.addEventListener('click', function (e) {
            const item = e.target.closest('.po-item');
            if (!item || !poInput) return;
            const po = item.getAttribute('data-po') || '';
            poInput.value = po;
            closePoSuggestions();
            fetchPoDetail(po);

            const dir = item.getAttribute('data-dir') || '';
            if (!directionTouched && directionSelect && dir) {
                directionSelect.value = dir;
            }
        });
    }

    document.addEventListener('click', function (e) {
        if (!poSuggestions || !poInput) return;
        if (e.target === poInput || poSuggestions.contains(e.target)) return;
        closePoSuggestions();
    });

    if (poInput && (poInput.value || '').trim() !== '') {
        fetchPoDetail((poInput.value || '').trim());
    }

    // Gate data from backend
    const gatesData = @json($gates);

    // Update gates when warehouse changes
    warehouseSelect.addEventListener('change', function() {
        const warehouseId = this.value;
        gateSelect.innerHTML = '<option value="">Auto-assign by Admin</option>';

        if (warehouseId && gatesData[warehouseId]) {
            gatesData[warehouseId].forEach(function(gate) {
                const option = document.createElement('option');
                option.value = gate.id;
                option.textContent = gate.name || 'Gate ' + gate.gate_number;
                gateSelect.appendChild(option);
            });
        }

        loadCalendarPreview();
    });

    // Update duration when truck type changes
    truckTypeSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const duration = selectedOption.dataset.duration;
        if (duration) {
            durationInput.value = duration;
        }
        checkAvailability();
    });

    // Check availability when inputs change
    [dateInput, timeInput, durationInput, gateSelect].forEach(function(input) {
        input.addEventListener('change', checkAvailability);
    });

    dateInput.addEventListener('change', loadCalendarPreview);

    function checkAvailability() {
        const warehouseId = warehouseSelect.value;
        const gateId = gateSelect.value;
        const date = dateInput.value;
        const time = timeInput.value;
        const duration = durationInput.value;

        if (!warehouseId || !date || !time || !duration) {
            availabilityCheck.style.display = 'none';
            return;
        }

        const plannedStart = date + ' ' + time + ':00';

        fetch(`{{ route('vendor.ajax.check_availability') }}?warehouse_id=${warehouseId}&gate_id=${gateId}&planned_start=${encodeURIComponent(plannedStart)}&planned_duration=${duration}`)
            .then(response => response.json())
            .then(data => {
                availabilityCheck.style.display = 'block';
                
                if (data.available) {
                    let riskText = '';
                    let riskColor = '#10b981';
                    
                    if (data.blocking_risk === 0) {
                        riskText = 'Low Risk - Good Time Slot!';
                    } else if (data.blocking_risk === 1) {
                        riskText = 'Medium Risk - Some Overlap with Other Bookings';
                        riskColor = '#f59e0b';
                    } else {
                        riskText = 'High Risk - Consider Another Time';
                        riskColor = '#ef4444';
                    }
                    
                    availabilityResult.innerHTML = `
                        <div style="display: flex; align-items: center; gap: 0.5rem; color: #10b981;">
                            <i class="fas fa-check-circle"></i>
                            <span>Time Slot Is Available</span>
                        </div>
                        <div style="margin-top: 0.5rem; color: ${riskColor}; font-size: 0.875rem;">
                            <i class="fas fa-info-circle"></i>
                            ${riskText}
                        </div>
                    `;
                } else {
                    availabilityResult.innerHTML = `
                        <div style="display: flex; align-items: center; gap: 0.5rem; color: #ef4444;">
                            <i class="fas fa-times-circle"></i>
                            <span>${data.reason || 'Time Slot Is Not Available'}</span>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error checking availability:', error);
            });
    }

    function loadCalendarPreview() {
        const warehouseId = warehouseSelect.value;
        const date = dateInput.value;

        if (!warehouseId || !date) {
            calendarPreview.innerHTML = '<p style="text-align: center; color: #64748b; padding: 2rem;">Select a Warehouse and Date to See Availability</p>';
            return;
        }

        calendarPreview.innerHTML = '<p style="text-align: center; color: #64748b; padding: 2rem;"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';

        fetch(`{{ route('vendor.ajax.calendar_slots') }}?warehouse_id=${warehouseId}&date=${date}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.gates) {
                    renderCalendar(data.gates, date);
                }
            })
            .catch(error => {
                calendarPreview.innerHTML = '<p style="text-align: center; color: #ef4444; padding: 2rem;">Failed to Load Availability</p>';
            });
    }

    function renderCalendar(gates, date) {
        const hours = [];
        for (let h = 7; h < 23; h++) {
            hours.push(h.toString().padStart(2, '0') + ':00');
        }

        let html = '<div style="overflow-x: auto;"><table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">';
        html += '<thead><tr><th style="padding: 0.5rem; border: 1px solid #e5e7eb; background: #f8fafc;">Time</th>';
        
        gates.forEach(g => {
            html += `<th style="padding: 0.5rem; border: 1px solid #e5e7eb; background: #f8fafc; min-width: 120px;">${g.gate.name}</th>`;
        });
        html += '</tr></thead><tbody>';

        hours.forEach(hour => {
            html += `<tr><td style="padding: 0.5rem; border: 1px solid #e5e7eb; font-weight: 500;">${hour}</td>`;
            
            gates.forEach(g => {
                const slot = g.slots.find(s => s.start_time === hour || (s.start_time < hour && s.end_time > hour));
                
                if (slot && slot.start_time === hour) {
                    const statusColors = {
                        'pending_approval': '#fef3c7',
                        'scheduled': '#dcfce7',
                        'arrived': '#dbeafe',
                        'in_progress': '#ede9fe',
                        'pending_vendor_confirmation': '#fce7f3'
                    };
                    const color = statusColors[slot.status] || '#f3f4f6';
                    const rowspan = Math.ceil(slot.duration / 60) || 1;
                    
                    html += `<td style="padding: 0.5rem; border: 1px solid #e5e7eb; background: ${color}; vertical-align: top;" rowspan="${rowspan}">
                        <div style="font-weight: 500;">${slot.start_time} - ${slot.end_time}</div>
                        <div style="font-size: 0.75rem; color: #64748b;">${slot.vendor_name}</div>
                        <div style="font-size: 0.7rem; margin-top: 0.25rem;">${slot.status_label}</div>
                    </td>`;
                } else if (!slot || slot.start_time === hour) {
                    // Check if current hour is occupied by a slot from earlier
                    const occupiedSlot = g.slots.find(s => s.start_time < hour && s.end_time > hour);
                    if (!occupiedSlot) {
                        html += `<td style="padding: 0.5rem; border: 1px solid #e5e7eb; background: #f0fdf4; text-align: center; color: #16a34a;">
                            <i class="fas fa-check" style="opacity: 0.5;"></i>
                        </td>`;
                    }
                }
            });
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        html += '<div style="margin-top: 1rem; display: flex; gap: 1rem; flex-wrap: wrap; font-size: 0.75rem;">';
        html += '<span><span style="display: inline-block; width: 12px; height: 12px; background: #f0fdf4; border: 1px solid #e5e7eb; margin-right: 0.25rem;"></span> Available</span>';
        html += '<span><span style="display: inline-block; width: 12px; height: 12px; background: #fef3c7; border: 1px solid #e5e7eb; margin-right: 0.25rem;"></span> Pending Approval</span>';
        html += '<span><span style="display: inline-block; width: 12px; height: 12px; background: #dcfce7; border: 1px solid #e5e7eb; margin-right: 0.25rem;"></span> Scheduled</span>';
        html += '<span><span style="display: inline-block; width: 12px; height: 12px; background: #ede9fe; border: 1px solid #e5e7eb; margin-right: 0.25rem;"></span> In Progress</span>';
        html += '</div>';

        calendarPreview.innerHTML = html;
    }

    // Initial load
    if (warehouseSelect.value) {
        warehouseSelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endpush
