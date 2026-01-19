@extends('layouts.app')

@section('title', 'Edit Slot - Slot Time Management')
@section('page_title', 'Edit Slot (Scheduled)')

@section('content')
    <div class="st-card" style="margin-bottom:12px;">
        <div class="st-flex-between" style="gap:8px;flex-wrap:wrap;">
            <div>
                <h2 class="st-page-title" style="margin:0;">Edit Slot #{{ $slot->id }}</h2>
                <div style="font-size:12px;color:#6b7280;">Only scheduled planned slots can be edited</div>
            </div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <a href="{{ route('slots.show', ['slotId' => $slot->id]) }}" class="st-btn st-btn--secondary st-btn--sm">Back</a>
            </div>
        </div>
    </div>

    <div class="st-card">
        <form method="POST" action="{{ route('slots.update', ['slotId' => $slot->id]) }}">
            @csrf

            @if ($errors->any())
                <div class="st-alert st-alert--error">
                    <span class="st-alert__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                    <div class="st-alert__text">
                        <div style="font-weight:600;margin-bottom:2px;">Validation error</div>
                        <div style="font-size:12px;">
                            <ul style="margin:0;padding-left:16px;">
                                @foreach ($errors->all() as $msg)
                                    <li>{{ $msg }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <div class="st-form-row" style="margin-bottom:12px;">
                <div class="st-form-field">
                    <label class="st-label">PO/DO Number <span class="st-text--danger-dark">*</span></label>
                    <div style="position:relative;">
                        <input type="text" id="po_number" autocomplete="off" name="po_number" maxlength="12" class="st-input{{ $errors->has('po_number') ? ' st-input--invalid' : '' }}" required value="{{ old('po_number', $slot->truck_number ?? '') }}">
                        <div id="po_suggestions" class="st-suggestions st-suggestions--po" style="display:none;"></div>
                    </div>
                    @error('po_number')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Direction <span class="st-text--danger-dark">*</span></label>
                    <select name="direction" id="direction" class="st-select{{ $errors->has('direction') ? ' st-input--invalid' : '' }}" required>
                        <option value="">Choose direction...</option>
                        <option value="inbound" {{ old('direction', $slot->direction ?? '') === 'inbound' ? 'selected' : '' }}>Inbound</option>
                        <option value="outbound" {{ old('direction', $slot->direction ?? '') === 'outbound' ? 'selected' : '' }}>Outbound</option>
                    </select>
                    @error('direction')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Truck Type <span class="st-text--danger-dark">*</span></label>
                    <select name="truck_type" id="truck_type" class="st-select{{ $errors->has('truck_type') ? ' st-input--invalid' : '' }}" required>
                        <option value="">Choose...</option>
                        @foreach ($truckTypes as $tt)
                            <option value="{{ $tt }}" {{ old('truck_type', $slot->truck_type ?? '') === $tt ? 'selected' : '' }}>{{ $tt }}</option>
                        @endforeach
                    </select>
                    @error('truck_type')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field" style="position:relative;">
                    <label class="st-label">Vendor <span class="st-text--optional">(optional)</span></label>
                    <input
                        type="text"
                        id="vendor_search"
                        class="st-input{{ $errors->has('vendor_id') ? ' st-input--invalid' : '' }}"
                        placeholder="Pilih direction dulu..."
                        style="margin-bottom:4px;"
                        value="{{ old('vendor_search') }}"
                        {{ old('direction', $slot->direction ?? '') ? '' : 'disabled' }}
                    >
                    <input type="hidden" name="vendor_id" id="vendor_id" value="{{ old('vendor_id', $slot->vendor_id ?? '') }}">
                    <div id="vendor_suggestions" class="st-suggestions" style="display:none;"></div>
                    @error('vendor_id')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="st-form-row" style="margin-bottom:12px;">
                <div class="st-form-field">
                    <label class="st-label">Warehouse <span class="st-text--danger-dark">*</span></label>
                    <select name="warehouse_id" id="warehouse_id" class="st-select{{ $errors->has('warehouse_id') ? ' st-input--invalid' : '' }}" required>
                        <option value="">Choose warehouse...</option>
                        @foreach ($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ old('warehouse_id', $slot->warehouse_id ?? '') === (string) $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                    @error('warehouse_id')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Planned Gate <span class="st-text--optional">(optional)</span></label>
                    <select name="planned_gate_id" id="planned_gate_id" class="st-select{{ $errors->has('planned_gate_id') ? ' st-input--invalid' : '' }}">
                        <option value="">- Optional -</option>
                        @foreach ($gates as $gate)
                            <option value="{{ $gate->id }}" {{ old('planned_gate_id', $slot->planned_gate_id ?? '') === (string) $gate->id ? 'selected' : '' }}>{{ $gate->warehouse_name }} - {{ $gate->gate_number }}</option>
                        @endforeach
                    </select>
                    @error('planned_gate_id')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="st-form-row" style="margin-bottom:12px;">
                <div class="st-form-field">
                    <label class="st-label">ETA <span class="st-text--danger-dark">*</span></label>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <input type="text" name="planned_start" id="planned_start_input" class="st-input{{ $errors->has('planned_start') ? ' st-input--invalid' : '' }}" style="flex:1;" required {{ old('warehouse_id', $slot->warehouse_id ?? '') ? '' : 'disabled' }} value="{{ old('planned_start', $slot->planned_start ?? '') }}" placeholder="Select date and time">
                        <button type="button" id="btn_schedule_preview" class="st-btn st-btn--secondary" style="white-space:nowrap;" {{ old('warehouse_id', $slot->warehouse_id ?? '') ? '' : 'disabled' }}>Lihat Jadwal</button>
                    </div>
                    @error('planned_start')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Planned Duration <span class="st-text--optional">(optional)</span></label>
                    <div style="display:flex;gap:4px;">
                        <input type="number" name="planned_duration" class="st-input" style="flex:1;" min="0" max="1440" value="{{ old('planned_duration', $slot->planned_duration ?? '') }}">
                        <select name="duration_unit" class="st-select" style="width:100px;">
                            <option value="minutes">Minutes</option>
                            <option value="hours">Hours</option>
                        </select>
                    </div>
                    @error('planned_duration')
                        <div class="st-text--small st-text--danger" style="margin-top:2px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div style="margin-top:4px;display:flex;gap:8px;">
                <button type="submit" class="st-btn" id="save_button">Save</button>
                <a href="{{ route('slots.index') }}" class="st-btn st-btn--secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script type="application/json" id="truck_types_json">{{ json_encode(array_values($truckTypes)) }}</script>
    <script type="application/json" id="vendors_json">{{ json_encode($vendors->map(function($v) { return ['id' => $v->id, 'name' => $v->name, 'code' => $v->code, 'type' => $v->type]; })->values()) }}</script>
    <script type="application/json" id="truck_type_durations_json">{{ json_encode($truckTypeDurations) }}</script>
@endsection
