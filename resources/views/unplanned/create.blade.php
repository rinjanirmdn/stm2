@extends('layouts.app')

@section('title', 'Create Unplanned Transaction - Slot Time Management')
@section('page_title', 'Create Unplanned Transaction')

@section('content')
    @php
        $urlPoSearch = route('slots.ajax.po_search');
        $urlPoDetailTemplate = route('slots.ajax.po_detail', ['poNumber' => '__PO__']);
    @endphp
    <div class="st-card st-text--sm">
        <form method="POST" action="{{ route('unplanned.store') }}" enctype="multipart/form-data">
            @csrf

            @if ($errors->any())
                <div class="st-alert st-alert--error">
                    <span class="st-alert__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                    <div class="st-alert__text">
                        <div class="st-font-semibold st-mb-2">Validation Error</div>
                        <div class="st-text--sm">
                            <ul class="st-list">
                                @foreach ($errors->all() as $msg)
                                    <li>{{ $msg }}</li>
                                @endforeach
                            </ul>
                        </div>

                    </div>
                </div>
            @endif

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field">
                    <label class="st-label">PO/DO Number <span class="st-text--danger-dark">*</span></label>
                    <div class="st-relative">
                        <input type="text" id="po_number" name="po_number" maxlength="12" autocomplete="off" class="st-input st-input--pr-40{{ $errors->has('po_number') ? ' st-input--invalid' : '' }}" required value="{{ old('po_number', old('truck_number')) }}">
                        <span class="st-input-loader" id="po_loading" aria-hidden="true"></span>
                        <div id="po_suggestions" class="st-suggestions st-suggestions--po st-hidden"></div>
                    </div>
                    @error('po_number')
                        <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Direction</label>
                    <select name="direction" id="direction" class="st-select{{ $errors->has('direction') ? ' st-input--invalid' : '' }}">
                        <option value="">Choose...</option>
                        <option value="inbound" @if (old('direction') === 'inbound') selected @endif>Inbound</option>
                        <option value="outbound" @if (old('direction') === 'outbound') selected @endif>Outbound</option>
                    </select>
                    @error('direction')
                        <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Gate (Actual) <span class="st-text--danger-dark">*</span></label>
                    <select name="actual_gate_id" id="unplanned-gate" class="st-select{{ $errors->has('actual_gate_id') ? ' st-input--invalid' : '' }}" required>
                        <option value="">Choose Gate...</option>
                        @foreach ($gates as $gate)
                            @php
                                $gateLabel = app(\App\Services\SlotService::class)->getGateDisplayName($gate->warehouse_code ?? '', $gate->gate_number ?? '');
                            @endphp
                            <option value="{{ $gate->id }}" data-warehouse-id="{{ $gate->warehouse_id }}" {{ (string)old('actual_gate_id') === (string)$gate->id ? 'selected' : '' }}>
                                {{ $gateLabel }}
                            </option>
                        @endforeach
                    </select>
                    @error('actual_gate_id')
                        <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field">
                    <label class="st-label">Vendor Name</label>
                    <input type="text" id="vendor_name" class="st-input" placeholder="Vendor will auto-fill from PO" readonly>
                </div>
            </div>

            <div class="st-form-row st-form-field--mb-12 st-hidden">
                <div class="st-form-field">
                    <label class="st-label">Warehouse</label>
                    <select name="warehouse_id" id="unplanned-warehouse" class="st-select">
                        <option value="">Choose...</option>
                        @foreach ($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ (string)old('warehouse_id') === (string)$wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field">
                    <label class="st-label">Arrival Time <span class="st-text--danger-dark">*</span></label>
                    <input type="hidden" name="actual_arrival" id="actual_arrival_input" value="{{ old('actual_arrival') }}">
                    <div class="st-grid st-grid-cols-2 st-gap-8">
                        <input type="text" id="actual_arrival_date_input" class="st-input" placeholder="Select Date" autocomplete="off" min="{{ now()->format('Y-m-d') }}">
                        <input type="text" id="actual_arrival_time_input" class="st-input" placeholder="Select Time" autocomplete="off" inputmode="none" readonly>
                    </div>
                    @error('actual_arrival')
                        <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">MAT DOC <span class="st-text--optional">(Optional)</span></label>
                    <input type="text" name="mat_doc" class="st-input" value="{{ old('mat_doc') }}">
                </div>
            </div>

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field">
                    <label class="st-label">Truck Type <span class="st-text--optional">(Optional)</span></label>
                    <select name="truck_type" class="st-select">
                        <option value="">-</option>
                        @foreach ($truckTypes as $tt => $label)
                            <option value="{{ $tt }}" {{ old('truck_type') === $tt ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="st-form-field">
                    <label class="st-label">Vehicle Number <span class="st-text--optional">(Optional)</span></label>
                    <input type="text" name="vehicle_number_snap" class="st-input" value="{{ old('vehicle_number_snap') }}">
                </div>
                <div class="st-form-field">
                    <label class="st-label">Driver Name <span class="st-text--optional">(Optional)</span></label>
                    <input type="text" name="driver_name" class="st-input" value="{{ old('driver_name') }}">
                </div>
            </div>

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field">
                    <label class="st-label">Driver Number <span class="st-text--optional">(Optional)</span></label>
                    <input type="text" name="driver_number" class="st-input" value="{{ old('driver_number') }}">
                </div>
                <div class="st-form-field">
                    <label class="st-label">Notes <span class="st-text--optional">(Optional)</span></label>
                    <input type="text" name="notes" class="st-input" value="{{ old('notes') }}">
                </div>
            </div>

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field st-w-full">
                    <label class="st-label st-font-semibold">Queue Status</label>
                    <div class="st-flex st-gap-12 st-align-center">
                        <span class="st-badge st-badge--secondary">Waiting</span>
                        <span class="st-text--sm st-text--muted">Set to Waiting</span>
                    </div>
                    <input type="hidden" name="set_waiting" value="1">
                </div>
            </div>

            <div class="st-form-actions st-mt-4">
                <button type="submit" class="st-btn">Save</button>
                <a href="{{ route('unplanned.index') }}" class="st-btn st-btn--outline-primary">Cancel</a>
            </div>
        </form>
    </div>

    <script type="application/json" id="slot_routes_json">{!! json_encode([
        'po_search' => route('slots.ajax.po_search'),
        'po_detail_template' => route('slots.ajax.po_detail', ['poNumber' => '__PO__']),
    ]) !!}</script>
@endsection

@push('scripts')
@vite(['resources/js/pages/unplanned-create.js'])
@endpush

