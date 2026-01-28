@extends('vendor.layouts.vendor')

@section('title', 'Gate Availability - Vendor Portal')

@section('body_class', 'vendor-body--no-scroll')
@section('page_class', 'vendor-page--layout vendor-page--availability')

@section('content')

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
            <div class="av-empty">
                <i class="fas fa-spinner fa-spin av-empty__icon av-empty__icon--spinner"></i>
                <p>Loading availability...</p>
            </div>
        </div>
    </div>
            </div>
        </div>
    </div>

    <!-- Footer Container -->
    <div class="av-footer-container">
        <div class="av-footer-text">
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
        container.innerHTML = '<div class="av-empty"><i class="fas fa-spinner fa-spin av-empty__icon av-empty__icon--spinner"></i><p>Loading availability...</p></div>';

        // Add timeout to show error if loading takes too long
        const timeout = setTimeout(() => {
            container.innerHTML = '<div class="av-empty av-empty--error"><i class="fas fa-exclamation-triangle av-empty__icon av-empty__icon--alert"></i><p>Loading is taking longer than expected. Please try again.</p></div>';
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
                    container.innerHTML = '<p class="av-empty av-empty--error av-empty--compact">Failed to load</p>';
                    return;
                }
                const available = data.slots.filter(s => s.is_available);
                if (!available.length) {
                    container.innerHTML = '<p class="av-empty av-empty--compact">No available times</p>';
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
                container.innerHTML = `<p class="av-empty av-empty--error av-empty--compact">Error loading availability: ${error.message}</p>`;
            });
    }
});
</script>
@endpush
