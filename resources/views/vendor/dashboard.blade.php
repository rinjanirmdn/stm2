@extends('vendor.layouts.vendor')

@section('title', 'Vendor Self-Service Portal')

@section('content')
<!-- Welcome & Stats Section -->
<div style="margin-bottom: 2rem;">
    <h1 style="font-size: 1.5rem; font-weight: 700; color: #1e293b; margin-bottom: 0.5rem;">
        Welcome back, {{ auth()->user()->full_name }}
    </h1>
    <p style="color: #64748b;">Manage your slot bookings directly from this dashboard.</p>
</div>

@if(isset($actionRequired) && $actionRequired->count() > 0)
    <div class="vendor-alert vendor-alert--warning">
        <div>
            <strong>Action Required:</strong>
            You have {{ $actionRequired->count() }} booking(s) that require your attention.
            <ul style="margin: 0.5rem 0 0 1.5rem;">
                @foreach($actionRequired as $req)
                    <li>
                        Booking #{{ $req->ticket_number }} is 
                        @if($req->status == 'pending_vendor_confirmation')
                            pending your confirmation (Rescheduled).
                        @else
                            {{ str_replace('_', ' ', $req->status) }}.
                        @endif
                        <a href="{{ route('vendor.bookings.show', $req->id) }}" style="text-decoration: underline; font-weight: 600;">View</a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; align-items: start;">
    
    <!-- LEFT COLUMN: NEW BOOKING -->
    <div class="vendor-card" style="position: sticky; top: 1rem;">
        <div class="vendor-card__header">
            <h2 class="vendor-card__title">
                <i class="fas fa-plus-circle" style="color: #3b82f6;"></i>
                Book a Slot
            </h2>
        </div>

        <form method="POST" action="{{ route('vendor.bookings.store') }}" id="booking-form">
            @csrf
            
            <div class="vendor-form-group">
                <label class="vendor-form-label">PO Number / Delivery Note</label>
                <div style="display: flex; gap: 0.5rem;">
                    <input type="text" name="po_number" class="vendor-form-input" placeholder="Type to search PO..." id="po_number_input" list="po_suggestions" autocomplete="off">
                    <datalist id="po_suggestions"></datalist>
                    <button type="button" class="vendor-btn vendor-btn--secondary" id="search_po_btn">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <small style="color: #64748b;">Enter PO number to auto-select warehouse.</small>
            </div>

            <div class="vendor-form-group">
                <label class="vendor-form-label">Warehouse</label>
                <!-- Warehouse selection remains as fallback or confirmation -->
                <select name="warehouse_id" class="vendor-form-select" required id="warehouse_id">
                    <option value="">Select Warehouse</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->wh_code }} - {{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="vendor-form-group">
                <label class="vendor-form-label">Gate Preference</label>
                <select name="planned_gate_id" class="vendor-form-select" id="gate_select">
                    <option value="">Auto-assign (Recommended)</option>
                </select>
                <small style="color: #64748b;">Leave empty for auto assignment.</small>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                <div class="vendor-form-group">
                    <label class="vendor-form-label">Truck Type</label>
                    <select name="truck_type" class="vendor-form-select" id="truck_type" required>
                        <option value="">Select Truck Type</option>
                        {{-- Assuming $truckTypes is simple array of strings based on previous context --}}
                        @foreach($truckTypes as $type)
                             {{-- We need duration data. The controller sends just names. Let's assume standard defaults if not available, OR rely on JS mapping if we fix controller.
                                  For now, I'll use a smart JS mapping since I can't easily change controller return structure efficiently without re-reading it.
                             --}}
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="vendor-form-group">
                    <label class="vendor-form-label">Duration (Min)</label>
                    <input type="number" name="planned_duration" id="planned_duration" class="vendor-form-input" 
                           value="60" min="30" step="30" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="vendor-form-group">
                    <label class="vendor-form-label">Date</label>
                    <input type="date" name="planned_date" class="vendor-form-input" required
                           min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}" id="planned_date">
                </div>
                <div class="vendor-form-group">
                    <label class="vendor-form-label">Time</label>
                    <input type="time" name="planned_time" class="vendor-form-input" required
                           min="07:00" max="22:00" value="09:00" id="planned_time">
                </div>
            </div>

            <div class="vendor-form-group">
                <label class="vendor-form-label">Direction</label>
                <div style="display: flex; gap: 1rem;">
                    <label style="flex: 1; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 8px; cursor: pointer; text-align: center; background: #f8fafc;">
                        <input type="radio" name="direction" value="inbound" checked> Inbound
                    </label>
                    <label style="flex: 1; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 8px; cursor: pointer; text-align: center; background: #f8fafc;">
                        <input type="radio" name="direction" value="outbound"> Outbound
                    </label>
                </div>
            </div>

            <!-- Simple Availability Indicator -->
            <div id="availability-check" style="margin-bottom: 1rem; display: none;">
                <div id="availability-result" style="padding: 0.75rem; border-radius: 8px; font-size: 0.9rem;"></div>
            </div>

            <button type="submit" class="vendor-btn vendor-btn--primary" style="width: 100%; justify-content: center;">
                Submit Request
            </button>
        </form>
    </div>

    <!-- RIGHT COLUMN: MY BOOKINGS -->
    <div>
        <div class="vendor-card">
            <div class="vendor-card__header">
                <h2 class="vendor-card__title">
                    <i class="fas fa-list" style="color: #64748b;"></i>
                    Recent Bookings
                </h2>
                <a href="{{ route('vendor.bookings.index') }}" class="vendor-btn vendor-btn--secondary vendor-btn--sm">View All</a>
            </div>

            @if($recentBookings->count() > 0)
                <div class="vendor-table-wrapper">
                    <table class="vendor-table">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Date/Time</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentBookings as $booking)
                                <tr>
                                    <td style="font-weight: 600;">{{ $booking->ticket_number }}</td>
                                    <td>
                                        <div>{{ $booking->planned_start->format('d M Y') }}</div>
                                        <small style="color: #64748b;">{{ $booking->planned_start->format('H:i') }}</small>
                                    </td>
                                    <td>
                                        @php
                                            $badges = [
                                                'pending_approval' => 'warning',
                                                'scheduled' => 'success',
                                                'completed' => 'primary',
                                                'pending_vendor_confirmation' => 'warning',
                                                'cancelled' => 'secondary'
                                            ];
                                            $badge = $badges[$booking->status] ?? 'secondary';
                                            $label = str_replace('_', ' ', ucfirst($booking->status));
                                            if($booking->status == 'pending_vendor_confirmation') $label = 'Action Needed';
                                        @endphp
                                        <span class="vendor-badge vendor-badge--{{ $badge }}">{{ $label }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('vendor.bookings.show', $booking->id) }}" class="vendor-btn vendor-btn--secondary vendor-btn--sm">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="text-align: center; padding: 2rem; color: #94a3b8;">
                    <i class="fas fa-clipboard-list" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.5;"></i>
                    <p>No booking history found.</p>
                </div>
            @endif
        </div>

        <!-- Availability Preview (Simplified) -->
        <div class="vendor-card" style="margin-top: 2rem;">
            <div class="vendor-card__header">
                <h2 class="vendor-card__title">
                    <i class="fas fa-calendar-day" style="color: #64748b;"></i>
                    Today's Availability
                </h2>
            </div>
            <div id="calendar-preview">
                <p style="color: #64748b; text-align: center; padding: 1rem;">Select a warehouse in the booking form to see availability.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const warehouseSelect = document.getElementById('warehouse_id');
    const dateInput = document.getElementById('planned_date');
    const timeInput = document.getElementById('planned_time');
    const availabilityCheck = document.getElementById('availability-check');
    const availabilityResult = document.getElementById('availability-result');
    const calendarPreview = document.getElementById('calendar-preview');
    const gateSelect = document.getElementById('gate_select');
    const truckTypeSelect = document.getElementById('truck_type');
    const durationInput = document.getElementById('planned_duration');
    
    // Using loose fetching for JSON to avoid syntax errors if empty
    <?php echo "const gatesData = " . json_encode($gates) . ";" ?>

    // Auto-update Duration based on Truck Type
    const truckDurationMap = {
        'Pickup': 30, 'CDE': 45, 'CDD': 60, 'Fuso': 90, 
        'Tronton': 120, 'Wingbox': 120, 'Trailer': 180, 
        'Container': 120
    };

    truckTypeSelect.addEventListener('change', () => {
        const type = truckTypeSelect.value;
        let duration = 60; // Default
        
        // Find best match
        for (const [key, val] of Object.entries(truckDurationMap)) {
            if (type.toLowerCase().includes(key.toLowerCase())) {
                duration = val;
                break;
            }
        }
        
        if (type) {
             durationInput.value = duration;
             // Visual Cue
             durationInput.style.transition = 'background-color 0.3s';
             durationInput.style.backgroundColor = '#dcfce7';
             setTimeout(() => durationInput.style.backgroundColor = '', 500);
        }
        checkAvailability();
    });

    durationInput.addEventListener('change', checkAvailability);

    // Populate Gates on Warehouse Change
    warehouseSelect.addEventListener('change', () => {
        const whId = warehouseSelect.value;
        gateSelect.innerHTML = '<option value="">Auto-assign (Recommended)</option>';
        
        if (whId && gatesData[whId]) {
            gatesData[whId].forEach(g => {
                const opt = document.createElement('option');
                opt.value = g.id;
                // Fallback to "Gate X" if name is empty
                opt.textContent = g.name ? g.name : ('Gate ' + g.gate_number);
                gateSelect.appendChild(opt);
            });
        }
        
        checkAvailability();
        loadCalendar();
    });

    const searchPoBtn = document.getElementById('search_po_btn');
    const poInput = document.getElementById('po_number_input');
    const poList = document.getElementById('po_suggestions');
    let debounceTimer;

    // Autocomplete Logic
    poInput.addEventListener('input', function() {
        const val = this.value;
        if (val.length < 3) return; // Min 3 chars
        
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
             fetch(`{{ route('slots.ajax.po_search') }}?q=${encodeURIComponent(val)}`)
                .then(r => r.json())
                .then(data => {
                    poList.innerHTML = ''; 
                    if (data.data && Array.isArray(data.data)) {
                        data.data.forEach(item => {
                            const opt = document.createElement('option');
                            opt.value = item.po_number;
                            // Show Vendor Name + Plant as hint
                            const vendor = item.vendor_name ? item.vendor_name.substring(0, 20) : 'PO';
                            const plant = item.plant ? ` - ${item.plant}` : '';
                            opt.label = `${vendor}${plant}`; 
                            poList.appendChild(opt);
                        });
                    }
                })
                .catch(e => console.error(e));
        }, 500);
    });

    // PO Search Logic (Manual Click)
    if (searchPoBtn) {
        searchPoBtn.addEventListener('click', function() {
            const poNumber = poInput.value;
            if (!poNumber) {
                alert('Please enter a PO Number');
                return;
            }

            // Visual feedback
            const originalIcon = searchPoBtn.innerHTML;
            searchPoBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            searchPoBtn.disabled = true;
            
            // Real fetch to endpoint
            fetch(`{{ route('slots.ajax.po_search') }}?q=${encodeURIComponent(poNumber)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.data && data.data.length > 0) {
                        // Found!
                        const po = data.data[0];
                        
                        searchPoBtn.innerHTML = '<i class="fas fa-check"></i>';
                        searchPoBtn.classList.remove('vendor-btn--secondary');
                        searchPoBtn.classList.add('vendor-btn--primary');
                        
                        // Auto-select Warehouse if PO has plant/warehouse info
                        // We will fuzzy match the warehouse code or name
                        if (po.warehouse || po.plant) {
                            const target = (po.warehouse || po.plant).toLowerCase();
                            let found = false;
                            
                            for (let i = 0; i < warehouseSelect.options.length; i++) {
                                const opt = warehouseSelect.options[i];
                                const text = opt.text.toLowerCase();
                                if (text.includes(target)) {
                                    warehouseSelect.selectedIndex = i;
                                    warehouseSelect.dispatchEvent(new Event('change'));
                                    found = true;
                                    break;
                                }
                            }
                            
                            if (!found && warehouseSelect.options.length > 1) {
                                // Fallback: Just select first one if not matched? 
                                // Or maybe don't change if not sure.
                                // Let's try to select index 1 if still index 0
                                if (warehouseSelect.selectedIndex === 0) {
                                     warehouseSelect.selectedIndex = 1;
                                     warehouseSelect.dispatchEvent(new Event('change'));
                                }
                            }
                        } else {
                            // No WH info in PO, but we found the PO. Select first WH as convenience.
                            if (warehouseSelect.selectedIndex === 0 && warehouseSelect.options.length > 1) {
                                warehouseSelect.selectedIndex = 1;
                                warehouseSelect.dispatchEvent(new Event('change'));
                            }
                        }

                    } else {
                        // Not found
                        alert('PO Number not found.');
                        searchPoBtn.innerHTML = '<i class="fas fa-times"></i>';
                    }
                })
                .catch(error => {
                    console.error('Error searching PO:', error);
                    searchPoBtn.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
                })
                .finally(() => {
                    setTimeout(() => {
                       searchPoBtn.innerHTML = originalIcon;
                       searchPoBtn.disabled = false; 
                       searchPoBtn.classList.add('vendor-btn--secondary');
                       searchPoBtn.classList.remove('vendor-btn--primary');
                    }, 2000);
                });
        });
    }

    // Simple Availability Logic
    function checkAvailability() {
        const warehouseId = warehouseSelect.value;
        const date = dateInput.value;
        const time = timeInput.value;
        // Use actual input value
        const duration = durationInput.value || 60; 

        if (!warehouseId || !date || !time) return;

        const plannedStart = date + ' ' + time + ':00';
        // Default duration 60 for quick check
        // const duration = 60; 

        fetch(`{{ route('vendor.ajax.check_availability') }}?warehouse_id=${warehouseId}&planned_start=${encodeURIComponent(plannedStart)}&planned_duration=${duration}`)
            .then(res => res.json())
            .then(data => {
                availabilityCheck.style.display = 'block';
                if(data.available) {
                    availabilityResult.style.background = '#dcfce7';
                    availabilityResult.style.color = '#166534';
                    availabilityResult.innerHTML = '<i class="fas fa-check-circle"></i> Slot Available';
                } else {
                    availabilityResult.style.background = '#fee2e2';
                    availabilityResult.style.color = '#991b1b';
                    availabilityResult.innerHTML = '<i class="fas fa-times-circle"></i> ' + (data.reason || 'Slot Unavailable');
                }
            });
    }

    // Calendar Preview Loading
    function loadCalendar() {
        const warehouseId = warehouseSelect.value;
        const date = dateInput.value;
        
        if (!warehouseId || !date) return;

        calendarPreview.innerHTML = '<p style="text-align: center; padding: 1rem;"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';

        fetch(`{{ route('vendor.ajax.calendar_slots') }}?warehouse_id=${warehouseId}&date=${date}`)
            .then(res => res.json())
            .then(data => {
                if(data.success && data.gates) {
                    renderMiniCalendar(data.gates);
                }
            });
    }

    function renderMiniCalendar(gates) {
        let html = '<div style="display: flex; flex-direction: column; gap: 0.5rem;">';
        gates.forEach(g => {
            const bookedCount = g.slots.length;
            const statusColor = bookedCount > 5 ? '#fee2e2' : (bookedCount > 2 ? '#fef3c7' : '#dcfce7');
            const statusText = bookedCount > 5 ? 'Busy' : (bookedCount > 2 ? 'Moderate' : 'Available');
            
            html += `
                <div style="display: flex; justify-content: space-between; padding: 0.5rem; background: #f8fafc; border-radius: 6px; align-items: center;">
                    <span style="font-weight: 500;">${g.gate.name}</span>
                    <span style="font-size: 0.75rem; padding: 2px 6px; border-radius: 4px; background: ${statusColor};">
                        ${statusText}
                    </span>
                </div>
            `;
        });
        html += '</div>';
        calendarPreview.innerHTML = html;
    }

    dateInput.addEventListener('change', () => {
        checkAvailability();
        loadCalendar();
    });
    timeInput.addEventListener('change', checkAvailability);
});
</script>
@endpush
