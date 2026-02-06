@extends('layouts.app')

@section('title', 'Trucks - Slot Time Management')
@section('page_title', 'Trucks')

@section('content')
    <div class="st-card st-mb-12">
        <div class="st-p-12">
            <div class="st-form-row st-items-end">
                <div class="st-form-field st-maxw-120">
                    <label class="st-label">Show</label>
                    <select id="truck-page-size" class="st-select">
                        @foreach ($pageSizeAllowed as $ps)
                            <option value="{{ $ps }}" {{ $ps === '10' ? 'selected' : '' }}>{{ strtoupper($ps) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="st-form-field st-flex-1 st-minw-220 st-relative">
                    <label class="st-label">Search</label>
                    <input
                        type="text"
                        id="truck-search"
                        class="st-input"
                        placeholder="Search truck type"
                        autocomplete="off"
                    >
                </div>
                <div class="st-form-field st-flex-0">
                    <button type="button" id="btn-add-truck" class="st-btn st-btn--primary">Add Truck</button>
                </div>
            </div>
        </div>
    </div>

    <section class="st-row st-flex-1">
        <div class="st-col-12 st-flex-1 st-flex st-flex-col">
            <div class="st-card st-mb-0 st-flex st-flex-col st-flex-1">
                <div class="st-table-wrapper st-table-wrapper--minh-400">
                    <table class="st-table">
                        <thead>
                            <tr>
                                <th class="st-table-col-60">#</th>
                                <th>Truck Type</th>
                                <th class="st-table-col-180 st-th-center">Duration (minutes)</th>
                                <th class="st-table-col-190 st-th-center">Created</th>
                                <th class="st-table-col-170 st-th-center">Actions</th>
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
                                <td colspan="5" class="st-table-empty st-text-center st-text--muted st-table-empty--roomy">No trucks found</td>
                            </tr>
                        @else
                            @foreach ($rows as $row)
                                <tr
                                    data-row-id="{{ (int) $row->id }}"
                                    data-truck-type="{{ (string) ($row->truck_type ?? '') }}"
                                >
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>{{ $row->truck_type }}</td>
                                    <td class="st-td-center">{{ (int) $row->target_duration_minutes }}</td>
                                    <td class="st-td-center">{{ $fmt($row->created_at ?? null) }}</td>
                                    <td class="st-td-center">
                                        <div class="tw-actionbar">
                                            <button type="button" class="tw-action btn-edit-truck" data-id="{{ $row->id }}" data-truck-type="{{ $row->truck_type }}" data-duration="{{ $row->target_duration_minutes }}" data-tooltip="Edit" aria-label="Edit">
                                                <i class="fa-solid fa-pencil"></i>
                                            </button>
                                            <form method="POST" action="{{ route('trucks.delete', ['truckTypeDurationId' => $row->id]) }}" class="st-inline-form">
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
    <div id="truck-modal" class="st-modal st-modal--hidden">
        <div class="st-modal__content st-modal__content--sm">
            <div class="st-modal__header">
                <h3 class="st-modal__title" id="truck-modal-title">Add Truck</h3>
                <button type="button" id="truck-modal-close" class="st-btn st-btn--outline-primary st-btn--sm">&times;</button>
            </div>
            <div class="st-modal__body">
                <form id="truck-form" method="POST" action="{{ route('trucks.store') }}">
                    @csrf
                    <input type="hidden" id="truck-id" name="truck_id" value="">

                    <div class="st-form-field st-mb-12">
                        <label class="st-label">Truck Type <span class="st-text--danger-dark">*</span></label>
                        <input type="text" id="truck-type-input" name="truck_type" class="st-input" maxlength="100" required>
                    </div>

                    <div class="st-form-field st-mb-16">
                        <label class="st-label">Duration (minutes) <span class="st-text--danger-dark">*</span></label>
                        <input type="number" id="truck-duration-input" name="target_duration_minutes" class="st-input" min="0" max="1440" required>
                    </div>

                    <div class="st-flex st-gap-8 st-justify-end">
                        <button type="button" id="truck-modal-cancel" class="st-btn st-btn--outline-primary">Cancel</button>
                        <button type="submit" class="st-btn st-btn--primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script type="application/json" id="trucks_index_config">{!! json_encode([
    'baseUrl' => url('trucks'),
    'storeUrl' => route('trucks.store'),
]) !!}</script>
@vite(['resources/js/pages/trucks-index.js'])
@endpush

