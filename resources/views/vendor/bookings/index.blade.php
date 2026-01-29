@extends('vendor.layouts.vendor')

@section('title', 'My Bookings - Vendor Portal')

@section('page_class', 'vendor-page--layout vendor-page--bookings-index')

@section('content')

@php
    $currentStatus = request('status', '');
    $tabCounts = is_array($counts ?? null) ? $counts : [];
@endphp

<!-- Status Tabs -->
<div class="mb-container">
    <div class="mb-scroll-container">
        <div class="mb-tabs">
            <a href="{{ route('vendor.bookings.index', array_merge(request()->except('page'), ['status' => 'pending'])) }}"
               class="mb-tab mb-tab--pending {{ $currentStatus === 'pending' ? 'mb-tab--active' : '' }}">
                <span class="mb-tab__count">{{ ($tabCounts['pending'] ?? 0) }}</span>
                <span>Pending</span>
            </a>
            <a href="{{ route('vendor.bookings.index', array_merge(request()->except('page'), ['status' => 'approved'])) }}"
               class="mb-tab mb-tab--scheduled {{ $currentStatus === 'approved' ? 'mb-tab--active' : '' }}">
                <span class="mb-tab__count">{{ ($tabCounts['scheduled'] ?? 0) }}</span>
                <span>Approved</span>
            </a>
            <a href="{{ route('vendor.bookings.index', array_merge(request()->except('page', 'status'), ['status' => ''])) }}"
               class="mb-tab mb-tab--all {{ $currentStatus === '' ? 'mb-tab--active' : '' }}">
                <span class="mb-tab__count">{{ ($tabCounts['all'] ?? $bookings->total()) }}</span>
                <span>All</span>
            </a>
        </div>

        <!-- Content Container -->
        <div class="mb-content-container">
            <div class="mb-content">
                <!-- Search Bar -->
                <form method="GET" action="{{ route('vendor.bookings.index') }}" class="mb-search">
                    <input type="hidden" name="status" value="{{ $currentStatus }}">
                    <input type="text" name="search" class="mb-search__input" placeholder="Search ticket, vehicle, PO..." value="{{ request('search') }}">
                    <div class="date-range-container mb-date-range">
                        <input type="text" id="date-range" class="mb-search__input date-range-input" placeholder="Select date range" readonly>
                        <i class="fas fa-calendar-alt date-range-icon"></i>
                    </div>
                    <input type="hidden" name="date_from" id="date_from" value="{{ request('date_from') }}">
                    <input type="hidden" name="date_to" id="date_to" value="{{ request('date_to') }}">
                    <button type="submit" class="vendor-btn vendor-btn--primary vendor-btn--sm">
                        <i class="fas fa-search"></i> Search
                    </button>
                    @if(request()->hasAny(['search', 'date_from', 'date_to']))
                    <a href="{{ route('vendor.bookings.index', ['status' => $currentStatus]) }}" class="vendor-btn vendor-btn--secondary vendor-btn--sm">
                        <i class="fas fa-times"></i>
                    </a>
                    @endif
                </form>

    <!-- Booking Rows -->
    @if($bookings->count() > 0)
        @foreach($bookings as $booking)
        @php
            $statusClass = match($booking->status) {
                'pending' => 'pending',
                'approved' => 'scheduled',
                'rejected' => 'cancelled',
                'cancelled' => 'cancelled',
                default => 'cancelled'
            };
            $statusLabel = match($booking->status) {
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                'cancelled' => 'Cancelled',
                default => ucfirst(str_replace('_', ' ', $booking->status))
            };

            // Arrival status logic - selalu tampilkan
            $arrivalStatus = '-';
            $arrivalColor = 'secondary';
            if($booking->actual_arrival && $booking->planned_start) {
                $arrivalDiff = $booking->actual_arrival->diffInMinutes($booking->planned_start, false);
                if($arrivalDiff > 15) {
                    $arrivalStatus = 'Late';
                    $arrivalColor = 'danger';
                } elseif($arrivalDiff >= -15 && $arrivalDiff <= 15) {
                    $arrivalStatus = 'On-Time';
                    $arrivalColor = 'success';
                } else {
                    $arrivalStatus = 'Early';
                    $arrivalColor = 'info';
                }
            }
        @endphp
        <div class="mb-row">
            <span class="mb-row__ticket">{{ $booking->request_number ?? ('REQ-' . $booking->id) }}</span>
            <span class="mb-row__time">
                <i class="fas fa-calendar mb-row__icon"></i>
                {{ $booking->planned_start?->format('d M Y H:i') ?? '-' }}
            </span>
            <span class="mb-row__status mb-row__status--{{ $statusClass }}">{{ $statusLabel }}</span>
            <span class="mb-row__status mb-row__status--{{ $arrivalColor }} mb-row__status--arrival">
                <i class="fas fa-clock mb-row__status-icon"></i>{{ $arrivalStatus }}
            </span>
            <div class="mb-row__actions">
                <a href="{{ route('vendor.bookings.show', $booking->id) }}" class="mb-row__btn mb-row__btn--view" title="View">
                    <i class="fas fa-eye"></i>
                </a>
                @if(in_array($booking->status, ['pending']))
                <form method="POST" action="{{ route('vendor.bookings.cancel', $booking->id) }}" class="vendor-inline-form" onsubmit="return confirm('Cancel this booking?');">
                    @csrf
                    <input type="hidden" name="reason" value="Cancelled by vendor">
                    <button type="submit" class="mb-row__btn mb-row__btn--cancel" title="Cancel">
                        <i class="fas fa-times"></i>
                    </button>
                </form>
                @endif
            </div>
        </div>
        @endforeach

        <!-- Pagination -->
        <div class="mb-pagination">
            {{ $bookings->withQueryString()->links() }}
        </div>
    @else
        <div class="mb-empty">
            <div class="mb-empty__icon"><i class="fas fa-inbox"></i></div>
            <p class="mb-empty__title">No bookings found</p>
            <p class="mb-empty__subtitle">Try adjusting your filters or create a new booking</p>
            <a href="{{ route('vendor.bookings.create') }}" class="vendor-btn vendor-btn--primary mb-empty__action">
                <i class="fas fa-plus"></i> Create Booking
            </a>
        </div>
    @endif
            </div>
        </div>
    </div>

    <!-- Footer Container -->
    <div class="mb-footer-container">
        <div class="mb-footer-text">
            © {{ date('Y') }} Slot Time Management. All rights reserved.
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
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

    function initDatepicker(el, beforeShowDay, onSelect) {
        if (!el) return;
        if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.datepicker !== 'function') return;
        if (el.getAttribute('data-vendor-datepicker') === '1') return;
        el.setAttribute('data-vendor-datepicker', '1');

        window.jQuery(el).datepicker({
            dateFormat: 'yy-mm-dd',
            beforeShowDay: beforeShowDay,
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
            onSelect: onSelect
        });

        const inst = window.jQuery(el).data('datepicker');
        if (inst) {
            applyDatepickerTooltips(inst);
            bindDatepickerHover(inst);
        }
    }

    // Date Range Picker (single date)
    var dateRangeInput = document.getElementById('date-range');
    if (dateRangeInput && window.jQuery && window.jQuery.fn.dateRangePicker) {
        var dateFrom = document.getElementById('date_from');
        var dateTo = document.getElementById('date_to');
        var initial = dateFrom && dateFrom.value ? dateFrom.value : '';
        if (initial) {
            dateRangeInput.value = initial;
        }

        window.jQuery(dateRangeInput).dateRangePicker({
            autoClose: true,
            singleDate: true,
            showShortcuts: false,
            singleMonth: true,
            format: 'YYYY-MM-DD'
        }).bind('datepicker-change', function(event, obj) {
            var value = (obj && obj.value) ? obj.value : '';
            if (dateFrom) dateFrom.value = value;
            if (dateTo) dateTo.value = value;
            dateRangeInput.value = value;
        });
    }

    var inputs = document.querySelectorAll('input.flatpickr-date');
    Array.prototype.slice.call(inputs).forEach(function (input) {
        initDatepicker(input, function(date) {
            const ds = toIsoDate(date);
            if (holidayData[ds]) {
                return [true, 'is-holiday', holidayData[ds]];
            }
            return [true, '', ''];
        });
    });
});
</script>
@endpush
