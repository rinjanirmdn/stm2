@extends('layouts.app')

@section('title', 'Trucks - Slot Time Management')
@section('page_title', 'Trucks')

@section('content')
    <div class="st-card" style="margin-bottom:12px;">
        <div style="padding:12px;">
            <div class="st-form-row" style="align-items:flex-end;">
                <div class="st-form-field" style="max-width:120px;">
                    <label class="st-label">Show</label>
                    <select id="truck-page-size" class="st-select">
                        @foreach ($pageSizeAllowed as $ps)
                            <option value="{{ $ps }}" {{ $ps === '10' ? 'selected' : '' }}>{{ strtoupper($ps) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="st-form-field" style="flex:1;min-width:220px;position:relative;">
                    <label class="st-label">Search</label>
                    <input
                        type="text"
                        id="truck-search"
                        class="st-input"
                        placeholder="Search truck type"
                        autocomplete="off"
                    >
                </div>
                <div class="st-form-field" style="flex:0 0 auto;">
                    <button type="button" id="btn-add-truck" class="st-btn st-btn--primary">Add Truck</button>
                </div>
            </div>
        </div>
    </div>

    <section class="st-row" style="flex:1;">
        <div class="st-col-12" style="flex:1;display:flex;flex-direction:column;">
            <div class="st-card" style="margin-bottom:0;flex:1;display:flex;flex-direction:column;">
                <div class="st-table-wrapper">
                    <table class="st-table">
                        <thead>
                            <tr>
                                <th style="width:60px;">#</th>
                                <th>Truck Type</th>
                                <th style="width:180px;">Duration (minutes)</th>
                                <th style="width:190px;">Created</th>
                                <th style="width:170px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="truck-table-body">
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

                        @if (count($rows) === 0)
                            <tr class="truck-empty-row">
                                <td colspan="5" style="text-align:center;color:#6b7280;padding:16px 8px;">No trucks found</td>
                            </tr>
                        @else
                            @foreach ($rows as $row)
                                <tr
                                    data-row-id="{{ (int) $row->id }}"
                                    data-truck-type="{{ (string) ($row->truck_type ?? '') }}"
                                >
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>{{ $row->truck_type }}</td>
                                    <td>{{ (int) $row->target_duration_minutes }}</td>
                                    <td>{{ $fmt($row->created_at ?? null) }}</td>
                                    <td>
                                        <div class="tw-actionbar">
                                            <button type="button" class="tw-action btn-edit-truck" data-id="{{ $row->id }}" data-truck-type="{{ $row->truck_type }}" data-duration="{{ $row->target_duration_minutes }}" data-tooltip="Edit" aria-label="Edit">
                                                <i class="fa-solid fa-pencil"></i>
                                            </button>
                                            <form method="POST" action="{{ route('trucks.delete', ['truckTypeDurationId' => $row->id]) }}" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="tw-action tw-action--danger" data-tooltip="Delete" aria-label="Delete" onclick="return confirm('Delete this truck type?');">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
    <!-- Modal Add/Edit Truck -->
    <div id="truck-modal" class="st-modal" style="display:none;">
        <div class="st-modal__content" style="width:400px;max-width:95vw;">
            <div class="st-modal__header">
                <h3 class="st-modal__title" id="truck-modal-title">Add Truck</h3>
                <button type="button" id="truck-modal-close" class="st-btn st-btn--secondary st-btn--sm">&times;</button>
            </div>
            <div class="st-modal__body">
                <form id="truck-form" method="POST" action="{{ route('trucks.store') }}">
                    @csrf
                    <input type="hidden" id="truck-id" name="truck_id" value="">
                    
                    <div class="st-form-field" style="margin-bottom:12px;">
                        <label class="st-label">Truck Type <span class="st-text--danger-dark">*</span></label>
                        <input type="text" id="truck-type-input" name="truck_type" class="st-input" maxlength="100" required>
                    </div>

                    <div class="st-form-field" style="margin-bottom:16px;">
                        <label class="st-label">Duration (minutes) <span class="st-text--danger-dark">*</span></label>
                        <input type="number" id="truck-duration-input" name="target_duration_minutes" class="st-input" min="0" max="1440" required>
                    </div>

                    <div style="display:flex;gap:8px;justify-content:flex-end;">
                        <button type="button" id="truck-modal-cancel" class="st-btn st-btn--secondary">Cancel</button>
                        <button type="submit" class="st-btn st-btn--primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var tableBody = document.getElementById('truck-table-body');
    if (!tableBody) return;

    var rows = Array.prototype.slice.call(tableBody.querySelectorAll('tr[data-row-id]'));
    var emptyRow = tableBody.querySelector('.truck-empty-row');

    var searchInput = document.getElementById('truck-search');
    var pageSizeSelect = document.getElementById('truck-page-size');

    function applyFilter() {
        var term = searchInput ? searchInput.value.trim().toLowerCase() : '';
        var pageSizeVal = pageSizeSelect ? pageSizeSelect.value : '10';
        var pageSize = pageSizeVal === 'all' ? Infinity : parseInt(pageSizeVal, 10);
        if (!pageSize || pageSize <= 0) pageSize = Infinity;

        var visibleRows = [];
        rows.forEach(function (row) {
            var tt = (row.getAttribute('data-truck-type') || '').toLowerCase();
            var matches = true;
            if (term) {
                matches = tt.indexOf(term) !== -1;
            }
            if (matches) {
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
        Array.prototype.slice.call(tableBody.querySelectorAll('tr[data-row-id]')).forEach(function (row) {
            if (row.style.display === 'none') return;
            var cell = row.querySelector('td');
            if (cell) {
                cell.textContent = String(number);
                number++;
            }
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            applyFilter();
        });
    }

    if (pageSizeSelect) {
        pageSizeSelect.addEventListener('change', function () {
            applyFilter();
        });
    }

    applyFilter();

    // Modal functionality
    var modal = document.getElementById('truck-modal');
    var modalTitle = document.getElementById('truck-modal-title');
    var modalForm = document.getElementById('truck-form');
    var truckIdInput = document.getElementById('truck-id');
    var truckTypeInput = document.getElementById('truck-type-input');
    var truckDurationInput = document.getElementById('truck-duration-input');
    var btnAdd = document.getElementById('btn-add-truck');
    var btnClose = document.getElementById('truck-modal-close');
    var btnCancel = document.getElementById('truck-modal-cancel');

    function openModal(isEdit, data) {
        if (!modal) return;
        
        if (isEdit && data) {
            modalTitle.textContent = 'Edit Truck';
            truckIdInput.value = data.id || '';
            truckTypeInput.value = data.truckType || '';
            truckDurationInput.value = data.duration || '';
            modalForm.action = '{{ url("trucks") }}/' + data.id + '/edit';
        } else {
            modalTitle.textContent = 'Add Truck';
            truckIdInput.value = '';
            truckTypeInput.value = '';
            truckDurationInput.value = '';
            modalForm.action = '{{ route("trucks.store") }}';
        }
        
        modal.style.display = 'flex';
        truckTypeInput.focus();
    }

    function closeModal() {
        if (!modal) return;
        modal.style.display = 'none';
    }

    if (btnAdd) {
        btnAdd.addEventListener('click', function() {
            openModal(false);
        });
    }

    if (btnClose) {
        btnClose.addEventListener('click', closeModal);
    }

    if (btnCancel) {
        btnCancel.addEventListener('click', closeModal);
    }

    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }

    // Edit buttons
    document.querySelectorAll('.btn-edit-truck').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var data = {
                id: this.getAttribute('data-id'),
                truckType: this.getAttribute('data-truck-type'),
                duration: this.getAttribute('data-duration')
            };
            openModal(true, data);
        });
    });
});
</script>
@endpush
