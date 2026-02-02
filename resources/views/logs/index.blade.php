@extends('layouts.app')

@section('title', 'Activity Logs - Slot Time Management')
@section('page_title', 'Activity Logs')

@section('content')
    <div class="st-card st-mb-12">
        <form method="GET" class="st-form-row st-mt-4 st-items-end">
            <div class="st-form-field st-maxw-250 st-relative">
                <label class="st-label">Date Range</label>
                <input
                    type="text"
                    id="date_range"
                    placeholder="Select Date Range"
                    readonly
                    class="st-input st-input--cursor"
                >
                <input type="hidden" name="date_from" id="date_from" value="{{ $date_from ?? '' }}">
                <input type="hidden" name="date_to" id="date_to" value="{{ $date_to ?? '' }}">
            </div>
            <div class="st-form-row st-form-row--gap-4 st-items-end">
                <div class="st-form-field">
                    <label class="st-label">Type</label>
                    <select name="type" class="st-select">
                        <option value="">All</option>
                        @foreach ($allowedTypes as $t)
                            <option value="{{ $t }}" {{ ($type ?? '') === $t ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $t)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="st-form-field st-maxw-220">
                    <label class="st-label">Search</label>
                    <input type="text" name="q" class="st-input" placeholder="MAT DOC / PO / Text" value="{{ $q ?? '' }}">
                </div>
                <div class="st-form-field st-minw-80 st-flex-0 st-flex st-justify-end">
                    <a href="{{ route('logs.index') }}" class="st-btn st-btn--outline-primary">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <section class="st-row st-flex-1">
        <div class="st-col-12 st-flex-1 st-flex st-flex-col">
            <div class="st-card st-mb-0 st-flex st-flex-col st-flex-1">
                <div class="st-table-wrapper st-table-wrapper--minh-400">
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
                                    <th class="st-table-col-190">
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Time</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="created_at" data-type="date" title="Sort">⇅</button>
                                            </span>
                                        </div>
                                    </th>
                                    <th class="st-table-col-180">
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
                                    <th class="st-table-col-150">
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">MAT DOC</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="mat_doc" data-type="text" title="Sort">⇅</button>
                                            </span>
                                        </div>
                                    </th>
                                    <th class="st-table-col-150">
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">PO</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="po" data-type="text" title="Sort">⇅</button>
                                            </span>
                                        </div>
                                    </th>
                                    <th class="st-table-col-160">
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
                                <td>{{ $row->created_by_name ?? $row->created_by_email ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="st-text-center st-text--muted st-table-empty--roomy">No Logs Found</td>
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
    var dateRangeInput = document.getElementById('date_range');
    var dateFromInput = document.getElementById('date_from');
    var dateToInput = document.getElementById('date_to');

    if (dateRangeInput && window.jQuery && window.jQuery.fn.dateRangePicker) {
        var initial = dateFromInput && dateFromInput.value ? dateFromInput.value : '';
        if (initial) {
            dateRangeInput.value = initial;
        }

        window.jQuery(dateRangeInput).dateRangePicker({
            autoClose: true,
            singleDate: true,
            showShortcuts: false,
            singleMonth: true,
            format: 'YYYY-MM-DD'
        }).bind('datepicker-change', function(event, obj) {
            var value = (obj && obj.value) ? obj.value : '';
            if (dateFromInput) dateFromInput.value = value;
            if (dateToInput) dateToInput.value = value;
            dateRangeInput.value = value;
        });
    }

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
