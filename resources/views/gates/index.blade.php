@extends('layouts.app')

@section('title', 'Gate Status Report - Slot Time Management')
@section('page_title', 'Gate Status Report')

@section('content')
    <div class="st-card" style="margin-bottom:12px;">
        <div class="st-flex-between" style="align-items:flex-end;gap:12px;flex-wrap:wrap;">
            <div>
                <h1 class="st-page-title">Gate Status Report</h1>
                <div style="font-size:12px;color:#6b7280;margin-top:4px;">
                    Summary of slot statuses per gate in the selected date range.
                </div>
            </div>

            <form method="GET" class="st-flex" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;">
                <input type="hidden" name="date_from" id="date_from_hidden" value="{{ $date_from ?? '' }}">
                <input type="hidden" name="date_to" id="date_to_hidden" value="{{ $date_to ?? '' }}">
                <div style="min-width:260px;">
                    <label class="st-label" style="font-size:12px;">Date Range</label>
                    <input type="text" id="gate_date_range" class="st-input" placeholder="Pilih rentang tanggal" value="{{ ($date_from ?? '') && ($date_to ?? '') ? ($date_from.' to '.$date_to) : '' }}">
                </div>
                <div>
                    <label class="st-label" style="font-size:12px;">Warehouse</label>
                    <select name="warehouse_id[]" class="st-select" multiple size="1" style="min-width:160px;">
                        @foreach ($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ in_array((string)$wh->id, array_map('strval', $warehouse_id ?? []), true) ? 'selected' : '' }}>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit" class="st-btn st-btn--primary">Apply</button>
                    <a href="{{ route('gates.index') }}" class="st-btn st-btn--secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <section class="st-row">
        <div class="st-col-12">
            <div class="st-card">
                <div class="st-table-wrapper">
                    <table class="st-table">
                        <thead>
                            <tr>
                                <th>Warehouse</th>
                                <th>Gate</th>
                                <th style="width:110px;">Scheduled</th>
                                <th style="width:110px;">Waiting</th>
                                <th style="width:130px;">In Progress</th>
                                <th style="width:120px;">Completed</th>
                                <th style="width:120px;">Cancelled</th>
                                <th style="width:110px;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                        @php
                            $totals = [
                                'scheduled' => 0,
                                'waiting' => 0,
                                'in_progress' => 0,
                                'completed' => 0,
                                'cancelled' => 0,
                                'total' => 0,
                            ];
                        @endphp

                        @forelse ($rows as $row)
                            @php
                                $scheduled = (int)($row['scheduled_count'] ?? 0);
                                $waiting = (int)($row['waiting_count'] ?? 0);
                                $inProgress = (int)($row['in_progress_count'] ?? 0);
                                $completed = (int)($row['completed_count'] ?? 0);
                                $cancelled = (int)($row['cancelled_count'] ?? 0);
                                $total = (int)($row['total_count'] ?? 0);

                                $totals['scheduled'] += $scheduled;
                                $totals['waiting'] += $waiting;
                                $totals['in_progress'] += $inProgress;
                                $totals['completed'] += $completed;
                                $totals['cancelled'] += $cancelled;
                                $totals['total'] += $total;
                            @endphp
                            <tr>
                                <td>{{ $row['warehouse_name'] ?? '-' }}</td>
                                <td>{{ $row['gate_label'] ?? '-' }}</td>
                                <td>{{ $scheduled }}</td>
                                <td>{{ $waiting }}</td>
                                <td>{{ $inProgress }}</td>
                                <td>{{ $completed }}</td>
                                <td>{{ $cancelled }}</td>
                                <td>{{ $total }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align:center;color:#6b7280;padding:12px 8px;">No data for selected range.</td>
                            </tr>
                        @endforelse

                        @if (!empty($rows))
                            <tr>
                                <td colspan="2" style="font-weight:600;">Total</td>
                                <td>{{ $totals['scheduled'] }}</td>
                                <td>{{ $totals['waiting'] }}</td>
                                <td>{{ $totals['in_progress'] }}</td>
                                <td>{{ $totals['completed'] }}</td>
                                <td>{{ $totals['cancelled'] }}</td>
                                <td>{{ $totals['total'] }}</td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    @can('gates.toggle')
        <section class="st-row" style="margin-top:12px;">
            <div class="st-col-12">
                <div class="st-card" style="margin-top:12px;">
                    <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:10px;">
                        <div>
                            <h2 class="st-page-title" style="font-size:18px;margin:0;">Manage Gates</h2>
                            <div style="font-size:12px;color:#6b7280;margin-top:4px;">
                                Activate or deactivate standby gates.
                            </div>
                        </div>
                    </div>

                    <div class="st-table-wrapper">
                        <table class="st-table">
                            <thead>
                                <tr>
                                    <th>Warehouse</th>
                                    <th style="width:160px;">Gate</th>
                                    <th style="width:130px;">Type</th>
                                    <th style="width:130px;">Status</th>
                                    <th style="width:160px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($gates as $g)
                                    @php
                                        $label = app(\App\Services\SlotService::class)->getGateDisplayName((string)($g->warehouse_code ?? ''), (string)($g->gate_number ?? ''));
                                        $isBackup = (int)($g->is_backup ?? 0) === 1;
                                        $isActive = (int)($g->is_active ?? 0) === 1;
                                    @endphp
                                    <tr>
                                        <td>{{ $g->warehouse_name ?? '-' }}</td>
                                        <td>{{ $label }}</td>
                                        <td>
                                            @if ($isBackup)
                                                <span class="st-table__status-badge st-status-idle">Standby</span>
                                            @else
                                                <span class="st-table__status-badge st-status-processing">Primary</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($isActive)
                                                <span class="st-table__status-badge st-status-on-time">Active</span>
                                            @else
                                                <span class="st-table__status-badge st-status-early">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($isBackup)
                                                <form method="POST" action="{{ route('gates.toggle', ['gateId' => $g->id]) }}" style="display:inline;">
                                                    @csrf
                                                    <button type="submit" class="st-btn st-btn--sm {{ $isActive ? 'st-btn--secondary' : '' }}">
                                                        {{ $isActive ? 'Deactivate' : 'Activate' }}
                                                    </button>
                                                </form>
                                            @else
                                                <span style="font-size:12px;color:#6b7280;">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" style="padding:14px;">No gates found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    @endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.flatpickr !== 'function') return;

    var dateRangeEl = document.getElementById('gate_date_range');
    var dateFromHidden = document.getElementById('date_from_hidden');
    var dateToHidden = document.getElementById('date_to_hidden');

    if (dateRangeEl && dateFromHidden && dateToHidden) {
        flatpickr(dateRangeEl, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            allowInput: true,
            clickOpens: true,
            theme: 'dark',
            defaultDate: [dateFromHidden.value || null, dateToHidden.value || null].filter(Boolean),
            onChange: function (selectedDates, dateStr, instance) {
                if (!selectedDates || !selectedDates.length) {
                    dateFromHidden.value = '';
                    dateToHidden.value = '';
                    return;
                }
                if (selectedDates.length === 1) {
                    var d0 = instance.formatDate(selectedDates[0], 'Y-m-d');
                    dateFromHidden.value = d0;
                    dateToHidden.value = d0;
                } else {
                    var dStart = instance.formatDate(selectedDates[0], 'Y-m-d');
                    var dEnd = instance.formatDate(selectedDates[1], 'Y-m-d');
                    dateFromHidden.value = dStart;
                    dateToHidden.value = dEnd;
                }
            }
        });
    }
});
</script>
@endpush
