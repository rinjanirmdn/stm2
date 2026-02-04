@extends('layouts.app')

@section('title', 'Dashboard - Slot Time Management')
@section('page_title', 'Dashboard')
@section('body_class', 'st-app--dashboard')

@section('content')
    <div class="st-dashboard-slideshow" data-autoplay-ms="10000" data-active-index="0">
        <div class="st-dashboard-slideshow__nav" aria-label="Dashboard slides">
            <button type="button" class="st-dashboard-slideshow__btn" data-slide-prev aria-label="Previous slide">&lsaquo;</button>
            <div class="st-dashboard-slideshow__dots" role="tablist" aria-label="Dashboard slide selector">
                <button type="button" class="st-dashboard-slideshow__dot" data-slide-dot="0" aria-label="Slide 1" aria-current="true"></button>
                <button type="button" class="st-dashboard-slideshow__dot" data-slide-dot="1" aria-label="Slide 2" aria-current="false"></button>
                <button type="button" class="st-dashboard-slideshow__dot" data-slide-dot="2" aria-label="Slide 3" aria-current="false"></button>
                <button type="button" class="st-dashboard-slideshow__dot" data-slide-dot="3" aria-label="Slide 4" aria-current="false"></button>
                <button type="button" class="st-dashboard-slideshow__dot" data-slide-dot="4" aria-label="Slide 5" aria-current="false"></button>
            </div>
            <button type="button" class="st-dashboard-slideshow__btn" data-slide-next aria-label="Next slide">&rsaquo;</button>
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
                            <div class="st-form-field st-dashboard-range-field">
                                <label class="st-label">Range</label>
                                <div id="reportrange" class="st-dashboard-range-picker">
                                    <i class="fa fa-calendar"></i>&nbsp;
                                    <span></span> <i class="fa fa-caret-down"></i>
                                </div>
                                <input type="hidden" name="range_start" id="range_start" value="{{ $range_start ?? $today }}">
                                <input type="hidden" name="range_end" id="range_end" value="{{ $range_end ?? $today }}">
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
                            <input type="text" name="schedule_date" class="st-input" value="{{ $schedule_date ?? $today }}" placeholder="YYYY-MM-DD" autocomplete="off" readonly>
                        </div>
                        <div class="st-form-field">
                            <label class="st-label">From (HH:MM)</label>
                            <input type="time" name="time_from" class="st-input" value="{{ $time_from ?? '00:00' }}" placeholder="00:00" step="60">
                        </div>
                        <div class="st-form-field">
                            <label class="st-label">To (HH:MM)</label>
                            <input type="time" name="time_to" class="st-input" value="{{ $time_to ?? '23:59' }}" placeholder="23:59" step="60">
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
<script type="application/json" id="dashboard_schedule_data">{!! json_encode($schedule ?? []) !!}</script>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2" defer></script>
@vite(['resources/js/pages/dashboard.js'])
@endpush
