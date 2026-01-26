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
    }
    .av-calendar__day:hover { background: #f1f5f9; }
    .av-calendar__day--today { background: #dbeafe; color: #1e40af; font-weight: 600; }
    .av-calendar__day--selected { background: #1e40af; color: white; font-weight: 600; }
    .av-calendar__day--other { color: #9ca3af; }

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
        border-color: #bfdbfe;
        box-shadow: 0 6px 16px rgba(30, 64, 175, 0.08);
        transform: translateY(-1px);
    }
    .av-available-time {
        font-weight: 700;
        color: #1e293b;
        font-size: 14px;
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

        <div class="av-sidebar__body" id="av-sidebar-body">
        <form method="GET" action="{{ route('vendor.availability') }}" id="av-form">
            <input type="hidden" name="date" id="hidden-date" value="{{ $selectedDate }}">
        </form>

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
                <div class="av-calendar__day-header">Mo</div>
                <div class="av-calendar__day-header">Tu</div>
                <div class="av-calendar__day-header">We</div>
                <div class="av-calendar__day-header">Th</div>
                <div class="av-calendar__day-header">Fr</div>
                <div class="av-calendar__day-header">Sa</div>
                <div class="av-calendar__day-header">Su</div>
            </div>
            <div class="av-calendar__grid" id="calendar-days"></div>
        </div>

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

        <div id="availability-list">
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
    const warehouseId = '{{ $selectedWarehouse?->id }}';
    let currentDate = new Date('{{ $selectedDate }}');
    const today = new Date();
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
    if (warehouseId) {
        loadAvailability();
    }

    // Mini Calendar
    function renderMiniCalendar() {
        const container = document.getElementById('calendar-days');
        const monthLabel = document.getElementById('calendar-month');

        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();

        monthLabel.textContent = currentDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const startDay = (firstDay.getDay() + 6) % 7; // Monday = 0

        let html = '';

        // Previous month days
        const prevMonth = new Date(year, month, 0);
        for (let i = startDay - 1; i >= 0; i--) {
            const day = prevMonth.getDate() - i;
            html += `<div class="av-calendar__day av-calendar__day--other">${day}</div>`;
        }

        // Current month days
        const selectedStr = '{{ $selectedDate }}';
        const todayStr = today.toISOString().split('T')[0];

        for (let day = 1; day <= lastDay.getDate(); day++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            let classes = 'av-calendar__day';
            if (dateStr === todayStr) classes += ' av-calendar__day--today';
            if (dateStr === selectedStr) classes += ' av-calendar__day--selected';
            html += `<div class="${classes}" onclick="selectDate('${dateStr}')">${day}</div>`;
        }

        // Next month days
        const remaining = 42 - (startDay + lastDay.getDate());
        for (let day = 1; day <= remaining && day <= 7; day++) {
            html += `<div class="av-calendar__day av-calendar__day--other">${day}</div>`;
        }

        container.innerHTML = html;
    }

    window.changeMonth = function(delta) {
        currentDate.setMonth(currentDate.getMonth() + delta);
        renderMiniCalendar();
    };

    window.selectDate = function(dateStr) {
        document.getElementById('hidden-date').value = dateStr;
        document.getElementById('av-form').submit();
    };

    // Availability list
    function loadAvailability() {
        const container = document.getElementById('availability-list');
        const date = '{{ $selectedDate }}';

        fetch(`{{ route('vendor.ajax.available_slots') }}?warehouse_id=${warehouseId}&date=${date}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.slots) {
                    container.innerHTML = '<p style="text-align: center; color: #ef4444; padding: 40px;">Failed to load</p>';
                    return;
                }
                const available = data.slots.filter(s => s.is_available);
                if (!available.length) {
                    container.innerHTML = '<p style="text-align: center; color: #64748b; padding: 40px;">No available times</p>';
                    return;
                }
                let html = '<div class="av-available-list">';
                available.forEach(slot => {
                    html += `
                        <button type="button" class="av-available-item" data-time="${slot.time}">
                            <span class="av-available-time">${slot.time}</span>
                        </button>
                    `;
                });
                html += '</div>';
                container.innerHTML = html;

                container.querySelectorAll('.av-available-item[data-time]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const url = new URL('{{ route("vendor.bookings.create") }}', window.location.origin);
                        url.searchParams.set('date', date);
                        url.searchParams.set('time', btn.dataset.time);
                        window.location.href = url.toString();
                    });
                });
            })
            .catch(() => {
                container.innerHTML = '<p style="text-align: center; color: #ef4444; padding: 40px;">Error loading availability</p>';
            });
    }
});
</script>
@endpush
