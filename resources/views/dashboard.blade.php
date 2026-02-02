@extends('layouts.app')

@section('title', 'Dashboard - Slot Time Management')
@section('page_title', 'Dashboard')
@section('body_class', 'st-app--dashboard')

@section('content')
    <div class="st-dashboard-slideshow" data-autoplay-ms="10000" data-active-index="0">
        <div class="st-dashboard-slideshow__nav" aria-label="Dashboard slides">
            <button type="button" class="st-dashboard-slideshow__btn" data-slide-prev aria-label="Previous slide">‹</button>
            <div class="st-dashboard-slideshow__dots" role="tablist" aria-label="Dashboard slide selector">
                <button type="button" class="st-dashboard-slideshow__dot" data-slide-dot="0" aria-label="Slide 1" aria-current="true"></button>
                <button type="button" class="st-dashboard-slideshow__dot" data-slide-dot="1" aria-label="Slide 2" aria-current="false"></button>
                <button type="button" class="st-dashboard-slideshow__dot" data-slide-dot="2" aria-label="Slide 3" aria-current="false"></button>
                <button type="button" class="st-dashboard-slideshow__dot" data-slide-dot="3" aria-label="Slide 4" aria-current="false"></button>
                <button type="button" class="st-dashboard-slideshow__dot" data-slide-dot="4" aria-label="Slide 5" aria-current="false"></button>
            </div>
            <button type="button" class="st-dashboard-slideshow__btn" data-slide-next aria-label="Next slide">›</button>
        </div>
        <div class="st-dashboard-slideshow__viewport">
        <div class="st-dashboard-slideshow__track">
        <div class="st-dashboard-slide is-active" data-slide-index="0">
    <section class="st-row">
        <div class="st-col-12">
            <div class="st-card" id="analytics-tabs-card">
                <div class="st-card__header st-dashboard-card-header">
                    <div>
                        <h2 class="st-card__title">Analytics Range</h2>
                        <div class="st-card__subtitle">Date Range for Analytics (Overview / KPI / Bottleneck)</div>
                    </div>
                    <div class="st-dashboard-card-actions">
                        <form method="GET" class="st-form-row st-dashboard-range-form">
                            <input type="hidden" name="activity_date" value="{{ $activity_date ?? $today }}">
                            <input type="hidden" name="activity_warehouse" value="{{ $activity_warehouse ?? 0 }}">
                            <input type="hidden" name="activity_user" value="{{ $activity_user ?? 0 }}">
                            <input type="hidden" id="range_start" name="range_start" value="{{ $range_start }}">
                            <input type="hidden" id="range_end" name="range_end" value="{{ $range_end }}">
                            <div class="st-form-field st-dashboard-range-field">
                                <label class="st-label">Range</label>
                                <input type="text" id="analytics_range" class="st-input" placeholder="Select Date Range" value="{{ $range_start ?? $today }}" autocomplete="off" readonly>
                            </div>
                            <div class="st-form-field st-dashboard-range-reset">
                                <a href="{{ route('dashboard', ['range_start' => \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d'), 'range_end' => $today]) }}" class="st-btn st-btn--outline-primary">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="st-dashboard-mini-grid">
                    <div class="st-mini-card st-mini-card--with-info">
                        <button type="button" class="st-tooltip st-mini-card__info" aria-label="Info: Pending" title="Booking requests awaiting approval before entering the official schedule.">
                            <i class="fa-solid fa-info"></i>
                        </button>
                        <div class="st-text--small st-text--muted">Pending</div>
                        <div class="st-mini-card__value-row">
                            <div class="st-dashboard-mini-value">{{ $pendingRange ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="st-mini-card st-mini-card--with-info">
                        <button type="button" class="st-tooltip st-mini-card__info" aria-label="Info: Scheduled" title="Slots already scheduled, but the truck has not arrived yet.">
                            <i class="fa-solid fa-info"></i>
                        </button>
                        <div class="st-text--small st-text--muted">Scheduled</div>
                        <div class="st-mini-card__value-row">
                            <div class="st-dashboard-mini-value">{{ $scheduledRange ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="st-mini-card st-mini-card--with-info">
                        <button type="button" class="st-tooltip st-mini-card__info" aria-label="Info: Waiting" title="Slots where the truck has arrived and is waiting in the queue.">
                            <i class="fa-solid fa-info"></i>
                        </button>
                        <div class="st-text--small st-text--muted">Waiting</div>
                        <div class="st-mini-card__value-row">
                            <div class="st-dashboard-mini-value">{{ $waitingRange ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="st-mini-card st-mini-card--with-info">
                        <button type="button" class="st-tooltip st-mini-card__info" aria-label="Info: In progress" title="Slots currently being processed at the gate (Loading/Unloading in progress).">
                            <i class="fa-solid fa-info"></i>
                        </button>
                        <div class="st-text--small st-text--muted">In Progress</div>
                        <div class="st-mini-card__value-row">
                            <div class="st-dashboard-mini-value">{{ $activeRange ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="st-mini-card st-mini-card--with-info">
                        <button type="button" class="st-tooltip st-mini-card__info" aria-label="Info: Completed" title="Slots where the process is already finished.">
                            <i class="fa-solid fa-info"></i>
                        </button>
                        <div class="st-text--small st-text--muted">Completed</div>
                        <div class="st-mini-card__value-row">
                            <div class="st-dashboard-mini-value">{{ $completedStatusRange ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="st-mini-card st-mini-card--with-info">
                        <button type="button" class="st-tooltip st-mini-card__info" aria-label="Info: Cancel" title="Cancelled slots.">
                            <i class="fa-solid fa-info"></i>
                        </button>
                        <div class="st-text--small st-text--muted">Cancel</div>
                        <div class="st-mini-card__value-row">
                            <div class="st-dashboard-mini-value">{{ $cancelledRange ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="st-mini-card st-mini-card--with-info">
                        <button type="button" class="st-tooltip st-mini-card__info" aria-label="Info: Total" title="Total of all slots within the selected date range.">
                            <i class="fa-solid fa-info"></i>
                        </button>
                        <div class="st-text--small st-text--muted">Total</div>
                        <div class="st-mini-card__value-row">
                            <div class="st-dashboard-mini-value">{{ ((int)($pendingRange ?? 0) + (int)($scheduledRange ?? 0) + (int)($waitingRange ?? 0) + (int)($activeRange ?? 0) + (int)($completedStatusRange ?? 0) + (int)($cancelledRange ?? 0)) }}</div>
                        </div>
                    </div>
                </div>

                <div class="st-card__header st-dashboard-card-header">
                    <div>
                        <br>
                        <h2 class="st-card__title">Analytics</h2>
                        <div class="st-card__subtitle">Performance & Bottleneck Summary</div>
                    </div>
                </div>

                <div class="st-chart-grid">
                        <div class="st-chart-col-8">
                            <div class="st-chart-card">
                                <div class="st-chart-card__title" id="chart_trend_title">Completed Trend</div>
                                <div class="st-chart-wrap st-chart-wrap--sm">
                                    <canvas id="chart_trend"></canvas>
                                </div>
                                <div class="st-chart-axis-legend st-text--small st-text--muted">
                                    <span class="st-chart-axis-legend__pill">
                                        <span class="st-legend-dot st-bg-teal-500"></span>
                                        <strong>INBOUND</strong>
                                    </span>
                                    <span class="st-chart-axis-legend__pill">
                                        <span class="st-legend-dot st-bg-indigo-500"></span>
                                        <strong>OUTBOUND</strong>
                                    </span>
                                </div>

                            </div>
                        </div>
                        <div class="st-chart-col-4">
                            <div class="st-chart-card">
                                <div class="st-dashboard-chart-header">
                                    <div class="st-chart-card__title st-mb-0">Direction</div>
                                    <select id="direction_gate" class="st-select st-select--dashboard-compact">
                                        <option value="all">All Gates</option>
                                        <option value="A">Gate A</option>
                                        <option value="B">Gate B</option>
                                        <option value="C">Gate C</option>
                                    </select>
                                </div>
                                <div class="st-direction-stack">
                                    <div class="st-chart-wrap st-chart-wrap--direction">
                                        <canvas id="chart_direction"></canvas>
                                    </div>
                                    <div class="st-metric-row st-metric-row--thirds">
                                        <div class="st-mini-card">
                                            <div class="st-text--small st-text--muted">Inbound</div>
                                            <div id="direction_inbound_value" class="st-direction-metric">0</div>
                                        </div>
                                        <div class="st-mini-card">
                                            <div class="st-text--small st-text--muted">Outbound</div>
                                            <div id="direction_outbound_value" class="st-direction-metric">0</div>
                                        </div>
                                        <div class="st-mini-card">
                                            <div class="st-text--small st-text--muted">Total</div>
                                            <div id="direction_total_value" class="st-direction-metric">0</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

            </div>
        </div>
    </section>

        </div>
        <div class="st-dashboard-slide" data-slide-index="1">
            <section class="st-row st-dashboard-section-full">
                <div class="st-col-12 st-dashboard-col-full">
                    <div class="st-card st-dashboard-card-fill">
                        <div class="st-card__header st-dashboard-card-header">
                            <div>
                                <h2 class="st-card__title">Analytics</h2>
                                <div class="st-card__subtitle">Bottleneck & Performance</div>
                            </div>
                        </div>

                        <div class="st-chart-grid st-dashboard-chart-grid-start">
                            <div class="st-chart-col-6">
                                <div class="st-chart-card st-dashboard-chart-card-fill">
                                    <div class="st-dashboard-chart-header-row">
                                        <div class="st-chart-card__title st-dashboard-chart-title-nowrap">Bottleneck (Avg Waiting)</div>
                                        <select id="bottleneck_dir" class="st-select st-select--dashboard-compact-sm">
                                            <option value="all">All</option>
                                            <option value="inbound">Inbound</option>
                                            <option value="outbound">Outbound</option>
                                        </select>
                                    </div>
                                    <div class="st-chart-wrap st-dashboard-chart-wrap-fill">
                                        <canvas id="chart_bottleneck"></canvas>
                                    </div>
                                    <div class="st-metric-row st-metric-row--bottleneck st-metric-row--mt">
                                        <div class="st-mini-card st-mini-card--dashboard">
                                            <div class="st-text--small st-text--muted st-text--xs">Top Wait Location</div>
                                            <div id="bottleneck_top_label" class="st-dashboard-metric-md st-truncate">-</div>
                                        </div>
                                        <div class="st-mini-card st-mini-card--dashboard">
                                            <div class="st-text--small st-text--muted st-text--xs">Avg Wait</div>
                                            <div id="bottleneck_top_avg" class="st-dashboard-metric-md">0</div>
                                        </div>
                                        <div class="st-mini-card st-mini-card--dashboard">
                                            <div class="st-text--small st-text--muted st-text--xs">Qty</div>
                                            <div id="bottleneck_top_slots" class="st-dashboard-metric-md">0</div>
                                        </div>
                                    </div>
                                    <div class="st-text--small st-text--muted st-dashboard-footnote">Top 20, Threshold {{ (int)($bottleneckThresholdMinutes ?? 30) }} Min</div>
                                </div>
                            </div>

                            <div class="st-chart-col-6">
                                <div class="st-chart-card st-dashboard-chart-card-fill">
                                    <div class="st-dashboard-chart-header-row st-dashboard-chart-header-row--lg">
                                        <div class="st-chart-card__title st-mb-0">Performance by Truck Type</div>
                                        <select id="lead_proc_unit" class="st-select st-select--dashboard-compact-sm">
                                            <option value="minute">Minutes</option>
                                            <option value="hour">Hours</option>
                                        </select>
                                    </div>
                                    <div class="st-dashboard-truck-grid">
                                            @foreach($avgTimesByTruckType ?? [] as $t)
                                            @php
                                                $truckType = (string) data_get($t, 'truck_type', '-');
                                                $truckLabel = preg_replace('/^Container\s+/i', '', $truckType);
                                                $truckLabel = str_ireplace(['Wingbox'], ['WB'], $truckLabel);
                                                $truckLabel = trim($truckLabel);
                                                $totalCount = (int) data_get($t, 'total_count', 0);
                                                $avgLeadMinutesByTruck = (float) data_get($t, 'avg_lead_minutes', 0);
                                                $avgProcessMinutesByTruck = (float) data_get($t, 'avg_process_minutes', 0);
                                            @endphp
                                            <div class="st-dashboard-truck-card">
                                                <div class="st-dashboard-truck-card-header">
                                                    <span class="st-truncate" title="{{ $truckType }}">{{ $truckLabel }}</span>
                                                    <span class="st-dashboard-truck-count">{{ $totalCount }}</span>
                                                </div>
                                                <div class="st-dashboard-truck-body">
                                                    <div class="st-dashboard-truck-box st-dashboard-truck-box--lead">
                                                        <div class="st-dashboard-truck-label st-dashboard-truck-label--lead">Avg Lead Time</div>
                                                        <div class="lead-avg-truck st-dashboard-truck-value st-dashboard-truck-value--lead" data-minutes="{{ $avgLeadMinutesByTruck }}">
                                                            @if($totalCount > 0)
                                                                {{ number_format($avgLeadMinutesByTruck, 1) }}<span class="st-dashboard-truck-unit">m</span>
                                                            @else
                                                                -
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="st-dashboard-truck-box st-dashboard-truck-box--proc">
                                                        <div class="st-dashboard-truck-label st-dashboard-truck-label--proc">Avg Process Time</div>
                                                        <div class="proc-avg-truck st-dashboard-truck-value st-dashboard-truck-value--proc" data-minutes="{{ $avgProcessMinutesByTruck }}">
                                                            @if($totalCount > 0)
                                                                {{ number_format($avgProcessMinutesByTruck, 1) }}<span class="st-dashboard-truck-unit">m</span>
                                                            @else
                                                                -
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div id="lead_proc_empty" class="st-hidden"></div>
                                    <div id="lead_avg_value" class="st-hidden" data-minutes="{{ $avgLeadMinutes }}"></div>
                                    <div id="proc_avg_value" class="st-hidden" data-minutes="{{ $avgProcessMinutes }}"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </div>
        <div class="st-dashboard-slide" data-slide-index="2">
            <section class="st-row st-dashboard-section-gap">
                <div class="st-col-12">
                    <div class="st-card st-dashboard-card-padded">
                        <div class="st-card__header st-dashboard-card-header">
                            <div>
                                <h2 class="st-card__title">KPI</h2>
                                <div class="st-card__subtitle">On Time, Target Achievement, Completion Rate</div>
                            </div>
                            <div class="st-dashboard-filter-row">
                                <select id="kpi_dir_filter" class="st-select st-select--dashboard-wide">
                                    <option value="all">All Direction</option>
                                    <option value="inbound">Inbound</option>
                                    <option value="outbound">Outbound</option>
                                </select>
                                <select id="kpi_gate_filter" class="st-select st-select--dashboard-wide">
                                    <option value="all">All Gates</option>
                                    <option value="A">Gate A</option>
                                    <option value="B">Gate B</option>
                                    <option value="C">Gate C</option>
                                </select>
                            </div>
                        </div>

                        <div class="st-chart-grid">
                            <div class="st-chart-col-4">
                                <div class="st-chart-card">
                                    <div class="st-flex-between-center st-flex-wrap st-gap-2">
                                        <div class="st-chart-card__title st-mb-0">On Time vs Late</div>
                                    </div>
                                    <div class="st-chart-wrap st-chart-wrap--sm st-dashboard-chart-wrap-offset">
                                        <canvas id="chart_on_time"></canvas>
                                    </div>
                                    <div class="st-metric-row st-metric-row--mt">
                                        <div class="st-mini-card"><div class="st-text--small st-text--muted">On Time</div><div id="on_time_value" class="st-dashboard-metric-xl">0</div></div>
                                        <div class="st-mini-card"><div class="st-text--small st-text--muted">Late</div><div id="late_value" class="st-dashboard-metric-xl">0</div></div>
                                        <div class="st-mini-card"><div class="st-text--small st-text--muted">Total</div><div id="on_time_total" class="st-dashboard-metric-xl">0</div></div>
                                    </div>
                                </div>
                            </div>

                            <div class="st-chart-col-4">
                                <div class="st-chart-card">
                                    <div class="st-dashboard-chart-header-top">
                                        <div class="st-chart-card__title st-mb-0">Target Achievement</div>
                                    </div>
                                    <div class="st-chart-wrap st-chart-wrap--sm st-mt-2">
                                        <canvas id="chart_target_achievement"></canvas>
                                    </div>
                                    <div class="st-metric-row st-metric-row--mt">
                                        <div class="st-mini-card"><div class="st-text--small st-text--muted">Achieve</div><div id="target_achieve_value" class="st-dashboard-metric-xl">0</div></div>
                                        <div class="st-mini-card"><div class="st-text--small st-text--muted">Not Achieve</div><div id="target_not_achieve_value" class="st-dashboard-metric-xl">0</div></div>
                                        <div class="st-mini-card"><div class="st-text--small st-text--muted">Total</div><div id="target_total_eval" class="st-dashboard-metric-xl">0</div></div>
                                    </div>
                                </div>
                            </div>

                            <div class="st-chart-col-4">
                                <div class="st-chart-card">
                                    <div class="st-dashboard-chart-header">
                                        <div class="st-chart-card__title st-mb-0">Completion Rate</div>
                                    </div>
                                    <div class="st-chart-wrap st-chart-wrap--sm st-dashboard-chart-wrap-gap-sm">
                                        <canvas id="chart_completion_rate"></canvas>
                                    </div>
                                    <div class="st-metric-row st-metric-row--mt">
                                        <div class="st-mini-card"><div class="st-text--small st-text--muted">Rate</div><div id="completion_rate_value" class="st-dashboard-metric-xl">0%</div></div>
                                        <div class="st-mini-card"><div class="st-text--small st-text--muted">Completed</div><div id="completion_completed_value" class="st-dashboard-metric-xl">0</div></div>
                                        <div class="st-mini-card"><div class="st-text--small st-text--muted">Total</div><div id="completion_total_value" class="st-dashboard-metric-xl">0</div></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </div>
        <div class="st-dashboard-slide" data-slide-index="3">

    <section class="st-row st-dashboard-section-full">
        <div class="st-col-12 st-dashboard-col-full">
            <div class="st-card st-dashboard-card-fill">
                <div class="st-card__header st-dashboard-card-header">
                    <div>
                        <h2 class="st-card__title">24h Timeline</h2>

                        <div class="st-text--small st-text--muted">Slot Availability (Real-time Visual)</div>
                    </div>
                    <div class="st-dashboard-card-actions">
                    <form method="GET" class="st-form-row st-timeline-filters st-dashboard-filter-form">
                        <input type="hidden" name="range_start" value="{{ $range_start ?? $today }}">
                        <input type="hidden" name="range_end" value="{{ $range_end ?? $today }}">
                        <input type="hidden" name="activity_date" value="{{ $activity_date ?? $today }}">
                        <input type="hidden" name="activity_warehouse" value="{{ $activity_warehouse ?? 0 }}">
                        <input type="hidden" name="activity_user" value="{{ $activity_user ?? 0 }}">
                        <div class="st-form-field">
                            <label class="st-label">Date</label>
                            <input type="text" name="schedule_date" class="st-input flatpickr-date" value="{{ $schedule_date ?? $today }}" placeholder="YYYY-MM-DD">
                        </div>
                        <div class="st-form-field">
                            <label class="st-label">From (HH:MM)</label>
                            <input type="text" name="time_from" class="st-input" data-st-timepicker="md" value="{{ $time_from ?? '00:00' }}" placeholder="00:00">
                        </div>
                        <div class="st-form-field">
                            <label class="st-label">To (HH:MM)</label>
                            <input type="text" name="time_to" class="st-input" data-st-timepicker="md" value="{{ $time_to ?? '23:59' }}" placeholder="23:59">
                        </div>
                        <div class="st-form-field">
                            <label class="st-label">Gate (Timeline)</label>
                            <select name="timeline_gate" class="st-select" onchange="this.form.submit()">
                                @php $timelineGateFilter = (int) request()->query('timeline_gate', 0); @endphp
                                <option value="0">All</option>
                                @foreach (($gateCards ?? []) as $g)
                                    <option value="{{ (int)($g['gate_id'] ?? 0) }}" {{ $timelineGateFilter === (int)($g['gate_id'] ?? 0) ? 'selected' : '' }}>{{ $g['title'] ?? '-' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="st-form-field st-timeline-filters__actions st-dashboard-range-reset">
                            <a href="{{ route('dashboard', ['range_start' => $range_start ?? $today, 'range_end' => $range_end ?? $today, 'activity_date' => $activity_date ?? $today, 'activity_warehouse' => $activity_warehouse ?? 0, 'activity_user' => $activity_user ?? 0]) }}" class="st-btn st-btn--outline-primary">Reset</a>
                        </div>
                    </form>
                </div>




                @php
                    $timelineDate = $schedule_date ?? $today;
                    $timelineGateFilter2 = (int) request()->query('timeline_gate', 0);
                    $timelineStartHour = 7;
                    $timelineHours = range(7, 23);
                @endphp

                        <div class="st-dashboard-timeline-wrap">
                        <div
                            class="st-timeline st-dashboard-timeline"
                            id="dashboard-timeline"
                            data-date="{{ $timelineDate }}"
                            data-start-hour="{{ (int) $timelineStartHour }}"
                            data-route-view="{{ route('slots.show', ['slotId' => 0], false) }}"
                            data-route-arrival="{{ route('slots.arrival', ['slotId' => 0], false) }}"
                            data-route-start="{{ route('slots.start', ['slotId' => 0], false) }}"
                            data-route-complete="{{ route('slots.complete', ['slotId' => 0], false) }}"
                            data-route-cancel="{{ route('slots.cancel', ['slotId' => 0], false) }}"
                        >
                            <div class="st-timeline__header">
                                <div class="st-timeline__header-left st-dashboard-timeline-header-left">
                                    <div>Gate</div>
                                </div>
                                <div class="st-dashboard-timeline-lane-head">
                                    Lane
                                </div>
                                <div class="st-timeline__header-grid">
                                    @foreach ($timelineHours as $h)
                                        @php
                                            $hInt = (int) $h;
                                        @endphp
                                        <div class="st-timeline__hour"><span>{{ str_pad((string)$hInt, 2, '0', STR_PAD_LEFT) }}</span></div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="st-timeline__body st-dashboard-timeline-body">
                                @foreach (($gateCards ?? []) as $g)
                                    @php
                                        $gateId = (int)($g['gate_id'] ?? 0);
                                        if ($timelineGateFilter2 > 0 && $timelineGateFilter2 !== $gateId) {
                                            continue;
                                        }
                                        $blocks = (array) (($timelineBlocksByGate ?? [])[$gateId] ?? []);
                                        // Filter Warehouse name from title if present
                                        $gateTitle = $g['title'] ?? '-';
                                        $gateTitle = preg_replace('/^Warehouse\s*\d*\s*-\s*/i', '', $gateTitle);
                                        $gateTitle = str_replace('Warehouse ', '', $gateTitle);
                                    @endphp
                                    <div class="st-timeline__row">
                                        <div class="st-timeline__row-left">
                                            <div class="st-dashboard-timeline-gate-title">{{ $gateTitle }}</div>
                                            <div class="st-text--small st-text--muted st-dashboard-timeline-gate-status">{{ $g['status_label'] ?? 'Idle' }}</div>
                                        </div>
                                        <div class="st-dashboard-timeline-lane-labels">
                                            <div class="st-dashboard-timeline-lane-label st-dashboard-timeline-lane-label--planned">Planned</div>
                                            <div class="st-dashboard-timeline-lane-label">Actual</div>
                                        </div>
                                        <div class="st-timeline__row-lanes" data-timeline-gate-id="{{ $gateId }}">
                                            <div class="st-timeline__lane st-timeline__lane--planned">
                                                @foreach ($blocks as $b)
                                                    @if (($b['lane'] ?? '') === 'planned')
                                                        @php
                                                            $status = 'scheduled';
                                                            $cls = 'st-timeline-block--' . $status;
                                                            $label = trim((string)($b['po_number'] ?? ''));
                                                            if ($label === '') {
                                                                $label = 'Slot #' . (int)($b['id'] ?? 0);
                                                            }
                                                        @endphp
                                                        <button
                                                            type="button"
                                                            class="st-timeline-block {{ $cls }}"
                                                            data-left="{{ (int)($b['left'] ?? 0) }}"
                                                            data-width="{{ (int)($b['width'] ?? 1) }}"
                                                            data-slot='@json($b)'
                                                            data-slot-id="{{ (int)($b['id'] ?? 0) }}"
                                                            data-lane="schedule"
                                                            data-status="{{ $status }}"
                                                            data-info-po="{{ $b['po_number'] ?? '' }}"
                                                            data-info-direction="{{ $b['direction'] ?? '' }}"
                                                            data-info-vendor="{{ $b['vendor_name'] ?? '-' }}"
                                                            data-info-vendor-type="{{ $b['vendor_type'] ?? '' }}"
                                                            data-info-priority="{{ $b['priority'] ?? 'Low' }}"
                                                            data-info-performance="{{ $b['performance'] ?? '' }}"
                                                            data-info-planned-start="{{ $b['planned_start'] ?? '' }}"
                                                            data-info-planned-end="{{ $b['planned_end'] ?? '' }}"
                                                            data-info-achieve="{{ $b['achieve_label'] ?? '' }}"
                                                        >
                                                            <span class="st-timeline-block__label">{{ $label }}</span>
                                                        </button>
                                                    @endif
                                                @endforeach
                                            </div>
                                            <div class="st-timeline__lane st-timeline__lane--actual">
                                                @foreach ($blocks as $b)
                                                    @if (($b['lane'] ?? '') === 'actual')
                                                        @php
                                                            $status = (string)($b['status'] ?? 'scheduled');
                                                            $cls = 'st-timeline-block--' . $status;
                                                            $label = trim((string)($b['po_number'] ?? ''));
                                                            if ($label === '') {
                                                                $label = 'Slot #' . (int)($b['id'] ?? 0);
                                                            }
                                                        @endphp
                                                        <button
                                                            type="button"
                                                            class="st-timeline-block {{ $cls }}"
                                                            data-left="{{ (int)($b['left'] ?? 0) }}"
                                                            data-width="{{ (int)($b['width'] ?? 1) }}"
                                                            data-slot='@json($b)'
                                                            data-slot-id="{{ (int)($b['id'] ?? 0) }}"
                                                            data-lane="actual"
                                                            data-status="{{ $status }}"
                                                            data-info-po="{{ $b['po_number'] ?? '' }}"
                                                            data-info-direction="{{ $b['direction'] ?? '' }}"
                                                            data-info-vendor="{{ $b['vendor_name'] ?? '-' }}"
                                                            data-info-vendor-type="{{ $b['vendor_type'] ?? '' }}"
                                                            data-info-status="{{ ucfirst(str_replace('_',' ', $status)) }}"
                                                            data-info-arrival="{{ $b['arrival_time'] ?? '' }}"
                                                            data-info-start="{{ $b['actual_start'] ?? '' }}"
                                                            data-info-end="{{ $b['actual_finish'] ?? '' }}"
                                                            data-info-performance="{{ $b['performance'] ?? '' }}"
                                                            data-info-waiting-minutes="{{ (int)($b['waiting_minutes'] ?? 0) }}"
                                                            data-info-achieve="{{ $b['achieve_label'] ?? '' }}"
                                                        >
                                                            <span class="st-timeline-block__label">{{ $label }}</span>
                                                        </button>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="st-timeline-meta st-dashboard-timeline-meta">

                        <!-- Status Legend -->
                        <div class="st-timeline-legend st-dashboard-timeline-legend" aria-label="Timeline color legend">
                            <span class="st-timeline-legend__item"><span class="st-timeline-legend__dot st-timeline-legend__dot--scheduled"></span> Scheduled</span>
                            <span class="st-timeline-legend__item"><span class="st-timeline-legend__dot st-timeline-legend__dot--waiting"></span> Waiting</span>
                            <span class="st-timeline-legend__item"><span class="st-timeline-legend__dot st-timeline-legend__dot--in_progress"></span> In Progress</span>
                            <span class="st-timeline-legend__item"><span class="st-timeline-legend__dot st-timeline-legend__dot--completed"></span> Completed</span>
                        </div>
                    </div>

            </div>
        </div>
    </section>

        </div>
        <div class="st-dashboard-slide" data-slide-index="4">

    <section class="st-row st-dashboard-section-gap">
        <div class="st-col-12">
            <div class="st-card st-dashboard-card-padded">

                @php
                    $processStatusCounts = [
                        'pending' => 0,
                        'scheduled' => 0,
                        'waiting' => 0,
                        'in_progress' => 0,
                        'completed' => 0,
                        'cancelled' => 0,
                    ];

                    // Query langsung ke database untuk chart status (100% akurat)
                    $dateFilter = $schedule_date ?? date('Y-m-d');

                    // Hitung slots per status langsung dari database
                    $slotStats = \DB::table('slots')
                        ->where(function($q) use ($dateFilter) {
                            $q->whereDate('actual_start', $dateFilter)
                              ->orWhereDate('planned_start', $dateFilter);
                        })
                        ->where(function($q) {
                            $q->whereNull('slot_type')
                              ->orWhere('slot_type', '!=', 'unplanned');
                        })
                        ->whereNotIn('status', ['pending_approval', 'cancelled'])
                        ->selectRaw('
                            SUM(CASE WHEN status = \'scheduled\' THEN 1 ELSE 0 END) as scheduled,
                            SUM(CASE WHEN status = \'waiting\' THEN 1 ELSE 0 END) as waiting,
                            SUM(CASE WHEN status = \'arrived\' THEN 1 ELSE 0 END) as arrived,
                            SUM(CASE WHEN status = \'in_progress\' THEN 1 ELSE 0 END) as in_progress,
                            SUM(CASE WHEN status = \'completed\' THEN 1 ELSE 0 END) as completed,
                            SUM(CASE WHEN status = \'cancelled\' THEN 1 ELSE 0 END) as cancelled
                        ')
                        ->first();

                    $processStatusCounts['scheduled'] = (int)($slotStats->scheduled ?? 0);
                    $processStatusCounts['waiting'] = (int)(($slotStats->waiting ?? 0) + ($slotStats->arrived ?? 0));
                    $processStatusCounts['in_progress'] = (int)($slotStats->in_progress ?? 0);
                    $processStatusCounts['completed'] = (int)($slotStats->completed ?? 0);
                    $processStatusCounts['cancelled'] = (int)($slotStats->cancelled ?? 0);

                    // Debug: Log direct query result
                    \Log::info('Direct database query result', [
                        'date_filter' => $dateFilter,
                        'slot_stats' => (array)$slotStats,
                        'final_counts' => $processStatusCounts
                    ]);

                    // Tambahkan pending bookings dari booking_requests
                    $pendingCount = \App\Models\BookingRequest::where('status', \App\Models\BookingRequest::STATUS_PENDING)
                        ->when(($schedule_from ?? '') && ($schedule_to ?? ''), function($q) use ($schedule_from, $schedule_to) {
                            return $q->whereBetween('planned_start', [$schedule_from, $schedule_to]);
                        }, function($q) use ($schedule_date) {
                            return $q->whereDate('planned_start', $schedule_date ?? date('Y-m-d'));
                        })
                        ->count();

                    $processStatusCounts['pending'] += $pendingCount;
                @endphp

                <div class="st-dashboard-schedule-row">
                    <div class="st-dashboard-schedule-chart-col">
                        <div class="st-chart-card st-dashboard-schedule-chart-card">
                            <div class="st-flex-between-center st-flex-wrap st-gap-2">
                                <div class="st-chart-card__title st-mb-0">Chart Status</div>
                                <select id="status_direction" class="st-select st-w-160">
                                    <option value="all">All Direction</option>
                                    <option value="inbound">Inbound</option>
                                    <option value="outbound">Outbound</option>
                                </select>
                            </div>
                            <div class="st-chart-wrap st-chart-wrap--sm st-dashboard-schedule-chart-wrap">
                                <canvas id="chart_process_status"></canvas>
                            </div>
                            <div class="st-dashboard-schedule-metrics-wrap">
                                <div class="st-metric-row st-dashboard-schedule-metric-row">
                                    <div class="st-mini-card">
                                        <div class="st-text--small st-text--muted">Pending</div>
                                        <div id="status_pending_value" class="st-metric-value-lg">{{ (int) ($processStatusCounts['pending'] ?? 0) }}</div>
                                    </div>
                                    <div class="st-mini-card">
                                        <div class="st-text--small st-text--muted">Scheduled</div>
                                        <div id="status_scheduled_value" class="st-metric-value-lg">{{ (int) ($processStatusCounts['scheduled'] ?? 0) }}</div>
                                    </div>
                                    <div class="st-mini-card">
                                        <div class="st-text--small st-text--muted">Waiting</div>
                                        <div id="status_waiting_value" class="st-metric-value-lg">{{ (int) ($processStatusCounts['waiting'] ?? 0) }}</div>
                                    </div>
                                    <div class="st-mini-card">
                                        <div class="st-text--small st-text--muted">In Progress</div>
                                        <div id="status_in_progress_value" class="st-metric-value-lg">{{ (int) ($processStatusCounts['in_progress'] ?? 0) }}</div>
                                    </div>
                                    <div class="st-mini-card">
                                        <div class="st-text--small st-text--muted">Completed</div>
                                        <div id="status_completed_value" class="st-metric-value-lg">{{ (int) ($processStatusCounts['completed'] ?? 0) }}</div>
                                    </div>
                                    <div class="st-mini-card">
                                        <div class="st-text--small st-text--muted">Cancelled</div>
                                        <div id="status_cancelled_value" class="st-metric-value-lg">{{ (int) ($processStatusCounts['cancelled'] ?? 0) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="st-dashboard-schedule-table-col">
                        <div class="st-flex-between-center st-flex-wrap st-gap-2 st-dashboard-schedule-head">
                            <div>
                                <h3 class="st-card__title">Schedule</h3>
                                <div class="st-card__subtitle">Trucks, ETA, Gates, Status, and Estimated Finish</div>
                            </div>
                            <div class="st-dashboard-schedule-actions">
                                @can('slots.create')
                                <a href="{{ route('slots.create') }}" class="st-btn st-btn--primary st-btn--sm">
                                    <i class="fa-solid fa-plus st-mr-1"></i> Create Slot
                                </a>
                                @endcan
                            </div>
                        </div>

                        <div class="st-table-wrapper st-dashboard-schedule-table-wrap">
                            <table class="st-table">
                        <thead>
                            <tr>
                                <th>PO</th>
                                <th>Vendor</th>
                                <th>Warehouse</th>
                                <th>Gate</th>
                                <th>ETA</th>
                                <th>Status</th>
                                <th>Est. Finish</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse (($schedule ?? []) as $row)
                            @php
                                // Skip anomali data
                                if ((!isset($row['id']) || $row['id'] <= 0) && (!isset($row['is_pending_booking']) || !$row['is_pending_booking'])) {
                                    continue;
                                }

                                $st = (string)($row['status'] ?? 'scheduled');
                                $stLabel = ucwords(str_replace('_',' ', $st));

                                $badgeMap = [
                                    'scheduled' => 'bg-secondary',
                                    'waiting' => 'bg-waiting',
                                    'arrived' => 'bg-info',
                                    'in_progress' => 'bg-in_progress',
                                    'completed' => 'bg-completed',
                                    'cancelled' => 'bg-danger',
                                    'pending_approval' => 'st-badge--pending_approval',
                                    'pending' => 'st-badge--pending_approval', // Use same class for pending bookings
                                ];
                                $badgeClass = $badgeMap[$st] ?? 'bg-secondary';
                                if ($st === 'arrived') {
                                    $stLabel = 'Waiting'; // Map arrive to Waiting label as per other views
                                } elseif ($st === 'pending') {
                                    $stLabel = 'Pending'; // Show as Pending for pending bookings
                                }

                                $performance = (string)($row['performance'] ?? '');
                                $priority = (string)($row['priority'] ?? 'low');
                            @endphp
                            <tr>
                                <td>
                                    @if(isset($row['is_pending_booking']) && $row['is_pending_booking'])
                                        {{ $row['request_number'] ?? ('REQ-' . ($row['id'] ?? 0)) }}
                                    @else
                                        {{ $row['po_number'] ?? ('Slot #' . (int)($row['id'] ?? 0)) }}
                                    @endif
                                </td>
                                <td>{{ $row['vendor_name'] ?? '-' }}</td>
                                <td>{{ $row['warehouse_name'] ?? '-' }}</td>
                                <td>{{ $row['gate_label'] ?? '-' }}</td>
                                <td>{{ $row['eta'] ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $badgeClass }}">{{ $stLabel }}</span>
                                    @if ($performance === 'ontime')
                                        <span class="st-kpi-badge st-kpi-ontime">(Ontime)</span>
                                    @elseif ($performance === 'late')
                                        <span class="st-kpi-badge st-kpi-late">(Late)</span>
                                    @endif
                                </td>
                                <td>{{ $row['est_finish'] ?? '-' }}</td>
                                <td>
                                    <div class="st-action-dropdown">
                                        <button type="button" class="st-btn st-btn--ghost st-action-trigger st-action-trigger--compact">
                                            &#x22ee;
                                        </button>
                                        <div class="st-action-menu">
                                            @if(isset($row['id']) && $row['id'])
                                                <a href="{{ route('slots.show', ['slotId' => $row['id']]) }}" class="st-action-item">View</a>
                                            @endif
                                            @if ($st === 'scheduled')
                                                @if(isset($row['id']) && $row['id'])
                                                    <a href="{{ route('slots.arrival', ['slotId' => $row['id']]) }}" class="st-action-item">Arrival</a>
                                                    @can('slots.cancel')
                                                    <a href="{{ route('slots.cancel', ['slotId' => $row['id']]) }}" class="st-action-item st-action-item--danger" data-confirm="Are you sure you want to cancel this slot?">Cancel</a>
                                                    @endcan
                                                @endif
                                            @elseif (in_array($st, ['arrived', 'waiting'], true))
                                                @if(isset($row['id']) && $row['id'])
                                                    <a href="{{ route('slots.start', ['slotId' => $row['id']]) }}" class="st-action-item">Start</a>
                                                @endif
                                            @elseif ($st === 'in_progress')
                                                @if(isset($row['id']) && $row['id'])
                                                    <a href="{{ route('slots.complete', ['slotId' => $row['id']]) }}" class="st-action-item">Complete</a>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="st-table-empty">No Schedule for Selected Filter</td></tr>
                        @endforelse
                        </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

        </div>
        </div>
        </div>

        <div class="st-timeline-modal" id="timeline-modal" aria-hidden="true">
        <div class="st-timeline-modal__backdrop" data-modal-close></div>
        <div class="st-timeline-modal__panel" role="dialog" aria-modal="true" aria-label="Slot details">
            <div class="st-timeline-modal__header">
                <div>
                    <div id="timeline-modal-title" class="st-timeline-modal__title">Slot</div>
                    <div id="timeline-modal-subtitle" class="st-text--small st-text--muted">-</div>
                </div>
                <button type="button" class="st-btn st-btn--ghost st-btn--sm" data-modal-close>Close</button>
            </div>
            <div class="st-timeline-modal__body">
                <div class="st-mini-card st-timeline-modal__status">
                    <div class="st-text--small st-text--muted">Status</div>
                    <div id="timeline-modal-status" class="st-timeline-modal__status-value">-</div>
                </div>
                <div class="st-timeline-modal__grid">
                    <div>
                        <div class="st-text--small st-text--muted">Vendor</div>
                        <div id="timeline-modal-vendor" class="st-timeline-modal__value">-</div>
                    </div>
                    <div>
                        <div class="st-text--small st-text--muted">Gate</div>
                        <div id="timeline-modal-gate" class="st-timeline-modal__value">-</div>
                    </div>
                    <div>
                        <div class="st-text--small st-text--muted">ETA</div>
                        <div id="timeline-modal-eta" class="st-timeline-modal__value">-</div>
                    </div>
                    <div>
                        <div class="st-text--small st-text--muted">Est. Finish</div>
                        <div id="timeline-modal-finish" class="st-timeline-modal__value">-</div>
                    </div>
                </div>
            </div>
            <div class="st-timeline-modal__footer st-timeline-modal__footer-actions">
                <a href="#" id="timeline-modal-view" class="st-btn st-btn--outline-primary st-btn--sm">View</a>
                <a href="#" id="timeline-modal-arrival" class="st-btn st-btn--primary st-btn--sm st-hidden">Arrival</a>
                <a href="#" id="timeline-modal-start" class="st-btn st-btn--primary st-btn--sm st-hidden">Start</a>
                <a href="#" id="timeline-modal-complete" class="st-btn st-btn--primary st-btn--sm st-hidden">Complete</a>
                <a href="#" id="timeline-modal-cancel" class="st-btn st-btn--sm st-btn--danger st-hidden" data-confirm="Are you sure you want to cancel this slot?">Cancel</a>
            </div>
        </div>
    </div>

    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    function getHolidayMap() {
        if (typeof window.getIndonesiaHolidays === 'function') {
            return window.getIndonesiaHolidays() || {};
        }
        var el = document.getElementById('indonesia_holidays');
        if (!el) return {};
        try {
            return JSON.parse(el.textContent || '{}') || {};
        } catch (e) {
            return {};
        }
    }

    if (typeof window.getIndonesiaHolidays !== 'function') {
        window.getIndonesiaHolidays = function () {
            return getHolidayMap();
        };
    }
    function initAnalyticsRangePicker() {
        return;
        var rangeInput = document.getElementById('analytics_range');
        if (!rangeInput || !window.jQuery) return;
        var holidayData = getHolidayMap();

        // Use daterangepicker.js for true range selection
        if (typeof $.fn.daterangepicker !== 'undefined') {
            var startEl = document.getElementById('range_start');
            var endEl = document.getElementById('range_end');
            var startVal = startEl ? startEl.value : '';
            var endVal = endEl ? endEl.value : '';

            // Set initial value
            var startDate = startVal ? moment(startVal) : moment();
            var endDate = endVal ? moment(endVal) : moment();

            // Initialize daterangepicker
            function applyRangeHolidayTooltips(picker) {
                if (!picker || !picker.container) return;
                try {
                    picker.container.find('td.is-holiday').each(function () {
                        var cell = $(this);
                        var date = cell.data('date') || '';
                        var label = holidayData[date] || '';
                        if (!label) return;
                        cell.attr('title', label);
                    });
                } catch (e) {
                    // ignore
                }
            }

            $(rangeInput).daterangepicker({
                startDate: startDate,
                endDate: endDate,
                autoUpdateInput: true,
                locale: {
                    format: 'YYYY-MM-DD'
                },
                isCustomDate: function (date) {
                    var ds = date ? date.format('YYYY-MM-DD') : '';
                    return holidayData[ds] ? 'is-holiday' : '';
                }
            }, function(start, end) {
                var startStr = start.format('YYYY-MM-DD');
                var endStr = end.format('YYYY-MM-DD');
                if (startEl) startEl.value = startStr;
                if (endEl) endEl.value = endStr;
                rangeInput.value = startStr + ' - ' + endStr;
                if (startEl && startEl.form) startEl.form.submit();
            });

            $(rangeInput).on('show.daterangepicker', function (ev, picker) {
                setTimeout(function () { applyRangeHolidayTooltips(picker); }, 0);
            });
            $(rangeInput).on('showCalendar.daterangepicker', function (ev, picker) {
                setTimeout(function () { applyRangeHolidayTooltips(picker); }, 0);
            });

            // Set initial display value
            if (startVal && endVal) {
                rangeInput.value = startVal + ' - ' + endVal;
            } else if (startVal) {
                rangeInput.value = startVal + ' - ' + startVal;
            }

            return;
        }

        // Fallback to jQuery UI datepicker if daterangepicker is not available
        if (window.jQuery.fn.datepicker) {
            var startEl = document.getElementById('range_start');
            var endEl = document.getElementById('range_end');
            var startVal = startEl ? startEl.value : '';
            var endVal = endEl ? endEl.value : '';

            // Set initial display value
            if (startVal && endVal) {
                rangeInput.value = startVal + ' - ' + endVal;
            } else if (startVal) {
                rangeInput.value = startVal + ' - ' + startVal;
            }

            // Initialize datepicker for range selection
            window.jQuery(rangeInput).datepicker({
                dateFormat: 'yy-mm-dd',
                beforeShowDay: function (date) {
                    var ds = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
                    if (holidayData[ds]) {
                        return [true, 'is-holiday', holidayData[ds]];
                    }
                    return [true, '', ''];
                },
                beforeShow: function(input, inst) {
                    // Show two months for range selection
                    inst.dpDiv.addClass('ui-datepicker-range');
                },
                onSelect: function(dateText) {
                    // For simplicity, set both start and end to the same date
                    if (startEl) startEl.value = dateText;
                    if (endEl) endEl.value = dateText;
                    rangeInput.value = dateText + ' - ' + dateText;
                    if (startEl && startEl.form) startEl.form.submit();
                }
            });
        }
    }

    initAnalyticsRangePicker();

    document.addEventListener('click', function(e) {
        var trigger = e.target.closest('.st-action-trigger');
        if (trigger) {
            e.preventDefault();
            e.stopPropagation();
            var menu = trigger.nextElementSibling;
            if (!menu) return;
            document.querySelectorAll('.st-action-menu.show').forEach(function (m) {
                if (m !== menu) m.classList.remove('show');
            });
            menu.classList.toggle('show');
            return;
        }

        if (!e.target.closest('.st-action-menu')) {
            document.querySelectorAll('.st-action-menu.show').forEach(function (m) {
                m.classList.remove('show');
            });
        }
    });

    function initDashboardSlideshow() {
        var root = document.querySelector('.st-dashboard-slideshow');
        if (!root) return null;
        var track = root.querySelector('.st-dashboard-slideshow__track');
        var viewport = root.querySelector('.st-dashboard-slideshow__viewport');
        if (!track) return null;
        var slides = Array.prototype.slice.call(root.querySelectorAll('.st-dashboard-slide'));
        if (!slides.length) return null;
        var dots = Array.prototype.slice.call(root.querySelectorAll('[data-slide-dot]'));

        var autoplayMs = parseInt(root.getAttribute('data-autoplay-ms') || '0', 10);
        if (isNaN(autoplayMs) || autoplayMs < 0) autoplayMs = 0;

        var activeIndex = parseInt(root.getAttribute('data-active-index') || '0', 10);
        if (isNaN(activeIndex) || activeIndex < 0) activeIndex = 0;
        if (activeIndex > slides.length - 1) activeIndex = slides.length - 1;

        var timer = null;
        var isPaused = false;

        var isWrapping = false;

        var fitTimer = null;
        var isFitting = false;

        var resizeObs = null;
        function observeActiveSlide() {
            try {
                if (resizeObs) resizeObs.disconnect();
            } catch (e) {}

            if (!window.ResizeObserver) return;
            var s = slides[activeIndex];
            if (!s) return;

            try {
                resizeObs = new ResizeObserver(function () {
                    if (isFitting) return;
                    scheduleFit();
                });
                resizeObs.observe(s);
            } catch (e) {
                resizeObs = null;
            }
        }
        function fitActiveSlide() {
            if (!viewport) return;
            try {
                viewport.style.transform = 'none';
                viewport.style.width = '100%';
                viewport.style.height = '100%';
            } catch (e) {}

            var activeSlide = slides[activeIndex];
            if (!activeSlide) return;

            var prevHeight = activeSlide.style.height;
            var prevOverflow = activeSlide.style.overflow;
            try {
                // Temporarily measure natural content size (without clipping constraints)
                activeSlide.style.height = 'auto';
                activeSlide.style.overflow = 'visible';
            } catch (e) {}

            var availableH = viewport.clientHeight || 0;
            var availableW = viewport.clientWidth || 0;
            var contentH = activeSlide.scrollHeight || 0;
            var contentW = activeSlide.scrollWidth || 0;

            try {
                activeSlide.style.height = prevHeight;
                activeSlide.style.overflow = prevOverflow;
            } catch (e) {}

            if (!availableH || !availableW || !contentH || !contentW) return;

            // If it already fits, keep the UI at normal size.
            if (contentH <= availableH && contentW <= availableW) {
                try {
                    viewport.style.transform = 'none';
                    viewport.style.width = '100%';
                    viewport.style.height = '100%';
                } catch (e) {}
                return;
            }

            var scaleH = availableH / contentH;
            var scaleW = availableW / contentW;
            var scale = Math.min(scaleH, scaleW);
            if (!isFinite(scale) || scale <= 0) scale = 1;
            // Clamp: never scale above 1 (prevents clipping). Allow downscales
            // so everything fits without scrollbars.
            scale = Math.max(0.10, Math.min(1, scale));

            viewport.style.transformOrigin = 'top left';
            viewport.style.transform = 'scale(' + String(scale) + ')';
            viewport.style.width = (availableW / scale) + 'px';
            viewport.style.height = (availableH / scale) + 'px';
        }

        function scheduleFit() {
            if (fitTimer) clearTimeout(fitTimer);
            fitTimer = setTimeout(function () {
                var raf = window.requestAnimationFrame || function (cb) { return setTimeout(cb, 16); };
                raf(function () {
                    isFitting = true;
                    fitActiveSlide();
                    setTimeout(function () {
                        fitActiveSlide();
                    }, 220);
                    setTimeout(function () {
                        fitActiveSlide();
                    }, 700);
                    setTimeout(function () {
                        fitActiveSlide();
                    }, 1500);
                    setTimeout(function () {
                        isFitting = false;
                    }, 1600);
                });
            }, 0);
        }

        function apply() {
            track.style.transform = 'translateX(' + String(activeIndex * -100) + '%)';
            slides.forEach(function (s, i) {
                s.classList.toggle('is-active', i === activeIndex);
            });
            dots.forEach(function (d) {
                var idx = parseInt(d.getAttribute('data-slide-dot') || '0', 10);
                var isActive = idx === activeIndex;
                d.classList.toggle('is-active', isActive);
                d.setAttribute('aria-current', isActive ? 'true' : 'false');
            });

            observeActiveSlide();
            scheduleFit();
        }

        function wrapTo(nextIndex, opts) {
            if (isWrapping) return;
            var n = parseInt(nextIndex, 10);
            if (isNaN(n)) return;
            if (n < 0) n = 0;
            if (n > slides.length - 1) n = slides.length - 1;

            isWrapping = true;
            root.classList.add('is-wrapping');
            clearTimer();

            setTimeout(function () {
                try {
                    track.style.transition = 'none';
                } catch (e) {}

                activeIndex = n;
                root.setAttribute('data-active-index', String(activeIndex));
                apply();

                try {
                    track.offsetHeight;
                    track.style.transition = '';
                } catch (e) {}

                root.classList.remove('is-wrapping');
                isWrapping = false;

                if (opts && opts.userAction) {
                    scheduleNext();
                }
            }, 180);
        }

        function clearTimer() {
            if (timer) {
                clearTimeout(timer);
                timer = null;
            }
        }

        function scheduleNext() {
            clearTimer();
            if (!autoplayMs) return;
            if (isPaused) return;
            timer = setTimeout(function () {
                if (activeIndex === slides.length - 1) {
                    wrapTo(0, { userAction: false });
                } else {
                    setIndex(activeIndex + 1, { userAction: false });
                }
                scheduleNext();
            }, autoplayMs);
        }

        function pauseAutoplay() {
            isPaused = true;
            clearTimer();
        }

        function resumeAutoplay() {
            isPaused = false;
            scheduleNext();
        }

        function setIndex(nextIndex, opts) {
            var n = parseInt(nextIndex, 10);
            if (isNaN(n)) return;
            if (n < 0) n = 0;
            if (n > slides.length - 1) n = slides.length - 1;
            if (n === activeIndex) return;

            activeIndex = n;
            root.setAttribute('data-active-index', String(activeIndex));
            apply();
            if (opts && opts.userAction) {
                scheduleNext();
            }
        }

        var btnPrev = root.querySelector('[data-slide-prev]');
        var btnNext = root.querySelector('[data-slide-next]');
        if (btnPrev) {
            btnPrev.addEventListener('click', function () {
                if (activeIndex === 0) {
                    wrapTo(slides.length - 1, { userAction: true });
                } else {
                    setIndex(activeIndex - 1, { userAction: true });
                }
            });
        }
        if (btnNext) {
            btnNext.addEventListener('click', function () {
                if (activeIndex === slides.length - 1) {
                    wrapTo(0, { userAction: true });
                } else {
                    setIndex(activeIndex + 1, { userAction: true });
                }
            });
        }
        dots.forEach(function (d) {
            d.addEventListener('click', function () {
                setIndex(d.getAttribute('data-slide-dot') || '0', { userAction: true });
            });
        });

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                clearTimer();
            } else {
                scheduleNext();
            }
        });

        var nav = root.querySelector('.st-dashboard-slideshow__nav');
        if (viewport) {
            viewport.addEventListener('mouseenter', pauseAutoplay);
            viewport.addEventListener('mouseleave', resumeAutoplay);
        }
        if (nav) {
            nav.addEventListener('mouseenter', pauseAutoplay);
            nav.addEventListener('mouseleave', resumeAutoplay);
        }

        window.addEventListener('resize', function () {
            scheduleFit();
        });

        window.addEventListener('load', function () {
            scheduleFit();
        });

        if (window.ResizeObserver) {
            var resizeObserver = new ResizeObserver(function () {
                if (isFitting) return;
                scheduleFit();
            });
            resizeObserver.observe(viewport);
        }

        if (window.visualViewport) {
            try {
                window.visualViewport.addEventListener('resize', scheduleFit);
                window.visualViewport.addEventListener('scroll', scheduleFit);
            } catch (e) {}
        }

        apply();
        observeActiveSlide();
        scheduleNext();

        return {
            setIndex: setIndex,
            getIndex: function () { return activeIndex; },
            fit: scheduleFit
        };
    }

    var dashboardSlideshow = initDashboardSlideshow();

    // Scroll to timeline section if URL contains filter parameters
    if (window.location.search.includes('schedule_date') ||
        window.location.search.includes('timeline_gate') ||
        window.location.search.includes('schedule_from') ||
        window.location.search.includes('schedule_to')) {
        setTimeout(function() {
            if (dashboardSlideshow && typeof dashboardSlideshow.setIndex === 'function') {
                dashboardSlideshow.setIndex(3, { userAction: false });
            }
        }, 100);
    }

    // Handle form submission to add scroll behavior
    var filterForm = document.getElementById('schedule-filter-form');
    if (filterForm) {
        filterForm.addEventListener('submit', function() {
            // Store scroll position in sessionStorage
            sessionStorage.setItem('scrollToTimeline', 'true');
        });
    }

    // Check if we need to scroll after page load
    if (sessionStorage.getItem('scrollToTimeline') === 'true') {
        sessionStorage.removeItem('scrollToTimeline');
        setTimeout(function() {
            if (dashboardSlideshow && typeof dashboardSlideshow.setIndex === 'function') {
                dashboardSlideshow.setIndex(3, { userAction: false });
            }
        }, 300);
    }

    try {
        // Use global holiday helper
        var globalHolidayData = typeof window.getIndonesiaHolidays === 'function' ? window.getIndonesiaHolidays() : {};

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
                const title = globalHolidayData[ds] || '';
                if (title) {
                    cell.attr('data-st-tooltip', title);
                    cell.find('a, span').attr('data-st-tooltip', title);
                }
                cell.removeAttr('title');
                cell.find('a, span').removeAttr('title');
            });
        }

        function bindDatepickerHover(inst) {
            if (!inst || !inst.dpDiv) return;
            const dp = window.jQuery(inst.dpDiv);
            let hideTimer = null;
            let tooltip = document.getElementById('st-datepicker-tooltip');
            if (!tooltip) {
                tooltip = document.createElement('div');
                tooltip.id = 'st-datepicker-tooltip';
                tooltip.className = 'st-datepicker-tooltip';
                document.body.appendChild(tooltip);
            }

            dp.off('mouseenter.st-tooltip mousemove.st-tooltip mouseleave.st-tooltip', 'td.is-holiday');
            dp.on('mouseenter.st-tooltip', 'td.is-holiday', function(event) {
                const text = window.jQuery(this).attr('data-st-tooltip') || '';
                if (!text) return;
                if (hideTimer) {
                    clearTimeout(hideTimer);
                    hideTimer = null;
                }
                tooltip.textContent = text;
                tooltip.classList.add('st-datepicker-tooltip--visible');
                tooltip.style.left = `${event.clientX + 12}px`;
                tooltip.style.top = `${event.clientY + 12}px`;
            });
            dp.on('mousemove.st-tooltip', 'td.is-holiday', function(event) {
                tooltip.style.left = `${event.clientX + 12}px`;
                tooltip.style.top = `${event.clientY + 12}px`;
            });
            dp.on('mouseleave.st-tooltip', 'td.is-holiday', function() {
                hideTimer = setTimeout(function() {
                    tooltip.classList.remove('st-datepicker-tooltip--visible');
                }, 300);
            });
        }

        function initDatepicker(el, beforeShowDay, onSelect) {
            return;
            if (!el) return;
            if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.datepicker !== 'function') return;
            if (el.getAttribute('data-st-datepicker') === '1') return;
            el.setAttribute('data-st-datepicker', '1');
            try { el.type = 'text'; } catch (e) {}

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

        // Initialize generic date inputs (exclude analytics_range)
        document.querySelectorAll('.flatpickr-date, input[type="date"]').forEach(function (input) {
            if (input.id === 'analytics_range') return; // Skip analytics_range
            initDatepicker(input, function(date) {
                const ds = toIsoDate(date);
                if (globalHolidayData[ds]) {
                    return [true, 'is-holiday', globalHolidayData[ds]];
                }
                return [true, '', ''];
            });
        });

            // Auto-submit for other form elements
            const analyticsForm = document.querySelector('form[action*="dashboard"]');
            if (analyticsForm) {
                // Auto-submit on select change
                analyticsForm.addEventListener('change', function(e) {
                    if (e.target.tagName === 'SELECT') {
                        analyticsForm.submit();
                    }
                });

                // Auto-submit on input with debounce for text inputs
                const textInputs = analyticsForm.querySelectorAll('input[type="text"]:not(#analytics_range)');
                textInputs.forEach(function(input) {
                    let timeout;
                    input.addEventListener('input', function() {
                        clearTimeout(timeout);
                        timeout = setTimeout(function() {
                            analyticsForm.submit();
                        }, 500); // 500ms debounce
                    });

                    // Submit on Enter key
                    input.addEventListener('keypress', function(e) {
                        if (e.key === 'Enter') {
                            clearTimeout(timeout);
                            analyticsForm.submit();
                        }
                    });
                });
            }
        } catch (e) {
            // ignore
        }

    // Auto-submit for all forms on dashboard
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-submit for all forms with selects and inputs
        const allForms = document.querySelectorAll('form');
        allForms.forEach(function(form) {
            // Skip if form doesn't have action or is a modal form
            if (!form.action || form.id.includes('modal') || form.id.includes('approve') || form.id.includes('reject')) {
                return;
            }

            // Auto-submit on select change
            form.addEventListener('change', function(e) {
                if (e.target.tagName === 'SELECT') {
                    form.submit();
                }
            });

            // Auto-submit on input with debounce for text inputs (excluding date range inputs)
            const textInputs = form.querySelectorAll('input[type="text"]:not([id*="range"]):not([id*="date"])');
            textInputs.forEach(function(input) {
                let timeout;
                input.addEventListener('input', function() {
                    clearTimeout(timeout);
                    timeout = setTimeout(function() {
                        form.submit();
                    }, 500); // 500ms debounce
                });

                // Submit on Enter key
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        clearTimeout(timeout);
                        form.submit();
                    }
                });
            });
        });
    });

    const timeline = document.getElementById('dashboard-timeline');
    const infoContent = document.getElementById('timeline-info-content');
    const defaultInfoHtml = infoContent ? infoContent.innerHTML : '';
    let currentBlock = null;
    let lockedBlock = null;
    let hoverCard = null;
    let hoverHideTimer = null;

    function layoutTimeline() {
        if (!timeline) return;

        const headerHours = timeline.querySelectorAll('.st-timeline__header-grid .st-timeline__hour');
        const hoursCount = headerHours ? headerHours.length : 0;
        if (!hoursCount) return;

        const startHour = parseInt(timeline.getAttribute('data-start-hour') || '0', 10) || 0;
        const startOffsetMin = startHour * 60;
        const rangeMin = hoursCount * 60;

        const laneEl = timeline.querySelector('.st-timeline__lane');
        const laneWidth = laneEl ? (laneEl.clientWidth || 0) : 0;
        if (!laneWidth) return;

        const hourWidth = laneWidth / hoursCount;
        timeline.style.setProperty('--st-hour-width', hourWidth + 'px');
        const pxPerMinute = hourWidth / 60;

        const blocks = timeline.querySelectorAll('.st-timeline__lane .st-timeline-block');
        blocks.forEach(function (block) {
            if (!block) return;

            const leftMinAbs = parseInt(block.getAttribute('data-left') || '0', 10) || 0;
            const widthMinAbs = parseInt(block.getAttribute('data-width') || '1', 10) || 1;

            const startMinAbs = Math.max(0, leftMinAbs);
            const endMinAbs = Math.max(startMinAbs + 1, startMinAbs + Math.max(1, widthMinAbs));

            const relStart = clamp(startMinAbs - startOffsetMin, 0, rangeMin);
            const relEnd = clamp(endMinAbs - startOffsetMin, 0, rangeMin);

            if (relEnd <= 0 || relStart >= rangeMin || relEnd <= relStart) {
                block.style.display = 'none';
                return;
            }

            block.style.display = '';
            block.style.left = (relStart * pxPerMinute) + 'px';
            block.style.width = Math.max(1, (relEnd - relStart) * pxPerMinute) + 'px';
        });
    }

    let timelineLayoutTimer = null;
    function scheduleLayoutTimeline() {
        if (timelineLayoutTimer) clearTimeout(timelineLayoutTimer);
        timelineLayoutTimer = setTimeout(function () {
            layoutTimeline();
        }, 50);
    }

    if (timeline) {
        scheduleLayoutTimeline();
        window.addEventListener('resize', scheduleLayoutTimeline);
        if (window.ResizeObserver) {
            var resizeObserver = new ResizeObserver(function () {
                scheduleLayoutTimeline();
            });
            resizeObserver.observe(timeline);
        }
        if (window.visualViewport) {
            try {
                window.visualViewport.addEventListener('resize', scheduleLayoutTimeline);
                window.visualViewport.addEventListener('scroll', scheduleLayoutTimeline);
            } catch (e) {}
        }
    }

    function ensureHoverCard() {
        if (hoverCard) return hoverCard;
        hoverCard = document.createElement('div');
        hoverCard.id = 'timeline-hovercard';
        hoverCard.className = 'st-timeline-hovercard';
        hoverCard.style.display = 'none';
        document.body.appendChild(hoverCard);
        return hoverCard;
    }

    function clamp(v, min, max) {
        return Math.max(min, Math.min(max, v));
    }

    function escHtml(v) {
        return String(v === null || v === undefined ? '' : v).replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function fmtTime(v) {
        var s = String(v || '');
        if (!s) return '-';
        // Accept "YYYY-mm-dd HH:ii:ss" or ISO
        var m = s.match(/\b(\d{2}:\d{2})\b/);
        return m ? m[1] : s;
    }

    function fmtVendorType(v) {
        var t = String(v || '').toLowerCase();
        if (t === 'supplier') return 'Supplier';
        if (t === 'customer') return 'Customer';
        return '';
    }

    function fmtDirection(v) {
        var d = String(v || '').toLowerCase();
        if (d === 'inbound') return 'Inbound';
        if (d === 'outbound') return 'Outbound';
        return d ? (d.charAt(0).toUpperCase() + d.slice(1)) : '-';
    }

    function fmtPerformance(v) {
        var p = String(v || '').toLowerCase();
        if (p === 'ontime') return 'On Time';
        if (p === 'late') return 'Late';
        return '';
    }

    function setLockedBlock(block) {
        if (lockedBlock && lockedBlock !== block) {
            lockedBlock.classList.remove('is-locked');
        }
        lockedBlock = block || null;
        if (lockedBlock) {
            lockedBlock.classList.add('is-locked');
        }
        if (hoverCard) {
            hoverCard.classList.toggle('is-locked', !!lockedBlock);
        }
    }

    function showHoverCard(block) {
        if (!block) return;
        const card = ensureHoverCard();
        if (hoverHideTimer) {
            clearTimeout(hoverHideTimer);
            hoverHideTimer = null;
        }

        if (lockedBlock && block !== lockedBlock) {
            return;
        }

        const lane = block.dataset.lane || '';
        const po = block.dataset.infoPo || '-';
        const direction = fmtDirection(block.dataset.infoDirection || '');
        const vendor = block.dataset.infoVendor || '-';
        const vendorType = fmtVendorType(block.dataset.infoVendorType || '');
        const vendorLabel = vendorType ? (vendor + ' (' + vendorType + ')') : vendor;
        const priority = block.dataset.infoPriority || '-';
        const performance = fmtPerformance(block.dataset.infoPerformance || '');
        const perfHtml = performance ? ('<span class="st-timeline-hovercard__badge st-timeline-hovercard__badge--' + (performance === 'Late' ? 'late' : 'ontime') + '">' + escHtml(performance) + '</span>') : '';

        const headerHtml = ''
            + '<div class="st-timeline-hovercard__header">'
            + '  <div class="st-timeline-hovercard__meta">' + escHtml(po) + '</div>'
            + '  <div class="st-timeline-hovercard__meta-right">' + perfHtml + '</div>'
            + '</div>';

        const titleHtml = '<div class="st-timeline-hovercard__title" title="' + escHtml(vendorLabel) + '">' + escHtml(vendorLabel) + '</div>';

        if (lane === 'schedule') {
            const plannedStart = fmtTime(block.dataset.infoPlannedStart || '');
            const plannedEnd = fmtTime(block.dataset.infoPlannedEnd || '');
            const achieve = block.dataset.infoAchieve || '';
            const showAchieve = (String(block.dataset.status || '') === 'completed') && !!achieve;
            card.innerHTML = ''
                + headerHtml
                + titleHtml
                + '<div class="st-timeline-hovercard__row"><span>Direction</span><b>' + escHtml(direction) + '</b></div>'
                + '<div class="st-timeline-hovercard__row"><span>Planned</span><b>' + escHtml(plannedStart) + '</b></div>'
                + '<div class="st-timeline-hovercard__row"><span>Target</span><b>' + escHtml(plannedEnd) + '</b></div>'
                + '<div class="st-timeline-hovercard__row"><span>Priority</span><b>' + escHtml(priority) + '</b></div>';

            if (showAchieve) {
                card.innerHTML += '<div class="st-timeline-hovercard__row"><span>Achievement</span><b>' + escHtml(achieve) + '</b></div>';
            }
        } else {
            const status = block.dataset.infoStatus || '-';
            const arrival = fmtTime(block.dataset.infoArrival || '');
            const start = fmtTime(block.dataset.infoStart || '');
            const end = fmtTime(block.dataset.infoEnd || '');
            const waitingMinutes = parseInt(block.dataset.infoWaitingMinutes || '0', 10) || 0;
            const achieve = block.dataset.infoAchieve || '';
            const showWaiting = (String(block.dataset.status || '') === 'completed') && waitingMinutes > 0;
            const showAchieve = (String(block.dataset.status || '') === 'completed') && !!achieve;

            card.innerHTML = ''
                + headerHtml
                + titleHtml
                + '<div class="st-timeline-hovercard__row"><span>Direction</span><b>' + escHtml(direction) + '</b></div>'
                + '<div class="st-timeline-hovercard__row"><span>Status</span><b>' + escHtml(status) + '</b></div>'
                + (showWaiting ? ('<div class="st-timeline-hovercard__row"><span>Waiting</span><b>' + escHtml(waitingMinutes + ' min') + '</b></div>') : '')
                + '<div class="st-timeline-hovercard__row"><span>Arrival</span><b>' + escHtml(arrival) + '</b></div>'
                + '<div class="st-timeline-hovercard__row"><span>Start</span><b>' + escHtml(start) + '</b></div>'
                + '<div class="st-timeline-hovercard__row"><span>End</span><b>' + escHtml(end) + '</b></div>';

            if (showAchieve) {
                card.innerHTML += '<div class="st-timeline-hovercard__row"><span>Achievement</span><b>' + escHtml(achieve) + '</b></div>';
            }
        }

        const rect = block.getBoundingClientRect();
        const margin = 8;

        card.style.display = 'block';
        card.style.left = '0px';
        card.style.top = '0px';

        const cardRect = card.getBoundingClientRect();
        const vw = window.innerWidth || document.documentElement.clientWidth || 0;
        const vh = window.innerHeight || document.documentElement.clientHeight || 0;

        let left = rect.left;
        let top = rect.bottom + margin;

        if (left + cardRect.width > vw - margin) {
            left = vw - margin - cardRect.width;
        }
        left = clamp(left, margin, Math.max(margin, vw - margin - cardRect.width));

        if (top + cardRect.height > vh - margin) {
            top = rect.top - margin - cardRect.height;
        }
        top = clamp(top, margin, Math.max(margin, vh - margin - cardRect.height));

        card.style.left = Math.round(left) + 'px';
        card.style.top = Math.round(top) + 'px';
    }

    function hideHoverCardSoon() {
        if (!hoverCard) return;
        if (lockedBlock) return;
        if (hoverHideTimer) clearTimeout(hoverHideTimer);
        hoverHideTimer = setTimeout(function () {
            if (hoverCard) hoverCard.style.display = 'none';
        }, 60);
    }

    if (timeline) {
        timeline.addEventListener('mouseover', function (e) {
            const block = e.target.closest('.st-timeline-block');
            if (block) {
                currentBlock = block;
                showHoverCard(block);

                if (infoContent) {
                    const po = block.dataset.infoPo || '-';
                    const lane = block.dataset.lane || '';
                    const direction = fmtDirection(block.dataset.infoDirection || '');
                    const vendor = block.dataset.infoVendor || '-';
                    const vendorType = fmtVendorType(block.dataset.infoVendorType || '');
                    const vendorLabel = vendorType ? (vendor + ' (' + vendorType + ')') : vendor;
                    const priority = block.dataset.infoPriority || '-';
                    const status = block.dataset.infoStatus || '-';
                    const arrival = fmtTime(block.dataset.infoArrival || '');
                    const start = fmtTime(block.dataset.infoStart || '');
                    const end = fmtTime(block.dataset.infoEnd || '');
                    const performance = fmtPerformance(block.dataset.infoPerformance || '');

                    let newContent = '';
                    if (lane === 'schedule') {
                        newContent = `
                            <dl class="timeline-tooltip-grid">
                                <dt>PO:</dt> <dd>${escHtml(po)}</dd>
                                <dt>PT:</dt> <dd>${escHtml(vendorLabel)}</dd>
                                <dt>Direction:</dt> <dd>${escHtml(direction)}</dd>
                                <dt>Priority:</dt> <dd>${escHtml(priority)}</dd>
                                ${performance ? `<dt>KPI:</dt> <dd>${escHtml(performance)}</dd>` : ''}
                            </dl>
                        `;
                    } else {
                        newContent = `
                            <dl class="timeline-tooltip-grid">
                                <dt>PO:</dt> <dd>${escHtml(po)}</dd>
                                <dt>PT:</dt> <dd>${escHtml(vendorLabel)}</dd>
                                <dt>Direction:</dt> <dd>${escHtml(direction)}</dd>
                                <dt>Status:</dt> <dd>${escHtml(status)}</dd>
                                <dt>Arrival:</dt> <dd>${escHtml(arrival)}</dd>
                                <dt>Start:</dt> <dd>${escHtml(start)}</dd>
                                <dt>End:</dt> <dd>${escHtml(end)}</dd>
                                ${performance ? `<dt>KPI:</dt> <dd>${escHtml(performance)}</dd>` : ''}
                            </dl>
                        `;
                    }
                    infoContent.innerHTML = newContent;
                }
            }
        });

        timeline.addEventListener('click', function (e) {
            const block = e.target.closest('.st-timeline-block');
            if (!block) return;
            e.preventDefault();
            e.stopPropagation();
            if (lockedBlock === block) {
                setLockedBlock(null);
                if (hoverCard) hoverCard.style.display = 'none';
                return;
            }
            setLockedBlock(block);
            showHoverCard(block);
        });

        timeline.addEventListener('mouseout', function (e) {
            const leftBlock = e.target && e.target.closest ? e.target.closest('.st-timeline-block') : null;
            const enteredBlock = e.relatedTarget && e.relatedTarget.closest ? e.relatedTarget.closest('.st-timeline-block') : null;
            if (leftBlock && !enteredBlock) {
                hideHoverCardSoon();
                currentBlock = null;
            }
            if (!timeline.contains(e.relatedTarget)) {
                if (infoContent) infoContent.innerHTML = defaultInfoHtml;
                currentBlock = null;
                if (hoverCard && !lockedBlock) hoverCard.style.display = 'none';
            }
        });
    }

    document.addEventListener('click', function (e) {
        if (!lockedBlock) return;
        const inCard = hoverCard && hoverCard.contains(e.target);
        const inBlock = e.target && e.target.closest ? e.target.closest('.st-timeline-block') : null;
        if (!inCard && !inBlock) {
            setLockedBlock(null);
            if (hoverCard) hoverCard.style.display = 'none';
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && lockedBlock) {
            setLockedBlock(null);
            if (hoverCard) hoverCard.style.display = 'none';
        }
    });
});
</script>
<script type="application/json" id="dashboard_trend_days">{!! json_encode($trendDays ?? []) !!}</script>
<script type="application/json" id="dashboard_trend_counts">{!! json_encode($trendCounts ?? []) !!}</script>
<script type="application/json" id="dashboard_trend_inbound">{!! json_encode($trendInbound ?? []) !!}</script>
<script type="application/json" id="dashboard_trend_outbound">{!! json_encode($trendOutbound ?? []) !!}</script>
<script type="application/json" id="dashboard_on_time_dir">{!! json_encode($onTimeDir ?? []) !!}</script>
<script type="application/json" id="dashboard_target_dir">{!! json_encode($targetDir ?? []) !!}</script>
<script type="application/json" id="dashboard_on_time_wh_data">{!! json_encode($onTimeWarehouseData ?? []) !!}</script>
<script type="application/json" id="dashboard_target_wh_data">{!! json_encode($targetWarehouseData ?? []) !!}</script>
<script type="application/json" id="dashboard_on_time_gate_data">{!! json_encode($onTimeGateData ?? []) !!}</script>
<script type="application/json" id="dashboard_target_gate_data">{!! json_encode($targetGateData ?? []) !!}</script>
<script type="application/json" id="dashboard_completion_gate_data">{!! json_encode($completionGateData ?? []) !!}</script>
<script type="application/json" id="dashboard_target_segment_labels">{!! json_encode($targetSegmentLabels ?? []) !!}</script>
<script type="application/json" id="dashboard_target_segment_achieve">{!! json_encode($targetSegmentAchieve ?? []) !!}</script>
<script type="application/json" id="dashboard_target_segment_not_achieve">{!! json_encode($targetSegmentNotAchieve ?? []) !!}</script>
<script type="application/json" id="dashboard_bottleneck_labels">{!! json_encode($bottleneckLabels ?? []) !!}</script>
<script type="application/json" id="dashboard_bottleneck_values">{!! json_encode($bottleneckValues ?? []) !!}</script>
<script type="application/json" id="dashboard_bottleneck_directions">{!! json_encode($bottleneckDirections ?? []) !!}</script>
<script type="application/json" id="dashboard_bottleneck_rows">{!! json_encode($bottleneckRows ?? []) !!}</script>
<script type="application/json" id="dashboard_completion_data">{!! json_encode($completionData ?? []) !!}</script>
<script type="application/json" id="dashboard_process_status_counts">{!! json_encode($processStatusCounts ?? []) !!}</script>
<script type="application/json" id="dashboard_numbers">{!! json_encode([
    'inbound' => (int) ($inboundRange ?? 0),
    'outbound' => (int) ($outboundRange ?? 0),
    'on_time' => (int) ($onTimeRange ?? 0),
    'late' => (int) ($lateRange ?? 0),
    'achieve' => (int) ($achieveRange ?? 0),
    'not_achieve' => (int) ($notAchieveRange ?? 0),
    'completion_completed' => (int) ($completionCompletedSlots ?? 0),
    'completion_total' => (int) ($completionTotalSlots ?? 0),
]) !!}</script>
<script type="application/json" id="dashboard_direction_by_gate">{!! json_encode($directionByGate ?? []) !!}</script>
<script type="application/json" id="indonesia_holidays">{!! json_encode($holidays ?? []) !!}</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            var msg = el.getAttribute('data-confirm') || 'Are you sure?';
            if (!confirm(msg)) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
            return true;
        });
    });

    var tabButtons = document.querySelectorAll('#analytics-tabs-card .st-tab-btn');
    var tabPanels = document.querySelectorAll('#analytics-tabs-card .st-tab-panel');

    if (tabButtons && tabButtons.length && tabPanels && tabPanels.length) {
        function setTab(tab) {
            tabButtons.forEach(function (b) {
                var isActive = b.getAttribute('data-tab') === tab;
                if (isActive) {
                    b.style.background = 'var(--primary)';
                    b.style.color = '#ffffff';
                    b.style.borderColor = 'var(--primary)';
                } else {
                    b.style.background = 'transparent';
                    b.style.color = 'var(--primary)';
                    b.style.borderColor = 'var(--primary)';
                }
                b.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
            tabPanels.forEach(function (p) {
                var isActive = p.getAttribute('data-tab-panel') === tab;
                p.style.display = isActive ? 'block' : 'none';
            });

            // Resize charts when switching tabs
            try {
                if (typeof trendChart !== 'undefined' && trendChart && trendChart.resize) trendChart.resize();
                if (typeof directionChart !== 'undefined' && directionChart && directionChart.resize) directionChart.resize();
                if (typeof onTimeChart !== 'undefined' && onTimeChart && onTimeChart.resize) onTimeChart.resize();
                if (typeof targetChart !== 'undefined' && targetChart && targetChart.resize) targetChart.resize();
                if (typeof completionChart !== 'undefined' && completionChart && completionChart.resize) completionChart.resize();
                if (typeof bottleneckChart !== 'undefined' && bottleneckChart && bottleneckChart.resize) bottleneckChart.resize();

                // Force a redraw so datalabels appear immediately (some browsers only paint after interaction)
                if (typeof trendChart !== 'undefined' && trendChart && trendChart.update) trendChart.update();
                if (typeof directionChart !== 'undefined' && directionChart && directionChart.update) directionChart.update();
                if (typeof onTimeChart !== 'undefined' && onTimeChart && onTimeChart.update) onTimeChart.update();
                if (typeof targetChart !== 'undefined' && targetChart && targetChart.update) targetChart.update();
                if (typeof completionChart !== 'undefined' && completionChart && completionChart.update) completionChart.update();
                if (typeof bottleneckChart !== 'undefined' && bottleneckChart && bottleneckChart.update) bottleneckChart.update();
            } catch (e) {
                // ignore
            }
        }

        tabButtons.forEach(function (b) {
            b.addEventListener('click', function () {
                setTab(b.getAttribute('data-tab'));
            });
        });

        setTab('overview');
    }

    function readJsonFromEl(id) {
        var el = document.getElementById(id);
        if (!el) return null;
        try {
            return JSON.parse(el.textContent || 'null');
        } catch (e) {
            return null;
        }
    }

    function ensureChartOverlay(canvasEl) {
        if (!canvasEl || !canvasEl.parentElement) return null;
        var wrap = canvasEl.parentElement;
        try {
            var pos = (window.getComputedStyle ? window.getComputedStyle(wrap).position : wrap.style.position);
            if (!pos || pos === 'static') {
                wrap.style.position = 'relative';
            }
        } catch (e) {
            // ignore
        }
        var overlay = wrap.querySelector('.st-chart-empty');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'st-chart-empty';
            overlay.style.position = 'absolute';
            overlay.style.inset = '0';
            overlay.style.display = 'none';
            overlay.style.alignItems = 'center';
            overlay.style.justifyContent = 'center';
            overlay.style.textAlign = 'center';
            overlay.style.padding = '10px';
            overlay.style.fontSize = '12px';
            overlay.style.fontWeight = '600';
            overlay.style.color = '#6b7280';
            overlay.style.pointerEvents = 'none';
            wrap.appendChild(overlay);
        }
        return overlay;
    }

    function setChartMessage(canvasEl, msg) {
        if (!canvasEl) return;
        var overlay = ensureChartOverlay(canvasEl);
        if (!overlay) return;
        if (msg) {
            overlay.textContent = msg;
            overlay.style.display = 'flex';
            canvasEl.style.opacity = '0.12';
        } else {
            overlay.textContent = '';
            overlay.style.display = 'none';
            canvasEl.style.opacity = '';
        }
    }

    var trendCanvas = document.getElementById('chart_trend');
    var directionCanvas = document.getElementById('chart_direction');
    var onTimeCanvas = document.getElementById('chart_on_time');
    var targetCanvas = document.getElementById('chart_target_achievement');
    var completionCanvas = document.getElementById('chart_completion_rate');
    var bottleneckCanvas = document.getElementById('chart_bottleneck');
    var processStatusCanvas = document.getElementById('chart_process_status');

    if (!window.Chart) {
        setChartMessage(trendCanvas, 'Chart.js gagal dimuat.');
        setChartMessage(directionCanvas, 'Chart.js gagal dimuat.');
        setChartMessage(onTimeCanvas, 'Chart.js gagal dimuat.');
        setChartMessage(targetCanvas, 'Chart.js gagal dimuat.');
        setChartMessage(completionCanvas, 'Chart.js gagal dimuat.');
        setChartMessage(bottleneckCanvas, 'Chart.js gagal dimuat.');
        setChartMessage(processStatusCanvas, 'Chart.js gagal dimuat.');
        return;
    }

    var isDark = false;
    try {
        isDark = (document.documentElement && document.documentElement.getAttribute('data-theme') === 'dark');
    } catch (e) {
        isDark = false;
    }

    var stTheme = {
        text: isDark ? '#f9fafb' : '#111827',
        muted: isDark ? 'rgba(249,250,251,0.72)' : 'rgba(17,24,39,0.72)',
        grid: isDark ? 'rgba(148,163,184,0.16)' : 'rgba(15,23,42,0.10)',
        tooltipBg: isDark ? 'rgba(17,24,39,0.92)' : 'rgba(255,255,255,0.96)',
        tooltipBorder: isDark ? 'rgba(148,163,184,0.22)' : 'rgba(15,23,42,0.10)',
        shadow: isDark ? 'rgba(0,0,0,0.55)' : 'rgba(0,0,0,0.18)'
    };

    try {
        if (window.Chart && window.Chart.defaults) {
            window.Chart.defaults.color = stTheme.muted;
            window.Chart.defaults.font = window.Chart.defaults.font || {};
            window.Chart.defaults.font.family = 'system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif';
        }
    } catch (e) {
        // ignore
    }

    var stShadowPlugin = {
        id: 'stShadow',
        beforeDatasetsDraw: function (chart, args, opts) {
            var ctx = chart && chart.ctx ? chart.ctx : null;
            if (!ctx) return;
            var o = opts || {};
            ctx.save();
            ctx.shadowColor = o.color || stTheme.shadow;
            ctx.shadowBlur = typeof o.blur === 'number' ? o.blur : 14;
            ctx.shadowOffsetX = typeof o.offsetX === 'number' ? o.offsetX : 0;
            ctx.shadowOffsetY = typeof o.offsetY === 'number' ? o.offsetY : 6;
        },
        afterDatasetsDraw: function (chart) {
            var ctx = chart && chart.ctx ? chart.ctx : null;
            if (!ctx) return;
            try { ctx.restore(); } catch (e) {}
        }
    };

    if (window.Chart && typeof window.Chart.register === 'function') {
        try {
            window.Chart.register(stShadowPlugin);
        } catch (e) {
            // ignore
        }
    }

    var stTrendValueLabelsPlugin = {
        id: 'stTrendValueLabels',
        afterDatasetsDraw: function (chart) {
            try {
                if (!chart || !chart.canvas || chart.canvas.id !== 'chart_trend') return;
                var ctx = chart.ctx;
                if (!ctx) return;

                var isDarkLocal = false;
                try { isDarkLocal = (document.documentElement && document.documentElement.getAttribute('data-theme') === 'dark'); } catch (e) {}

                function drawPill(text, x, y, customColor) {
                    if (!text) return;
                    var fontSize = 10;
                    ctx.save();
                    ctx.font = '800 ' + fontSize + 'px system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';

                    ctx.fillStyle = customColor || (isDarkLocal ? 'rgba(226,232,240,0.98)' : 'rgba(15,23,42,0.95)');
                    // Add grey outline if customColor is used (assuming it's white for bars)
                   if (customColor) {
                       ctx.lineWidth = 2;
                       ctx.strokeStyle = '#475569'; // Slate-600 grey
                       ctx.strokeText(text, x, y);
                   }
                    ctx.fillText(text, x, y);
                    ctx.restore();
                }

                var datasets = (chart.data && chart.data.datasets) ? chart.data.datasets : [];
                datasets.forEach(function (ds, di) {
                    if (!ds) return;
                    var meta = chart.getDatasetMeta(di);
                    if (!meta || meta.hidden || !meta.data) return;

                    if (ds.type === 'bar') {
                        meta.data.forEach(function (el, i) {
                            var v = parseInt((ds.data || [])[i] || 0, 10) || 0;
                            if (v <= 0) return;
                            if (!el || typeof el.x !== 'number') return;
                            var x = el.x;
                            var y = (typeof el.y === 'number' && typeof el.base === 'number') ? ((el.y + el.base) / 2) : el.y;
                            // Make bar text white
                            drawPill(String(v), x, y, '#ffffff');
                        });
                    }

                    if (ds.type === 'line') {
                        meta.data.forEach(function (el, i) {
                            var v = parseInt((ds.data || [])[i] || 0, 10) || 0;
                            if (v <= 0) return;
                            if (!el || typeof el.x !== 'number' || typeof el.y !== 'number') return;
                            drawPill(String(v), el.x, el.y - 14);
                        });
                    }
                });
            } catch (e) {
                // ignore
            }
        }
    };

    function hexToRgb(hex) {
        var h = String(hex || '').replace('#', '');
        if (h.length === 3) h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2];
        if (h.length !== 6) return null;
        var n = parseInt(h, 16);
        if (isNaN(n)) return null;
        return { r: (n >> 16) & 255, g: (n >> 8) & 255, b: n & 255 };
    }

    function mixRgb(a, b, t) {
        var tt = Math.max(0, Math.min(1, parseFloat(t || 0)));
        return {
            r: Math.round(a.r + (b.r - a.r) * tt),
            g: Math.round(a.g + (b.g - a.g) * tt),
            b: Math.round(a.b + (b.b - a.b) * tt)
        };
    }

    function rgbToCss(c, alpha) {
        var a = (alpha === null || alpha === undefined) ? 1 : Math.max(0, Math.min(1, parseFloat(alpha)));
        return 'rgba(' + [c.r, c.g, c.b, a].join(',') + ')';
    }

    function lightenHex(hex, t) {
        var c = hexToRgb(hex);
        if (!c) return hex;
        var w = { r: 255, g: 255, b: 255 };
        return rgbToCss(mixRgb(c, w, t), 1);
    }

    var dataLabelsPlugin = window.ChartDataLabels || (window.Chart && window.ChartDataLabels);
    if (dataLabelsPlugin && window.Chart && typeof window.Chart.register === 'function') {
        try {
            window.Chart.register(dataLabelsPlugin);
        } catch (e) {
            // ignore
        }
    }

    function withDataLabels(cfg, type, isCompact) {
        if (!dataLabelsPlugin) return cfg;
        cfg.plugins = cfg.plugins || [];
        if (cfg.plugins.indexOf(dataLabelsPlugin) === -1) {
            cfg.plugins.push(dataLabelsPlugin);
        }
        cfg.options = cfg.options || {};
        cfg.options.plugins = cfg.options.plugins || {};
        cfg.options.plugins.datalabels = cfg.options.plugins.datalabels || {};

        if (type === 'doughnut') {
            // Posisikan label di luar chart
            cfg.options.plugins.datalabels = Object.assign({
                anchor: 'end',
                align: 'end',
                offset: 6,
                clip: false,
                color: stTheme.text,
                font: { weight: '700', size: 11 },
                formatter: function (value, ctx) {
                    var data = (ctx && ctx.chart && ctx.chart.data && ctx.chart.data.datasets && ctx.chart.data.datasets[0]) ? (ctx.chart.data.datasets[0].data || []) : [];
                    var total = (data || []).reduce(function (a, b) { return a + (parseFloat(b || 0) || 0); }, 0);
                    if (!total || !value) return '';
                    var rawPct = ((parseFloat(value || 0) || 0) / total) * 100;
                    // Tampilkan persentase detail (desimal) agar tidak terlihat 50% vs 50% saat total beda tipis
                    var pctText = rawPct.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
                    return String(value) + ' (' + pctText + '%)';
                },
                display: function(ctx) {
                    // Hanya tampilkan jika value > 0
                    return ctx.dataset.data[ctx.dataIndex] > 0;
                }
            }, cfg.options.plugins.datalabels);

            // Tambahkan layout padding agar ada ruang untuk label
            cfg.options.layout = cfg.options.layout || {};

            if (isCompact) {
                // Compact mode (Direction Chart) - Reduced padding, Larger Radius
                cfg.options.layout.padding = { top: 15, bottom: 15, left: 20, right: 20 };
                cfg.options.cutout = cfg.options.cutout || '55%';
                cfg.options.radius = '90%';
            } else {
                // Normal mode (KPI, etc) - Standard padding, Smaller Radius
                cfg.options.layout.padding = { top: 28, bottom: 28, left: 44, right: 44 };
                cfg.options.cutout = cfg.options.cutout || '50%';
                cfg.options.radius = '68%';
            }
        } else {
            cfg.options.plugins.datalabels = Object.assign({
                anchor: 'end',
                align: 'top',
                offset: 2,
                clip: false,
                color: stTheme.text,
                font: { weight: '700', size: 11 },
                formatter: function (value) {
                    if (value === null || value === undefined) return '';
                    return String(value);
                }
            }, cfg.options.plugins.datalabels);
        }

        return cfg;
    }

    var trendDays = readJsonFromEl('dashboard_trend_days') || [];
    var trendCounts = readJsonFromEl('dashboard_trend_counts') || [];
    var trendInbound = readJsonFromEl('dashboard_trend_inbound') || [];
    var trendOutbound = readJsonFromEl('dashboard_trend_outbound') || [];
    var numbers = readJsonFromEl('dashboard_numbers') || {};
    var inbound = parseInt(numbers.inbound || 0, 10);
    var outbound = parseInt(numbers.outbound || 0);
    var baseInbound = inbound;
    var baseOutbound = outbound;

    // Update chart title with month name
    if (trendDays && trendDays.length > 0) {
        try {
            var firstDay = trendDays[0]; // YYYY-MM-DD
            if (firstDay) {
                var d = new Date(firstDay);
                if (!isNaN(d.getTime())) {
                    var monthName = d.toLocaleString('en-US', { month: 'long' });
                    // Optional: Add Year if needed
                    // var year = d.getFullYear();
                    var titleEl = document.getElementById('chart_trend_title');
                    if (titleEl) {
                        titleEl.textContent = 'Completed Trend for ' + monthName;
                    }
                }
            }
        } catch(e) { console.error('Error updating title date', e); }
    }

    // Update direction mini cards
    var inboundEl = document.getElementById('direction_inbound_value');
    var outboundEl = document.getElementById('direction_outbound_value');
    var totalEl = document.getElementById('direction_total_value');

    if (inboundEl) inboundEl.textContent = inbound.toLocaleString();
    if (outboundEl) outboundEl.textContent = outbound.toLocaleString();
    if (totalEl) totalEl.textContent = (inbound + outbound).toLocaleString();

    var onTimeDir = readJsonFromEl('dashboard_on_time_dir') || {};
    var targetDir = readJsonFromEl('dashboard_target_dir') || {};
    var onTimeWarehouseData = readJsonFromEl('dashboard_on_time_wh_data') || [];
    var targetWarehouseData = readJsonFromEl('dashboard_target_wh_data') || [];

    var onTimeGateData = readJsonFromEl('dashboard_on_time_gate_data') || [];
    var targetGateData = readJsonFromEl('dashboard_target_gate_data') || [];
    var completionGateData = readJsonFromEl('dashboard_completion_gate_data') || [];

    var directionByGate = readJsonFromEl('dashboard_direction_by_gate') || {};

    var targetLabels = readJsonFromEl('dashboard_target_segment_labels') || [];
    var targetAch = readJsonFromEl('dashboard_target_segment_achieve') || [];
    var targetNotAch = readJsonFromEl('dashboard_target_segment_not_achieve') || [];

    var bottleneckLabels = readJsonFromEl('dashboard_bottleneck_labels') || [];
    var bottleneckValues = readJsonFromEl('dashboard_bottleneck_values') || [];
    var bottleneckDirections = readJsonFromEl('dashboard_bottleneck_directions') || [];
    var bottleneckRows = readJsonFromEl('dashboard_bottleneck_rows') || [];

    var completionData = readJsonFromEl('dashboard_completion_data') || [];
    var processStatusCounts = readJsonFromEl('dashboard_process_status_counts') || {};

    function getTooltipOptions() {
        return {
            backgroundColor: stTheme.tooltipBg,
            titleColor: stTheme.text,
            bodyColor: stTheme.text,
            borderColor: stTheme.tooltipBorder,
            borderWidth: 1,
            padding: 10,
            cornerRadius: 10,
            displayColors: true,
            usePointStyle: true
        };
    }

    function getLegendOptions(display) {
        return {
            display: !!display,
            position: 'bottom',
            labels: {
                color: stTheme.muted,
                padding: 14,
                boxWidth: 10,
                usePointStyle: true,
                font: { size: 11, weight: '600' }
            }
        };
    }

    var trendSum = (trendCounts || []).reduce(function (a, b) { return a + (parseInt(b || 0, 10) || 0); }, 0);
    setChartMessage(trendCanvas, trendSum <= 0 ? 'No Completed data in this range.' : '');

    setChartMessage(directionCanvas, (inbound + outbound) <= 0 ? 'No Inbound/Outbound data in this range.' : '');

    var onTimeTotal0 = parseInt(numbers.on_time || 0, 10) + parseInt(numbers.late || 0, 10);
    setChartMessage(onTimeCanvas, onTimeTotal0 <= 0 ? 'No On Time/Late KPI data in this range.' : '');

    var targetTotal0 = parseInt(numbers.achieve || 0, 10) + parseInt(numbers.not_achieve || 0, 10);
    setChartMessage(targetCanvas, targetTotal0 <= 0 ? 'No Target Achievement data in this range.' : '');

    var compTotal0 = parseInt(numbers.completion_total || 0, 10);
    setChartMessage(completionCanvas, compTotal0 <= 0 ? 'No Completion data in this range.' : '');

    var bottleCount0 = (bottleneckRows || []).length;
    setChartMessage(bottleneckCanvas, bottleCount0 <= 0 ? 'No Bottlenecks found in this range.' : '');

    function makeChart(canvasEl, cfg) {
        if (!canvasEl || !window.Chart) return null;
        try {
            return new Chart(canvasEl, cfg);
        } catch (e) {
            return null;
        }
    }

    var chartAreaBorderPlugin = {
        id: 'chartAreaBorder',
        afterDraw: function(chart, args, options) {
            var ctx = chart.ctx;
            var area = chart.chartArea;
            ctx.save();
            ctx.beginPath();
            ctx.lineWidth = 0.5;
            ctx.strokeStyle = '#d1d5db'; // Subtle grey
            ctx.rect(area.left, area.top, area.width, area.height);
            ctx.stroke();
            ctx.restore();
        }
    };

    var bar3DShinePlugin = {
        id: 'bar3DShine',
        afterDatasetsDraw: function(chart) {
            var ctx = chart.ctx;
            chart.data.datasets.forEach(function(ds, i) {
                if (ds.type !== 'bar') return;
                var meta = chart.getDatasetMeta(i);
                if (!meta.hidden) {
                    meta.data.forEach(function(element) {
                        var x = element.x;
                        var y = element.y;
                        var width = element.width;
                        var base = element.base;
                        var height = Math.abs(base - y);
                        var top = Math.min(base, y);

                        ctx.save();
                        ctx.beginPath();
                        // Capsule shape path for clipping
                        // Assuming radius is width/2 for full capsule
                        var r = width / 2;
                        ctx.moveTo(x - width/2 + r, top);
                        // Simple Rect Clip for unified shape compatibility
                        ctx.rect(x - width/2, top, width, height);
                        ctx.clip();

                        // Simulated Left-Reflection Gradient
                        var gradient = ctx.createLinearGradient(x - width/2, top, x + width/2, top);
                        gradient.addColorStop(0, 'rgba(255, 255, 255, 0.5)'); // Bright edge
                        gradient.addColorStop(0.25, 'rgba(255, 255, 255, 0.05)'); // Fade
                        gradient.addColorStop(1, 'rgba(255, 255, 255, 0)');

                        ctx.fillStyle = gradient;
                        ctx.fill();
                        ctx.restore();
                    });
                }
            });
        }
    };

    var completedLineEffectsPlugin = {
        id: 'completedLineEffects',
        beforeDatasetDraw: function(chart, args) {
            var ctx = chart.ctx;
            var ds = chart.data.datasets[args.index];
            if (ds.type === 'line' && ds.label === 'Completed') {
                ctx.save();
                // 1. Antigravity Line Shadow
                ctx.shadowColor = 'rgba(54, 63, 57, 0.5)'; // Dark Green Glow
                ctx.shadowBlur = 15;
                ctx.shadowOffsetY = 12; // Floating effect
                ctx.shadowOffsetX = 0;
            }
        },
        afterDatasetDraw: function(chart, args) {
            var ctx = chart.ctx;
            var ds = chart.data.datasets[args.index];
            // 2. Draw 3D Sphere Points
            if (ds.type === 'line' && ds.label === 'Completed') {
                // Restore first to clear line shadow settings for points (we want distinct shadow for beads)
                ctx.restore();

                var meta = chart.getDatasetMeta(args.index);
                if (!meta.hidden) {
                    ctx.save();
                    meta.data.forEach(function(pt, index) {
                        // Skip if value is 0 or invalid if desired, though requested for all points
                        var value = ds.data[index];
                        if (value === null || value === undefined) return;

                        var x = pt.x;
                        var y = pt.y;
                        var r = 4; // Bead radius

                        // Bead Shadow (closer to object)
                        ctx.shadowColor = 'rgba(0, 0, 0, 0.15)';
                        ctx.shadowBlur = 4;
                        ctx.shadowOffsetY = 3;

                        ctx.beginPath();
                        ctx.arc(x, y, r, 0, Math.PI * 2);

                        // 3D Sphere Radial Gradient
                        // Light source from top-left
                        var grad = ctx.createRadialGradient(x - r/3, y - r/3, r/6, x, y, r);
                        grad.addColorStop(0, '#ffffff'); // Highlight
                        grad.addColorStop(1, '#e2e8f0'); // Shading (Slate-200)

                        ctx.fillStyle = grad;
                        ctx.fill();

                        // Check if Sunday (Holiday)
                        var dateLabel = chart.data.labels[index];
                        var isSunday = false;
                        if (dateLabel) {
                            var dObj = new Date(dateLabel);
                            if (dObj && !isNaN(dObj.getTime()) && dObj.getDay() === 0) {
                                isSunday = true;
                            }
                        }

                        if (isSunday) {
                            // Red outline for Sunday
                            ctx.strokeStyle = '#dc2626';
                        } else {
                            // Green outline matching the line
                            ctx.strokeStyle = '#238a1bff';
                        }

                        ctx.lineWidth = 2;
                        ctx.stroke();
                    });
                    ctx.restore();
                }
            } else {
                // Restore if not completed line (though we only saved for completed line in beforeDatasetDraw)
                 // This block handles the case where beforeDatasetDraw didn't run or logic differs,
                 // but strictly for this plugin structure, 'restore' is handled in the if block above
                 // effectively closing the 'save' from beforeDatasetDraw.
                 // Note: Chart.js plugins 'beforeDatasetDraw' and 'afterDatasetDraw' balance is manual.
                 // If we didn't enter the 'if' in before, we shouldn't restore.
                 // Logic check: The save is inside the if. The restore is inside the if. Correct.
            }
        }
    };

    var trendChart = makeChart(trendCanvas, withDataLabels({
        plugins: [stTrendValueLabelsPlugin, chartAreaBorderPlugin, bar3DShinePlugin, completedLineEffectsPlugin],
        type: 'bar',
        data: {
            labels: trendDays,
            datasets: [
                {
                    type: 'bar',
                    label: 'Inbound',
                    data: trendInbound,
                    stack: 'dir',
                    backgroundColor: 'rgba(2, 132, 199, 0.85)', // Solid Primary Blue (Inbound)
                    borderColor: 'rgba(2, 132, 199, 0.8)', // Stronger Outline
                    borderWidth: 1, // Full outline
                    borderRadius: { bottomLeft: 0, bottomRight: 0, topLeft: 0, topRight: 0 }, // Flat bottom aligned with X-axis
                    borderSkipped: false, // Ensure rounded corners draw
                    maxBarThickness: 20,
                    order: 2,
                    datalabels: { display: false }
                },
                {
                    type: 'bar',
                    label: 'Outbound',
                    data: trendOutbound,
                    stack: 'dir',
                    backgroundColor: 'rgba(234, 88, 12, 0.85)', // Solid Orange (Outbound)
                    borderColor: 'rgba(234, 88, 12, 0.8)', // Stronger Outline
                    borderWidth: 1, // Full outline
                    borderRadius: { topLeft: 100, topRight: 100, bottomLeft: 0, bottomRight: 0 }, // Top half capsule
                    borderSkipped: false, // Ensure rounded corners draw
                    maxBarThickness: 20,
                    order: 2,
                    datalabels: { display: false }
                },
                {
                    type: 'line',
                    label: 'Completed',
                    data: trendCounts,
                    borderColor: '#238a1bff', // Dark Green
                    backgroundColor: function(context) {
                        const chart = context.chart;
                        const {ctx, chartArea} = chart;
                        if (!chartArea) return null;
                        const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                        gradient.addColorStop(0, 'rgba(255, 255, 255, 0.1)'); // Fades to white-ish transparent
                        gradient.addColorStop(1, 'rgba(21, 128, 61, 0.1)'); // Very light Green (10%)
                        return gradient;
                    },
                    fill: true,
                    tension: 0.4, // Smooth Bezier (Requested 0.4)
                    pointRadius: 0, // Hide default points, using custom 3D spheres
                    pointHoverRadius: 8,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#15803d',
                    pointBorderWidth: 2,
                    borderWidth: 4, // Bold Neon Tube
                    pointHitRadius: 20,
                    order: 1,
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        offset: 4,
                        clip: false,
                        color: isDark ? 'rgba(226,232,240,0.95)' : 'rgba(15,23,42,0.92)',
                        backgroundColor: isDark ? 'rgba(2,6,23,0.45)' : 'rgba(255,255,255,0.80)',
                        borderColor: isDark ? 'rgba(148,163,184,0.22)' : 'rgba(15,23,42,0.10)',
                        borderWidth: 1,
                        borderRadius: 6,
                        padding: { top: 1, right: 4, bottom: 1, left: 4 },
                        font: { weight: '800', size: 9 },
                        display: function (ctx) {
                            var v = ctx && ctx.raw;
                            var n = parseInt(v || 0, 10) || 0;
                            return n > 0;
                        },
                        formatter: function (v) {
                            var n = parseInt(v || 0, 10) || 0;
                            return n > 0 ? n.toLocaleString('id-ID') : '';
                        }
                    }
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 900, easing: 'easeOutQuart' },
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: getLegendOptions(false),
                datalabels: { clamp: true, display: true },
                tooltip: (function () {
                    var t = getTooltipOptions();
                    t.callbacks = t.callbacks || {};
                    t.callbacks.label = function (ctx) {
                        var label = (ctx && ctx.dataset && ctx.dataset.label) ? String(ctx.dataset.label) : '';
                        var v = (ctx && typeof ctx.parsed === 'object') ? (ctx.parsed.y) : (ctx && ctx.parsed);
                        var n = parseInt(v || 0, 10) || 0;
                        return (label ? (label + ': ') : '') + n.toLocaleString();
                    };
                    // Custom sort order for tooltip: Completed, Outbound, Inbound
                    var labelOrder = { 'Completed': 1, 'Outbound': 2, 'Inbound': 3 };
                    t.itemSort = function(a, b) {
                        var orderA = labelOrder[a.dataset.label] || 99;
                        var orderB = labelOrder[b.dataset.label] || 99;
                        return orderA - orderB;
                    };
                    return t;
                })(),
                stShadow: { color: stTheme.shadow, blur: 18, offsetY: 8, offsetX: 0 }
            },
            layout: {
                padding: { top: 18, right: 10, left: 10, bottom: 6 }
            },
            scales: {
                x: {
                    grid: {
                        borderColor: '#d1d5db',
                        borderWidth: 0.5,
                        color: stTheme.grid,
                        drawBorder: true,
                        drawOnChartArea: false
                    },
                    ticks: {
                        color: stTheme.muted,
                        maxRotation: 0,
                        autoSkip: true,
                        callback: function(val, index) {
                            var label = this.getLabelForValue(val);
                            // Assuming label is YYYY-MM-DD, take the last part (Day)
                            if (typeof label === 'string' && label.includes('-')) {
                                var parts = label.split('-');
                                return parts[parts.length - 1];
                            }
                            return label;
                        }
                    },
                    stacked: true,
                    title: {
                        display: true,
                        text: 'DATE',
                        align: 'end',
                        color: stTheme.muted,
                        font: { size: 10, weight: '700' },
                        padding: { top: 6 }
                    }
                },
                y: {
                    beginAtZero: true,
                    suggestedMax: Math.max(10, (trendCounts || []).reduce(function (m, v) { return Math.max(m, parseInt(v || 0, 10) || 0); }, 0) * 1.25),
                    grid: {
                        borderColor: '#d1d5db',
                        borderWidth: 0.5,
                        color: stTheme.grid,
                        drawBorder: true,
                        drawOnChartArea: false
                    },
                    ticks: { color: stTheme.muted, precision: 0 },
                    stacked: true,
                    title: {
                        display: true,
                        text: 'COMPLETED COUNT',
                        align: 'end',
                        color: stTheme.muted,
                        font: { size: 10, weight: '700' },
                        padding: { bottom: 6 }
                    }
                }
            }
        }
    }, 'line'));

    // Ensure initial paint includes datalabels without requiring hover
    try {
        if (trendChart && trendChart.update) {
            setTimeout(function () {
                try { trendChart.update(); } catch (e) {}
            }, 50);
        }
    } catch (e) {
        // ignore
    }

    var directionChart = makeChart(directionCanvas, withDataLabels({
        type: 'doughnut',
        data: {
            labels: ['Inbound', 'Outbound'],
            datasets: [{
                data: [inbound, outbound],
                backgroundColor: function (ctx) {
                    var idx = ctx && typeof ctx.dataIndex === 'number' ? ctx.dataIndex : 0;
                    var base = idx === 0 ? '#0284c7' : '#ea580c';
                    var chart = ctx && ctx.chart ? ctx.chart : null;
                    if (!chart || !chart.chartArea) return base;
                    var area = chart.chartArea;
                    var g = chart.ctx.createLinearGradient(0, area.top, 0, area.bottom);
                    g.addColorStop(0, lightenHex(base, 0.25));
                    g.addColorStop(1, base);
                    return g;
                },
                borderColor: isDark ? 'rgba(15,23,42,0.45)' : 'rgba(255,255,255,0.85)',
                borderWidth: 2,
                borderRadius: 10,
                spacing: 2,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '62%',
            radius: '86%',
            plugins: {
                legend: getLegendOptions(true),
                tooltip: getTooltipOptions(),
                stShadow: { color: stTheme.shadow, blur: 14, offsetY: 8, offsetX: 0 }
            }
        }
    }, 'doughnut', true)); // isCompact = true for Direction Chart

    var scheduleData = @json($schedule ?? []);
    var processStatusData = @json($processStatusCounts ?? []);

    function normalizeGateKeyForDirection(row) {
        var raw = '';
        if (row && row.gate_label !== undefined && row.gate_label !== null) raw = row.gate_label;
        else if (row && row.gate_number !== undefined && row.gate_number !== null) raw = row.gate_number;
        else if (row && row.gate !== undefined && row.gate !== null) raw = row.gate;

        // If numeric, map 1/2/3 -> A/B/C
        if (typeof raw === 'number') {
            if (raw === 1) return 'A';
            if (raw === 2) return 'B';
            if (raw === 3) return 'C';
        }

        var s = String(raw || '').trim().toUpperCase();
        if (!s) return '';

        // Accept only explicit gate patterns OR pure single-token inputs.
        // Examples supported:
        // - "Gate A", "Warehouse 1 - Gate A", "G1", "G 2", "Gate-3"
        // - "A" / "B" / "C" / "1" / "2" / "3"
        var m = null;

        // Pure token
        m = s.match(/^\s*([ABC123])\s*$/);

        // Gate patterns
        if (!m) m = s.match(/\bGATE\b[^A-Z0-9]*([ABC123])\b/);
        if (!m) m = s.match(/\bG\b\s*([ABC123])\b/);
        if (!m) m = s.match(/\bG\s*([ABC123])\b/);

        if (!m || !m[1]) return '';

        var k = String(m[1] || '').toUpperCase();
        if (k === '1') return 'A';
        if (k === '2') return 'B';
        if (k === '3') return 'C';
        if (k === 'A' || k === 'B' || k === 'C') return k;
        return '';
    }

    function normalizeDirectionKey(raw) {
        var s = String(raw || '').trim().toLowerCase();
        if (!s) return '';
        if (s.indexOf('in') === 0) return 'inbound';
        if (s.indexOf('out') === 0) return 'outbound';
        if (s === 'i') return 'inbound';
        if (s === 'o') return 'outbound';
        return '';
    }

    function calcDirectionCountsByGate(gateKey) {
        var gk = String(gateKey || 'all').toUpperCase();

        // All Gates = overall totals for the selected analytics range.
        if (!gk || gk === 'ALL') {
            return { inbound: baseInbound, outbound: baseOutbound };
        }

        // Gate A/B/C uses backend aggregation (range-based, accurate).
        try {
            var row = directionByGate ? directionByGate[gk] : null;
            if (row) {
                return {
                    inbound: parseInt(row.inbound || 0, 10) || 0,
                    outbound: parseInt(row.outbound || 0, 10) || 0
                };
            }
        } catch (e) {}

        return { inbound: 0, outbound: 0 };
    }

    function applyDirectionFilter(gateKey) {
        var res = calcDirectionCountsByGate(gateKey);
        var inV = parseInt(res.inbound || 0, 10) || 0;
        var outV = parseInt(res.outbound || 0, 10) || 0;

        if (inboundEl) inboundEl.textContent = inV.toLocaleString();
        if (outboundEl) outboundEl.textContent = outV.toLocaleString();
        if (totalEl) totalEl.textContent = (inV + outV).toLocaleString();

        setChartMessage(directionCanvas, (inV + outV) <= 0 ? 'No Inbound/Outbound data in this range.' : '');
        if (directionChart) {
            try {
                directionChart.data.datasets[0].data = [inV, outV];
                directionChart.update();
            } catch (e) {}
        }
    }

    var directionGateSel = document.getElementById('direction_gate');
    if (directionGateSel) {
        directionGateSel.addEventListener('change', function () {
            applyDirectionFilter(directionGateSel.value || 'all');
        });
        applyDirectionFilter(directionGateSel.value || 'all');
    }

    function calcProcessStatus(dir) {
        var counts = {
            pending: 0,
            scheduled: 0,
            waiting: 0,
            in_progress: 0,
            completed: 0,
            cancelled: 0
        };

        // Gunakan data dari backend yang sudah dihitung
        if (processStatusData && Object.keys(processStatusData).length > 0) {
            counts.pending = processStatusData.pending || 0;
            counts.scheduled = processStatusData.scheduled || 0;
            counts.waiting = processStatusData.waiting || 0;
            counts.in_progress = processStatusData.in_progress || 0;
            counts.completed = processStatusData.completed || 0;
            counts.cancelled = processStatusData.cancelled || 0;

            // Filter by direction if needed
            if (dir && dir !== 'all') {
                // For now, use the same counts for all directions
                // TODO: Add direction filtering if needed
            }
            return counts;
        }

        // Fallback to original logic if no backend data
        (scheduleData || []).forEach(function (r) {
            var rDir = (r && r.direction) ? String(r.direction).toLowerCase() : '';
            if (dir && dir !== 'all' && rDir !== dir) return;

            var st = (r && r.status) ? String(r.status).toLowerCase().trim() : 'scheduled';
            if (st === 'arrived') st = 'waiting';

            if (st === 'pending_approval' || st === 'pending_vendor_confirmation') {
                counts.pending++;
            } else if (typeof counts[st] !== 'undefined') {
                counts[st]++;
            }
        });
        return counts;
    }

    function getStatusBgColor(bgClass) {
        try {
            var el = document.createElement('span');
            el.className = bgClass;
            el.style.display = 'none';
            document.body.appendChild(el);
            var c = window.getComputedStyle(el).backgroundColor;
            document.body.removeChild(el);
            return c || '';
        } catch (e) {
            return '';
        }
    }

    var processStatusColors = [
        getStatusBgColor('bg-pending_approval'),
        getStatusBgColor('bg-scheduled'),
        getStatusBgColor('bg-waiting'),
        getStatusBgColor('bg-in_progress'),
        getStatusBgColor('bg-completed'),
        getStatusBgColor('bg-danger')
    ];

    var processStatusChart = makeChart(processStatusCanvas, {
        type: 'bar',
        data: {
            labels: ['Pending', 'Scheduled', 'Waiting', 'In Progress', 'Completed', 'Cancelled'],
            datasets: [{
                data: [0, 0, 0, 0, 0, 0],
                backgroundColor: processStatusColors,
                borderRadius: 4,
                barThickness: 32
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 700, easing: 'easeOutQuart' },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: getTooltipOptions(),
                stShadow: { color: stTheme.shadow, blur: 18, offsetY: 10, offsetX: 0 }
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        font: { size: 10, weight: '600' },
                        color: '#374151'
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        display: true,
                        drawBorder: false,
                        color: function(context) {
                            return context.tick.value === 0 ? '#e5e7eb' : '#f3f4f6';
                        }
                    },
                    ticks: {
                        font: { size: 10, weight: '500' },
                        color: '#6b7280',
                        precision: 0
                    },
                    suggestedMax: 10
                }
            }
        }
    });

    function updateProcessStatusUI(dir) {
        var counts = calcProcessStatus(dir);

        var elPending = document.getElementById('status_pending_value');
        var elScheduled = document.getElementById('status_scheduled_value');
        var elWaiting = document.getElementById('status_waiting_value');
        var elInProgress = document.getElementById('status_in_progress_value');
        var elCompleted = document.getElementById('status_completed_value');
        var elCancelled = document.getElementById('status_cancelled_value');

        if (elPending) elPending.textContent = counts.pending;
        if (elScheduled) elScheduled.textContent = counts.scheduled;
        if (elWaiting) elWaiting.textContent = counts.waiting;
        if (elInProgress) elInProgress.textContent = counts.in_progress;
        if (elCompleted) elCompleted.textContent = counts.completed;
        if (elCancelled) elCancelled.textContent = counts.cancelled;

        var total = counts.pending + counts.scheduled + counts.waiting + counts.in_progress + counts.completed + counts.cancelled;
        setChartMessage(processStatusCanvas, total <= 0 ? 'No Schedule data for selected filter.' : '');

        if (processStatusChart) {
            processStatusChart.data.datasets[0].data = [
                counts.pending,
                counts.scheduled,
                counts.waiting,
                counts.in_progress,
                counts.completed,
                counts.cancelled
            ];

            var maxVal = Math.max(counts.pending, counts.scheduled, counts.waiting, counts.in_progress, counts.completed, counts.cancelled);
            var suggMax = maxVal <= 10 ? 10 : Math.ceil(maxVal * 1.2);
            if (processStatusChart.options.scales.y) {
                 processStatusChart.options.scales.y.suggestedMax = suggMax;
            }
            processStatusChart.update();
        }
    }

    var statusDirSelect = document.getElementById('status_direction');
    if (statusDirSelect) {
        statusDirSelect.addEventListener('change', function() {
            updateProcessStatusUI(statusDirSelect.value);
        });
    }
    updateProcessStatusUI('all');

    var onTimeChart = makeChart(onTimeCanvas, withDataLabels({
        type: 'doughnut',
        data: {
            labels: ['On Time', 'Late'],
            datasets: [{
                data: [parseInt(numbers.on_time || 0, 10), parseInt(numbers.late || 0, 10)],
                backgroundColor: function (ctx) {
                    var idx = ctx && typeof ctx.dataIndex === 'number' ? ctx.dataIndex : 0;
                    var base = idx === 0 ? '#16a34a' : '#dc2626';
                    var chart = ctx && ctx.chart ? ctx.chart : null;
                    if (!chart || !chart.chartArea) return base;
                    var area = chart.chartArea;
                    var g = chart.ctx.createLinearGradient(0, area.top, 0, area.bottom);
                    g.addColorStop(0, lightenHex(base, 0.22));
                    g.addColorStop(1, base);
                    return g;
                },
                borderColor: isDark ? 'rgba(15,23,42,0.45)' : 'rgba(255,255,255,0.85)',
                borderWidth: 2,
                borderRadius: 10,
                spacing: 2,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '62%',
            radius: '86%',
            plugins: {
                legend: getLegendOptions(true),
                tooltip: getTooltipOptions(),
                stShadow: { color: stTheme.shadow, blur: 14, offsetY: 8, offsetX: 0 }
            }
        }
    }, 'doughnut'));

    var targetChart = makeChart(targetCanvas, withDataLabels({
        type: 'doughnut',
        data: {
            labels: ['Achieve', 'Not Achieve'],
            datasets: [{
                data: [parseInt(numbers.achieve || 0, 10), parseInt(numbers.not_achieve || 0, 10)],
                backgroundColor: function (ctx) {
                    var idx = ctx && typeof ctx.dataIndex === 'number' ? ctx.dataIndex : 0;
                    var base = idx === 0 ? '#16a34a' : '#dc2626';
                    var chart = ctx && ctx.chart ? ctx.chart : null;
                    if (!chart || !chart.chartArea) return base;
                    var area = chart.chartArea;
                    var g = chart.ctx.createLinearGradient(0, area.top, 0, area.bottom);
                    g.addColorStop(0, lightenHex(base, 0.22));
                    g.addColorStop(1, base);
                    return g;
                },
                borderColor: isDark ? 'rgba(15,23,42,0.45)' : 'rgba(255,255,255,0.85)',
                borderWidth: 2,
                borderRadius: 10,
                spacing: 2,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '62%',
            radius: '86%',
            plugins: {
                legend: getLegendOptions(true),
                tooltip: getTooltipOptions(),
                stShadow: { color: stTheme.shadow, blur: 14, offsetY: 8, offsetX: 0 }
            }
        }
    }, 'doughnut'));

    var completionChart = makeChart(completionCanvas, withDataLabels({
        type: 'doughnut',
        data: {
            labels: ['Completed', 'Remaining'],
            datasets: [{
                data: [
                    parseInt(numbers.completion_completed || 0, 10),
                    Math.max(0, parseInt(numbers.completion_total || 0, 10) - parseInt(numbers.completion_completed || 0, 10))
                ],
                backgroundColor: function (ctx) {
                    var idx = ctx && typeof ctx.dataIndex === 'number' ? ctx.dataIndex : 0;
                    var base = idx === 0 ? '#15803d' : (isDark ? '#334155' : '#e5e7eb');
                    var chart = ctx && ctx.chart ? ctx.chart : null;
                    if (!chart || !chart.chartArea) return base;
                    var area = chart.chartArea;
                    var g = chart.ctx.createLinearGradient(0, area.top, 0, area.bottom);
                    g.addColorStop(0, lightenHex(base, idx === 0 ? 0.22 : 0.08));
                    g.addColorStop(1, base);
                    return g;
                },
                borderColor: isDark ? 'rgba(15,23,42,0.45)' : 'rgba(255,255,255,0.85)',
                borderWidth: 2,
                borderRadius: 10,
                spacing: 2,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '62%',
            radius: '86%',
            plugins: {
                legend: getLegendOptions(true),
                tooltip: getTooltipOptions(),
                stShadow: { color: stTheme.shadow, blur: 14, offsetY: 8, offsetX: 0 }
            }
        }
    }, 'doughnut'));

    var bottleneckChart = makeChart(bottleneckCanvas, withDataLabels({
        type: 'bar',
        data: {
            labels: bottleneckLabels,
            datasets: [{
                label: 'Avg waiting (min)',
                data: bottleneckValues,
                backgroundColor: function (ctx) {
                    var chart = ctx && ctx.chart ? ctx.chart : null;
                    if (!chart || !chart.chartArea) return 'rgba(234,88,12,0.70)';
                    var area = chart.chartArea;
                    var g = chart.ctx.createLinearGradient(0, area.top, 0, area.bottom);
                    g.addColorStop(0, 'rgba(249,115,22,0.95)');
                    g.addColorStop(1, 'rgba(234,88,12,0.35)');
                    return g;
                },
                borderRadius: 10,
                borderSkipped: false,
                maxBarThickness: 42
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 800, easing: 'easeOutQuart' },
            interaction: { mode: 'index', intersect: false },
            layout: {
                padding: { top: 18, right: 12, left: 8, bottom: 0 }
            },
            plugins: {
                legend: getLegendOptions(false),
                tooltip: getTooltipOptions(),
                stShadow: { color: stTheme.shadow, blur: 16, offsetY: 8, offsetX: 0 },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    offset: 10,
                    clip: false,
                    color: stTheme.text,
                    font: { weight: '800', size: 10 },
                    backgroundColor: isDark ? 'rgba(15,23,42,0.55)' : 'rgba(255,255,255,0.85)',
                    borderColor: isDark ? 'rgba(148,163,184,0.22)' : 'rgba(15,23,42,0.12)',
                    borderWidth: 1,
                    borderRadius: 8,
                    padding: { top: 2, right: 6, bottom: 2, left: 6 },
                    formatter: function (v) {
                        if (v === null || v === undefined) return '';
                        var n = parseFloat(v);
                        if (!isFinite(n) || n <= 0) return '';
                        return n.toLocaleString('id-ID', { maximumFractionDigits: 1 });
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: stTheme.grid, drawBorder: false },
                    ticks: { color: stTheme.text, maxRotation: 0, autoSkip: true }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: stTheme.grid, drawBorder: false },
                    ticks: { color: stTheme.text, precision: 0 }
                }
            }
        }
    }, 'bar'));

    function calcCompletion(dir, wh) {
        var d = String(dir || 'all');
        var g = String(wh || 'all').toUpperCase();

        var total = 0;
        var completed = 0;

        if (completionGateData && completionGateData.length) {
            (completionGateData || []).forEach(function (r) {
                var rDir = (r && r.direction) ? String(r.direction) : '';
                var rGate = (r && r.gate_key) ? String(r.gate_key).toUpperCase() : '';
                if (g && g !== 'ALL' && rGate !== g) return;
                if (d && d !== 'all' && rDir !== d) return;
                total += parseInt(r.total || 0, 10);
                completed += parseInt(r.completed || 0, 10);
            });
        } else {
            (completionData || []).forEach(function (r) {
                var rDir2 = (r && r.direction) ? String(r.direction) : '';
                if (d && d !== 'all' && rDir2 !== d) return;
                total += parseInt(r.total || 0, 10);
                completed += parseInt(r.completed || 0, 10);
            });
        }

        var rate = total > 0 ? Math.round((completed / total) * 100) : 0;
        return { total: total, completed: completed, rate: rate };
    }

    function updateCompletionUI(dir, wh) {
        var res = calcCompletion(dir, wh);
        var elRate = document.getElementById('completion_rate_value');
        var elCompleted = document.getElementById('completion_completed_value');
        var elTotal = document.getElementById('completion_total_value');
        if (elRate) elRate.textContent = String(res.rate) + '%';
        if (elCompleted) elCompleted.textContent = String(res.completed);
        if (elTotal) elTotal.textContent = String(res.total);

        if (completionChart) {
            completionChart.data.datasets[0].data = [res.completed, Math.max(0, res.total - res.completed)];
            completionChart.update();
        }
    }

    function filterBottleneck(dir, wh) {
        var rows = (bottleneckRows || []).filter(function (r) {
            var rDir = r && r.direction ? String(r.direction) : '';
            var rWh = r && r.warehouse_code ? String(r.warehouse_code) : '';
            if (dir && dir !== 'all' && rDir !== dir) return false;
            if (wh && wh !== 'all' && rWh !== wh) return false;
            return true;
        });

        var gateOrder = ['A', 'B', 'C'];
        var agg = { A: { slot_count: 0, total_wait_minutes: 0 }, B: { slot_count: 0, total_wait_minutes: 0 }, C: { slot_count: 0, total_wait_minutes: 0 } };

        function normalizeGateKey(raw) {
            var s = String(raw || '').trim().toUpperCase();
            if (!s) return '';
            // Accept formats: A/B/C, 1/2/3, G1/G2/G3, GATE A, GATE 1
            s = s.replace(/^GATE\s*/i, '');
            s = s.replace(/^G\s*/i, '');
            s = s.replace(/\s+/g, '');
            if (s === '1') return 'A';
            if (s === '2') return 'B';
            if (s === '3') return 'C';
            if (s === 'A' || s === 'B' || s === 'C') return s;
            return '';
        }

        rows.forEach(function (r) {
            var gateRaw = r && r.gate_number ? String(r.gate_number) : '';
            var gate = normalizeGateKey(gateRaw);
            if (gateOrder.indexOf(gate) === -1) return;

            var sc = parseInt(r && r.slot_count ? r.slot_count : 0, 10) || 0;
            var tw = parseFloat(r && r.total_wait_minutes ? r.total_wait_minutes : 0) || 0;
            if (!tw && sc) {
                var av = parseFloat(r && r.avg_wait_minutes ? r.avg_wait_minutes : 0) || 0;
                tw = av * sc;
            }
            agg[gate].slot_count += sc;
            agg[gate].total_wait_minutes += tw;
        });

        var labels = gateOrder.map(function (g) { return 'Gate ' + g; });
        var values = gateOrder.map(function (g) {
            var sc = agg[g].slot_count;
            if (sc <= 0) return 0;
            return parseFloat((agg[g].total_wait_minutes / sc).toFixed(2));
        });

        var topRows = gateOrder.map(function (g, idx) {
            return {
                label: labels[idx],
                gate_number: g,
                slot_count: agg[g].slot_count,
                avg_wait_minutes: values[idx]
            };
        }).filter(function (r) { return (parseInt(r.slot_count || 0, 10) || 0) > 0; });

        topRows.sort(function (a, b) {
            var av = parseFloat(a && a.avg_wait_minutes ? a.avg_wait_minutes : 0);
            var bv = parseFloat(b && b.avg_wait_minutes ? b.avg_wait_minutes : 0);
            return bv - av;
        });

        return { rows: topRows, labels: labels, values: values };
    }

    function updateBottleneckUI(dir, wh) {
        var res = filterBottleneck(dir, wh);
        var top = res.rows[0] || null;

        var elTopLabel = document.getElementById('bottleneck_top_label');
        var elTopAvg = document.getElementById('bottleneck_top_avg');
        var elTopSlots = document.getElementById('bottleneck_top_slots');
        if (elTopLabel) elTopLabel.textContent = top && top.label ? String(top.label) : '-';
        if (elTopAvg) elTopAvg.textContent = top && top.avg_wait_minutes ? String(top.avg_wait_minutes) : '0';
        if (elTopSlots) elTopSlots.textContent = top && top.slot_count ? String(top.slot_count) : '0';

        if (bottleneckChart) {
            bottleneckChart.data.labels = res.labels;
            bottleneckChart.data.datasets[0].data = res.values;
            bottleneckChart.update();
        }
    }

    function calcOnTime(dir, wh) {
        var d = String(dir || 'all');
        var g = String(wh || 'all').toUpperCase();

        if (!g || g === 'ALL') {
            var src = (d === 'inbound' || d === 'outbound') ? (onTimeDir[d] || {}) : (onTimeDir.all || {});
            var onV0 = parseInt(src.on_time || 0, 10) || 0;
            var lateV0 = parseInt(src.late || 0, 10) || 0;
            return { on_time: onV0, late: lateV0, total: onV0 + lateV0 };
        }

        var onV = 0;
        var lateV = 0;
        (onTimeGateData || []).forEach(function (r) {
            var rDir = (r && r.direction) ? String(r.direction) : '';
            var rGate = (r && r.gate_key) ? String(r.gate_key).toUpperCase() : '';
            if (rGate !== g) return;
            if (d && d !== 'all' && rDir !== d) return;
            onV += parseInt(r.on_time || 0, 10);
            lateV += parseInt(r.late || 0, 10);
        });
        return { on_time: onV, late: lateV, total: onV + lateV };
    }

    function updateOnTimeUI(dir, wh) {
        var res = calcOnTime(dir, wh);
        var onV = parseInt(res.on_time || 0, 10);
        var lateV = parseInt(res.late || 0, 10);
        var totalV = parseInt(res.total || 0, 10);
        var elOn = document.getElementById('on_time_value');
        var elLate = document.getElementById('late_value');
        var elTotal = document.getElementById('on_time_total');
        if (elOn) elOn.textContent = onV;
        if (elLate) elLate.textContent = lateV;
        if (elTotal) elTotal.textContent = totalV;
        if (onTimeChart) {
            onTimeChart.data.datasets[0].data = [onV, lateV];
            onTimeChart.update();
        }
    }

    function calcTarget(dir, wh) {
        var d = String(dir || 'all');
        var g = String(wh || 'all').toUpperCase();

        if (!g || g === 'ALL') {
            var src = (d === 'inbound' || d === 'outbound') ? (targetDir[d] || {}) : (targetDir.all || {});
            var a0 = parseInt(src.achieve || 0, 10) || 0;
            var n0 = parseInt(src.not_achieve || 0, 10) || 0;
            return { achieve: a0, not_achieve: n0, total: a0 + n0 };
        }

        var a = 0;
        var n = 0;
        (targetGateData || []).forEach(function (r) {
            var rDir = (r && r.direction) ? String(r.direction) : '';
            var rGate = (r && r.gate_key) ? String(r.gate_key).toUpperCase() : '';
            if (rGate !== g) return;
            if (d && d !== 'all' && rDir !== d) return;
            a += parseInt(r.achieve || 0, 10);
            n += parseInt(r.not_achieve || 0, 10);
        });
        return { achieve: a, not_achieve: n, total: a + n };
    }

    function updateTargetUI(dir, wh) {
        var res = calcTarget(dir, wh);
        var a = parseInt(res.achieve || 0, 10);
        var n = parseInt(res.not_achieve || 0, 10);
        var tot = parseInt(res.total || 0, 10);
        var elA = document.getElementById('target_achieve_value');
        var elN = document.getElementById('target_not_achieve_value');
        var elT = document.getElementById('target_total_eval');
        var elAp = document.getElementById('target_achieve_pct');
        var elNp = document.getElementById('target_not_achieve_pct');
        if (elA) elA.textContent = a;
        if (elN) elN.textContent = n;
        if (elT) elT.textContent = tot;
        if (elAp) elAp.textContent = (tot ? Math.round((a/tot)*100) : 0) + '% of evaluated';
        if (elNp) elNp.textContent = (tot ? Math.round((n/tot)*100) : 0) + '% of evaluated';
        if (targetChart) {
            targetChart.data.datasets[0].data = [a, n];
            targetChart.update();
        }
    }

    var kpiDirSel = document.getElementById('kpi_dir_filter');
    var kpiGateSel = document.getElementById('kpi_gate_filter');
    function getKpiDir() { return kpiDirSel ? kpiDirSel.value : 'all'; }
    function getKpiGate() { return kpiGateSel ? kpiGateSel.value : 'all'; }
    function applyKpiFilters() {
        var dir = getKpiDir();
        var gate = getKpiGate();
        updateOnTimeUI(dir, gate);
        updateTargetUI(dir, gate);
        updateCompletionUI(dir, gate);
    }
    if (kpiDirSel) {
        kpiDirSel.addEventListener('change', applyKpiFilters);
    }
    if (kpiGateSel) {
        kpiGateSel.addEventListener('change', applyKpiFilters);
    }
    applyKpiFilters();

    var bottleneckDirSel = document.getElementById('bottleneck_dir');
    var bottleneckWhSel = document.getElementById('bottleneck_wh');
    function getBottleneckDir() { return bottleneckDirSel ? bottleneckDirSel.value : 'all'; }
    function getBottleneckWh() { return bottleneckWhSel ? bottleneckWhSel.value : 'all'; }
    if (bottleneckDirSel) {
        bottleneckDirSel.addEventListener('change', function () {
            updateBottleneckUI(getBottleneckDir(), getBottleneckWh());
        });
    }
    if (bottleneckWhSel) {
        bottleneckWhSel.addEventListener('change', function () {
            updateBottleneckUI(getBottleneckDir(), getBottleneckWh());
        });
    }
    updateBottleneckUI(getBottleneckDir(), getBottleneckWh());

    function toMinutesText(min, unit) {
        var v = parseFloat(min);
        if (isNaN(v)) return '-';
        if (unit === 'hour') return (v/60).toFixed(1) + ' h';
        return v.toFixed(1) + ' min';
    }

    var unitSel = document.getElementById('lead_proc_unit');
    if (unitSel) {
        unitSel.addEventListener('change', function () {
            var unit = unitSel.value;
            var leadEl = document.getElementById('lead_avg_value');
            var procEl = document.getElementById('proc_avg_value');
            if (leadEl) leadEl.textContent = toMinutesText(leadEl.getAttribute('data-minutes'), unit);
            if (procEl) procEl.textContent = toMinutesText(procEl.getAttribute('data-minutes'), unit);

            // Update Truck Specific Cards
            document.querySelectorAll('.lead-avg-truck').forEach(function(el) {
                el.textContent = toMinutesText(el.getAttribute('data-minutes'), unit);
            });
            document.querySelectorAll('.proc-avg-truck').forEach(function(el) {
                el.textContent = toMinutesText(el.getAttribute('data-minutes'), unit);
            });
        });
    }

    var emptyEl = document.getElementById('lead_proc_empty');
    var leadEl0 = document.getElementById('lead_avg_value');
    var procEl0 = document.getElementById('proc_avg_value');
    var hasLead = leadEl0 && leadEl0.getAttribute('data-minutes') !== '';
    var hasProc = procEl0 && procEl0.getAttribute('data-minutes') !== '';
    if (emptyEl && (hasLead || hasProc)) {
        emptyEl.style.display = 'none';
    }

    var timeline = document.getElementById('dashboard-timeline');
    var modal = document.getElementById('timeline-modal');
    var modalTitle = document.getElementById('timeline-modal-title');
    var modalSubtitle = document.getElementById('timeline-modal-subtitle');
    var modalStatus = document.getElementById('timeline-modal-status');
    var modalVendor = document.getElementById('timeline-modal-vendor');
    var modalGate = document.getElementById('timeline-modal-gate');
    var modalEta = document.getElementById('timeline-modal-eta');
    var modalFinish = document.getElementById('timeline-modal-finish');

    var btnView = document.getElementById('timeline-modal-view');
    var btnArrival = document.getElementById('timeline-modal-arrival');
    var btnStart = document.getElementById('timeline-modal-start');
    var btnComplete = document.getElementById('timeline-modal-complete');
    var btnCancel = document.getElementById('timeline-modal-cancel');

    function buildRoute(tpl, id) {
        if (!tpl) return '#';
        return String(tpl).replace(/\b0\b/g, String(id));
    }

    function openModal() {
        if (!modal) return;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeModal() {
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    }

    if (modal) {
        modal.querySelectorAll('[data-modal-close]').forEach(function (el) {
            el.addEventListener('click', function () { closeModal(); });
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeModal();
    });

    if (timeline) {
        function computeTimelineScale() {
            var grid = timeline.querySelector('.st-timeline__header-grid');
            if (!grid) return null;
            var startHour = 7;
            var endHour = 23;
            var startMins = startHour * 60;
            var endMins = (endHour + 1) * 60;
            var totalMins = Math.max(1, endMins - startMins);
            var gridWidth = grid.clientWidth || grid.offsetWidth || 1;
            var pxPerMin = gridWidth / Math.max(1, totalMins);
            var hourWidthPx = Math.round(pxPerMin * 60);
            if (typeof timeline._stLastHourWidthPx !== 'number' || Math.abs(hourWidthPx - timeline._stLastHourWidthPx) >= 1) {
                timeline._stLastHourWidthPx = hourWidthPx;
                timeline.style.setProperty('--st-hour-width', String(hourWidthPx) + 'px');
            }

            return {
                startHour: startHour,
                startMins: startMins,
                totalMins: totalMins,
                gridWidth: gridWidth,
                pxPerMin: pxPerMin
            };
        }

        function applyTimelinePositions() {
            if (timeline._stApplyPending) return;
            timeline._stApplyPending = true;

            window.requestAnimationFrame(function () {
                timeline._stApplyPending = false;
                var s = computeTimelineScale();
                if (!s) return;
                timeline._stLastScale = s;

                try {
                    // Only handle timeline blocks positioning, hours are now handled by CSS Grid
                    timeline.querySelectorAll('.st-timeline-block[data-left][data-width]').forEach(function (el) {
                        var leftMin = parseInt(el.getAttribute('data-left') || '0', 10);
                        var widthMin = parseInt(el.getAttribute('data-width') || '1', 10);
                        if (isNaN(leftMin)) leftMin = 0;
                        if (isNaN(widthMin)) widthMin = 1;

                        var relLeft = leftMin - s.startMins;
                        var relWidth = widthMin;

                        if (relLeft < 0) {
                            relWidth = relWidth + relLeft;
                            relLeft = 0;
                        }

                        var maxWidth = s.totalMins - relLeft;
                        if (relWidth > maxWidth) {
                            relWidth = maxWidth;
                        }

                        if (relWidth <= 0) {
                            if (el.style.display !== 'none') el.style.display = 'none';
                            return;
                        }

                        if (el.style.display === 'none') el.style.display = '';

                        // Calculate grid column positions (17 columns for hours 7-23)
                        var startHour = 7;
                        var endHour = 23;
                        var totalHours = endHour - startHour + 1; // 17 hours

                        var startCol = Math.floor(relLeft / 60) + 1; // +1 because grid columns start at 1
                        var endCol = Math.ceil((relLeft + relWidth) / 60) + 1;

                        // Ensure columns are within bounds
                        startCol = Math.max(1, Math.min(startCol, totalHours));
                        endCol = Math.max(startCol, Math.min(endCol, totalHours + 1));

                        if (el._stStartCol !== startCol) {
                            el._stStartCol = startCol;
                            el.style.gridColumnStart = String(startCol);
                        }
                        if (el._stEndCol !== endCol) {
                            el._stEndCol = endCol;
                            el.style.gridColumnEnd = String(endCol);
                        }
                    });
                } catch (e) {
                    // ignore
                }
            });
        }

        applyTimelinePositions();
        // now-marker disabled

        if (!timeline._stResizeBound) {
            timeline._stResizeBound = true;
            var roTimer = null;
            function scheduleReflow() {
                if (roTimer) clearTimeout(roTimer);
                roTimer = setTimeout(function () {
                    applyTimelinePositions();
                }, 120);
            }

            try {
                var gridEl = timeline.querySelector('.st-timeline__header-grid');
                if (gridEl && typeof window.ResizeObserver === 'function') {
                    var ro = new ResizeObserver(function () {
                        var w = gridEl.clientWidth || gridEl.offsetWidth || 0;
                        if (!timeline._stLastGridWidth) {
                            timeline._stLastGridWidth = w;
                        }
                        if (Math.abs(w - timeline._stLastGridWidth) >= 1) {
                            timeline._stLastGridWidth = w;
                            scheduleReflow();
                        }
                    });
                    ro.observe(gridEl);
                    timeline._stResizeObserver = ro;
                }
            } catch (e) {
                // ignore
            }

            if (window.visualViewport && window.visualViewport.addEventListener) {
                try {
                    window.visualViewport.addEventListener('resize', scheduleReflow);
                } catch (e) {
                    // ignore
                }
            }

            setTimeout(function () {
                applyTimelinePositions();
            }, 0);
            setTimeout(function () {
                applyTimelinePositions();
            }, 250);
        }

        var resizeTimer = null;
        window.addEventListener('resize', function () {
            if (resizeTimer) clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                applyTimelinePositions();
            }, 120);
        });
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2" defer></script>
@endpush
