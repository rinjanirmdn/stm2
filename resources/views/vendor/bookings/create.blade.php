@extends('vendor.layouts.vendor')

@section('title', 'Create Booking - Vendor Portal')

@section('content')
<style>
    .cb-layout {
        display: grid;
        grid-template-columns: 1fr 380px;
        gap: 20px;
        align-items: start;
        padding-bottom: 100px;
    }
    .cb-form {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
        padding: 24px;
    }
    .cb-form__header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1px solid #e5e7eb;
    }
    .cb-form__title {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .cb-section {
        margin-bottom: 24px;
    }
    .cb-section__title {
        font-size: 14px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .cb-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .cb-sidebar {
        position: sticky;
        top: 20px;
    }
    .cb-availability {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }
    .cb-availability__header {
        background: #1e3a5f;
        color: white;
        padding: 14px 16px;
        font-weight: 600;
        font-size: 14px;
    }
    .cb-availability__body {
        max-height: 500px;
        overflow-y: auto;
    }
    .cb-slot-row {
        display: flex;
        border-bottom: 1px solid #f1f5f9;
    }
    .cb-slot-time {
        width: 60px;
        padding: 10px 8px;
        background: #f8fafc;
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
        text-align: center;
        flex-shrink: 0;
    }
    .cb-slot-gates {
        display: flex;
        flex: 1;
        gap: 4px;
        padding: 4px;
    }
    .cb-slot-cell {
        flex: 1;
        min-height: 32px;
        border-radius: 4px;
        font-size: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.15s;
    }
    .cb-slot-cell--available { background: #f0fdf4; border: 1px dashed #86efac; color: #16a34a; }
    .cb-slot-cell--available:hover { background: #dcfce7; }
    .cb-slot-cell--booked { background: #dbeafe; border: 1px solid #3b82f6; color: #1e40af; font-weight: 600; }
    .cb-slot-cell--pending { background: #fef3c7; border: 1px solid #f59e0b; color: #92400e; }
    .cb-slot-cell--selected { background: #1e40af; color: white; border-color: #1e40af; }

    .cb-summary {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: #ffffff;
        border-top: 1px solid #e5e7eb;
        box-shadow: 0 -4px 20px rgba(15, 23, 42, 0.1);
        padding: 16px 24px;
        z-index: 100;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
    }
    .cb-summary__info {
        display: flex;
        gap: 32px;
        font-size: 13px;
    }
    .cb-summary__item {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .cb-summary__label {
        color: #64748b;
        font-size: 11px;
        text-transform: uppercase;
    }
    .cb-summary__value {
        font-weight: 600;
        color: #1e293b;
    }
    .cb-summary__status {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
    }
    .cb-summary__status--available { background: #dcfce7; color: #166534; }
    .cb-summary__status--unavailable { background: #fee2e2; color: #991b1b; }
    .cb-summary__status--checking { background: #f1f5f9; color: #64748b; }
    .cb-summary__actions {
        display: flex;
        gap: 12px;
    }

    @media (max-width: 900px) {
        .cb-layout { grid-template-columns: 1fr; }
        .cb-sidebar { position: static; }
        .cb-grid { grid-template-columns: 1fr; }
        .cb-summary { flex-wrap: wrap; }
        .cb-summary__info { flex-wrap: wrap; gap: 16px; }
    }
</style>

<form method="POST" action="{{ route('vendor.bookings.store') }}" id="booking-form" enctype="multipart/form-data">
@csrf

<div class="cb-layout">
    <!-- LEFT: Form -->
    <div class="cb-form">
        <div class="cb-form__header">
            <h1 class="cb-form__title">
                <i class="fas fa-plus-circle"></i>
                Create New Booking
            </h1>
            <a href="{{ route('vendor.bookings.index') }}" class="vendor-btn vendor-btn--secondary vendor-btn--sm">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <!-- PO Section -->
        <div class="cb-section">
            <div class="cb-section__title"><i class="fas fa-file-invoice"></i> PO/DO Information</div>
            <div class="vendor-form-group">
                <label class="vendor-form-label">PO/DO Number <span style="color: #ef4444;">*</span></label>
                <div style="position: relative;">
                    <input type="text" name="po_number" id="po_number" class="vendor-form-input" autocomplete="off"
                           placeholder="Type PO/DO..." required value="{{ old('po_number') }}">
                    <button type="button" id="po_clear" class="vendor-btn--secondary" style="position:absolute; right:8px; top:8px; font-size:12px; padding:4px 8px; border-radius:6px;">Clear</button>
                    <div id="po_suggestions" style="display:none; position:absolute; top:100%; left:0; right:0; z-index:50; background:#fff; border:1px solid #e5e7eb; border-radius:10px; margin-top:6px; max-height:260px; overflow:auto; box-shadow: 0 8px 20px rgba(15, 23, 42, 0.12);"></div>
                </div>
                <div id="po_preview" style="margin-top:8px;"></div>
            </div>
            <div class="vendor-form-group" id="po_items_group" style="display:none;">
                <label class="vendor-form-label">Items & Quantity</label>
                <div id="po_items_box" style="max-height:320px; overflow:auto;"></div>
            </div>
        </div>

        <!-- Documents -->
        <div class="cb-section">
            <div class="cb-section__title"><i class="fas fa-file-pdf"></i> Documents</div>
            <div class="vendor-form-group">
                <label class="vendor-form-label">COA (PDF, Max 10MB) <span style="color: #ef4444;">*</span></label>
                <input type="file" name="coa_pdf" class="vendor-form-input" accept="application/pdf" required>
            </div>
        </div>

        <!-- Schedule -->
        <div class="cb-section">
            <div class="cb-section__title"><i class="fas fa-calendar-alt"></i> Date & Time</div>
            <div class="cb-grid">
                <div class="vendor-form-group">
                    <label class="vendor-form-label">Date <span style="color: #ef4444;">*</span></label>
                    <input type="date" name="planned_date" class="vendor-form-input" required
                           min="{{ date('Y-m-d') }}" value="{{ old('planned_date', request('date', date('Y-m-d'))) }}" id="planned_date">
                </div>
                <div class="vendor-form-group">
                    <label class="vendor-form-label">Time <span style="color: #ef4444;">*</span></label>
                    <input type="time" name="planned_time" class="vendor-form-input" required
                           min="07:00" max="22:00" value="{{ old('planned_time', request('time', '09:00')) }}" id="planned_time">
                </div>
            </div>
            <input type="hidden" name="planned_duration" id="planned_duration" value="{{ old('planned_duration') }}">
        </div>

        <!-- Vehicle & Documents -->
        <div class="cb-section">
            <div class="cb-section__title"><i class="fas fa-truck"></i> Vehicle Details</div>
            <div class="cb-grid">
                <div class="vendor-form-group">
                    <label class="vendor-form-label">Truck Type</label>
                    <select name="truck_type" class="vendor-form-select" id="truck_type">
                        <option value="">Select Type</option>
                        @foreach($truckTypes as $type)
                            <option value="{{ $type->truck_type }}" data-duration="{{ $type->target_duration_minutes }}">
                                {{ $type->truck_type }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="vendor-form-group">
                    <label class="vendor-form-label">Vehicle Number</label>
                    <input type="text" name="vehicle_number" class="vendor-form-input" placeholder="B 1234 ABC" value="{{ old('vehicle_number') }}">
                </div>
                <div class="vendor-form-group">
                    <label class="vendor-form-label">Driver Name</label>
                    <input type="text" name="driver_name" class="vendor-form-input" placeholder="Driver name" value="{{ old('driver_name') }}">
                </div>
                <div class="vendor-form-group">
                    <label class="vendor-form-label">Driver Number</label>
                    <input type="text" name="driver_number" class="vendor-form-input" placeholder="08xxxxxxxxxx" value="{{ old('driver_number') }}">
                </div>
            </div>
            <div class="vendor-form-group">
                <label class="vendor-form-label">Notes</label>
                <textarea name="notes" class="vendor-form-textarea" rows="2" placeholder="Special requests...">{{ old('notes') }}</textarea>
            </div>
        </div>
    </div>

    <!-- RIGHT: Live Availability -->
    <div class="cb-sidebar">
        <div class="cb-availability">
            <div class="cb-availability__header">
                <i class="fas fa-calendar-alt"></i>
                Live Availability
            </div>
            <div class="cb-availability__body" id="live-availability">
                <div style="text-align: center; padding: 40px 20px; color: #64748b;">
                    <i class="fas fa-clock" style="font-size: 32px; opacity: 0.3; margin-bottom: 8px;"></i>
                    <p style="margin: 0; font-size: 13px;">Select a date to load availability</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sticky Summary -->
<div class="cb-summary">
    <div class="cb-summary__info">
        <div class="cb-summary__item">
            <span class="cb-summary__label">Date</span>
            <span class="cb-summary__value" id="summary-date">-</span>
        </div>
        <div class="cb-summary__item">
            <span class="cb-summary__label">Time</span>
            <span class="cb-summary__value" id="summary-time">-</span>
        </div>
        <div class="cb-summary__item">
            <span class="cb-summary__label">Duration</span>
            <span class="cb-summary__value" id="summary-duration">-</span>
        </div>
    </div>
    <div id="summary-status" class="cb-summary__status cb-summary__status--checking">
        <i class="fas fa-circle-notch fa-spin"></i> Checking...
    </div>
    <div class="cb-summary__actions">
        <a href="{{ route('vendor.bookings.index') }}" class="vendor-btn vendor-btn--secondary">Cancel</a>
        <button type="submit" class="vendor-btn vendor-btn--primary" id="submit-btn">
            <i class="fas fa-paper-plane"></i> Submit Booking
        </button>
    </div>
</div>

</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const truckTypeSelect = document.getElementById('truck_type');
    const durationInput = document.getElementById('planned_duration');
    const dateInput = document.getElementById('planned_date');
    const timeInput = document.getElementById('planned_time');
    const liveAvailability = document.getElementById('live-availability');

    const poInput = document.getElementById('po_number');
    const poSuggestions = document.getElementById('po_suggestions');
    const poPreview = document.getElementById('po_preview');
    const poItemsGroup = document.getElementById('po_items_group');
    const poItemsBox = document.getElementById('po_items_box');
    const poClearBtn = document.getElementById('po_clear');
    const summaryDate = document.getElementById('summary-date');
    const summaryTime = document.getElementById('summary-time');
    const summaryDuration = document.getElementById('summary-duration');
    const summaryStatus = document.getElementById('summary-status');

    const urlPoSearch = '{{ route('vendor.ajax.po_search') }}';
    const urlPoDetailTemplate = '{{ route('vendor.ajax.po_detail', ['poNumber' => '__PO__']) }}';
    const urlAvailableSlots = '{{ route('vendor.ajax.available_slots') }}';
    const defaultWarehouseId = {{ (int) ($defaultWarehouseId ?? 0) }};
    let poDebounceTimer = null;
    function escapeHtml(str) {
        return String(str || '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[m]));
    }

    // Summary update
    function updateSummary() {
        summaryDate.textContent = dateInput.value || '-';
        summaryTime.textContent = timeInput.value || '-';
        summaryDuration.textContent = durationInput.value ? durationInput.value + ' min' : '-';
    }

    // Availability check
    function checkAvailability() {
        updateSummary();
        const date = dateInput.value;
        const time = timeInput.value;
        const duration = durationInput.value;

        if (!date || !time || !duration) {
            summaryStatus.className = 'cb-summary__status cb-summary__status--checking';
            summaryStatus.innerHTML = '<i class="fas fa-info-circle"></i> Fill required fields';
            return;
        }

        summaryStatus.className = 'cb-summary__status cb-summary__status--checking';
        summaryStatus.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Checking...';

        const plannedStart = date + ' ' + time + ':00';

        // Global blocking is enforced server-side on submit.
        summaryStatus.className = 'cb-summary__status cb-summary__status--checking';
        summaryStatus.innerHTML = '<i class="fas fa-info-circle"></i> Will be checked on submit';
    }
    function loadLiveAvailability() {
        const date = dateInput.value;
        if (!date || !defaultWarehouseId) {
            liveAvailability.innerHTML = '<div style="padding: 16px; font-size: 13px; color: #64748b;">Select a date to load availability.</div>';
            return;
        }

        liveAvailability.innerHTML = '<div style="padding: 16px; font-size: 13px; color: #64748b;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';

        const url = urlAvailableSlots + '?warehouse_id=' + encodeURIComponent(defaultWarehouseId) + '&date=' + encodeURIComponent(date);
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(resp => {
                const slots = (resp && resp.success) ? (resp.slots || []) : [];
                const available = slots.filter(s => s.is_available);
                if (!available.length) {
                    liveAvailability.innerHTML = '<div style="padding: 16px; font-size: 13px; color: #64748b;">No available times for this date.</div>';
                    return;
                }
                let html = '<div style="padding: 6px 0;">';
                available.forEach(slot => {
                    html += `
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px 14px; border-bottom:1px solid #f1f5f9;">
                            <span style="font-size:12px; font-weight:600; color:#1e293b;">${slot.time}</span>
                            <button type="button" class="vendor-btn vendor-btn--secondary vendor-btn--sm" data-time="${slot.time}" style="padding:4px 10px;">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    `;
                });
                html += '</div>';
                liveAvailability.innerHTML = html;

                liveAvailability.querySelectorAll('button[data-time]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        timeInput.value = btn.dataset.time;
                        checkAvailability();
                    });
                });
            })
            .catch(() => {
                liveAvailability.innerHTML = '<div style="padding: 16px; font-size: 13px; color: #ef4444;">Failed to load availability.</div>';
            });
    }
    truckTypeSelect.addEventListener('change', function() {
        const dur = this.options[this.selectedIndex].dataset.duration;
        durationInput.value = dur || '';
        checkAvailability();
    });

    [dateInput, timeInput].forEach(el => {
        el.addEventListener('change', () => {
            checkAvailability();
            if (el === dateInput) loadLiveAvailability();
        });
    });

    // PO Functions
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
        if (!items || !items.length) {
            showPoSuggestionsMessage('<div style="padding:12px; color:#64748b; font-size:13px;">No PO/DO Found</div>');
            return;
        }
        let html = '';
        items.forEach(it => {
            html += `<div class="po-item" data-po="${escapeHtml(it.po_number)}" data-dir="${escapeHtml(it.direction || '')}" 
                style="padding:10px 12px; cursor:pointer; border-bottom:1px solid #f1f5f9;">
                <div style="font-weight:600;">${escapeHtml(it.po_number)}</div>
                <div style="font-size:12px; color:#64748b;">${escapeHtml(it.vendor_name || '')}</div>
            </div>`;
        });
        poSuggestions.innerHTML = html;
        poSuggestions.style.display = 'block';
    }

    function setPoPreview(data) {
        if (!poPreview) return;
        if (!data) { poPreview.innerHTML = ''; if (poItemsGroup) poItemsGroup.style.display = 'none'; return; }
        poPreview.innerHTML = `<div style="padding:10px 12px; border:1px solid #e5e7eb; border-radius:8px; background:#f8fafc;">
            <div style="font-weight:600;">${escapeHtml(data.po_number)}</div>
            <div style="font-size:12px; color:#475569;">Vendor: ${escapeHtml(data.vendor_name || '-')}</div>
        </div>`;
    }

    function renderPoItems(items) {
        if (!poItemsGroup || !poItemsBox || !items || !items.length) {
            if (poItemsGroup) poItemsGroup.style.display = 'none';
            return;
        }
        let html = '<div style="border:1px solid #e5e7eb; border-radius:8px; overflow:hidden; font-size:12px;">';
        items.forEach(it => {
            html += `<div style="display:flex; gap:8px; padding:8px 10px; border-top:1px solid #f1f5f9; align-items:center;">
                <span style="font-weight:600; min-width:60px;">${escapeHtml(it.item_no)}</span>
                <span style="flex:1; color:#475569;">${escapeHtml(it.material || '')}</span>
                <span style="min-width:80px; text-align:right;">${it.remaining_qty} ${escapeHtml(it.uom || '')}</span>
                <input type="number" min="0" step="0.001" name="po_items[${escapeHtml(it.item_no)}][qty]" 
                    style="width:80px; padding:6px 8px; border:1px solid #e5e7eb; border-radius:6px;" max="${it.remaining_qty}">
            </div>`;
        });
        html += '</div>';
        poItemsBox.innerHTML = html;
        poItemsGroup.style.display = 'block';
    }

    function fetchPoDetail(poNumber) {
        if (!poNumber) { setPoPreview(null); return; }
        const url = urlPoDetailTemplate.replace('__PO__', encodeURIComponent(poNumber));
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(resp => {
                if (!resp.success) { setPoPreview(null); return; }
                setPoPreview(resp.data);
                renderPoItems(resp.data?.items || []);
            })
            .catch(() => setPoPreview(null));
    }

    if (poInput) {
        poInput.addEventListener('input', function() {
            const q = poInput.value.trim();
            setPoPreview(null);
            if (poDebounceTimer) clearTimeout(poDebounceTimer);
            poDebounceTimer = setTimeout(() => {
                if (!q) { closePoSuggestions(); return; }
                showPoSuggestionsMessage('<div style="padding:12px;"><i class="fas fa-spinner fa-spin"></i></div>');
                fetch(urlPoSearch + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(resp => renderPoSuggestions(resp.data || []))
                    .catch(() => showPoSuggestionsMessage('<div style="padding:12px; color:#ef4444;">Error</div>'));
            }, 250);
        });
        poInput.addEventListener('blur', () => { if (poInput.value.trim()) fetchPoDetail(poInput.value.trim()); });
    }

    if (poClearBtn) {
        poClearBtn.addEventListener('click', function () {
            if (poInput) poInput.value = '';
            setPoPreview(null);
            if (poItemsBox) poItemsBox.innerHTML = '';
            if (poItemsGroup) poItemsGroup.style.display = 'none';
            closePoSuggestions();
        });
    }

    if (poSuggestions) {
        poSuggestions.addEventListener('click', e => {
            const item = e.target.closest('.po-item');
            if (!item) return;
            poInput.value = item.dataset.po;
            closePoSuggestions();
            fetchPoDetail(item.dataset.po);
        });
    }

    document.addEventListener('click', e => {
        if (poSuggestions && !poInput.contains(e.target) && !poSuggestions.contains(e.target)) closePoSuggestions();
    });

    // Initial load
    updateSummary();
    loadLiveAvailability();
    if (poInput?.value.trim()) fetchPoDetail(poInput.value.trim());
});
</script>
@endpush
