@extends('layouts.app')

@section('title', 'Gate Management Schedule')
@section('page_title', 'Gate Management')

@section('content')
<div class="st-gate-layout">
    <!-- Left Sidebar -->
    <aside class="st-gate-sidebar">
        <div class="st-gate-sidebar__header">
            <h3>Gate Management</h3>
            <p>Monage Your Gate Schedule</p>
        </div>

        <!-- Date Picker (Static Mockup) -->
        <div class="st-gate-calendar-widget">
            <div class="flatpickr-inline-container" id="inline_calendar"></div>
            <!-- We will initialize flatpickr here via JS -->
        </div>

        <!-- Legend -->
        <div class="st-gate-legend">
            <div class="st-gate-legend__title">STATUS LEGEND</div>
            
            <div class="st-gate-legend__item">
                <span class="st-status-dot" style="background-color: #e2e8f0;"></span>
                <span>Pre-Booking</span>
            </div>
            <div class="st-gate-legend__item">
                <span class="st-status-dot" style="background-color: #1e40af;"></span>
                <span>Confirmed</span>
            </div>
            <div class="st-gate-legend__item">
                <span class="st-status-dot" style="background-color: #7c3aed;"></span>
                <span>In Progress</span>
            </div>
            <div class="st-gate-legend__item">
                <span class="st-status-dot" style="background-color: #15803d;"></span>
                <span>Completed</span>
            </div>
            <div class="st-gate-legend__item">
                <span class="st-status-dot" style="background-color: #dc2626;"></span>
                <span>Reject / Cancel</span>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="st-gate-main">
        <!-- Top Toolbar -->
        <div class="st-gate-toolbar">
            <div class="st-search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search for a Booking..." class="st-input st-input--search">
            </div>
            <button class="st-btn st-btn--primary">
                <i class="fas fa-plus"></i> New Booking
            </button>
        </div>

        <!-- Schedule Grid -->
        <div class="st-gate-grid-wrapper">
            <!-- Header Row -->
            <div class="st-gate-grid-header">
                <div class="st-col-time"></div> <!-- Empty corner -->
                <div class="st-col-header">
                    <div class="st-col-title">Gate A</div>
                    <label class="st-switch">
                        <input type="checkbox" checked>
                        <span class="st-switch-slider"></span>
                    </label>
                </div>
                <div class="st-col-header">
                    <div class="st-col-title">Gate B</div>
                    <label class="st-switch">
                        <input type="checkbox" checked>
                        <span class="st-switch-slider"></span>
                    </label>
                </div>
                <div class="st-col-header">
                    <div class="st-col-title">Gate C</div>
                    <label class="st-switch">
                        <input type="checkbox">
                        <span class="st-switch-slider"></span>
                    </label>
                </div>
            </div>

            <!-- Timeline Body -->
            <div class="st-gate-grid-body">
                <!-- Current Time Indicator Line (example position) -->
                <div class="st-current-time-line" style="top: 320px;">
                    <span class="st-time-badge">09:15</span>
                </div>

                <!-- Colors mapped from style.css logic where possible, or hardcoded for specific request -->
                <!-- Pre-Booking: Light Gray (#e2e8f0 / slate-200) -->
                <!-- Confirmed: Dark Blue (#1e40af / blue-800 or similar) -->
                <!-- In Progress: Purple (#7c3aed) -->
                
                @php
                    $hours = range(7, 23);
                @endphp

                <div class="st-time-column">
                    @foreach($hours as $h)
                        <div class="st-time-slot">
                            <span>{{ sprintf('%02d:00', $h) }}</span>
                        </div>
                    @endforeach
                </div>

                <!-- Gate A Column -->
                <div class="st-gate-column">
                    <!-- Example Card: Confirmed -->
                    <div class="st-booking-card st-status-confirmed" style="top: 60px; height: 90px;">
                        <div class="st-card-header">
                            <span class="st-ticket-no">A26A0027</span>
                            <span class="st-category">Logistics</span>
                        </div>
                        <div class="st-card-time">08:00 - 09:30</div>
                        <div class="st-card-actions">
                            <!-- Helper icon or status -->
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>

                    <!-- Example Card: In Progress -->
                    <div class="st-booking-card st-status-inprogress" style="top: 180px; height: 120px;">
                        <div class="st-card-header">
                            <span class="st-ticket-no">A26A0029</span>
                            <span class="st-category">Supplier</span>
                        </div>
                        <div class="st-card-time">10:00 - 12:00</div>
                    </div>
                </div>

                <!-- Gate B Column -->
                <div class="st-gate-column">
                    <!-- Example Card: Pre-Booking / Pending -->
                    <div class="st-booking-card st-status-prebooking" style="top: 120px; height: 60px;">
                        <div class="st-card-header">
                            <span class="st-ticket-no">REQ-001</span>
                            <span class="st-category">Ad-hoc</span>
                        </div>
                        <div class="st-card-time">09:00 - 10:00</div>
                        <div class="st-card-actions">
                            <button class="st-icon-btn st-btn-confirm" title="Confirm"><i class="fas fa-check"></i></button>
                            <button class="st-icon-btn st-btn-reject" title="Reject"><i class="fas fa-times"></i></button>
                            <button class="st-icon-btn st-btn-reschedule" title="Reschedule"><i class="fas fa-clock"></i></button>
                        </div>
                    </div>

                    <!-- Empty State / Confirm Button -->
                    <div class="st-empty-slot" style="top: 250px; height: 60px;">
                        <button class="st-btn-dashed">
                            <i class="fas fa-plus"></i> Confirm Slot
                        </button>
                    </div>
                </div>

                 <!-- Gate C Column (Deactive visual) -->
                 <div class="st-gate-column st-col-deactive">
                    <!-- Maybe shaded or disabled -->
                </div>

            </div>
        </div>
    </main>
</div>

@push('styles')
<style>
    /* Scoped Styles for Gate Schedule */
    .st-gate-layout {
        display: flex;
        height: calc(100vh - 80px); /* Adjust based on topbar height */
        background: #f8fafc; /* Very light gray */
        gap: 0;
        overflow: hidden;
    }

    /* Sidebar */
    .st-gate-sidebar {
        width: 300px;
        flex-shrink: 0;
        background: #ffffff;
        border-right: 1px solid #e2e8f0;
        padding: 24px;
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .st-gate-sidebar__header h3 {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 4px 0;
    }
    .st-gate-sidebar__header p {
        font-size: 0.875rem;
        color: #64748b;
        margin: 0;
    }

    /* Legend */
    .st-gate-legend__title {
        font-size: 0.75rem;
        font-weight: 700;
        color: #94a3b8;
        letter-spacing: 0.05em;
        margin-bottom: 12px;
    }
    .st-gate-legend__item {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 8px;
        font-size: 0.875rem;
        color: #475569;
    }
    .st-status-dot {
        width: 12px;
        height: 12px;
        border-radius: 4px; /* Soft square */
    }

    /* Main Area */
    .st-gate-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        background: #ffffff;
    }

    /* Toolbar */
    .st-gate-toolbar {
        padding: 16px 24px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #ffffff;
    }
    .st-search-box {
        position: relative;
        width: 300px;
    }
    .st-search-box i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }
    .st-input--search {
        padding-left: 36px;
        border-radius: 8px;
        background: #f1f5f9;
        border: 1px solid transparent;
    }
    .st-input--search:focus {
        background: #ffffff;
        border-color: #0284c7;
    }

    /* Grid Wrapper */
    .st-gate-grid-wrapper {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        position: relative;
    }

    /* Grid Header */
    .st-gate-grid-header {
        display: flex;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
        flex-shrink: 0;
    }
    .st-col-time {
        width: 80px; /* Time column width */
        flex-shrink: 0;
        border-right: 1px solid #e2e8f0;
        background: #ffffff;
    }
    .st-col-header {
        flex: 1;
        padding: 12px 16px;
        border-right: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #ffffff;
    }
    .st-col-title {
        font-weight: 600;
        color: #334155;
    }

    /* Toggle Switch */
    .st-switch {
        position: relative;
        display: inline-block;
        width: 36px;
        height: 20px;
    }
    .st-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .st-switch-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #cbd5e1;
        transition: .4s;
        border-radius: 20px;
    }
    .st-switch-slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 2px;
        bottom: 2px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    input:checked + .st-switch-slider {
        background-color: #0284c7;
    }
    input:checked + .st-switch-slider:before {
        transform: translateX(16px);
    }

    /* Grid Body */
    .st-gate-grid-body {
        flex: 1;
        overflow-y: auto;
        display: flex;
        position: relative;
        /* Background grid lines can be done with repeating-linear-gradient */
        background-image: linear-gradient(#f1f5f9 1px, transparent 1px);
        background-size: 100% 60px; /* 60px per hour/slot */
    }

    .st-time-column {
        width: 80px;
        flex-shrink: 0;
        border-right: 1px solid #e2e8f0;
        background: #ffffff;
        display: flex;
        flex-direction: column;
    }
    .st-time-slot {
        height: 60px; /* 1 hour height */
        display: flex;
        justify-content: center;
        align-items: flex-start; /* Time at top line */
        padding-top: 4px; /* push text slightly down from line */
        font-size: 0.75rem;
        color: #94a3b8;
        font-weight: 500;
        border-bottom: 1px solid transparent; /* Align with grid lines */
    }
    
    .st-gate-column {
        flex: 1;
        position: relative;
        border-right: 1px solid #e2e8f0;
        /* Columns need height based on total hours (16 hours * 60px = 960px) */
        min-height: {{ count($hours) * 60 }}px; 
    }
    .st-col-deactive {
        background: repeating-linear-gradient(
            45deg,
            #f8fafc,
            #f8fafc 10px,
            #f1f5f9 10px,
            #f1f5f9 20px
        );
        opacity: 0.6;
    }

    /* Booking Cards */
    .st-booking-card {
        position: absolute;
        width: 90%; /* Some padding on sides */
        left: 5%;
        border-radius: 8px;
        padding: 8px 12px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 4px;
        color: white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        font-size: 0.8125rem;
        transition: transform 0.2s, box-shadow 0.2s;
        border-left: 4px solid rgba(0,0,0,0.1); /* Left accent */
        z-index: 10;
    }
    .st-booking-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 11;
    }

    .st-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .st-ticket-no {
        font-weight: 700;
        font-family: monospace;
    }
    .st-category {
        font-size: 0.65rem;
        opacity: 0.9;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .st-card-time {
        font-weight: 500;
        opacity: 0.9;
    }
    .st-card-actions {
        display: flex;
        gap: 6px;
        margin-top: 4px;
        justify-content: flex-end;
    }

    /* Icon Buttons inside card */
    .st-icon-btn {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,0.2);
        color: white;
        cursor: pointer;
        font-size: 0.75rem;
        transition: background 0.2s;
    }
    .st-icon-btn:hover {
        background: rgba(255,255,255,0.4);
    }
    .st-btn-confirm:hover { background: #22c55e; }
    .st-btn-reject:hover { background: #ef4444; }
    .st-btn-reschedule:hover { background: #eab308; }

    /* Colors */
    .st-status-prebooking {
        background-color: #cbd5e1; /* Light Gray */
        color: #334155; /* Dark Text */
        border-left-color: #94a3b8;
    }
    .st-status-prebooking .st-icon-btn {
        color: #334155;
        background: rgba(0,0,0,0.1);
    }
    .st-status-prebooking .st-icon-btn:hover {
        background: rgba(0,0,0,0.2);
    }

    .st-status-confirmed {
        background-color: #1e40af; /* Dark Blue */
    }
    .st-status-inprogress {
        background-color: #7c3aed; /* Purple */
    }
    .st-status-completed {
        background-color: #15803d; /* Green */
    }
    .st-status-reject {
        background-color: #dc2626; /* Red */
    }

    /* Current Time Line */
    .st-current-time-line {
        position: absolute;
        left: 0;
        right: 0;
        height: 2px;
        background: #ef4444; /* Red */
        z-index: 20;
        pointer-events: none;
    }
    .st-current-time-line:before {
        content: "";
        position: absolute;
        left: -4px;
        top: -3px;
        width: 8px;
        height: 8px;
        background: #ef4444;
        border-radius: 50%;
    }
    .st-time-badge {
        position: absolute;
        left: 10px;
        top: -10px;
        background: #ef4444;
        color: white;
        font-size: 0.65rem;
        padding: 2px 6px;
        border-radius: 10px;
        font-weight: 700;
    }

    /* Empty Slot / Add Button */
    .st-empty-slot {
        position: absolute;
        width: 90%;
        left: 5%;
        z-index: 5;
    }
    .st-btn-dashed {
        width: 100%;
        height: 100%;
        border: 2px dashed #cbd5e1;
        background: rgba(255,255,255,0.5);
        border-radius: 8px;
        color: #64748b;
        font-weight: 500;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.2s;
    }
    .st-btn-dashed:hover {
        border-color: #94a3b8;
        background: #f1f5f9;
        color: #0284c7;
    }

    /* Calendar Widget Overrides */
    .st-gate-calendar-widget {
        background: #f8fafc;
        border-radius: 12px;
        padding: 10px;
        border: 1px solid #e2e8f0;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Inline Calendar
        if (typeof flatpickr !== 'undefined') {
            flatpickr("#inline_calendar", {
                inline: true,
                dateFormat: "Y-m-d",
                defaultDate: "today",
                onChange: function(selectedDates, dateStr, instance) {
                    console.log("Date selected:", dateStr);
                    // Add logic to fetch schedule for date
                }
            });
        }

        // Search Focus interaction
        const searchInput = document.querySelector('.st-input--search');
        const searchBox = document.querySelector('.st-search-box i');
        if (searchInput) {
            searchInput.addEventListener('focus', () => searchBox.style.color = '#0284c7');
            searchInput.addEventListener('blur', () => searchBox.style.color = '#94a3b8');
        }
    });
</script>
@endpush
