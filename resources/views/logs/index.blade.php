@extends('layouts.app')

@section('title', 'Activity Logs - Slot Time Management')
@section('page_title', 'Activity Logs')

@section('content')
    <div class="st-card" style="margin-bottom:12px;">
        <form method="GET" class="st-form-row" style="margin-top:4px;align-items:flex-end;">
            <div class="st-form-field" style="max-width:250px;position:relative;">
                <label class="st-label">Date Range</label>
                <input
                    type="text"
                    id="date_range"
                    class="st-input"
                    placeholder="Select Date Range"
                    readonly
                    style="cursor:pointer;"
                >
                <input type="hidden" name="date_from" id="date_from" value="{{ $date_from ?? '' }}">
                <input type="hidden" name="date_to" id="date_to" value="{{ $date_to ?? '' }}">
            </div>
            <div class="st-form-row" style="gap:4px;align-items:flex-end;">
                <div class="st-form-field">
                    <label class="st-label">Type</label>
                    <select name="type" class="st-select">
                        <option value="">All</option>
                        @foreach ($allowedTypes as $t)
                            <option value="{{ $t }}" {{ ($type ?? '') === $t ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $t)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="st-form-field" style="max-width:220px;">
                    <label class="st-label">Search</label>
                    <input type="text" name="q" class="st-input" placeholder="MAT DOC / PO / Text" value="{{ $q ?? '' }}">
                </div>
                <div class="st-form-field" style="min-width:80px;flex:0 0 auto;display:flex;justify-content:flex-end;">
                    <a href="{{ route('logs.index') }}" class="st-btn" style="background:transparent;color:var(--primary);border:1px solid var(--primary);">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <section class="st-row" style="flex:1;">
        <div class="st-col-12" style="flex:1;display:flex;flex-direction:column;">
            <div class="st-card" style="margin-bottom:0;flex:1;display:flex;flex-direction:column;">
                <div class="st-table-wrapper" style="min-height: 400px;">
                    <form method="GET" id="logs-filter-form" data-multi-sort="1" action="{{ route('logs.index') }}">
                        <input type="hidden" name="q" value="{{ $q ?? '' }}">
                        <input type="hidden" name="type" value="{{ $type ?? '' }}">
                        <input type="hidden" name="date_from" value="{{ $date_from ?? '' }}">
                        <input type="hidden" name="date_to" value="{{ $date_to ?? '' }}">
                        @php
                            $sortsArr = isset($sorts) && is_array($sorts) ? $sorts : [];
                            $dirsArr = isset($dirs) && is_array($dirs) ? $dirs : [];
                        @endphp
                        @foreach ($sortsArr as $i => $s)
                            @php $d = $dirsArr[$i] ?? 'desc'; @endphp
                            <input type="hidden" name="sort[]" value="{{ $s }}">
                            <input type="hidden" name="dir[]" value="{{ $d }}">
                        @endforeach
                        <table class="st-table">
                            <thead>
                                <tr>
                                    <th style="width:190px;">
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Time</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="created_at" data-type="date" title="Sort">⇅</button>
                                            </span>
                                        </div>
                                    </th>
                                    <th style="width:180px;">
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Type</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="activity_type" data-type="text" title="Sort">⇅</button>
                                            </span>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Description</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="description" data-type="text" title="Sort">⇅</button>
                                            </span>
                                        </div>
                                    </th>
                                    <th style="width:150px;">
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">MAT DOC</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="mat_doc" data-type="text" title="Sort">⇅</button>
                                            </span>
                                        </div>
                                    </th>
                                    <th style="width:150px;">
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">PO</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="po" data-type="text" title="Sort">⇅</button>
                                            </span>
                                        </div>
                                    </th>
                                    <th style="width:160px;">
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">User</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="user" data-type="text" title="Sort">⇅</button>
                                            </span>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                        <tbody>
                        @php
                            $formatDescription = function ($text) {
                                if (empty($text)) return '-';
                                $conjunctions = [
                                    'and', 'or', 'but', 'nor', 'for', 'yet', 'so', 'a', 'an', 'the',
                                    'at', 'by', 'from', 'in', 'of', 'on', 'to', 'with',
                                    'dan', 'atau', 'tetapi', 'karena', 'agar', 'supaya', 'untuk', 'guna', 'bagi',
                                    'seperti', 'serta', 'saat', 'sejak', 'selama', 'sampai', 'hingga',
                                    'ke', 'di', 'dari', 'pada', 'dalam', 'kepada', 'oleh', 'dengan', 'tentang', 'yang'
                                ];
                                $words = explode(' ', $text);
                                foreach ($words as $i => $w) {
                                    $lower = strtolower($w);
                                    // Check if it's a conjunction and not the first word
                                    if ($i > 0 && in_array($lower, $conjunctions)) {
                                        $words[$i] = $lower;
                                    } else {
                                        // Capitalize first letter, keep existing case for the rest (preserves abbreviations like PO)
                                        $words[$i] = ucfirst($w);
                                    }
                                }
                                return implode(' ', $words);
                            };
                        @endphp
                        @forelse ($logs as $row)
                            <tr>
                                <td>
                                    @if (!empty($row->created_at))
                                        {{ \Carbon\Carbon::parse((string) $row->created_at)->format('d M Y H:i') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $t = (string) ($row->activity_type ?? '');
                                        $typeClass = 'st-activity-type--other';
                                        if ($t === 'status_change') {
                                            $typeClass = 'st-activity-type--status-change';
                                        } elseif ($t === 'gate_activation') {
                                            $typeClass = 'st-activity-type--gate-activation';
                                        } elseif ($t === 'gate_deactivation') {
                                            $typeClass = 'st-activity-type--gate-deactivation';
                                        } elseif ($t === 'late_arrival') {
                                            $typeClass = 'st-activity-type--late-arrival';
                                        }
                                    @endphp
                                    <span class="st-table__status-badge {{ $typeClass }}">
                                        {{ ucwords(str_replace('_', ' ', $t)) }}
                                    </span>
                                </td>
                                <td>{{ $formatDescription($row->description ?? '') }}</td>
                                <td>{{ $row->slot_mat_doc ?? '-' }}</td>
                                <td>{{ $row->slot_po_number ?? '-' }}</td>
                                <td>{{ $row->created_by_nik ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align:center;color:#6b7280;padding:16px 8px;">No Logs Found</td>
                            </tr>
                        @endforelse
                        </tbody>
                        </table>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

<!-- Flatpickr JS - loaded globally in layout -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    var holidayData = typeof window.getIndonesiaHolidays === 'function' ? window.getIndonesiaHolidays() : {};
    // Initialize Flatpickr Date Range Picker
    flatpickr("#date_range", {
        mode: "range",
        dateFormat: "Y-m-d",
        disableMobile: true,
        locale: {
            rangeSeparator: " to "
        },
        onDayCreate: function(dObj, dStr, fp, dayElem) {
            const dateStr = fp.formatDate(dayElem.dateObj, "Y-m-d");
            if (holidayData[dateStr]) {
                dayElem.classList.add('is-holiday');
                dayElem.title = holidayData[dateStr];
            }
        },
        onChange: function(selectedDates, dateStr, instance) {
            if (selectedDates.length === 2) {
                // Format date in local timezone
                var dateFrom = selectedDates[0].getFullYear() + '-' +
                              String(selectedDates[0].getMonth() + 1).padStart(2, '0') + '-' +
                              String(selectedDates[0].getDate()).padStart(2, '0');
                var dateTo = selectedDates[1].getFullYear() + '-' +
                            String(selectedDates[1].getMonth() + 1).padStart(2, '0') + '-' +
                            String(selectedDates[1].getDate()).padStart(2, '0');
                document.getElementById('date_from').value = dateFrom;
                document.getElementById('date_to').value = dateTo;
            } else if (selectedDates.length === 1) {
                // Format date in local timezone
                var dateFrom = selectedDates[0].getFullYear() + '-' +
                              String(selectedDates[0].getMonth() + 1).padStart(2, '0') + '-' +
                              String(selectedDates[0].getDate()).padStart(2, '0');
                document.getElementById('date_from').value = dateFrom;
                document.getElementById('date_to').value = dateFrom;
            } else {
                document.getElementById('date_from').value = '';
                document.getElementById('date_to').value = '';
            }
        },
        onReady: function(selectedDates, dateStr, instance) {
            // Set initial value if dates are pre-filled
            var dateFrom = document.getElementById('date_from').value;
            var dateTo = document.getElementById('date_to').value;
            if (dateFrom && dateTo) {
                instance.setDate([dateFrom, dateTo]);
            } else if (dateFrom) {
                instance.setDate([dateFrom]);
            }
        }
    });

    // Auto-submit form on input change
    const logsFilterForm = document.getElementById('logs-filter-form');
    if (logsFilterForm) {
        // Auto-submit on select change
        logsFilterForm.addEventListener('change', function(e) {
            if (e.target.tagName === 'SELECT') {
                logsFilterForm.submit();
            }
        });

        // Auto-submit on input with debounce for text inputs
        const textInputs = logsFilterForm.querySelectorAll('input[type="text"]');
        textInputs.forEach(function(input) {
            let timeout;
            input.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    logsFilterForm.submit();
                }, 500); // 500ms debounce
            });

            // Submit on Enter key
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    clearTimeout(timeout);
                    logsFilterForm.submit();
                }
            });
        });
    }
});
</script>
