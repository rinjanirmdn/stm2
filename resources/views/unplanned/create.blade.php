@extends('layouts.app')

@section('title', 'Create Unplanned - e-Docking Control System')
@section('page_title', 'Create Unplanned')

@section('content')
    @php
        $urlPoSearch = route('slots.ajax.po_search');
        $urlPoDetailTemplate = route('slots.ajax.po_detail', ['poNumber' => '__PO__']);
    @endphp
    <div class="st-card st-text--sm">
        <form method="POST" action="{{ route('unplanned.store') }}" enctype="multipart/form-data" novalidate>
            @csrf

            @if ($errors->any())
                <div class="st-alert st-alert--error st-alert--autodismiss">
                    <span class="st-alert__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                    <div class="st-alert__text">
                        <div class="st-font-semibold st-mb-2">Validation Error</div>
                        <div class="st-text--sm">
                            <ul class="st-ml-16">
                                @foreach ($errors->all() as $e)
                                    <li>{{ $e }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <button type="button" class="st-alert__close" onclick="this.parentElement.remove()"
                        aria-label="Close">&times;</button>
                </div>
            @endif

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field">
                    <label class="st-label">PO/SO Number <span class="st-text--danger-dark">*</span></label>
                    <div id="po_entries_container">
                        @php
                            $oldPoNumbers = old('po_number', [old('truck_number', '')]);
                            if (!is_array($oldPoNumbers)) {
                                $oldPoNumbers = explode(',', $oldPoNumbers);
                            }
                            if (empty(array_filter($oldPoNumbers, 'trim'))) {
                                $oldPoNumbers = [''];
                            }
                        @endphp
                        @foreach($oldPoNumbers as $index => $oldPo)
                        <div class="po-entry st-mb-8" data-po-index="{{ $index }}" style="margin-bottom: 8px;">
                            <div style="display: flex; gap: 8px; align-items: center; position: relative;">
                                <div class="st-relative" style="flex: 1; position: relative; margin-bottom: 0;">
                                    <input type="text"
                                           class="st-input st-input--pr-40 po-search-input"
                                           placeholder="Search PO/SO number..."
                                           autocomplete="off" maxlength="12"
                                           value="{{ trim($oldPo) }}" required>
                                    <input type="hidden" name="po_number[]" class="po-number-hidden" value="{{ trim($oldPo) }}">
                                    <span class="st-input-loader po-loading" aria-hidden="true"></span>
                                    <span class="st-input-status po-status" aria-hidden="true"></span>
                                    <div class="st-suggestions st-suggestions--po po-suggestions st-hidden"></div>
                                </div>
                                @if($index === 0)
                                <button type="button" id="btn_add_po" class="st-btn st-btn--primary" title="Add Another PO/SO" style="background: #3b82f6; color: white; border: 1px solid #3b82f6; border-radius: 6px; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#2563eb'; this.style.borderColor='#2563eb';" onmouseout="this.style.background='#3b82f6'; this.style.borderColor='#3b82f6';">
                                    <i class="fas fa-plus"></i>
                                </button>
                                @else
                                <button type="button" class="btn-remove-po" title="Delete" style="background: transparent; color: #6b7280; border: 1px solid #d1d5db; border-radius: 6px; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#fee2e2'; this.style.color='#ef4444'; this.style.borderColor='#fca5a5';" onmouseout="this.style.background='transparent'; this.style.color='#6b7280'; this.style.borderColor='#d1d5db';">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                                @endif
                            </div>
                            <div class="st-po-feedback po-feedback st-mt-4" style="display:none;"></div>
                        </div>
                        @endforeach
                    </div>
                    <div class="st-po-hint">Only displays released PO/SO numbers from SAP.</div>
                    <div class="st-po-bypass-row">
                        <input type="checkbox" id="po_bypass_sap" name="bypass_sap" value="1" {{ old('bypass_sap') ? 'checked' : '' }}>
                        <label for="po_bypass_sap">Without SAP API Integration</label>
                    </div>
                    @error('po_number')
                        <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Direction</label>
                    <select name="direction" id="direction"
                        class="st-select{{ $errors->has('direction') ? ' st-input--invalid' : '' }}">
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
                    <select name="actual_gate_id" id="unplanned-gate"
                        class="st-select{{ $errors->has('actual_gate_id') ? ' st-input--invalid' : '' }}" required>
                        <option value="">Choose Gate...</option>
                        @foreach ($gates as $gate)
                            @php
                                $gateLabel = app(\App\Services\SlotService::class)->getGateDisplayName($gate->warehouse_code ?? '', $gate->gate_number ?? '');
                            @endphp
                            <option value="{{ $gate->id_gates }}" data-warehouse-id="{{ $gate->warehouse_id }}" {{ (string) old('actual_gate_id') === (string) $gate->id_gates ? 'selected' : '' }}>
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
                    <input type="text" id="vendor_name" class="st-input" placeholder="Vendor will auto-fill from PO"
                        readonly value="{{ old('vendor_name_manual', '') }}">
                    <input type="hidden" name="vendor_name_manual" id="vendor_name_manual"
                        value="{{ old('vendor_name_manual', '') }}">
                </div>
                <div class="st-form-field">
                    <label class="st-label">Destination <span class="st-text--optional">(Optional)</span></label>
                    <input type="text" name="destination" class="st-input" value="{{ old('destination') }}"
                        placeholder="e.g., Surabaya">
                    @error('destination')
                        <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field st-hidden" id="vendor_transporter_container">
                    <label class="st-label" for="vendor_transporter_id">Vendor Transporter</label>
                    <div class="st-flex st-align-center st-gap-8">
                        <input type="checkbox" name="use_vendor_transporter" id="use_vendor_transporter" value="1" {{ old('use_vendor_transporter') ? 'checked' : '' }} style="flex-shrink:0;">
                        <div id="vendor_transporter_select_container" class="st-flex-1 {{ old('use_vendor_transporter') ? '' : 'st-hidden' }}">
                            <select name="vendor_transporter_id" id="vendor_transporter_id" class="st-select{{ $errors->has('vendor_transporter_id') ? ' st-input--invalid' : '' }}">
                                <option value="">-- Select Vendor Transporter --</option>
                                @foreach ($vendorTransporters ?? [] as $vt)
                                    <option value="{{ $vt->id_vendor_transporters }}" {{ (string) old('vendor_transporter_id') === (string) $vt->id_vendor_transporters ? 'selected' : '' }}>{{ $vt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @error('vendor_transporter_id')
                        <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="st-form-row st-form-field--mb-12 st-hidden">
                <div class="st-form-field">
                    <label class="st-label">Warehouse</label>
                    <select name="warehouse_id" id="unplanned-warehouse" class="st-select">
                        <option value="">Choose...</option>
                        @foreach ($warehouses as $wh)
                            <option value="{{ $wh->id_wh }}" {{ (string) old('warehouse_id') === (string) $wh->id_wh ? 'selected' : '' }}>
                                {{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field">
                    <label class="st-label">Arrival Time <span class="st-text--danger-dark">*</span></label>
                    <input type="hidden" name="actual_arrival" id="actual_arrival_input"
                        value="{{ old('actual_arrival') }}">
                    <div class="st-grid st-grid-cols-2 st-gap-8">
                        <input type="text" id="actual_arrival_date_input" class="st-input" placeholder="Select Date"
                            autocomplete="off" min="{{ now()->format('Y-m-d') }}">
                        <input type="text" id="actual_arrival_time_input" class="st-input" placeholder="Select Time"
                            autocomplete="off" inputmode="none" readonly>
                    </div>
                    @error('actual_arrival')
                        <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">SJ <span class="st-text--optional">(Optional)</span></label>
                    <input type="text" name="mat_doc" class="st-input" value="{{ old('mat_doc') }}">
                </div>
            </div>

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field">
                    <label class="st-label">Truck Type <span class="st-text--optional">(Optional)</span></label>
                    <select name="truck_type" class="st-select">
                        <option value="">-</option>
                        @foreach ($truckTypes as $tt => $label)
                            @php
                                $value = is_string($tt) ? $tt : (string) $label;
                                $text = is_string($tt) ? (string) $label : (string) $label;
                            @endphp
                            <option value="{{ $value }}" {{ old('truck_type') === $value ? 'selected' : '' }}>{{ $text }}</option>
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
                        <label class="st-flex st-align-center st-gap-6 st-cursor-pointer">
                            <input type="checkbox" name="set_waiting" value="1" {{ old('set_waiting', '') === '1' ? 'checked' : '' }} class="st-checkbox--plain">
                            <span>Set to Waiting</span>
                        </label>
                        <span class="st-text--sm st-text--muted">Unchecked = Completed</span>
                    </div>
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
