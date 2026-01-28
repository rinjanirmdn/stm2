@extends('vendor.layouts.vendor')

@section('title', 'Create Booking - Vendor Portal')

@section('page_class', 'vendor-page--layout vendor-page--bookings-create')

@section('content')

<div class="cb-container">
    <div class="cb-scroll-container">
        <div class="cb-content-container">
            <div class="cb-header">
                <h1 class="cb-header__title">
                    <i class="fas fa-plus-circle"></i>
                    Create New Booking
                </h1>
                <a href="{{ route('vendor.bookings.index') }}" class="cb-btn cb-btn--secondary">
                    <i class="fas fa-arrow-left"></i> Back to Bookings
                </a>
            </div>

            @if(session('error'))
                <div class="cb-alert cb-alert--error">
                    <i class="fas fa-exclamation-circle"></i>
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('vendor.bookings.store') }}" enctype="multipart/form-data" id="booking-form">
                @csrf

                <div class="cb-form">
                    <!-- PO Selection Section -->
                    <div class="cb-section cb-section--full">
                        <h3 class="cb-section__title">
                            <i class="fas fa-file-invoice"></i>
                            PO/DO Selection
                        </h3>

                        <div class="cb-field">
                            <label class="cb-label cb-label--required">PO/DO Number</label>
                            <div class="cb-po-search">
                                <input type="text" 
                                       id="po-search" 
                                       class="cb-input" 
                                       placeholder="Search PO/DO number..."
                                       autocomplete="off"
                                       value="{{ old('po_number') }}">
                                <input type="hidden" name="po_number" id="po-number-hidden" value="{{ old('po_number') }}">
                                <div class="cb-po-results" id="po-results"></div>
                            </div>
                            <div class="cb-loading" id="po-loading">
                                <div class="cb-spinner"></div>
                                <span>Searching...</span>
                            </div>
                            @error('po_number')
                                <div class="cb-hint cb-hint--error">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- PO Items Table -->
                        <div id="po-items-container" class="cb-po-items-container">
                            <label class="cb-label">PO Items</label>
                            <table class="cb-po-items-table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Material</th>
                                        <th>PO Qty</th>
                                        <th>GR Total</th>
                                        <th>Remaining</th>
                                        <th>Book Qty</th>
                                    </tr>
                                </thead>
                                <tbody id="po-items-body"></tbody>
                            </table>
                            <div class="cb-hint">Enter quantities for items you want to book.</div>
                        </div>
                    </div>

                    <!-- Row 1: Schedule + Live Availability -->
                    <div class="cb-row cb-row--sections">
                        <!-- Schedule Section -->
                        <div class="cb-section">
                            <h3 class="cb-section__title">
                                <i class="fas fa-calendar-alt"></i>
                                Schedule
                            </h3>

                            <div class="cb-field">
                                <label class="cb-label cb-label--required">Date</label>
                                <input type="text" 
                                       name="planned_date" 
                                       class="cb-input" 
                                       id="planned-date"
                                       autocomplete="off"
                                       readonly
                                       value="{{ old('planned_date') }}"
                                       required>
                                <div class="cb-hint">Minimum 4 hours from now. No Sundays or holidays.</div>
                                @error('planned_date')
                                    <div class="cb-hint cb-hint--error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="cb-field">
                                <label class="cb-label cb-label--required">Time</label>
                                <input type="text" 
                                       name="planned_time" 
                                       class="cb-input" 
                                       id="planned-time"
                                       inputmode="none"
                                       readonly
                                       value="{{ old('planned_time', '08:00') }}"
                                       required>
                                <div class="cb-hint">Operating hours: 07:00 - 19:00</div>
                                @error('planned_time')
                                    <div class="cb-hint cb-hint--error">{{ $message }}</div>
                                @enderror
                            </div>

                            <input type="hidden" name="planned_duration" id="planned-duration" value="{{ old('planned_duration', 60) }}">
                            <input type="hidden" name="planned_start" id="planned-start" value="{{ old('planned_start') }}">
                        </div>

                        <!-- Mini Availability Section -->
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

                    <!-- Row 2: Vehicle + Documents/Notes Stack -->
                    <div class="cb-row cb-row--sections">
                        <!-- Vehicle & Driver Section -->
                        <div class="cb-section">
                            <h3 class="cb-section__title">
                                <i class="fas fa-truck"></i>
                                Vehicle & Driver
                            </h3>

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

                        <div class="cb-stack">
                            <!-- Documents Section -->
                            <div class="cb-section">
                                <h3 class="cb-section__title">
                                    <i class="fas fa-file-pdf"></i>
                                    Documents
                                </h3>

                                <div class="cb-field">
                                    <label class="cb-label cb-label--required">COA (Certificate of Analysis)</label>
                                    <input type="file" 
                                           name="coa_pdf" 
                                           class="cb-file-input" 
                                           accept=".pdf"
                                           required>
                                    <div class="cb-hint">PDF only, max 10MB</div>
                                    <div class="cb-hint cb-hint--error cb-file-error" id="coa-error" hidden>File too large. Max 10MB.</div>
                                    @error('coa_pdf')
                                        <div class="cb-hint cb-hint--error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Notes Section -->
                            <div class="cb-section">
                                <h3 class="cb-section__title">
                                    <i class="fas fa-sticky-note"></i>
                                    Additional Notes
                                </h3>

                                <div class="cb-field">
                                    <textarea name="notes" 
                                              class="cb-textarea" 
                                              placeholder="Any additional information..."
                                              maxlength="500">{{ old('notes') }}</textarea>
                                    <div class="cb-hint">Maximum 500 characters</div>
                                    @error('notes')
                                        <div class="cb-hint cb-hint--error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cb-actions">
                    <a href="{{ route('vendor.bookings.index') }}" class="cb-btn cb-btn--secondary">
                        Cancel
                    </a>
                    <button type="submit" class="cb-btn cb-btn--primary" id="submit-btn">
                        <i class="fas fa-paper-plane"></i>
                        Submit Booking Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const poSearch = document.getElementById('po-search');
    const poResults = document.getElementById('po-results');
    const poHidden = document.getElementById('po-number-hidden');
    const poLoading = document.getElementById('po-loading');
    const poItemsContainer = document.getElementById('po-items-container');
    const poItemsBody = document.getElementById('po-items-body');

    let searchTimeout = null;

    // PO Search
    poSearch.addEventListener('input', function() {
        const q = this.value.trim();
        
        if (q.length < 2) {
            poResults.classList.remove('show');
            return;
        }

        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            poLoading.classList.add('show');
            
            fetch('{{ route("vendor.ajax.po_search") }}?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {
                    poLoading.classList.remove('show');
                    
                    if (data.success && data.data.length > 0) {
                        poResults.innerHTML = data.data.map(po => `
                            <div class="cb-po-item" data-po="${po.po_number}">
                                <div class="cb-po-item__number">${po.po_number}</div>
                                <div class="cb-po-item__info">${po.vendor_name || ''} | ${po.direction || ''}</div>
                            </div>
                        `).join('');
                        poResults.classList.add('show');
                    } else {
                        poResults.innerHTML = '<div class="cb-po-item">No results found</div>';
                        poResults.classList.add('show');
                    }
                })
                .catch(err => {
                    poLoading.classList.remove('show');
                    console.error('Search error:', err);
                });
        }, 300);
    });

    // Select PO
    poResults.addEventListener('click', function(e) {
        const item = e.target.closest('.cb-po-item');
        if (!item || !item.dataset.po) return;

        const poNumber = item.dataset.po;
        poSearch.value = poNumber;
        poHidden.value = poNumber;
        poResults.classList.remove('show');

        // Fetch PO details
        poLoading.classList.add('show');
        fetch('{{ url("vendor/ajax/po") }}/' + encodeURIComponent(poNumber))
            .then(r => r.json())
            .then(data => {
                poLoading.classList.remove('show');
                
                if (data.success && data.data.items) {
                    renderPoItems(data.data.items);
                }
            })
            .catch(err => {
                poLoading.classList.remove('show');
                console.error('Detail error:', err);
            });
    });

    function renderPoItems(items) {
        if (!items || items.length === 0) {
            poItemsContainer.classList.remove('is-visible');
            return;
        }

        poItemsBody.innerHTML = items.map((item, idx) => {
            const remaining = (parseFloat(item.qty_po) || 0) - (parseFloat(item.qty_gr_total) || 0);
            return `
                <tr>
                    <td>${item.item_no || (idx + 1)}</td>
                    <td>${item.material_name || item.material_code || '-'}</td>
                    <td>${item.qty_po || 0} ${item.unit_po || ''}</td>
                    <td>${item.qty_gr_total || 0}</td>
                    <td>${remaining.toFixed(2)}</td>
                    <td>
                        <input type="hidden" name="po_items[${idx}][item_no]" value="${item.item_no || ''}">
                        <input type="hidden" name="po_items[${idx}][material_code]" value="${item.material_code || ''}">
                        <input type="hidden" name="po_items[${idx}][material_name]" value="${item.material_name || ''}">
                        <input type="number" 
                               name="po_items[${idx}][qty]" 
                               value="${remaining > 0 ? remaining.toFixed(2) : 0}"
                               min="0" 
                               max="${remaining}"
                               step="0.001">
                    </td>
                </tr>
            `;
        }).join('');

        poItemsContainer.classList.add('is-visible');
    }

    // Close dropdown on outside click
    document.addEventListener('click', function(e) {
        if (!poSearch.contains(e.target) && !poResults.contains(e.target)) {
            poResults.classList.remove('show');
        }
    });

    // Form validation
    document.getElementById('booking-form').addEventListener('submit', function(e) {
        const poNumber = poHidden.value.trim();
        if (!poNumber) {
            e.preventDefault();
            alert('Please select a PO/DO number');
            poSearch.focus();
            return false;
        }
    });

    // Live Availability Check
    const plannedDate = document.getElementById('planned-date');
    const plannedTime = document.getElementById('planned-time');
    const plannedDurationInput = document.getElementById('planned-duration');
    const plannedStartInput = document.getElementById('planned-start');
    const truckTypeSelect = document.querySelector('select[name="truck_type"]');
    const miniAvailability = document.getElementById('mini-availability');
    const coaInput = document.querySelector('input[name="coa_pdf"]');
    const coaError = document.getElementById('coa-error');
    const MAX_COA_BYTES = 10 * 1024 * 1024;
    const holidayData = typeof window.getIndonesiaHolidays === 'function' ? window.getIndonesiaHolidays() : {};

    function loadMiniAvailability() {
        if (!miniAvailability) return;
        const date = plannedDate.value;
        if (!date) {
            miniAvailability.innerHTML = '<div class="cb-availability-mini__placeholder">Select date to see available hours</div>';
            return;
        }

        miniAvailability.innerHTML = '<div class="cb-availability-mini__placeholder"><i class="fas fa-spinner fa-spin"></i> Loading availability...</div>';

        fetch(`{{ route('vendor.ajax.available_slots') }}?date=${encodeURIComponent(date)}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.slots) {
                    miniAvailability.innerHTML = '<div class="cb-availability-mini__placeholder">Failed to load availability</div>';
                    return;
                }

                const hours = data.slots.filter(slot => slot.time.endsWith(':00'));
                if (!hours.length) {
                    miniAvailability.innerHTML = '<div class="cb-availability-mini__placeholder">No available hours</div>';
                    return;
                }

                const selectedTime = (plannedTime.value || '').trim();
                let html = '<div class="cb-availability-mini__grid">';
                hours.forEach(slot => {
                    const isAvailable = slot.is_available;
                    const isSelected = selectedTime === slot.time;
                    const classes = [
                        'cb-availability-mini__item',
                        isAvailable ? 'cb-availability-mini__item--available' : 'cb-availability-mini__item--busy',
                        isSelected ? 'cb-availability-mini__item--selected' : ''
                    ].join(' ').trim();
                    html += `
                        <button type="button" class="${classes}" data-time="${slot.time}" ${isAvailable ? '' : 'disabled'}>
                            <span class="cb-availability-mini__item-time">${slot.time}</span>
                            <span class="cb-availability-mini__item-status">${isAvailable ? 'Available' : 'Not available'}</span>
                        </button>
                    `;
                });
                html += '</div>';
                miniAvailability.innerHTML = html;

                miniAvailability.querySelectorAll('.cb-availability-mini__item[data-time]').forEach(btn => {
                    if (btn.hasAttribute('disabled')) return;
                    btn.addEventListener('click', () => {
                        plannedTime.value = btn.dataset.time;
                        syncPlannedStart();
                        loadMiniAvailability();
                    });
                });
            })
            .catch(() => {
                miniAvailability.innerHTML = '<div class="cb-availability-mini__placeholder">Error loading availability</div>';
            });
    }

    function syncPlannedDuration() {
        if (!plannedDurationInput || !truckTypeSelect) return;
        const selected = truckTypeSelect.options[truckTypeSelect.selectedIndex];
        const duration = selected ? selected.getAttribute('data-duration') : '';
        plannedDurationInput.value = duration ? duration : 60;
    }

    function syncPlannedStart() {
        if (!plannedStartInput) return;
        const date = (plannedDate.value || '').trim();
        const time = (plannedTime.value || '').trim();
        plannedStartInput.value = date && time ? date + ' ' + time : (date || '');
    }

    function initVendorDatepicker() {
        if (plannedDate.getAttribute('data-st-datepicker') === '1') return;
        if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.datepicker !== 'function') return;
        plannedDate.setAttribute('data-st-datepicker', '1');

        window.jQuery(plannedDate).datepicker({
            dateFormat: 'yy-mm-dd',
            minDate: 0,
            beforeShowDay: function(date) {
                const ds = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
                if (holidayData[ds]) {
                    return [true, 'is-holiday', holidayData[ds]];
                }
                return [true, '', ''];
            },
            onSelect: function() {
                syncPlannedStart();
                loadMiniAvailability();
            }
        });
    }

    function initVendorTimepicker() {
        if (plannedTime.getAttribute('data-st-timepicker') === '1') return;
        if (typeof window.mdtimepicker !== 'function') return;
        plannedTime.setAttribute('data-st-timepicker', '1');

        plannedTime.addEventListener('keydown', function(event) { event.preventDefault(); });
        plannedTime.addEventListener('paste', function(event) { event.preventDefault(); });

        window.mdtimepicker('#planned-time', {
            format: 'hh:mm',
            is24hour: true,
            theme: 'cyan',
            hourPadding: true
        });

        plannedTime.addEventListener('change', function() {
            syncPlannedStart();
            loadMiniAvailability();
        });
    }

    function bindFileSizeGuard() {
        if (!coaInput) return;
        coaInput.addEventListener('change', function() {
            if (!coaInput.files || !coaInput.files.length) {
                if (coaError) coaError.hidden = true;
                return;
            }
            const file = coaInput.files[0];
            const tooLarge = file.size > MAX_COA_BYTES;
            if (coaError) coaError.hidden = !tooLarge;
            if (tooLarge) {
                coaInput.value = '';
            }
        });
    }

    plannedDate.addEventListener('change', function() {
        syncPlannedStart();
        loadMiniAvailability();
    });

    plannedTime.addEventListener('change', function() {
        syncPlannedStart();
        loadMiniAvailability();
    });

    if (truckTypeSelect) {
        truckTypeSelect.addEventListener('change', function() {
            syncPlannedDuration();
            syncPlannedStart();
        });
    }

    syncPlannedDuration();
    syncPlannedStart();
    initVendorDatepicker();
    initVendorTimepicker();
    bindFileSizeGuard();
    loadMiniAvailability();

    // Initial sync if values already set
    if (plannedDate.value) {
        loadMiniAvailability();
    }
});
</script>
@endsection
