<!-- Row 2: Vehicle + Documents/Notes Stack -->
<div class="cb-row cb-row--sections">
    <!-- Vehicle & Driver Section -->
    <div class="cb-section">
        <h3 class="cb-section__title">
            <i class="fas fa-truck"></i>
            Vehicle & Driver
        </h3>

        <div class="cb-field">
            <label class="cb-label cb-label--required">Truck Type</label>
            <select name="truck_type" class="cb-select" required>
                <option value="">-- Select Truck Type --</option>
                @foreach($truckTypes as $type)
                    <option value="{{ $type->truck_type }}" data-duration="{{ $type->target_duration_minutes }}" {{ old('truck_type') == $type->truck_type ? 'selected' : '' }}>
                        {{ $type->truck_type }}
                    </option>
                @endforeach
            </select>
            @error('truck_type')
                <div class="cb-hint cb-hint--error">{{ $message }}</div>
            @enderror
        </div>

        <div class="cb-field">
            <label class="cb-label">Vehicle Number</label>
            <input type="text"
                   name="vehicle_number"
                   class="cb-input"
                   placeholder="e.g., B 1234 ABC"
                   value="{{ old('vehicle_number') }}"
                   maxlength="50">
            @error('vehicle_number')
                <div class="cb-hint cb-hint--error">{{ $message }}</div>
            @enderror
        </div>

        <div class="cb-field">
            <label class="cb-label">Driver Name</label>
            <input type="text"
                   name="driver_name"
                   class="cb-input"
                   placeholder="Driver's full name"
                   value="{{ old('driver_name') }}"
                   maxlength="50">
            @error('driver_name')
                <div class="cb-hint cb-hint--error">{{ $message }}</div>
            @enderror
        </div>

        <div class="cb-field">
            <label class="cb-label">Driver Phone</label>
            <input type="text"
                   name="driver_number"
                   class="cb-input"
                   placeholder="e.g., 08123456789"
                   value="{{ old('driver_number') }}"
                   maxlength="50">
            @error('driver_number')
                <div class="cb-hint cb-hint--error">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="cb-stack">
        <!-- Documents Section -->
        <div class="cb-section">
            <h3 class="cb-section__title">
                <i class="fas fa-file-pdf"></i>
                Documents
            </h3>

            <div class="cb-field">
                <label class="cb-label cb-label--required">COA (Certificate of Analysis)</label>
                <input type="file"
                       name="coa_pdf"
                       class="cb-file-input"
                       accept=".pdf"
                       required>
                <div class="cb-hint">PDF only, max 10MB</div>
                <div class="cb-hint cb-hint--error cb-file-error" id="coa-error" hidden>File too large. Max 10MB.</div>
                @error('coa_pdf')
                    <div class="cb-hint cb-hint--error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Notes Section -->
        <div class="cb-section">
            <h3 class="cb-section__title">
                <i class="fas fa-sticky-note"></i>
                Additional Notes
            </h3>

            <div class="cb-field">
                <textarea name="notes"
                          class="cb-textarea"
                          placeholder="Any additional information..."
                          maxlength="500">{{ old('notes') }}</textarea>
                <div class="cb-hint">Maximum 500 characters</div>
                @error('notes')
                    <div class="cb-hint cb-hint--error">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>
