@extends('layouts.app')

@section('title', 'Dashboard - Slot Time Management')
@section('page_title', 'Dashboard')

@section('content')
    <section class="st-row">
        <div class="st-col-12">
            <div class="st-card" id="analytics-tabs-card">
                <div class="st-card__header">
                    <div>
                        <h2 class="st-card__title">Analytics Range</h2>
                        <div class="st-card__subtitle">Date Range for Analytics (Overview / KPI / Bottleneck)</div>
                    </div>
                    <form method="GET" class="st-form-row" style="gap:8px;">
                        <input type="hidden" name="activity_date" value="{{ $activity_date ?? $today }}">
                        <input type="hidden" name="activity_warehouse" value="{{ $activity_warehouse ?? 0 }}">
                        <input type="hidden" name="activity_user" value="{{ $activity_user ?? 0 }}">
                        <input type="hidden" id="range_start" name="range_start" value="{{ $range_start }}">
                        <input type="hidden" id="range_end" name="range_end" value="{{ $range_end }}">
                        <div class="st-form-field" style="min-width:260px;">
                            <label class="st-label">Range</label>
                            <input type="text" id="analytics_range" class="st-input" placeholder="Select Date Range" value="{{ ($range_start ?? '') && ($range_end ?? '') ? ($range_start.' to '.$range_end) : ($range_start ?? $today) }}">
                        </div>
                        <div class="st-form-field" style="align-self:flex-end;min-width:120px;flex:0 0 auto;">
                            <button type="submit" class="st-btn st-btn--secondary">Apply</button>
                            <a href="{{ route('dashboard', ['range_start' => \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d'), 'range_end' => $today]) }}" class="st-btn st-btn--secondary">Reset</a>
                        </div>
                    </form>
                </div>
                <div style="display:grid;grid-template-columns:repeat(7, minmax(120px, 1fr));gap:8px;">
                    <div class="st-mini-card st-mini-card--with-info">
                        <button type="button" class="st-tooltip st-mini-card__info" aria-label="Info: Pending" title="Booking requests awaiting approval before entering the official schedule.">
                            <i class="fa-solid fa-info"></i>
                        </button>
                        <div class="st-text--small st-text--muted">Pending</div>
                        <div class="st-mini-card__value-row">
                            <div style="font-size:26px;font-weight:700;">{{ $pendingRange ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="st-mini-card st-mini-card--with-info">
                        <button type="button" class="st-tooltip st-mini-card__info" aria-label="Info: Scheduled" title="Slots already scheduled, but the truck has not arrived yet.">
                            <i class="fa-solid fa-info"></i>
                        </button>
                        <div class="st-text--small st-text--muted">Scheduled</div>
                        <div class="st-mini-card__value-row">
                            <div style="font-size:26px;font-weight:700;">{{ $scheduledRange ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="st-mini-card st-mini-card--with-info">
                        <button type="button" class="st-tooltip st-mini-card__info" aria-label="Info: Waiting" title="Slots where the truck has arrived and is waiting in the queue.">
                            <i class="fa-solid fa-info"></i>
                        </button>
                        <div class="st-text--small st-text--muted">Waiting</div>
                        <div class="st-mini-card__value-row">
                            <div style="font-size:26px;font-weight:700;">{{ $waitingRange ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="st-mini-card st-mini-card--with-info">
                        <button type="button" class="st-tooltip st-mini-card__info" aria-label="Info: In progress" title="Slots currently being processed at the gate (Loading/Unloading in progress).">
                            <i class="fa-solid fa-info"></i>
                        </button>
                        <div class="st-text--small st-text--muted">In Progress</div>
                        <div class="st-mini-card__value-row">
                            <div style="font-size:26px;font-weight:700;">{{ $activeRange ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="st-mini-card st-mini-card--with-info">
                        <button type="button" class="st-tooltip st-mini-card__info" aria-label="Info: Completed" title="Slots where the process is already finished.">
                            <i class="fa-solid fa-info"></i>
                        </button>
                        <div class="st-text--small st-text--muted">Completed</div>
                        <div class="st-mini-card__value-row">
                            <div style="font-size:26px;font-weight:700;">{{ $completedStatusRange ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="st-mini-card st-mini-card--with-info">
                        <button type="button" class="st-tooltip st-mini-card__info" aria-label="Info: Cancel" title="Cancelled slots.">
                            <i class="fa-solid fa-info"></i>
                        </button>
                        <div class="st-text--small st-text--muted">Cancel</div>
                        <div class="st-mini-card__value-row">
                            <div style="font-size:26px;font-weight:700;">{{ $cancelledRange ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="st-mini-card st-mini-card--with-info">
                        <button type="button" class="st-tooltip st-mini-card__info" aria-label="Info: Total" title="Total of all slots within the selected date range.">
                            <i class="fa-solid fa-info"></i>
                        </button>
                        <div class="st-text--small st-text--muted">Total</div>
                        <div class="st-mini-card__value-row">
                            <div style="font-size:26px;font-weight:700;">{{ $totalAllRange ?? 0 }}</div>
                        </div>
                    </div>
                </div>

                <div class="st-card__header" style="display:flex;justify-content:space-between;align-items:flex-end;gap:8px;flex-wrap:wrap;">
                    <div>
                        <br>
                        <h2 class="st-card__title">Analytics</h2>
                        <div class="st-card__subtitle">Performance & Bottleneck Summary</div>
                    </div>
                    <div class="st-tabbar" role="tablist" aria-label="Analytics tabs">
                        <button type="button" class="st-btn st-btn--secondary st-btn--sm st-tab-btn" data-tab="overview" aria-selected="true">Overview</button>
                        <button type="button" class="st-btn st-btn--ghost st-btn--sm st-tab-btn" data-tab="kpi" aria-selected="false">KPI</button>
                    </div>
                </div>

                <div class="st-tab-panel" data-tab-panel="overview" style="display:block;">
                    <div class="st-chart-grid">
                        <div class="st-chart-col-8">
                            <div class="st-chart-card">
                                <div class="st-chart-card__title" id="chart_trend_title">Completed Trend</div>
                                <div class="st-chart-wrap">
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
                                <div class="st-chart-card__title">Direction</div>
                                <div style="display:flex;flex-direction:column;gap:8px;">
                                    <div class="st-chart-wrap st-chart-wrap--direction">
                                        <canvas id="chart_direction"></canvas>
                                    </div>
                                    <div class="st-metric-row" style="grid-template-columns:1fr 1fr 1fr;">
                                        <div class="st-mini-card">
                                            <div class="st-text--small st-text--muted">Inbound</div>
                                            <div id="direction_inbound_value" style="font-size:16px;font-weight:700;">0</div>
                                        </div>
                                        <div class="st-mini-card">
                                            <div class="st-text--small st-text--muted">Outbound</div>
                                            <div id="direction_outbound_value" style="font-size:16px;font-weight:700;">0</div>
                                        </div>
                                        <div class="st-mini-card">
                                            <div class="st-text--small st-text--muted">Total</div>
                                            <div id="direction_total_value" style="font-size:16px;font-weight:700;">0</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="st-chart-col-5">
                            <div class="st-chart-card" style="height:100%;">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                                    <div class="st-chart-card__title" style="margin:0;white-space:nowrap;">Bottleneck (Avg Waiting)</div>
                                    <select id="bottleneck_dir" class="st-select" style="max-width:100px;font-size:11px;height:28px;">
                                        <option value="all">All</option>
                                        <option value="inbound">Inbound</option>
                                        <option value="outbound">Outbound</option>
                                    </select>
                                </div>
                                <div style="display:grid; grid-template-columns: 1fr auto; gap:16px; align-items: center;">
                                    <div class="st-chart-wrap" style="height:160px;">
                                        <canvas id="chart_bottleneck"></canvas>
                                    </div>
                                    <div style="display:flex; flex-direction:column; gap:8px; border-left:1px solid #f1f5f9; padding-left:16px; min-width:100px;">
                                        <div class="st-chart-card__title" style="font-size:10px; margin-bottom:4px; text-transform:uppercase; color:#94a3b8;">Top Wait</div>
                                        <div class="st-mini-card" style="padding:6px;">
                                            <div class="st-text--small st-text--muted" style="font-size:8px;">Location</div>
                                            <div id="bottleneck_top_label" style="font-size:13px; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">-</div>
                                        </div>
                                        <div class="st-mini-card" style="padding:6px;">
                                            <div class="st-text--small st-text--muted" style="font-size:8px;">Avg Wait</div>
                                            <div id="bottleneck_top_avg" style="font-size:13px; font-weight:700;">0</div>
                                        </div>
                                        <div class="st-mini-card" style="padding:6px;">
                                            <div class="st-text--small st-text--muted" style="font-size:8px;">Qty</div>
                                            <div id="bottleneck_top_slots" style="font-size:13px; font-weight:700;">0</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="st-text--small st-text--muted" style="margin-top:8px; font-size:10px;">Top 20, Threshold {{ (int)($bottleneckThresholdMinutes ?? 30) }} Min</div>
                            </div>
                        </div>

                        <div class="st-chart-col-7">
                            <div class="st-chart-card" style="height:100%;">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                                    <div class="st-chart-card__title" style="margin:0;">Performance by Truck Type</div>
                                    <select id="lead_proc_unit" class="st-select" style="max-width:90px;font-size:10px;height:24px;padding:2px 4px;">
                                        <option value="minute">Minutes</option>
                                        <option value="hour">Hours</option>
                                    </select>
                                </div>
                                <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:6px;">
                                        @foreach($avgTimesByTruckType ?? [] as $t)
                                        @php
                                            $truckType = (string) data_get($t, 'truck_type', '-');
                                            $totalCount = (int) data_get($t, 'total_count', 0);
                                            $avgLeadMinutesByTruck = (float) data_get($t, 'avg_lead_minutes', 0);
                                            $avgProcessMinutesByTruck = (float) data_get($t, 'avg_process_minutes', 0);
                                        @endphp
                                        <div style="padding:4px; border:1px solid #f1f5f9; background:#fff; border-radius:8px; display:flex; flex-direction:column; justify-content:space-between; min-width:0;">
                                            <div style="font-weight:700; color:#1e293b; font-size:10px; margin-bottom:4px; border-bottom:1px solid #f8fafc; padding-bottom:3px; display:flex; justify-content:space-between; align-items:center; gap:4px;">
                                                <span style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; min-width:0;" title="{{ $truckType }}">{{ $truckType }}</span>
                                                <span style="font-size:9px; color:#94a3b8; font-weight:400; flex-shrink:0;">{{ $totalCount }}</span>
                                            </div>
                                            <div style="display:flex; flex-direction:column; gap:4px;">
                                                <div style="background:#f0f9ff; padding:3px 4px; border-radius:4px;">
                                                    <div style="font-size:7px; color:#0369a1; text-transform:uppercase; font-weight:600; line-height:1;">Avg Lead Time</div>
                                                    <div class="lead-avg-truck" data-minutes="{{ $avgLeadMinutesByTruck }}" style="font-weight:800; color:#0369a1; font-size:11px;">
                                                        @if($totalCount > 0)
                                                            {{ number_format($avgLeadMinutesByTruck, 1) }}<span style="font-size:8px;">m</span>
                                                        @else
                                                            -
                                                        @endif
                                                    </div>
                                                </div>
                                                <div style="background:#f5f3ff; padding:3px 4px; border-radius:4px;">
                                                    <div style="font-size:7px; color:#5b21b6; text-transform:uppercase; font-weight:600; line-height:1;">Avg Process Time</div>
                                                    <div class="proc-avg-truck" data-minutes="{{ $avgProcessMinutesByTruck }}" style="font-weight:800; color:#5b21b6; font-size:11px;">
                                                        @if($totalCount > 0)
                                                            {{ number_format($avgProcessMinutesByTruck, 1) }}<span style="font-size:8px;">m</span>
                                                        @else
                                                            -
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div id="lead_proc_empty" style="display:none;"></div>
                                <div id="lead_avg_value" style="display:none;" data-minutes="{{ $avgLeadMinutes }}"></div>
                                <div id="proc_avg_value" style="display:none;" data-minutes="{{ $avgProcessMinutes }}"></div>
                            </div>
                        </div>


                    </div>
                </div>

                <div class="st-tab-panel" data-tab-panel="kpi" style="display:none;">
                    <div class="st-chart-grid">
                        <div class="st-chart-col-4">
                            <div class="st-chart-card">
                                <div class="st-flex-between-center st-flex-wrap st-gap-2">
                                    <div class="st-chart-card__title st-mb-0">On Time vs Late</div>
                                    <select id="on_time_dir" class="st-select st-w-160">
                                        <option value="all">All Direction</option>
                                        <option value="inbound">Inbound (Blue)</option>
                                        <option value="outbound">Outbound (Orange)</option>
                                    </select>
                                </div>
                                <div class="st-chart-wrap st-chart-wrap--sm" style="margin-top:38px;">
                                    <canvas id="chart_on_time"></canvas>
                                </div>
                                <div class="st-metric-row" style="margin-top:10px;">
                                    <div class="st-mini-card"><div class="st-text--small st-text--muted">On Time</div><div id="on_time_value" style="font-size:18px;font-weight:800;">0</div></div>
                                    <div class="st-mini-card"><div class="st-text--small st-text--muted">Late</div><div id="late_value" style="font-size:18px;font-weight:800;">0</div></div>
                                    <div class="st-mini-card"><div class="st-text--small st-text--muted">Total</div><div id="on_time_total" style="font-size:18px;font-weight:800;">0</div></div>
                                </div>
                            </div>
                        </div>

                        <div class="st-chart-col-4">
                            <div class="st-chart-card">
                                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;">
                                    <div class="st-chart-card__title" style="margin:0;">Target Achievement</div>
                                    <div style="display:flex;justify-content:flex-end;align-items:center;gap:6px;flex-wrap:wrap;">
                                        <select id="target_dir" class="st-select" style="max-width:160px;min-width:140px;flex:1 1 140px;">
                                            <option value="all">All Direction</option>
                                            <option value="inbound">Inbound</option>
                                            <option value="outbound">Outbound</option>
                                        </select>
                                        <select id="target_wh" class="st-select" style="max-width:160px;min-width:140px;flex:1 1 140px;">
                                            <option value="all">All WH</option>
                                            @foreach (($kpiWarehouses ?? []) as $whCode)
                                                <option value="{{ $whCode }}">{{ $whCode }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="st-chart-wrap st-chart-wrap--sm st-mt-2">
                                    <canvas id="chart_target_achievement"></canvas>
                                </div>
                                <div class="st-metric-row" style="margin-top:10px;">
                                    <div class="st-mini-card"><div class="st-text--small st-text--muted">Achieve</div><div id="target_achieve_value" style="font-size:18px;font-weight:800;">0</div></div>
                                    <div class="st-mini-card"><div class="st-text--small st-text--muted">Not Achieve</div><div id="target_not_achieve_value" style="font-size:18px;font-weight:800;">0</div></div>
                                    <div class="st-mini-card"><div class="st-text--small st-text--muted">Total</div><div id="target_total_eval" style="font-size:18px;font-weight:800;">0</div></div>
                                </div>
                            </div>
                        </div>

                        <div class="st-chart-col-4">
                            <div class="st-chart-card">
                                <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
                                    <div class="st-chart-card__title" style="margin:0;">Completion Rate</div>

                                    <select id="completion_dir" class="st-select" style="max-width:160px;">
                                            <option value="all">All Direction</option>
                                            <option value="inbound">Inbound</option>
                                            <option value="outbound">Outbound</option>
                                    </select>
                                </div>
                                <div style="display:flex;justify-content:flex-end;">
                                    <select id="completion_wh" class="st-select" style="max-width:160px;">
                                            <option value="all">All WH</option>
                                            @foreach (($completionWarehouses ?? []) as $whCode)
                                                <option value="{{ $whCode }}">{{ $whCode }}</option>
                                            @endforeach
                                    </select>
                                </div>
                                <div class="st-chart-wrap st-chart-wrap--sm" style="margin-top:8px;">
                                    <canvas id="chart_completion_rate"></canvas>
                                </div>
                                <div class="st-metric-row" style="margin-top:10px;">
                                    <div class="st-mini-card"><div class="st-text--small st-text--muted">Rate</div><div id="completion_rate_value" style="font-size:18px;font-weight:800;">0%</div></div>
                                    <div class="st-mini-card"><div class="st-text--small st-text--muted">Completed</div><div id="completion_completed_value" style="font-size:18px;font-weight:800;">0</div></div>
                                    <div class="st-mini-card"><div class="st-text--small st-text--muted">Total</div><div id="completion_total_value" style="font-size:18px;font-weight:800;">0</div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    @php
        $totalEval = (int)($achieveRange ?? 0) + (int)($notAchieveRange ?? 0);
        $achPct = $totalEval > 0 ? (int) round(((int)($achieveRange ?? 0) / $totalEval) * 100) : 0;
        $notAchPct = $totalEval > 0 ? (int) round(((int)($notAchieveRange ?? 0) / $totalEval) * 100) : 0;
    @endphp







    <section class="st-row" style="margin-top:8px;">
        <div class="st-col-12">
            <div class="st-card" style="padding:12px;">
                <div class="st-card__header" style="display:flex;justify-content:space-between;align-items:flex-end;gap:8px;flex-wrap:wrap;">
                    <div>
                        <h2 class="st-card__title">24h Timeline</h2>

                        <div class="st-text--small st-text--muted">Slot Availability (Real-time Visual)</div>
                    </div>
                    <form method="GET" id="schedule-filter-form" class="st-form-row" style="margin-top:4px;gap:8px;">
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
                            <input type="text" name="schedule_from" class="st-input" value="{{ $schedule_from ?? '' }}" placeholder="HH:MM">
                        </div>
                        <div class="st-form-field">
                            <label class="st-label">To (HH:MM)</label>
                            <input type="text" name="schedule_to" class="st-input" value="{{ $schedule_to ?? '' }}" placeholder="HH:MM">
                        </div>
                        <div class="st-form-field">
                            <label class="st-label">Gate (Timeline)</label>
                            <select name="timeline_gate" class="st-select">
                                @php $timelineGateFilter = (int) request()->query('timeline_gate', 0); @endphp
                                <option value="0">All</option>
                                @foreach (($gateCards ?? []) as $g)
                                    <option value="{{ (int)($g['gate_id'] ?? 0) }}" {{ $timelineGateFilter === (int)($g['gate_id'] ?? 0) ? 'selected' : '' }}>{{ $g['title'] ?? '-' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="st-form-field" style="align-self:flex-end;">
                            <button type="submit" class="st-btn st-btn--secondary">Apply</button>
                            <a href="{{ route('dashboard', ['range_start' => $range_start ?? $today, 'range_end' => $range_end ?? $today, 'activity_date' => $activity_date ?? $today, 'activity_warehouse' => $activity_warehouse ?? 0, 'activity_user' => $activity_user ?? 0]) }}" class="st-btn st-btn--secondary">Reset</a>
                        </div>
                    </form>
                </div>




                @php
                    $timelineDate = $schedule_date ?? $today;
                    $timelineGateFilter2 = (int) request()->query('timeline_gate', 0);
                    $timelineStartHour = 7;
                    $timelineHours = range(7, 23);
                @endphp

                    <div style="margin-top: 8px; width: 100%;">
                        <div
                            class="st-timeline"
                            id="dashboard-timeline"
                            style="min-width: 0;"
                            data-date="{{ $timelineDate }}"
                            data-start-hour="{{ (int) $timelineStartHour }}"
                            data-route-view="{{ route('slots.show', ['slotId' => 0], false) }}"
                            data-route-arrival="{{ route('slots.arrival', ['slotId' => 0], false) }}"
                            data-route-start="{{ route('slots.start', ['slotId' => 0], false) }}"
                            data-route-complete="{{ route('slots.complete', ['slotId' => 0], false) }}"
                            data-route-cancel="{{ route('slots.cancel', ['slotId' => 0], false) }}"
                        >
                            <div class="st-timeline__header">
                                <div class="st-timeline__header-left" style="flex-direction:column;align-items:flex-start;justify-content:center;line-height:1.2;">
                                    <div>Gate</div>
                                </div>
                                <div style="padding:10px 0;font-size:11px;font-weight:700;color:#374151;border-right:1px solid #e5e7eb;position:sticky;left:70px;background:#ffffff;z-index:20;display:flex;align-items:center;justify-content:flex-start;padding-left:8px;">
                                    Lane
                                </div>
                                <div class="st-timeline__header-grid">
                                    @foreach ($timelineHours as $h)
                                        @php
                                            $hInt = (int) $h;
                                            $minute = $hInt * 60;
                                        @endphp
                                        <div class="st-timeline__hour" data-minute="{{ (int) $minute }}"><span>{{ str_pad((string)$hInt, 2, '0', STR_PAD_LEFT) }}</span></div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="st-timeline__body">
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
                                            <div style="font-size:11px;font-weight:600;">{{ $gateTitle }}</div>
                                            <div class="st-text--small st-text--muted" style="font-size:9px;">{{ $g['status_label'] ?? 'Idle' }}</div>
                                        </div>
                                        <div style="border-right:1px solid #e5e7eb;position:sticky;left:70px;background:#ffffff;z-index:20;display:flex;flex-direction:column;font-size:9px;color:#6b7280;font-weight:600;">
                                            <div style="flex:1;display:flex;align-items:center;justify-content:flex-start;padding-left:8px;border-bottom:1px dashed #f3f4f6;">Planned</div>
                                            <div style="flex:1;display:flex;align-items:center;justify-content:flex-start;padding-left:8px;">Actual</div>
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
                    <div class="st-timeline-meta" style="margin-top:8px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">

                        <!-- Status Legend -->
                        <div class="st-timeline-legend" aria-label="Timeline color legend" style="margin-top:0;">
                            <span class="st-timeline-legend__item"><span class="st-timeline-legend__dot st-timeline-legend__dot--scheduled"></span> Scheduled</span>
                            <span class="st-timeline-legend__item"><span class="st-timeline-legend__dot st-timeline-legend__dot--waiting"></span> Waiting</span>
                            <span class="st-timeline-legend__item"><span class="st-timeline-legend__dot st-timeline-legend__dot--in_progress"></span> In Progress</span>
                            <span class="st-timeline-legend__item"><span class="st-timeline-legend__dot st-timeline-legend__dot--completed"></span> Completed</span>
                        </div>
                    </div>

                @php
                    $processStatusCounts = [
                        'pending' => 0,
                        'scheduled' => 0,
                        'waiting' => 0,
                        'in_progress' => 0,
                        'completed' => 0,
                        'cancelled' => 0,
                    ];
                    foreach (($schedule ?? []) as $r) {
                        $st0 = trim(strtolower((string)($r['status'] ?? 'scheduled')));
                        if ($st0 === 'arrived') {
                            $st0 = 'waiting';
                        }
                        if (in_array($st0, ['pending_approval', 'pending_vendor_confirmation'], true)) {
                            $processStatusCounts['pending']++;
                        } elseif (isset($processStatusCounts[$st0])) {
                            $processStatusCounts[$st0]++;
                        }
                    }
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
                        <div class="st-flex-between-center st-flex-wrap st-gap-2">
                            <div>
                                <h3 class="st-card__title">Schedule</h3>
                                <div class="st-card__subtitle">Trucks, ETA, Gates, Status, and Estimated Finish</div>
                            </div>
                            <div style="display:flex; flex-direction:column; align-items:flex-end; gap:4px;">
                                <div class="st-text--small st-text--muted" style="text-align:right;">Use filters to adjust Date/Time/Gate.</div>
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
                                $st = (string)($row['status'] ?? 'scheduled');
                                $stLabel = ucwords(str_replace('_',' ', $st));

                                $badgeMap = [
                                    'scheduled' => 'bg-secondary',
                                    'waiting' => 'bg-waiting',
                                    'arrived' => 'bg-info',
                                    'in_progress' => 'bg-in_progress',
                                    'completed' => 'bg-completed',
                                    'cancelled' => 'bg-danger',
                                    'pending_approval' => 'bg-pending_approval',
                                ];
                                $badgeClass = $badgeMap[$st] ?? 'bg-secondary';
                                if ($st === 'arrived') {
                                    $stLabel = 'Waiting'; // Map arrive to Waiting label as per other views
                                }

                                $performance = (string)($row['performance'] ?? '');
                                $priority = (string)($row['priority'] ?? 'low');
                            @endphp
                            <tr>
                                <td>{{ $row['po_number'] ?? ('Slot #' . (int)($row['id'] ?? 0)) }}</td>
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
                                    <div class="tw-actionbar">
                                        <a href="{{ route('slots.show', ['slotId' => $row['id']]) }}" class="tw-action" data-tooltip="View" aria-label="View">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        @if ($st === 'scheduled')
                                            <a href="{{ route('slots.arrival', ['slotId' => $row['id']]) }}" class="tw-action" data-tooltip="Arrival" aria-label="Arrival">
                                                <i class="fa-solid fa-truck"></i>
                                            </a>
                                            @can('slots.cancel')
                                            <a href="{{ route('slots.cancel', ['slotId' => $row['id']]) }}" class="tw-action tw-action--danger" data-tooltip="Cancel" aria-label="Cancel" data-confirm="Are you sure you want to cancel this slot?">
                                                <i class="fa-solid fa-xmark"></i>
                                            </a>
                                            @endcan
                                        @elseif (in_array($st, ['arrived', 'waiting'], true))
                                            <a href="{{ route('slots.start', ['slotId' => $row['id']]) }}" class="tw-action tw-action--primary" data-tooltip="Start" aria-label="Start">
                                                <i class="fa-solid fa-play"></i>
                                            </a>
                                        @elseif ($st === 'in_progress')
                                            <a href="{{ route('slots.complete', ['slotId' => $row['id']]) }}" class="tw-action tw-action--primary" data-tooltip="Complete" aria-label="Complete">
                                                <i class="fa-solid fa-check"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" style="padding:12px;">No Schedule for Selected Filter</td></tr>
                        @endforelse
                        </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section class="st-row" style="margin-top:8px;">
        <div class="st-col-12">
            <div class="st-card" style="padding:12px;">
                <div class="st-card__header" style="display:flex;justify-content:space-between;align-items:flex-end;gap:8px;flex-wrap:wrap;">
                    <div>
                        <h2 class="st-card__title">Activity Logs</h2>
                        <div class="st-card__subtitle">Status Changes and Events by Date, Warehouse, and User</div>
                    </div>
                    <div style="display:flex;gap:6px;align-items:flex-end;flex-wrap:wrap;">
                    <form method="GET" id="activity-filter-form" class="st-form-row" style="margin-top:4px;gap:8px;">
                        <input type="hidden" name="range_start" value="{{ $range_start ?? $today }}">
                        <input type="hidden" name="range_end" value="{{ $range_end ?? $today }}">
                        <div class="st-form-field">
                            <label class="st-label">Date</label>
                            <input type="text" name="activity_date" class="st-input flatpickr-date" value="{{ $activity_date ?? $today }}" placeholder="YYYY-MM-DD">
                        </div>
                        <div class="st-form-field">
                            <label class="st-label">Warehouse</label>
                            <select name="activity_warehouse" class="st-select">
                                <option value="0">All</option>
                                @foreach (($activityWarehouses ?? []) as $wh)
                                    <option value="{{ (int) data_get($wh, 'id', 0) }}" {{ (int)($activity_warehouse ?? 0) === (int) data_get($wh, 'id', 0) ? 'selected' : '' }}>{{ (string) data_get($wh, 'name', '-') }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="st-form-field">
                            <label class="st-label">User</label>
                            <select name="activity_user" class="st-select">
                                <option value="0">All</option>
                                @foreach (($activityUsers ?? []) as $u)
                                    <option value="{{ (int) data_get($u, 'id', 0) }}" {{ (int)($activity_user ?? 0) === (int) data_get($u, 'id', 0) ? 'selected' : '' }}>{{ (string) data_get($u, 'nik', '-') }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="st-form-field" style="align-self:flex-end;">
                            <button type="submit" class="st-btn st-btn--secondary">Apply</button>
                        </div>
                    </form>
                    </div>
                </div>

                <div class="st-table-wrapper" style="max-height:280px;">
                    <table class="st-table">
                        <thead>
                            <tr>
                                <th style="width:140px;">Time</th>
                                <th style="width:180px;">Warehouse</th>
                                <th style="width:140px;">User</th>
                                <th>Activity</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse (($recentActivities ?? []) as $act)
                            <tr>
                                <td>{{ data_get($act, 'created_at', '') }}</td>
                                <td>{{ data_get($act, 'warehouse_name', '-') }}</td>
                                <td>{{ data_get($act, 'nik', '-') }}</td>
                                <td>
                                    @php
                                        $activityType = (string) data_get($act, 'activity_type', '');
                                        $activityLabel = ucwords(str_replace('_',' ', $activityType));
                                        $isLateArrival = ($activityType === 'late_arrival');

                                        // Fix capitalization and gate letters in description
                                        $description = data_get($act, 'description', '');
                                        $description = preg_replace_callback('/\bGate\s+([a-z])/', function($matches) {
                                            return 'Gate ' . strtoupper($matches[1]);
                                        }, $description);
                                        $description = preg_replace_callback('/\b([a-z])\w+/', function($matches) {
                                            $word = $matches[0];
                                            $conjunctions = ['and', 'or', 'but', 'for', 'nor', 'on', 'at', 'to', 'from', 'with', 'in'];
                                            if (in_array(strtolower($word), $conjunctions)) {
                                                return strtolower($word);
                                            }
                                            return ucfirst($word);
                                        }, $description);
                                    @endphp
                                    <strong style="{{ $isLateArrival ? 'color: #dc2626;' : '' }}">{{ $activityLabel }}</strong>
                                    - {{ $description }}
                                    @if (!empty(data_get($act, 'truck_number')))
                                        (PO {{ data_get($act, 'truck_number') }})
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" style="padding:12px;">No Activities for Selected Filter.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <div class="st-timeline-modal" id="timeline-modal" aria-hidden="true">
        <div class="st-timeline-modal__backdrop" data-modal-close></div>
        <div class="st-timeline-modal__panel" role="dialog" aria-modal="true" aria-label="Slot details">
            <div class="st-timeline-modal__header">
                <div>
                    <div id="timeline-modal-title" style="font-size:14px;font-weight:700;">Slot</div>
                    <div id="timeline-modal-subtitle" class="st-text--small st-text--muted">-</div>
                </div>
                <button type="button" class="st-btn st-btn--ghost st-btn--sm" data-modal-close>Close</button>
            </div>
            <div class="st-timeline-modal__body">
                <div class="st-mini-card" style="margin-bottom:10px;">
                    <div class="st-text--small st-text--muted">Status</div>
                    <div id="timeline-modal-status" style="font-size:18px;font-weight:700;">-</div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    <div>
                        <div class="st-text--small st-text--muted">Vendor</div>
                        <div id="timeline-modal-vendor" style="font-size:13px;font-weight:600;">-</div>
                    </div>
                    <div>
                        <div class="st-text--small st-text--muted">Gate</div>
                        <div id="timeline-modal-gate" style="font-size:13px;font-weight:600;">-</div>
                    </div>
                    <div>
                        <div class="st-text--small st-text--muted">ETA</div>
                        <div id="timeline-modal-eta" style="font-size:13px;font-weight:600;">-</div>
                    </div>
                    <div>
                        <div class="st-text--small st-text--muted">Est. Finish</div>
                        <div id="timeline-modal-finish" style="font-size:13px;font-weight:600;">-</div>
                    </div>
                </div>
            </div>
            <div class="st-timeline-modal__footer" style="display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end;">
                <a href="#" id="timeline-modal-view" class="st-btn st-btn--secondary st-btn--sm">View</a>
                <a href="#" id="timeline-modal-arrival" class="st-btn st-btn--primary st-btn--sm" style="display:none;">Arrival</a>
                <a href="#" id="timeline-modal-start" class="st-btn st-btn--primary st-btn--sm" style="display:none;">Start</a>
                <a href="#" id="timeline-modal-complete" class="st-btn st-btn--primary st-btn--sm" style="display:none;">Complete</a>
                <a href="#" id="timeline-modal-cancel" class="st-btn st-btn--sm st-btn--danger" data-confirm="Are you sure you want to cancel this slot?" style="display:none;">Cancel</a>
            </div>
        </div>
    </div>
<style>
@media (min-width: 769px) {
    .st-timeline-modal__panel {
        left: 50% !important;
        top: 50% !important;
        right: auto !important;
        transform: translate(-50%, -50%);
    }
}
</style>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Scroll to timeline section if URL contains filter parameters
    if (window.location.search.includes('schedule_date') ||
        window.location.search.includes('timeline_gate') ||
        window.location.search.includes('schedule_from') ||
        window.location.search.includes('schedule_to')) {

        var timelineSection = document.querySelector('#dashboard-timeline');
        if (timelineSection) {
            setTimeout(function() {
                timelineSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
        }
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
        var timelineSection = document.querySelector('#dashboard-timeline');
        if (timelineSection) {
            setTimeout(function() {
                timelineSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 300);
        }
    }

    if (typeof window.flatpickr !== 'function') return;
    try {
        // Use global holiday helper
        var globalHolidayData = typeof window.getIndonesiaHolidays === 'function' ? window.getIndonesiaHolidays() : {};

        var flatpickrConfig = {
            dateFormat: "Y-m-d",
            allowInput: true,
            clickOpens: true,
            disableMobile: true,
            onDayCreate: function(dObj, dStr, fp, dayElem) {
                const dateStr = fp.formatDate(dayElem.dateObj, "Y-m-d");
                if (globalHolidayData[dateStr]) {
                    dayElem.classList.add('is-holiday');
                    dayElem.title = globalHolidayData[dateStr];
                }
            }
        };

        // Initialize generic date inputs
        flatpickr(".flatpickr-date, input[type='date']", flatpickrConfig);

            // Initialize Analytics Range Picker
            var rangeInput = document.getElementById('analytics_range');
            if (rangeInput) {
                var startVal = document.getElementById('range_start').value;
                var endVal = document.getElementById('range_end').value;

                // Use already defined globalHolidayData
                var hData = globalHolidayData || {};

                flatpickr(rangeInput, {
                    mode: 'range',
                    dateFormat: 'Y-m-d',
                    disableMobile: true,
                    defaultDate: [startVal, endVal],
                    onDayCreate: function(dObj, dStr, fp, dayElem) {
                        const dateStr = fp.formatDate(dayElem.dateObj, "Y-m-d");
                        if (hData[dateStr]) {
                            dayElem.classList.add('is-holiday');
                            dayElem.title = hData[dateStr];
                        }
                    },
                    onClose: function(selectedDates, dateStr, instance) {
                        if (selectedDates.length === 2) {
                            var s = instance.formatDate(selectedDates[0], 'Y-m-d');
                            var e = instance.formatDate(selectedDates[1], 'Y-m-d');
                            document.getElementById('range_start').value = s;
                            document.getElementById('range_end').value = e;

                            // Format for display: 2026-01-01 to 2026-01-31
                            rangeInput.value = s + ' to ' + e;
                        }
                    }
                });
            }
    } catch (e) {
        // ignore
    }

    const timeline = document.getElementById('dashboard-timeline');
    const infoContent = document.getElementById('timeline-info-content');
    const defaultInfoHtml = infoContent ? infoContent.innerHTML : '';
    let currentBlock = null;
    let lockedBlock = null;
    let hoverCard = null;
    let hoverHideTimer = null;

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
                            <dl class="timeline-tooltip-grid" style="font-size:13px;color:#111827;">
                                <dt style="color:#111827;">PO:</dt> <dd style="color:#111827;">${escHtml(po)}</dd>
                                <dt style="color:#111827;">PT:</dt> <dd style="color:#111827;">${escHtml(vendorLabel)}</dd>
                                <dt style="color:#111827;">Direction:</dt> <dd style="color:#111827;">${escHtml(direction)}</dd>
                                <dt style="color:#111827;">Priority:</dt> <dd style="color:#111827;">${escHtml(priority)}</dd>
                                ${performance ? `<dt style="color:#111827;">KPI:</dt> <dd style="color:#111827;">${escHtml(performance)}</dd>` : ''}
                            </dl>
                        `;
                    } else {
                        newContent = `
                            <dl class="timeline-tooltip-grid" style="font-size:13px;color:#111827;">
                                <dt style="color:#111827;">PO:</dt> <dd style="color:#111827;">${escHtml(po)}</dd>
                                <dt style="color:#111827;">PT:</dt> <dd style="color:#111827;">${escHtml(vendorLabel)}</dd>
                                <dt style="color:#111827;">Direction:</dt> <dd style="color:#111827;">${escHtml(direction)}</dd>
                                <dt style="color:#111827;">Status:</dt> <dd style="color:#111827;">${escHtml(status)}</dd>
                                <dt style="color:#111827;">Arrival:</dt> <dd style="color:#111827;">${escHtml(arrival)}</dd>
                                <dt style="color:#111827;">Start:</dt> <dd style="color:#111827;">${escHtml(start)}</dd>
                                <dt style="color:#111827;">End:</dt> <dd style="color:#111827;">${escHtml(end)}</dd>
                                ${performance ? `<dt style="color:#111827;">KPI:</dt> <dd style="color:#111827;">${escHtml(performance)}</dd>` : ''}
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

    function setTab(tab) {
        tabButtons.forEach(function (b) {
            var isActive = b.getAttribute('data-tab') === tab;
            b.classList.toggle('st-btn--secondary', isActive);
            b.classList.toggle('st-btn--ghost', !isActive);
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

    if (tabButtons && tabButtons.length) {
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

    function calcProcessStatus(dir) {
        var counts = {
            pending: 0,
            scheduled: 0,
            waiting: 0,
            in_progress: 0,
            completed: 0,
            cancelled: 0
        };

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
                padding: { top: 34, right: 14, left: 10, bottom: 8 }
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
        var total = 0;
        var completed = 0;

        (completionData || []).forEach(function (r) {
            var rDir = (r && r.direction) ? String(r.direction) : '';
            var rWh = (r && r.warehouse_code) ? String(r.warehouse_code) : '';
            if (dir && dir !== 'all' && rDir !== dir) return;
            if (wh && wh !== 'all' && rWh !== wh) return;
            total += parseInt(r.total || 0, 10);
            completed += parseInt(r.completed || 0, 10);
        });

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
        var onV = 0;
        var lateV = 0;
        (onTimeWarehouseData || []).forEach(function (r) {
            var rDir = (r && r.direction) ? String(r.direction) : '';
            var rWh = (r && r.warehouse_code) ? String(r.warehouse_code) : '';
            if (dir && dir !== 'all' && rDir !== dir) return;
            if (wh && wh !== 'all' && rWh !== wh) return;
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
        var a = 0;
        var n = 0;
        (targetWarehouseData || []).forEach(function (r) {
            var rDir = (r && r.direction) ? String(r.direction) : '';
            var rWh = (r && r.warehouse_code) ? String(r.warehouse_code) : '';
            if (dir && dir !== 'all' && rDir !== dir) return;
            if (wh && wh !== 'all' && rWh !== wh) return;
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

    var onTimeSelect = document.getElementById('on_time_dir');
    var onTimeWhSel = document.getElementById('on_time_wh');
    function getOnTimeDir() { return onTimeSelect ? onTimeSelect.value : 'all'; }
    function getOnTimeWh() { return onTimeWhSel ? onTimeWhSel.value : 'all'; }
    if (onTimeSelect) {
        onTimeSelect.addEventListener('change', function () {
            updateOnTimeUI(getOnTimeDir(), getOnTimeWh());
        });
    }
    if (onTimeWhSel) {
        onTimeWhSel.addEventListener('change', function () {
            updateOnTimeUI(getOnTimeDir(), getOnTimeWh());
        });
    }
    updateOnTimeUI(getOnTimeDir(), getOnTimeWh());

    var targetSelect = document.getElementById('target_dir');
    var targetWhSel = document.getElementById('target_wh');
    function getTargetDir() { return targetSelect ? targetSelect.value : 'all'; }
    function getTargetWh() { return targetWhSel ? targetWhSel.value : 'all'; }
    if (targetSelect) {
        targetSelect.addEventListener('change', function () {
            updateTargetUI(getTargetDir(), getTargetWh());
        });
    }
    if (targetWhSel) {
        targetWhSel.addEventListener('change', function () {
            updateTargetUI(getTargetDir(), getTargetWh());
        });
    }
    updateTargetUI(getTargetDir(), getTargetWh());

    var completionDirSel = document.getElementById('completion_dir');
    var completionWhSel = document.getElementById('completion_wh');
    function getCompletionDir() { return completionDirSel ? completionDirSel.value : 'all'; }
    function getCompletionWh() { return completionWhSel ? completionWhSel.value : 'all'; }
    if (completionDirSel) {
        completionDirSel.addEventListener('change', function () {
            updateCompletionUI(getCompletionDir(), getCompletionWh());
        });
    }
    if (completionWhSel) {
        completionWhSel.addEventListener('change', function () {
            updateCompletionUI(getCompletionDir(), getCompletionWh());
        });
    }
    updateCompletionUI(getCompletionDir(), getCompletionWh());

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
                    timeline.querySelectorAll('.st-timeline__hour[data-minute]').forEach(function (el) {
                        var minute = parseInt(el.getAttribute('data-minute') || '0', 10);
                        if (isNaN(minute)) minute = 0;
                        var rel = minute - s.startMins;
                        var leftPx = Math.round(Math.max(0, rel) * s.pxPerMin);
                        if (el._stLeftPx !== leftPx) {
                            el._stLeftPx = leftPx;
                            el.style.left = String(leftPx) + 'px';
                        }
                    });

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

                        var leftPx = Math.round(Math.max(0, relLeft) * s.pxPerMin);
                        var widthPx = Math.round(Math.max(1, relWidth) * s.pxPerMin);

                        if (el._stLeftPx !== leftPx) {
                            el._stLeftPx = leftPx;
                            el.style.left = String(leftPx) + 'px';
                        }
                        if (el._stWidthPx !== widthPx) {
                            el._stWidthPx = widthPx;
                            el.style.width = String(widthPx) + 'px';
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
