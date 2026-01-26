@extends('vendor.layouts.vendor')

@section('title', 'My Bookings - Vendor Portal')

@section('content')
<style>
    /* Vendor Bookings Layout Specific */
    .vendor-app .vendor-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        width: 100%;
        max-width: none;
        padding: 24px;
        margin: 0;
        box-sizing: border-box;
        overflow: hidden;
    }

    /* Consistent search input styling */
    .mb-search__input {
        width: 200px !important;
        padding: 8px 12px !important;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: all 0.2s ease;
    }

    .mb-search__input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    /* Date range input with icon */
    .date-range-input {
        padding-right: 40px !important;
    }

    .mb-search {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
    }

    .mb-container {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 72px - 48px);
        width: 100%;
        margin: 0;
        background: #f1f5f9;
        border-radius: 12px;
        overflow: hidden;
    }

    .mb-scroll-container {
        flex: 1;
        overflow-y: auto;
        background: #f1f5f9;
    }

    .mb-content-container {
        background: #ffffff;
        margin: 0;
        min-height: 100%;
    }

    .mb-footer-container {
        background: #ffffff;
        border-top: 1px solid #e5e7eb;
        padding: 16px 20px;
        flex-shrink: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        border-radius: 0 0 12px 12px;
    }

    .mb-tabs {
        display: flex;
        gap: 0;
        background: #ffffff;
        border-radius: 12px 12px 0 0;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
        border-bottom: 2px solid #e5e7eb;
        flex-shrink: 0;
    }
    .mb-tab {
        flex: 1;
        padding: 16px 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
        border-right: 1px solid #e5e7eb;
        text-decoration: none;
        color: #64748b;
        font-weight: 500;
        font-size: 14px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
    }
    .mb-tab:last-child { border-right: none; }
    .mb-tab:hover { background: #f8fafc; color: #1e293b; }
    .mb-tab--active {
        background: #ffffff;
        color: #1e40af;
        font-weight: 600;
        border-bottom: 3px solid #1e40af;
        margin-bottom: -2px;
    }
    .mb-tab__count {
        font-size: 20px;
        font-weight: 700;
        line-height: 1;
    }
    .mb-tab--action .mb-tab__count { color: #ef4444; }
    .mb-tab--pending .mb-tab__count { color: #f59e0b; }
    .mb-tab--scheduled .mb-tab__count { color: #3b82f6; }
    .mb-tab--completed .mb-tab__count { color: #10b981; }
    .mb-tab--all .mb-tab__count { color: #64748b; }

    .mb-content {
        background: transparent;
        padding: 20px;
        margin: 0;
    }

    .mb-search {
        display: flex;
        gap: 12px;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }
    .mb-search__input {
        flex: 1;
        min-width: 200px;
        padding: 10px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
    }
    .mb-search__input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .mb-row {
        display: flex;
        align-items: center;
        padding: 14px 16px;
        border-radius: 10px;
        margin-bottom: 8px;
        background: #f8fafc;
        transition: all 0.15s;
        gap: 16px;
        text-decoration: none;
        color: inherit;
    }
    .mb-row:hover { background: #f1f5f9; transform: translateX(2px); }
    .mb-row--action { background: #fef2f2; border-left: 4px solid #ef4444; }
    .mb-row--action:hover { background: #fee2e2; }

    .mb-row__ticket {
        font-weight: 700;
        color: #1e293b;
        min-width: 110px;
    }
    .mb-row__time {
        color: #475569;
        font-size: 13px;
        min-width: 140px;
    }
    .mb-row__gate {
        color: #64748b;
        font-size: 13px;
        min-width: 80px;
    }
    .mb-row__direction {
        min-width: 80px;
    }
    .mb-row__status {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        min-width: 100px;
        text-align: center;
    }
    .mb-row__status--pending { background: #fef3c7; color: #92400e; }
    .mb-row__status--scheduled { background: #dbeafe; color: #1e40af; }
    .mb-row__status--completed { background: #dcfce7; color: #166534; }
    .mb-row__status--action { background: #fee2e2; color: #991b1b; }
    .mb-row__status--progress { background: #ede9fe; color: #5b21b6; }
    .mb-row__status--cancelled { background: #f1f5f9; color: #64748b; }

    .mb-row__actions {
        display: flex;
        gap: 6px;
        margin-left: auto;
    }
    .mb-row__btn {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s;
        font-size: 12px;
    }
    .mb-row__btn--view { background: #e0e7ff; color: #3730a3; }
    .mb-row__btn--view:hover { background: #c7d2fe; }
    .mb-row__btn--confirm { background: #dcfce7; color: #166534; }
    .mb-row__btn--confirm:hover { background: #bbf7d0; }
    .mb-row__btn--cancel { background: #fee2e2; color: #991b1b; }
    .mb-row__btn--cancel:hover { background: #fecaca; }

    .mb-empty {
        text-align: center;
        padding: 60px 20px;
        color: #94a3b8;
        min-height: 300px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
    .mb-empty__icon {
        font-size: 48px;
        margin-bottom: 12px;
        opacity: 0.5;
    }

    @media (max-width: 768px) {
        .mb-tabs { flex-wrap: wrap; }
        .mb-tab { flex: 1 1 50%; }
        .mb-row { flex-wrap: wrap; gap: 8px; }
        .mb-row__actions { width: 100%; justify-content: flex-end; }
    }
</style>

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
                    <div class="date-range-container" style="position: relative;">
                        <input type="text" id="date-range" class="mb-search__input date-range-input" placeholder="Select date range" readonly>
                        <i class="fas fa-calendar-alt date-range-icon" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #64748b; pointer-events: none;"></i>
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
                <i class="fas fa-calendar" style="opacity: 0.5; margin-right: 4px;"></i>
                {{ $booking->planned_start?->format('d M Y H:i') ?? '-' }}
            </span>
            <span class="mb-row__gate">
                <i class="fas fa-door-open" style="opacity: 0.5; margin-right: 4px;"></i>
                {{ $booking->convertedSlot?->plannedGate?->name ?? '-' }}
            </span>
            <span class="mb-row__direction">
                @if($booking->direction === 'inbound')
                    <i class="fas fa-arrow-down" style="color: #3b82f6;"></i> In
                @else
                    <i class="fas fa-arrow-up" style="color: #f59e0b;"></i> Out
                @endif
            </span>
            <span class="mb-row__status mb-row__status--{{ $statusClass }}">{{ $statusLabel }}</span>
            <span class="mb-row__status mb-row__status--{{ $arrivalColor }}" style="font-size: 11px; margin-left: 4px;">
                <i class="fas fa-clock" style="font-size: 10px; margin-right: 2px;"></i>{{ $arrivalStatus }}
            </span>
            <div class="mb-row__actions">
                <a href="{{ route('vendor.bookings.show', $booking->id) }}" class="mb-row__btn mb-row__btn--view" title="View">
                    <i class="fas fa-eye"></i>
                </a>
                @if(in_array($booking->status, ['pending']))
                <form method="POST" action="{{ route('vendor.bookings.cancel', $booking->id) }}" style="display: inline;" onsubmit="return confirm('Cancel this booking?');">
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
        <div style="margin-top: 20px; display: flex; justify-content: center;">
            {{ $bookings->withQueryString()->links() }}
        </div>
    @else
        <div class="mb-empty">
            <div class="mb-empty__icon"><i class="fas fa-inbox"></i></div>
            <p style="font-size: 16px; margin-bottom: 4px;">No bookings found</p>
            <p style="font-size: 13px;">Try adjusting your filters or create a new booking</p>
            <a href="{{ route('vendor.bookings.create') }}" class="vendor-btn vendor-btn--primary" style="margin-top: 16px;">
                <i class="fas fa-plus"></i> Create Booking
            </a>
        </div>
    @endif
            </div>
        </div>
    </div>

    <!-- Footer Container -->
    <div class="mb-footer-container">
        <div style="text-align: center; color: #64748b; font-size: 14px;">
            © {{ date('Y') }} Slot Time Management. All rights reserved.
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.flatpickr !== 'function') return;
    var holidayData = typeof window.getIndonesiaHolidays === 'function' ? window.getIndonesiaHolidays() : {};

    // Date Range Picker
    var dateRangeInput = document.getElementById('date-range');
    if (dateRangeInput) {
        var dateFrom = document.getElementById('date_from');
        var dateTo = document.getElementById('date_to');

        // Set initial value if dates exist
        if (dateFrom.value && dateTo.value) {
            dateRangeInput.value = dateFrom.value + ' to ' + dateTo.value;
        }

        window.flatpickr(dateRangeInput, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd M Y',
            allowInput: true,
            disableMobile: true,
            defaultDate: dateFrom.value && dateTo.value ? [dateFrom.value, dateTo.value] : null,
            weekNumbers: false,
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    dateFrom.value = instance.formatDate(selectedDates[0], 'Y-m-d');
                    dateTo.value = instance.formatDate(selectedDates[1], 'Y-m-d');
                } else if (selectedDates.length === 0) {
                    dateFrom.value = '';
                    dateTo.value = '';
                }
            },
            onDayCreate: function(dObj, dStr, fp, dayElem) {
                var ds = fp.formatDate(dayElem.dateObj, 'Y-m-d');
                if (holidayData[ds]) {
                    dayElem.classList.add('is-holiday');
                    dayElem.title = holidayData[ds];
                }
            }
        });
    }

    var inputs = document.querySelectorAll('input.flatpickr-date');
    Array.prototype.slice.call(inputs).forEach(function (input) {
        if (!input || input.getAttribute('data-st-flatpickr') === '1') return;
        input.setAttribute('data-st-flatpickr', '1');

        window.flatpickr(input, {
            dateFormat: 'Y-m-d',
            allowInput: true,
            disableMobile: true,
            onDayCreate: function(dObj, dStr, fp, dayElem) {
                var ds = fp.formatDate(dayElem.dateObj, 'Y-m-d');
                if (holidayData[ds]) {
                    dayElem.classList.add('is-holiday');
                    dayElem.title = holidayData[ds];
                }
            }
        });
    });
});
</script>
@endpush
