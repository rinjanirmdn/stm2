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
        window.vendorAvailabilityConfig = {
            selectedDate: @json($selectedDate),
            holidays: @json($holidays ?? []),
            availableSlotsUrl: @json(route('vendor.ajax.available_slots')),
            bookingCreateUrl: @json(route('vendor.bookings.create'))
        };
    </script>
@endpush
