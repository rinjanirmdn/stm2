@extends('vendor.layouts.vendor')

@section('title', 'Gate Availability - Vendor Portal')

@section('page_class', 'vendor-page--layout vendor-page--availability')

@section('content')

<div class="av-container">
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
                    {{ \Carbon\Carbon::parse($selectedDate)->format('l, d F Y') }}
                </span>
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
@endsection

@push('scripts')
<script type="application/json" id="vendor_availability_config">{!! json_encode([
    'selectedDate' => $selectedDate,
    'holidays' => $holidays ?? [],
    'availableSlotsUrl' => auth()->user()->can('vendor.ajax.available_slots') ? route('vendor.ajax.available_slots') : null,
    'bookingCreateUrl' => auth()->user()->can('vendor.bookings.create') ? route('vendor.bookings.create') : null,
]) !!}</script>
@endpush

