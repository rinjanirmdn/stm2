@extends('layouts.app')

@section('title', 'Vendors - Slot Time Management')
@section('page_title', 'Vendors')

@section('content')
    <div class="st-card" style="margin-bottom:12px;">
        <div style="padding:12px;">
            <div class="st-form-row" style="align-items:flex-end;">
                <div class="st-form-field" style="max-width:120px;">
                    <label class="st-label">Show</label>
                    <select id="vendor-page-size" class="st-select">
                        @foreach ($pageSizeAllowed as $ps)
                            <option value="{{ $ps }}" {{ $ps === '10' ? 'selected' : '' }}>{{ strtoupper($ps) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="st-form-field" style="max-width:180px;">
                    <label class="st-label">Type</label>
                    <select id="vendor-type-filter" class="st-select">
                        <option value="all">All Types</option>
                        @foreach ($allowedTypes as $t)
                            <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="st-form-field" style="flex:1;min-width:220px;position:relative;">
                    <label class="st-label">Search</label>
                    <input
                        type="text"
                        id="vendor-search"
                        class="st-input"
                        placeholder="Search Code or Name"
                        autocomplete="off"
                    >
                    <div id="vendor-search-suggestions" class="st-suggestions st-suggestions--vendor" style="display:none;"></div>
                </div>
                <div class="st-form-field" style="flex:0 0 auto;">
                    <button type="button" id="reset-all-filters" class="st-btn st-btn--secondary">Reset</button>
                    <a href="{{ route('vendors.import') }}" class="st-btn st-btn--secondary">Import</a>
                </div>
            </div>
        </div>
    </div>

    <section class="st-row" style="flex:1;">
        <div class="st-col-12" style="flex:1;display:flex;flex-direction:column;">
            <div class="st-card" style="margin-bottom:0;flex:1;display:flex;flex-direction:column;">
                <div class="st-table-wrapper">
                    <form method="GET" id="vendors-filter-form" data-multi-sort="1" action="{{ route('vendors.index') }}">
                        @php
                            $sortsArr = isset($sorts) && is_array($sorts) ? $sorts : [];
                            $dirsArr = isset($dirs) && is_array($dirs) ? $dirs : [];
                        @endphp
                        @foreach ($sortsArr as $i => $s)
                            @php $d = $dirsArr[$i] ?? 'asc'; @endphp
                            <input type="hidden" name="sort[]" value="{{ $s }}">
                            <input type="hidden" name="dir[]" value="{{ $d }}">
                        @endforeach
                        <table class="st-table">
                            <thead>
                                <tr>
                                    <th style="width:60px;">#</th>
                                    <th style="width:140px;">
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Code</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="code" title="Sort">⇅</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="code" title="Filter">⏷</button>
                                            </span>
                                            <div class="st-filter-panel" data-filter-panel="code" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:200px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                                <div style="font-weight:600;margin-bottom:6px;">Code Filter</div>
                                                <input type="text" name="code" form="vendors-filter-form" class="st-input" placeholder="Search Code..." value="{{ $v_code ?? '' }}">
                                                <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="code">Clear</button>
                                                </div>
                                            </div>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Name</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="name" title="Sort">⇅</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="name" title="Filter">⏷</button>
                                            </span>
                                            <div class="st-filter-panel" data-filter-panel="name" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:240px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                                <div style="font-weight:600;margin-bottom:6px;">Name Filter</div>
                                                <input type="text" name="name" form="vendors-filter-form" class="st-input" placeholder="Search Name..." value="{{ $v_name ?? '' }}">
                                                <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="name">Clear</button>
                                                </div>
                                            </div>
                                        </div>
                                    </th>
                                    <th style="width:140px;">
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Type</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="type" title="Sort">⇅</button>
                                                <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="type" title="Filter">⏷</button>
                                            </span>
                                            <div class="st-filter-panel" data-filter-panel="type" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:160px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                                <div style="font-weight:600;margin-bottom:6px;">Type Filter</div>
                                                <select name="type" form="vendors-filter-form" class="st-select" style="width:100%;height:34px;">
                                                    <option value="">(All)</option>
                                                    @foreach($allowedTypes as $t)
                                                        <option value="{{ $t }}" {{ ($v_type ?? '') === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                                                    @endforeach
                                                </select>
                                                <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                    <button type="button" class="st-btn st-btn--sm st-btn--secondary st-filter-clear" data-filter="type">Clear</button>
                                                </div>
                                            </div>
                                        </div>
                                    </th>
                                    <th style="width:190px;">
                                        <div class="st-colhead">
                                            <span class="st-colhead__label">Created</span>
                                            <span class="st-colhead__icons">
                                                <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="created_at" data-type="date" title="Sort">⇅</button>
                                            </span>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                        <tbody id="vendor-table-body">
                        @php
                            $fmt = function ($v) {
                                if (empty($v)) return '-';
                                try {
                                    return \Carbon\Carbon::parse((string) $v)->format('d M Y H:i');
                                } catch (\Throwable $e) {
                                    return (string) $v;
                                }
                            };
                        @endphp

                        @if (count($vendors) === 0)
                            <tr class="vendor-empty-row">
                                <td colspan="6" style="text-align:center;color:#6b7280;padding:16px 8px;">No Vendors Found</td>
                            </tr>
                        @else
                            @foreach ($vendors as $row)
                                @php $vendorType = (string) ($row->type ?? 'supplier'); @endphp
                                <tr
                                    data-vendor-id="{{ (int) $row->id }}"
                                    data-vendor-code="{{ $row->code }}"
                                    data-vendor-name="{{ $row->name }}"
                                    data-vendor-type="{{ $vendorType }}"
                                >
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>{{ $row->code }}</td>
                                    <td>{{ $row->name }}</td>
                                    <td>
                                        <span class="badge bg-{{ $vendorType === 'supplier' ? 'supplier' : 'customer' }}">{{ ucfirst($vendorType) }}</span>
                                    </td>
                                    <td>{{ $fmt($row->created_at ?? null) }}</td>
                                </tr>
                            @endforeach
                        @endif
                        </tbody>
                        </table>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // NOTE: Filter panel toggle/clear/indicator handled globally in resources/js/main.js
    // Vendors page keeps only client-side search/type/page-size interactions below.

    var tableBody = document.getElementById('vendor-table-body');
    if (!tableBody) return;

    var rows = Array.prototype.slice.call(tableBody.querySelectorAll('tr[data-vendor-id]'));
    var emptyRow = tableBody.querySelector('.vendor-empty-row');

    var searchInput = document.getElementById('vendor-search');
    var suggestionsBox = document.getElementById('vendor-search-suggestions');
    var typeFilter = document.getElementById('vendor-type-filter');
    var pageSizeSelect = document.getElementById('vendor-page-size');

    var suggestionSource = [];
    (function buildSuggestionSource() {
        var seen = {};
        rows.forEach(function (row) {
            var code = (row.getAttribute('data-vendor-code') || '').trim();
            var name = (row.getAttribute('data-vendor-name') || '').trim();
            if (code) {
                var keyCode = 'code:' + code.toLowerCase();
                if (!seen[keyCode]) {
                    seen[keyCode] = true;
                    suggestionSource.push({ text: code, label: code });
                }
            }
            if (name) {
                var combined = code ? (code + ' - ' + name) : name;
                var keyName = 'name:' + combined.toLowerCase();
                if (!seen[keyName]) {
                    seen[keyName] = true;
                    suggestionSource.push({ text: combined, label: combined });
                }
            }
        });
    })();

    function hideSuggestions() {
        if (!suggestionsBox) return;
        suggestionsBox.style.display = 'none';
        suggestionsBox.innerHTML = '';
    }

    function highlightMatch(text, query) {
        if (!query) return text;
        var escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        var pattern = new RegExp('(' + escaped + ')', 'ig');
        return text.replace(pattern, '<strong>$1</strong>');
    }

    function showSuggestions(query) {
        if (!suggestionsBox) return;
        var q = (query || '').trim();
        if (q.length === 0) {
            hideSuggestions();
            return;
        }

        var lower = q.toLowerCase();
        var matches = [];
        for (var i = 0; i < suggestionSource.length; i++) {
            var item = suggestionSource[i];
            if (item.label.toLowerCase().indexOf(lower) !== -1) {
                matches.push(item);
                if (matches.length >= 10) break;
            }
        }

        if (matches.length === 0) {
            suggestionsBox.innerHTML = '<div class="st-suggestion-empty">No Suggestions for "' + q.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '"</div>';
            suggestionsBox.style.display = 'block';
            return;
        }

        var html = '';
        matches.forEach(function (item) {
            var safeText = item.text.replace(/'/g, "\\'");
            html += '<div class="vendor-suggestion-item" data-value="' + safeText.replace(/"/g, '&quot;') + '">' +
                highlightMatch(item.label, q) +
                '</div>';
        });
        suggestionsBox.innerHTML = html;
        suggestionsBox.style.display = 'block';

        Array.prototype.slice.call(suggestionsBox.querySelectorAll('.vendor-suggestion-item')).forEach(function (el) {
            el.addEventListener('click', function () {
                var value = el.getAttribute('data-value') || '';
                var normalized = value;
                var sepIndex = value.indexOf(' - ');
                if (sepIndex !== -1) {
                    normalized = value.substring(0, sepIndex);
                }
                if (searchInput) {
                    searchInput.value = normalized;
                }
                hideSuggestions();
                applyFilter();
            });
        });
    }

    function applyFilter() {
        var term = searchInput ? searchInput.value.trim().toLowerCase() : '';
        var type = typeFilter ? (typeFilter.value || 'all') : 'all';
        var pageSizeVal = pageSizeSelect ? pageSizeSelect.value : '10';
        var pageSize = pageSizeVal === 'all' ? Infinity : parseInt(pageSizeVal, 10);
        if (!pageSize || pageSize <= 0) pageSize = Infinity;

        var visibleRows = [];

        rows.forEach(function (row) {
            var code = (row.getAttribute('data-vendor-code') || '').toLowerCase();
            var name = (row.getAttribute('data-vendor-name') || '').toLowerCase();
            var rowType = (row.getAttribute('data-vendor-type') || 'supplier').toLowerCase();

            var matchesSearch = true;
            if (term) {
                matchesSearch = code.indexOf(term) !== -1 || name.indexOf(term) !== -1;
            }

            var matchesType = true;
            if (type !== 'all') {
                matchesType = rowType === type;
            }

            if (matchesSearch && matchesType) {
                visibleRows.push(row);
            }
        });

        var anyVisible = visibleRows.length > 0;
        if (emptyRow) {
            emptyRow.style.display = anyVisible ? 'none' : '';
        }

        rows.forEach(function (row) {
            row.style.display = 'none';
        });

        var counter = 0;
        visibleRows.forEach(function (row) {
            if (counter < pageSize) {
                row.style.display = '';
                counter++;
            }
        });

        var number = 1;
        Array.prototype.slice.call(tableBody.querySelectorAll('tr[data-vendor-id]')).forEach(function (row) {
            if (row.style.display === 'none') return;
            var firstCell = row.querySelector('td');
            if (firstCell) {
                firstCell.textContent = String(number++);
            }
        });
    }

    if (searchInput) {
        searchInput.addEventListener('keydown', function (e) {
            var value = searchInput.value || '';
            if (e.key === 'Enter') {
                e.preventDefault();
                e.stopPropagation();
                hideSuggestions();
                applyFilter();
                return;
            }
        });

        searchInput.addEventListener('input', function (e) {
            var value = searchInput.value || '';
            showSuggestions(value);
            applyFilter();
        });
    }

    if (typeFilter) {
        typeFilter.addEventListener('change', function () {
            applyFilter();
        });
    }

    if (pageSizeSelect) {
        pageSizeSelect.addEventListener('change', function () {
            applyFilter();
        });
    }

    document.addEventListener('click', function (e) {
        var isInside = (searchInput && (e.target === searchInput || (searchInput.contains && searchInput.contains(e.target)))) ||
            (suggestionsBox && (e.target === suggestionsBox || (e.target.closest && e.target.closest('#vendor-search-suggestions'))));
        if (!isInside) {
            hideSuggestions();
        }
    });

    // Reset all filters button
    document.getElementById('reset-all-filters').addEventListener('click', function() {
        // Clear all filter inputs
        const filterForm = document.getElementById('vendors-filter-form');
        if (filterForm) {
            // Clear text inputs in filter form
            filterForm.querySelectorAll('input[type="text"]').forEach(function(input) {
                input.value = '';
            });

            // Clear selects in filter form
            filterForm.querySelectorAll('select').forEach(function(select) {
                select.value = '';
            });

            try {
                filterForm.querySelectorAll('input[name="sort[]"], input[name="dir[]"]').forEach(function (el) {
                    el.remove();
                });
            } catch (e) {}
        }

        // Clear main search input
        if (searchInput) {
            searchInput.value = '';
        }

        // Clear type filter dropdown
        if (typeFilter) {
            typeFilter.value = 'all';
        }

        // Update indicators
        updateFilterIndicators();
        updateSortIndicators();

        // Apply client-side filter
        applyFilter();

        // Submit form to apply server-side reset
        if (filterForm) {
            filterForm.submit();
        }
    });

    applyFilter();
});
</script>
@endpush
