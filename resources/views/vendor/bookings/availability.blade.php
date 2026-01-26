@extends('vendor.layouts.vendor')

@section('title', 'Gate Availability - Vendor Portal')

@section('content')
<style>
    /* Vendor Availability Layout Specific */
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

    /* Force no scroll on body and main */
    body {
        overflow: hidden !important;
    }

    .vendor-app {
        overflow: hidden !important;
    }

    .vendor-main {
        overflow: hidden !important;
    }

    .av-container {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 72px - 48px);
        width: 100%;
        margin: 0;
        background: #f1f5f9;
        border-radius: 12px;
        overflow: hidden;
    }

    .av-scroll-container {
        flex: 1;
        overflow-y: auto;
        background: #f1f5f9;
        min-height: 0; /* Penting untuk flexbox */
    }

    .av-content-container {
        background: #ffffff;
        margin: 0;
        min-height: 100%;
        padding: 20px;
    }

    .av-footer-container {
        background: #ffffff;
        border-top: 1px solid #e5e7eb;
        padding: 16px 20px;
        flex-shrink: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        border-radius: 0 0 12px 12px;
        position: sticky;
        bottom: 0;
        z-index: 100;
        font-size: 14px;
    }

    /* Force footer visibility */
    .av-container {
        display: flex !important;
        flex-direction: column !important;
        height: 100vh !important;
        max-height: 100vh !important;
        overflow: hidden !important;
    }

    .av-scroll-container {
        flex: 1 !important;
        overflow-y: auto !important;
        background: #f1f5f9;
        min-height: 0 !important;
        max-height: calc(100vh - 200px) !important;
    }

    .av-layout {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 20px;
        align-items: start;
        height: 100%;
    }
    .av-sidebar {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
        padding: 20px;
        position: sticky;
        top: 20px;
        max-height: calc(100dvh - 120px);
        overflow: auto;
    }
    .av-sidebar__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 10px;
    }
    .av-sidebar__title {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 16px;
    }
    .av-sidebar__toggle {
        display: none;
        width: 36px;
        height: 36px;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        background: #fff;
        color: #334155;
        cursor: pointer;
        align-items: center;
        justify-content: center;
    }
    .av-sidebar__warehouse {
        font-size: 13px;
        color: #64748b;
        margin-bottom: 20px;
    }
    .av-calendar {
        margin-bottom: 20px;
    }
    .av-calendar__header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        font-weight: 600;
        color: #1e293b;
    }
    .av-calendar__nav {
        display: flex;
        gap: 4px;
    }
    .av-calendar__nav-btn {
        width: 28px;
        height: 28px;
        border: 1px solid #e5e7eb;
        background: #fff;
        border-radius: 6px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        transition: all 0.15s;
    }
    .av-calendar__nav-btn:hover {
        background: #f1f5f9;
        color: #1e293b;
    }
    .av-calendar__grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 2px;
        text-align: center;
        font-size: 12px;
    }
    .av-calendar__day-header {
        padding: 6px 0;
        font-weight: 600;
        color: #64748b;
    }
    .av-calendar__day {
        padding: 8px 4px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.15s;
        color: #374151;
        font-size: 14px;
        font-weight: 500;
        min-height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .av-calendar__day:hover { background: #f1f5f9; }
    .av-calendar__day--today { background: #dbeafe; color: #1e40af; font-weight: 600; }
    .av-calendar__day--selected { background: #1e40af; color: white; font-weight: 600; }
    .av-calendar__day--other { color: #9ca3af; }
    .av-calendar__day--disabled {
        color: #d1d5db;
        background: #f9fafb;
        cursor: not-allowed !important;
        pointer-events: none;
    }
    .av-calendar__day--sunday {
        color: #ef4444;
        background: #fef2f2;
        cursor: not-allowed !important;
        pointer-events: none;
    }
    .av-calendar__day--holiday {
        color: #f59e0b;
        background: #fffbeb;
        cursor: not-allowed !important;
        pointer-events: none;
    }

    .av-main {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }
    .av-main__header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 20px;
        border-bottom: 1px solid #e5e7eb;
        background: #f8fafc;
    }
    .av-main__title {
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
    }
    .av-main__actions {
        display: flex;
        gap: 8px;
    }

    .av-main__mobile-filter {
        display: none;
    }

    .av-available-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
        padding: 16px;
    }
    .av-available-item {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 16px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #ffffff;
        cursor: pointer;
        transition: all 0.15s;
        text-align: left;
    }
    .av-available-item:hover {
        border-color: #2563eb;
        box-shadow: 0 6px 12px rgba(37, 99, 235, 0.12);
        transform: translateY(-1px);
    }
    .av-available-item.cb-slot-btn--disabled {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
    }
    .av-available-time {
        font-size: 16px;
        font-weight: 600;
        color: #1e293b;
    }
    .av-slot__vendor { font-size: 10px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .av-slot__action {
        margin-top: 4px;
        padding: 4px 8px;
        background: #1e40af;
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 600;
        cursor: pointer;
    }

    .av-slot--available {
        background: #f0fdf4;
        border: 1px dashed #86efac;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #22c55e;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.15s;
    }
    .av-slot--available:hover {
        background: #dcfce7;
        border-color: #22c55e;
    }

    .av-break {
        background: #f1f5f9;
        text-align: center;
        padding: 12px;
        font-size: 12px;
        color: #64748b;
        font-weight: 500;
        grid-column: 2 / -1;
    }

    @media (max-width: 900px) {
        .av-layout { grid-template-columns: 1fr; }
        .av-sidebar {
            position: static;
            max-height: none;
            overflow: visible;
            padding: 14px;
        }
        .av-sidebar__toggle {
            display: inline-flex;
        }
        .av-sidebar__title {
            margin-bottom: 0;
            font-size: 16px;
        }
        .av-sidebar__body {
            display: none;
            margin-top: 12px;
        }
        .av-sidebar--open .av-sidebar__body {
            display: block;
        }

        .av-main__mobile-filter {
            display: inline-flex;
        }

        .av-grid-wrapper {
            max-height: calc(100dvh - 260px);
        }
    }
</style>

<div class="av-container">
    <div class="av-scroll-container">
        <div class="av-content-container">
            <div class="av-layout">
                <!-- LEFT SIDEBAR: Calendar -->
                <div class="av-sidebar" id="av-sidebar">
        <div class="av-sidebar__top">
            <div class="av-sidebar__title">Availability</div>
            <button type="button" class="av-sidebar__toggle" id="av-sidebar-toggle" aria-label="Toggle filters">
                <i class="fas fa-sliders"></i>
            </button>
        </div>

        <div class="av-sidebar__body" id="av-sidebar__body">
        <!-- Mini Calendar -->
        <div class="av-calendar">
            <div class="av-calendar__header">
                <span id="calendar-month">{{ \Carbon\Carbon::parse($selectedDate)->format('F Y') }}</span>
                <div class="av-calendar__nav">
                    <button type="button" class="av-calendar__nav-btn" onclick="changeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                    <button type="button" class="av-calendar__nav-btn" onclick="changeMonth(1)"><i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
            <div class="av-calendar__grid">
                <div class="av-calendar__day-header">Mon</div>
                <div class="av-calendar__day-header">Tue</div>
                <div class="av-calendar__day-header">Wed</div>
                <div class="av-calendar__day-header">Thu</div>
                <div class="av-calendar__day-header">Fri</div>
                <div class="av-calendar__day-header">Sat</div>
                <div class="av-calendar__day-header">Sun</div>
            </div>
            <div class="av-calendar__grid" id="calendar-days"></div>
        </div>

        <form method="GET" action="{{ route('vendor.availability') }}" id="av-form">
            <input type="hidden" name="date" id="hidden-date" value="{{ $selectedDate }}">
        </form>

        </div>
    </div>

    <!-- RIGHT MAIN: Time Availability -->
    <div class="av-main">
        <div class="av-main__header">
            <span class="av-main__title">
                <i class="fas fa-clock"></i>
                {{ \Carbon\Carbon::parse($selectedDate)->format('l, d F Y') }}
            </span>
            <div class="av-main__actions">
                <button type="button" class="vendor-btn vendor-btn--secondary vendor-btn--sm av-main__mobile-filter" id="av-mobile-filter-btn">
                    <i class="fas fa-sliders"></i> Filters
                </button>
                <a href="{{ route('vendor.bookings.create', ['date' => $selectedDate]) }}" class="vendor-btn vendor-btn--primary vendor-btn--sm">
                    <i class="fas fa-plus"></i> New Booking
                </a>
            </div>
        </div>

        <!-- Time Slots Grid -->
        <div id="availability-list" class="av-time-slots">
            <div style="text-align: center; padding: 60px 20px; color: #64748b;">
                <i class="fas fa-spinner fa-spin fa-2x" style="margin-bottom: 12px;"></i>
                <p>Loading availability...</p>
            </div>
        </div>
    </div>
            </div>
        </div>
    </div>

    <!-- Footer Container -->
    <div class="av-footer-container">
        <div style="text-align: center; color: #64748b; font-size: 14px;">
            &copy; {{ date('Y') }} Slot Time Management. All rights reserved.
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const holidayData = @json($holidays ?? []);
    let currentDate = new Date('{{ $selectedDate }}');
    const today = new Date();
    const todayMidnight = new Date(today.getFullYear(), today.getMonth(), today.getDate());

    function pad2(value) {
        return String(value).padStart(2, '0');
    }

    function toIsoDateLocal(dateObj) {
        return `${dateObj.getFullYear()}-${pad2(dateObj.getMonth() + 1)}-${pad2(dateObj.getDate())}`;
    }

    function getMinAllowedDateTime() {
        const now = new Date();
        now.setSeconds(0, 0);
        return new Date(now.getTime() + 4 * 60 * 60 * 1000);
    }

    function isTimeAllowed(dateStr, time) {
        if (!dateStr || !time) return true;
        if (time < '07:00' || time > '19:00') return false;
        const minAllowed = getMinAllowedDateTime();
        const selected = new Date(`${dateStr}T${time}:00`);
        return selected.getTime() >= minAllowed.getTime();
    }
    today.setHours(0,0,0,0);

    const sidebar = document.getElementById('av-sidebar');
    const sidebarToggle = document.getElementById('av-sidebar-toggle');
    const mobileFilterBtn = document.getElementById('av-mobile-filter-btn');

    function toggleSidebar() {
        if (!sidebar) return;
        sidebar.classList.toggle('av-sidebar--open');
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }
    if (mobileFilterBtn) {
        mobileFilterBtn.addEventListener('click', toggleSidebar);
    }

    // Initialize
    renderMiniCalendar();
    loadAvailability();

    // Mini Calendar
    function renderMiniCalendar() {
        const container = document.getElementById('calendar-days');
        const monthLabel = document.getElementById('calendar-month');

        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();

        // Update month label - using English
        monthLabel.textContent = new Date(year, month).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

        // Clear and render calendar days
        container.innerHTML = '';

        // Get first day of month and days in month
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const daysInMonth = lastDay.getDate();

        // Adjust for Monday start
        let startDay = firstDay.getDay() - 1;
        if (startDay === -1) startDay = 6;

        // Add empty cells for days before month starts
        for (let i = 0; i < startDay; i++) {
            const emptyDiv = document.createElement('div');
            container.appendChild(emptyDiv);
        }

        // Add days of month
        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(year, month, day);
            const dateStr = toIsoDateLocal(date);
            const isToday = date.toDateString() === today.toDateString();
            const isSelected = dateStr === '{{ $selectedDate }}';
            const isPast = date < todayMidnight;
            const isSunday = date.getDay() === 0; // 0 = Sunday
            const isHoliday = holidayData[dateStr];

            const dayDiv = document.createElement('div');
            dayDiv.className = 'av-calendar__day';
            dayDiv.textContent = day;

            if (isToday) dayDiv.classList.add('av-calendar__day--today');
            if (isSelected) dayDiv.classList.add('av-calendar__day--selected');
            if (isPast) dayDiv.classList.add('av-calendar__day--disabled');
            if (isSunday) dayDiv.classList.add('av-calendar__day--sunday');
            if (isHoliday) dayDiv.classList.add('av-calendar__day--holiday');

            // Disable clicking on past dates, Sundays, and holidays
            if (!isPast && !isSunday && !isHoliday) {
                dayDiv.style.cursor = 'pointer';
                dayDiv.addEventListener('click', () => selectDate(dateStr));
            }

            // Add tooltips
            if (isSunday) {
                dayDiv.title = 'Sunday - Not available';
            } else if (isHoliday) {
                dayDiv.title = isHoliday + ' - Holiday';
            }

            container.appendChild(dayDiv);
        }
    };

    window.changeMonth = function(direction) {
        currentDate.setMonth(currentDate.getMonth() + direction);
        renderMiniCalendar();
    };

    window.selectDate = function(dateStr) {
        document.getElementById('hidden-date').value = dateStr;
        document.getElementById('av-form').submit();
    };

    function loadAvailability() {
        const container = document.getElementById('availability-list');
        const date = '{{ $selectedDate }}';

        // Show loading state
        container.innerHTML = '<div style="text-align: center; padding: 60px 20px; color: #64748b;"><i class="fas fa-spinner fa-spin fa-2x" style="margin-bottom: 12px;"></i><p>Loading availability...</p></div>';

        // Add timeout to show error if loading takes too long
        const timeout = setTimeout(() => {
            container.innerHTML = '<div style="text-align: center; padding: 60px 20px; color: #ef4444;"><i class="fas fa-exclamation-triangle fa-2x" style="margin-bottom: 12px;"></i><p>Loading is taking longer than expected. Please try again.</p></div>';
        }, 10000); // 10 seconds timeout

        fetch(`{{ route('vendor.ajax.available_slots') }}?date=${date}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
            .then(r => {
                if (r.status === 401) {
                    // Session expired, reload page
                    window.location.reload();
                    return;
                }
                if (!r.ok) {
                    throw new Error(`HTTP ${r.status}: ${r.statusText}`);
                }
                return r.json();
            })
            .then(data => {
                clearTimeout(timeout);

                if (!data.success || !data.slots) {
                    container.innerHTML = '<p style="text-align: center; color: #ef4444; padding: 40px;">Failed to load</p>';
                    return;
                }
                const available = data.slots.filter(s => s.is_available);
                if (!available.length) {
                    container.innerHTML = '<p style="text-align: center; color: #64748b; padding: 40px;">No available times</p>';
                    return;
                }
                let html = '';
                available.forEach(slot => {
                    const isAllowed = isTimeAllowed(date, slot.time);
                    html += `
                        <button type="button" class="av-available-item${isAllowed ? '' : ' cb-slot-btn--disabled'}" data-time="${slot.time}" ${isAllowed ? '' : 'disabled'}>
                            <span class="av-available-time">${slot.time}</span>
                            ${isAllowed ? '' : '<span class="av-available-note">Not available</span>'}
                        </button>
                    `;
                });
                container.innerHTML = html;

                container.querySelectorAll('.av-available-item[data-time]').forEach(btn => {
                    if (btn.hasAttribute('disabled')) {
                        return;
                    }
                    btn.addEventListener('click', () => {
                        const url = new URL('{{ route("vendor.bookings.create") }}', window.location.origin);
                        url.searchParams.set('date', date);
                        url.searchParams.set('time', btn.dataset.time);
                        window.location.href = url.toString();
                    });
                });
            })
            .catch(error => {
                clearTimeout(timeout);
                console.error('Error loading availability:', error);
                container.innerHTML = `<p style="text-align: center; color: #ef4444; padding: 40px;">Error loading availability: ${error.message}</p>`;
            });
    }
});
</script>
@endpush
