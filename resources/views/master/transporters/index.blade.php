@extends('layouts.app')

@section('title', 'Master Vendor Transporter - e-Docking Control System')
@section('page_title', 'Master Vendor Transporter')

@section('content')



    {{-- Filter bar --}}
    <div class="st-card st-mb-12">
        <div class="st-p-12">
            <div class="st-form-row st-gap-4 st-align-end">
                <div class="st-form-field st-maxw-260">
                    <label class="st-label">Search</label>
                    <input type="text" id="transporter-search" class="st-input" placeholder="Transporter Name..." autocomplete="off">
                </div>
                <div class="st-form-field st-maxw-160">
                    <label class="st-label">Status</label>
                    <select id="transporter-status" class="st-select">
                        <option value="">All</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="st-form-field st-maxw-120">
                    <label class="st-label">Show</label>
                    <select id="transporter-page-size" class="st-select">
                        @foreach ($pageSizeAllowed as $ps)
                            <option value="{{ $ps }}" {{ $ps === '10' ? 'selected' : '' }}>{{ strtoupper($ps) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="st-form-field st-flex-1 st-minw-80 st-flex st-justify-end st-gap-8">
                    <button type="button" id="btn-add-transporter" class="st-btn st-btn--primary">+ Add</button>
                </div>
            </div>
        </div>
    </div>

    <section class="st-row st-flex-1 st-minh-0">
        <div class="st-col-12 st-flex-1 st-flex st-flex-col st-minh-0">
            <div class="st-card st-mb-0 st-flex st-flex-col st-flex-1 st-minh-0">
                <div class="st-table-wrapper st-table-wrapper--minh-400 st-flex-1 st-maxh-none st-minh-0">
                    <table class="st-table">
                        <thead>
                            <tr>
                                <th class="st-table-col-40">#</th>
                                <th>Transporter Name</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="transporter-table-body">
                        @if (count($transporters) === 0)
                            <tr class="transporter-empty-row">
                                <td colspan="4" class="st-table-empty st-text-center st-text--muted st-py-16">
                                    No Vendor Transporter data found.
                                </td>
                            </tr>
                        @else
                            @foreach ($transporters as $i => $t)
                                <tr class="st-table-row" data-row-id="{{ $t->id_vendor_transporters }}" data-name="{{ strtolower($t->name) }}" data-status="{{ $t->is_active ? 'active' : 'inactive' }}">
                                <td class="st-table-cell">{{ $i + 1 }}</td>
                                <td class="st-table-cell"><strong>{{ $t->name }}</strong></td>
                                <td class="st-table-cell">
                                    @if ($t->is_active)
                                        <span class="st-table__status-badge st-status-on-time">Active</span>
                                    @else
                                        <span class="st-table__status-badge st-status-late">Inactive</span>
                                    @endif
                                </td>
                                <td class="st-table-cell">
                                    <div class="tw-actionbar">
                                        <button type="button" class="tw-action btn-edit-transporter" data-id="{{ $t->id_vendor_transporters }}" data-name="{{ $t->name }}" data-status="{{ $t->is_active ? '1' : '0' }}" data-tooltip="Edit" aria-label="Edit">
                                            <i class="fa-solid fa-pencil"></i>
                                        </button>
                                        <button type="button" class="tw-action tw-action--danger btn-delete-transporter" data-tooltip="Delete" aria-label="Delete" data-delete-url="{{ route('master.transporters.destroy', $t->id_vendor_transporters) }}" data-name="{{ $t->name }}">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
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

    <!-- Modal Add/Edit Transporter -->
    <div id="transporter-modal" class="st-modal st-modal--hidden">
        <div class="st-modal__content st-modal__content--sm">
            <div class="st-modal__header">
                <h3 class="st-modal__title" id="transporter-modal-title">Add Transporter</h3>
                <button type="button" id="transporter-modal-close" class="st-btn st-btn--outline-primary st-btn--sm">&times;</button>
            </div>
            <div class="st-modal__body">
                <form id="transporter-form" method="POST" action="{{ route('master.transporters.store') }}">
                    @csrf
                    <div class="st-form-field st-mb-16">
                        <label class="st-label">Transporter Name <span class="st-text--danger-dark">*</span></label>
                        <input type="text" id="transporter-name-input" name="name" class="st-input" maxlength="150" required>
                    </div>

                    <div class="st-form-field st-mb-24">
                        <label class="st-label">Status</label>
                        <div class="st-flex st-items-center st-gap-16">
                            <label class="st-flex st-items-center st-gap-8 st-cursor-pointer">
                                <input type="radio" id="transporter-status-active" name="is_active" value="1" checked class="st-radio">
                                <span>Active</span>
                            </label>
                            <label class="st-flex st-items-center st-gap-8 st-cursor-pointer">
                                <input type="radio" id="transporter-status-inactive" name="is_active" value="0" class="st-radio">
                                <span>Inactive</span>
                            </label>
                        </div>
                    </div>

                    <div class="st-flex st-gap-8 st-justify-end">
                        <button type="button" id="transporter-modal-cancel" class="st-btn st-btn--outline-primary">Cancel</button>
                        <button type="submit" class="st-btn st-btn--primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Dialog -->
    <div id="deleteTransporterDialog" class="st-dialog st-dialog--overlay st-hidden">
        <div class="st-card st-dialog__card">
            <div class="st-card__header st-dialog__header">
                <h3 class="st-dialog__title">Delete Transporter</h3>
            </div>
            <div class="st-card__body st-dialog__body">
                <form id="delete-transporter-form" method="POST" action="">
                    @csrf
                    <p class="st-dialog__text">
                        Are you sure you want to delete transporter <span id="deleteTransporterName" class="st-font-semibold"></span>?
                    </p>
                    <div class="st-dialog__actions">
                        <button id="confirmDeleteYes" type="submit" class="st-btn st-btn--danger st-dialog__btn">
                            DELETE
                        </button>
                        <button id="confirmDeleteNo" type="button" class="st-btn st-btn--outline-primary st-dialog__btn">
                            CANCEL
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script type="application/json" id="transporters_index_config">{!! json_encode([
    'baseUrl' => url('master/transporters'),
    'storeUrl' => route('master.transporters.store'),
]) !!}</script>
@vite(['resources/js/pages/master-transporters.js'])
@endpush
