@extends('vendor.layouts.vendor')

@section('title', 'Confirm Booking - Vendor Portal')

@section('page_class', 'vendor-page--layout vendor-page--bookings-confirm')

@section('content')
<div class="vendor-card">
    <div class="vendor-card__header">
        <h1 class="vendor-card__title">
            <i class="fas fa-check-circle"></i>
            Confirm Rescheduled Booking
        </h1>
        <a href="{{ route('vendor.bookings.show', $booking->id) }}" class="vendor-btn vendor-btn--secondary">
            <i class="fas fa-arrow-left"></i>
            Back
        </a>
    </div>

    <div class="vendor-alert vendor-alert--info">
        <i class="fas fa-info-circle"></i>
        <div>
            <strong>Admin Has Rescheduled Your Booking.</strong>
            <p class="vc-info-text">Please review the new schedule below and confirm, reject, or propose a different time.</p>
        </div>
    </div>

    <!-- Schedule Comparison -->
    <div class="vc-compare-grid">
        <!-- Original Request -->
        <div class="vc-card vc-card--danger">
            <h3 class="vc-card__title vc-card__title--danger">
                <i class="fas fa-times-circle"></i>
                Your Original Request
            </h3>
            <table class="vc-card__table">
                <tr>
                    <td class="vc-card__label vc-card__label--danger">Date</td>
                    <td class="vc-card__value">
                        {{ $booking->original_planned_start?->format('d M Y') ?? $booking->requested_at?->format('d M Y') ?? '-' }}
                    </td>
                </tr>
                <tr>
                    <td class="vc-card__label vc-card__label--danger">Time</td>
                    <td class="vc-card__value">
                        {{ $booking->original_planned_start?->format('H:i') ?? '-' }}
                    </td>
                </tr>
                <tr>
                    <td class="vc-card__label vc-card__label--danger">Gate</td>
                    <td class="vc-card__value">
                        {{ $booking->originalPlannedGate?->name ?? 'Auto-assign' }}
                    </td>
                </tr>
            </table>
        </div>

        <!-- Arrow -->
        <div class="vc-arrow">
            <i class="fas fa-arrow-right vc-arrow__icon"></i>
        </div>

        <!-- New Schedule -->
        <div class="vc-card vc-card--success">
            <h3 class="vc-card__title vc-card__title--success">
                <i class="fas fa-check-circle"></i>
                New Schedule (Admin Proposed)
            </h3>
            <table class="vc-card__table">
                <tr>
                    <td class="vc-card__label vc-card__label--success">Date</td>
                    <td class="vc-card__value vc-card__value--strong">
                        {{ $booking->planned_start?->format('d M Y') ?? '-' }}
                    </td>
                </tr>
                <tr>
                    <td class="vc-card__label vc-card__label--success">Time</td>
                    <td class="vc-card__value vc-card__value--strong">
                        {{ $booking->planned_start?->format('H:i') ?? '-' }}
                    </td>
                </tr>
                <tr>
                    <td class="vc-card__label vc-card__label--success">Duration</td>
                    <td class="vc-card__value vc-card__value--strong">
                        {{ $booking->planned_duration }} Min
                    </td>
                </tr>
                <tr>
                    <td class="vc-card__label vc-card__label--success">Gate</td>
                    <td class="vc-card__value vc-card__value--strong">
                        {{ $booking->plannedGate?->name ?? 'TBD' }}
                    </td>
                </tr>
            </table>
        </div>
    </div>

    @if($booking->approval_notes)
    <div class="vc-notes">
        <strong class="vc-notes__label">Admin Notes:</strong>
        <p class="vc-notes__text">{{ $booking->approval_notes }}</p>
    </div>
    @endif

    <!-- Action Buttons -->
    <div class="vc-actions">
        <!-- Confirm -->
        <form method="POST" action="{{ route('vendor.bookings.confirm.store', $booking->id) }}">
            @csrf
            <input type="hidden" name="action" value="confirm">
            <button type="submit" class="vendor-btn vendor-btn--success vc-action-btn">
                <i class="fas fa-check"></i>
                Accept New Schedule
            </button>
        </form>

        <!-- Reject -->
        <button type="button" class="vendor-btn vendor-btn--danger vc-action-btn" onclick="document.getElementById('reject-form').classList.remove('vendor-hidden'); this.classList.add('vendor-hidden');">
            <i class="fas fa-times"></i>
            Reject & Cancel
        </button>

        <!-- Propose New -->
        <button type="button" class="vendor-btn vendor-btn--primary vc-action-btn" onclick="document.getElementById('propose-form').classList.remove('vendor-hidden');">
            <i class="fas fa-calendar-alt"></i>
            Propose Different Time
        </button>
    </div>

    <!-- Reject Form (Hidden) -->
    <form method="POST" action="{{ route('vendor.bookings.confirm.store', $booking->id) }}" id="reject-form" class="vc-form vc-form--danger vendor-hidden">
        @csrf
        <input type="hidden" name="action" value="reject">
        
        <h3 class="vc-form__title vc-form__title--danger">
            <i class="fas fa-times-circle"></i>
            Reject Booking
        </h3>
        
        <div class="vendor-form-group">
            <label class="vendor-form-label">Reason for Rejection <span class="vendor-required">*</span></label>
            <textarea name="reason" class="vendor-form-textarea" rows="3" required 
                      placeholder="Please Explain Why You're Rejecting This Schedule..."></textarea>
        </div>
        
        <div class="vc-form__actions">
            <button type="submit" class="vendor-btn vendor-btn--danger">
                <i class="fas fa-times"></i>
                Confirm Rejection
            </button>
            <button type="button" class="vendor-btn vendor-btn--secondary" onclick="document.getElementById('reject-form').classList.add('vendor-hidden');">
                Cancel
            </button>
        </div>
    </form>

    <!-- Propose New Schedule Form (Hidden) -->
    <form method="POST" action="{{ route('vendor.bookings.confirm.store', $booking->id) }}" id="propose-form" class="vc-form vc-form--info vendor-hidden">
        @csrf
        <input type="hidden" name="action" value="propose">
        
        <h3 class="vc-form__title vc-form__title--info">
            <i class="fas fa-calendar-alt"></i>
            Propose New Schedule
        </h3>

        <div class="vc-propose-grid">
            <div class="vendor-form-group vendor-form-group--tight">
                <label class="vendor-form-label">Date <span class="vendor-required">*</span></label>
                <input type="text" name="planned_date" id="planned_date_input" class="vendor-form-input" required
                       value="{{ $booking->planned_start?->format('Y-m-d') }}" placeholder="Select Date">
            </div>
            
            <div class="vendor-form-group vendor-form-group--tight">
                <label class="vendor-form-label">Time <span class="vendor-required">*</span></label>
                <input type="time" name="planned_time" class="vendor-form-input" required
                       min="07:00" max="22:00" value="{{ $booking->planned_start?->format('H:i') }}">
            </div>
            
            <div class="vendor-form-group vendor-form-group--tight">
                <label class="vendor-form-label">Duration (Min) <span class="vendor-required">*</span></label>
                <input type="number" name="planned_duration" class="vendor-form-input" required
                       min="30" max="480" step="10" value="{{ $booking->planned_duration }}">
            </div>
            
            <div class="vendor-form-group">
                <label class="vendor-form-label">Gate <span class="vendor-required">*</span></label>
                <select name="planned_gate_id" class="vendor-form-select" required>
                    <option value="">Select Gate...</option>
                    @foreach ($gates as $gate)
                        @php
                            $gateLabel = app(\App\Services\SlotService::class)->getGateDisplayName($gate->warehouse_code ?? '', $gate->gate_number ?? '');
                        @endphp
                        <option value="{{ $gate->id }}" data-warehouse-id="{{ $gate->warehouse_id }}" {{ (string)old('planned_gate_id') === (string)$gate->id ? 'selected' : '' }}>
                            {{ $gateLabel }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="warehouse_id" value="">
            </div>
        </div>
        
        <div class="vendor-form-group vc-form__notes">
            <label class="vendor-form-label">Notes (Optional)</label>
            <textarea name="notes" class="vendor-form-textarea" rows="2" 
                      placeholder="Any Additional Notes..."></textarea>
        </div>

        <div class="vc-form__actions">
            <button type="submit" class="vendor-btn vendor-btn--primary">
                <i class="fas fa-paper-plane"></i>
                Submit New Proposal
            </button>
            <button type="button" class="vendor-btn vendor-btn--secondary" onclick="document.getElementById('propose-form').classList.add('vendor-hidden');">
                Cancel
            </button>
        </div>
    </form>
</div>

<script>
    // Gate and warehouse sync
    function syncWarehouseFromGate(select) {
        const warehouseHidden = select.parentElement.querySelector('input[name="warehouse_id"]');
        if (!warehouseHidden) return;
        const selected = select.options[select.selectedIndex];
        if (!selected) return;
        warehouseHidden.value = selected.getAttribute('data-warehouse-id') || '';
    }

    document.addEventListener('DOMContentLoaded', function() {
        const gateSelects = document.querySelectorAll('select[name="planned_gate_id"]');
        gateSelects.forEach(select => {
            // Initial sync if gate is pre-selected
            if (select.value) {
                syncWarehouseFromGate(select);
            }
            // Add change listener
            select.addEventListener('change', function() {
                syncWarehouseFromGate(this);
            });
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        if (window.location && window.location.hash === '#propose') {
            var el = document.getElementById('propose-form');
            if (el) {
                el.style.display = 'block';
                try { el.scrollIntoView({ behavior: 'smooth', block: 'start' }); } catch (e) {}
            }
        }

        var dateInput = document.getElementById('planned_date_input');
        if (dateInput) {
            var holidayData = typeof window.getIndonesiaHolidays === 'function' ? window.getIndonesiaHolidays() : {};

            function toIsoDate(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
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
                        cell.attr('data-vendor-tooltip', title);
                        cell.find('a, span').attr('data-vendor-tooltip', title);
                    }
                    cell.removeAttr('title');
                    cell.find('a, span').removeAttr('title');
                });
            }

            function bindDatepickerHover(inst) {
                if (!inst || !inst.dpDiv) return;
                const dp = window.jQuery(inst.dpDiv);
                let hideTimer = null;
                let tooltip = document.getElementById('vendor-datepicker-tooltip');
                if (!tooltip) {
                    tooltip = document.createElement('div');
                    tooltip.id = 'vendor-datepicker-tooltip';
                    tooltip.className = 'vendor-datepicker-tooltip';
                    document.body.appendChild(tooltip);
                }

                dp.off('mouseenter.vendor-tooltip mousemove.vendor-tooltip mouseleave.vendor-tooltip', 'td.is-holiday');
                dp.on('mouseenter.vendor-tooltip', 'td.is-holiday', function(event) {
                    const text = window.jQuery(this).attr('data-vendor-tooltip') || '';
                    if (!text) return;
                    if (hideTimer) {
                        clearTimeout(hideTimer);
                        hideTimer = null;
                    }
                    tooltip.textContent = text;
                    tooltip.classList.add('vendor-datepicker-tooltip--visible');
                    tooltip.style.left = `${event.clientX + 12}px`;
                    tooltip.style.top = `${event.clientY + 12}px`;
                });
                dp.on('mousemove.vendor-tooltip', 'td.is-holiday', function(event) {
                    tooltip.style.left = `${event.clientX + 12}px`;
                    tooltip.style.top = `${event.clientY + 12}px`;
                });
                dp.on('mouseleave.vendor-tooltip', 'td.is-holiday', function() {
                    hideTimer = setTimeout(function() {
                        tooltip.classList.remove('vendor-datepicker-tooltip--visible');
                    }, 300);
                });
            }

            if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.datepicker !== 'function') return;
            if (dateInput.getAttribute('data-vendor-datepicker') === '1') return;
            dateInput.setAttribute('data-vendor-datepicker', '1');

            window.jQuery(dateInput).datepicker({
                dateFormat: 'yy-mm-dd',
                minDate: 0,
                beforeShowDay: function(date) {
                    const ds = toIsoDate(date);
                    if (holidayData[ds]) {
                        return [false, 'is-holiday', holidayData[ds]];
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
                }
            });

            const inst = window.jQuery(dateInput).data('datepicker');
            if (inst) {
                applyDatepickerTooltips(inst);
                bindDatepickerHover(inst);
            }
        }
    });
</script>
@endsection
