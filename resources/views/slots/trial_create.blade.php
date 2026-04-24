@extends('layouts.app')

@section('title', 'Create Planned Uji Coba - e-Docking Control System')
@section('page_title', 'Create Planned Uji Coba')

@section('content')
    <div class="st-card">
        {{-- Trial mode banner --}}
        <div class="st-alert st-alert--info st-mb-0" style="border-radius:0;border-left:4px solid var(--st-color-info,#3b82f6);background:rgba(59,130,246,.08);">
            <span class="st-alert__icon"><i class="fa-solid fa-flask"></i></span>
            <div class="st-alert__text">
                <strong>Mode Uji Coba</strong> — Form ini <em>tidak</em> terhubung ke SAP.
                Data vendor/customer diambil dari master data lokal (<a href="{{ route('md_bp.index') }}" class="st-link">md_bp</a>).
                Nomor referensi bisa diisi bebas.
            </div>
        </div>

        <form method="POST" action="{{ route('slots.trial.store') }}" enctype="multipart/form-data">
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
                    <button type="button" class="st-alert__close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
                </div>
            @endif

            {{-- Row 1: Nomor Referensi, BP (Vendor/Customer), Truck Type  --}}
            <div class="st-form-row st-form-field--mb-12 st-form-row--grid-3">
                <div class="st-form-field">
                    <label class="st-label">Nomor Referensi / PO <span class="st-text--danger-dark">*</span></label>
                    <div class="st-form-field--relative">
                        <input
                            type="text"
                            id="trial_po_number"
                            name="po_number"
                            autocomplete="off"
                            class="st-input{{ $errors->has('po_number') ? ' st-input--invalid' : '' }}"
                            placeholder="Contoh: TEST-001, PO-2026-001"
                            required
                            value="{{ old('po_number') }}"
                        >
                    </div>
                    @error('po_number')
                        <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Vendor / Customer <span class="st-text--danger-dark">*</span></label>
                    <select name="bp_id" id="trial_bp_id" class="st-select{{ $errors->has('bp_id') ? ' st-input--invalid' : '' }}" required>
                        <option value="">- Pilih Vendor / Customer -</option>
                        @foreach ($vendors as $v)
                            <option
                                value="{{ $v->id }}"
                                data-type="{{ $v->bp_type }}"
                                {{ (string)old('bp_id') === (string)$v->id ? 'selected' : '' }}
                            >
                                [{{ strtoupper($v->bp_type) }}] {{ $v->bp_code }} — {{ $v->bp_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('bp_id')
                        <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Truck Type <span class="st-text--danger-dark">*</span></label>
                    <select name="truck_type" id="trial_truck_type" class="st-select{{ $errors->has('truck_type') ? ' st-input--invalid' : '' }}" required>
                        <option value="">Choose...</option>
                        @foreach ($truckTypes as $tt)
                            <option value="{{ $tt }}" {{ old('truck_type') === $tt ? 'selected' : '' }}>{{ $tt }}</option>
                        @endforeach
                    </select>
                    @error('truck_type')
                        <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Row 2: Direction, Gate, ETA --}}
            <div class="st-form-row st-form-field--mb-12 st-form-row--grid-3">
                <div class="st-form-field">
                    <label class="st-label">Direction <span class="st-text--danger-dark">*</span></label>
                    <select name="direction" id="trial_direction" class="st-select{{ $errors->has('direction') ? ' st-input--invalid' : '' }}" required>
                        <option value="">Choose Direction...</option>
                        <option value="inbound"  {{ old('direction') === 'inbound'  ? 'selected' : '' }}>Inbound (Vendor)</option>
                        <option value="outbound" {{ old('direction') === 'outbound' ? 'selected' : '' }}>Outbound (Customer)</option>
                    </select>
                    @error('direction')
                        <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Planned Gate <span class="st-text--danger-dark">*</span></label>
                    <select name="planned_gate_id" id="trial_planned_gate_id" class="st-select{{ $errors->has('planned_gate_id') ? ' st-input--invalid' : '' }}" required>
                        <option value="">Choose Gate...</option>
                        @foreach ($gates as $gate)
                            @php
                                $gateLabel = app(\App\Services\SlotService::class)->getGateDisplayName($gate->warehouse_code ?? '', $gate->gate_number ?? '');
                            @endphp
                            <option
                                value="{{ $gate->id }}"
                                data-warehouse-id="{{ $gate->warehouse_id }}"
                                {{ (string)old('planned_gate_id') === (string)$gate->id ? 'selected' : '' }}
                            >
                                {{ $gateLabel }}
                            </option>
                        @endforeach
                    </select>
                    @error('planned_gate_id')
                        <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">ETA <span class="st-text--danger-dark">*</span></label>
                    <input type="hidden" name="planned_start" id="trial_planned_start_input" value="{{ old('planned_start') }}">
                    <div class="st-flex st-gap-8">
                        <input type="text" id="trial_planned_start_date_input" class="st-input" placeholder="Select Date" autocomplete="off" min="{{ now()->format('Y-m-d') }}">
                        <input type="text" id="trial_planned_start_time_input" class="st-input" placeholder="Select Time" autocomplete="off" inputmode="none" readonly>
                    </div>
                    @error('planned_start')
                        <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Row 3: Duration, Vehicle Number, Driver Name --}}
            <div class="st-form-row st-form-field--mb-12 st-form-row--grid-3 st-form-row--align-end">
                <div class="st-form-field">
                    <label class="st-label">Planned Duration</label>
                    <div class="st-flex st-gap-4">
                        <input type="number" name="planned_duration" class="st-input{{ $errors->has('planned_duration') ? ' st-input--invalid' : '' }} st-flex-1"
                               value="{{ old('planned_duration', '') }}" min="1" id="trial_planned_duration_input">
                        <span class="st-text--small st-text--muted st-align-self-center st-nowrap">Min</span>
                    </div>
                    @error('planned_duration')
                        <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Vehicle Number <span class="st-text--optional">(optional)</span></label>
                    <input type="text" name="vehicle_number_snap"
                           class="st-input{{ $errors->has('vehicle_number_snap') ? ' st-input--invalid' : '' }}"
                           value="{{ old('vehicle_number_snap') }}" placeholder="e.g., B 1234 ABC">
                    @error('vehicle_number_snap')
                        <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Driver Name <span class="st-text--optional">(optional)</span></label>
                    <input type="text" name="driver_name"
                           class="st-input{{ $errors->has('driver_name') ? ' st-input--invalid' : '' }}"
                           value="{{ old('driver_name') }}" placeholder="e.g., Budi">
                    @error('driver_name')
                        <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Row 4: Destination, Driver Number / Vendor Transporter, Risk/Schedule --}}
            <div class="st-form-row st-form-field--mb-12 st-form-row--grid-3 st-form-row--align-end">
                <div class="st-form-field {{ old('direction') === 'outbound' ? '' : 'st-hidden' }}" id="trial_destination_container">
                    <label class="st-label">Destination <span class="st-text--optional">(Optional)</span></label>
                    <input type="text" name="destination" class="st-input{{ $errors->has('destination') ? ' st-input--invalid' : '' }}" value="{{ old('destination') }}" placeholder="e.g., Surabaya">
                    @error('destination')
                        <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field" id="trial_driver_number_container">
                    <label class="st-label">Driver Number <span class="st-text--optional">(optional)</span></label>
                    <input type="text" name="driver_number"
                           class="st-input{{ $errors->has('driver_number') ? ' st-input--invalid' : '' }}"
                           value="{{ old('driver_number') }}" placeholder="e.g., 08xxxxxxxxxx">
                    @error('driver_number')
                        <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="st-form-field st-hidden" id="trial_vendor_transporter_container">
                    <label class="st-label st-flex st-align-center st-gap-8 st-cursor-pointer" style="display:inline-flex;">
                        <input type="checkbox" name="use_vendor_transporter" id="trial_use_vendor_transporter" value="1" {{ old('use_vendor_transporter') ? 'checked' : '' }}>
                        <span class="st-font-semibold">Use Vendor Transporter</span>
                    </label>
                    <div id="trial_vendor_transporter_select_container" class="st-mt-8 {{ old('use_vendor_transporter') ? '' : 'st-hidden' }}">
                        <select name="vendor_transporter_id" id="trial_vendor_transporter_id" class="st-select{{ $errors->has('vendor_transporter_id') ? ' st-input--invalid' : '' }}">
                            <option value="">-- Select Vendor Transporter --</option>
                            @foreach ($vendorTransporters ?? [] as $vt)
                                <option value="{{ $vt->id }}" {{ (string)old('vendor_transporter_id') === (string)$vt->id ? 'selected' : '' }}>{{ $vt->name }}</option>
                            @endforeach
                        </select>
                        @error('vendor_transporter_id')
                            <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="st-form-field">
                    <div class="st-form-row--grid-risk">
                        <div>
                            <label class="st-label">Risk &amp; Schedule</label>
                            <div id="trial_risk_preview" class="st-text--muted st-text--xs">Risk Not Calculated.</div>
                            <div id="trial_time_warning" class="st-text--small st-text--danger st-mt-1"></div>
                        </div>
                        <div>
                            <label class="st-label">View Schedule</label>
                            <button type="button" id="trial_btn_schedule_preview" class="st-btn st-btn--xs st-nowrap" disabled>View Schedule</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Row 5: Notes --}}
            <div class="st-form-row st-form-field--mb-12 st-form-row--grid-1">
                <div class="st-form-field">
                    <label class="st-label">Notes <span class="st-text--optional">(optional)</span></label>
                    <input type="text" name="notes" class="st-input{{ $errors->has('notes') ? ' st-input--invalid' : '' }}"
                           value="{{ old('notes') }}" placeholder="Catatan uji coba...">
                    @error('notes')
                        <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Hidden warehouse select —mirrors slots.create pattern --}}
            <div class="st-form-field st-form-field--hidden">
                <label class="st-label">Warehouse</label>
                <select name="warehouse_id" id="trial_warehouse_id" class="st-select">
                    <option value="">Choose...</option>
                    @foreach ($warehouses as $wh)
                        <option value="{{ $wh->id }}" {{ (string)old('warehouse_id') === (string)$wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="st-form-actions st-mt-4">
                <button type="submit" class="st-btn" id="trial_save_button">Simpan Uji Coba</button>
                <a href="{{ route('slots.index') }}" class="st-btn st-btn--outline-primary">Cancel</a>
            </div>
        </form>
    </div>

    {{-- Schedule Preview Modal (reuse same structure as slots.create) --}}
    <div id="trial_schedule_modal" class="st-modal">
        <div class="st-modal__content st-modal__content--schedule">
            <div class="st-modal__header">
                <h3 class="st-modal__title">Schedule Preview</h3>
                <button type="button" id="trial_schedule_modal_close" class="st-btn st-btn--sm st-modal__close">Close</button>
            </div>
            <div class="st-modal__body">
                <div id="trial_schedule_modal_info" class="st-modal__info"></div>
                <div class="st-modal__table-wrap">
                    <table class="st-table st-table--sm">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>PO/SO</th>
                                <th>Truck</th>
                                <th>Vendor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="trial_schedule_modal_body"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- JSON configs reused from slots-create.js —same AJAX endpoints --}}
    <script type="application/json" id="truck_type_durations_json">{!! json_encode($truckTypeDurations ?? []) !!}</script>
    <script type="application/json" id="slot_routes_json">{!! json_encode([
        'check_risk'           => route('slots.ajax.check_risk'),
        'check_slot_time'      => route('slots.ajax.check_slot_time'),
        'recommend_gate'       => route('slots.ajax.recommend_gate'),
        'schedule_preview'     => route('slots.ajax.schedule_preview'),
    ]) !!}</script>
@endsection

@push('scripts')
@vite(['resources/js/pages/slots-trial-create.js'])
@endpush
