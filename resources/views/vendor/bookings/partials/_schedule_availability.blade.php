<!-- Row 1: Schedule + Live Availability -->
<div class="cb-row cb-row--sections">
    <!-- Schedule Section -->
    <div class="cb-section">
        <h3 class="cb-section__title">
            <i class="fas fa-calendar-alt"></i>
            Schedule
        </h3>

        <div class="cb-field">
            <label class="cb-label cb-label--required">Date</label>
            <input type="text"
                   name="planned_date"
                   class="cb-input"
                   id="planned-date"
                   autocomplete="off"
                   readonly
                   value="{{ old('planned_date') }}"
                   required>
            <div class="cb-hint">Minimum 4 hours from now. No Sundays or holidays.</div>
            @error('planned_date')
                <div class="cb-hint cb-hint--error">{{ $message }}</div>
            @enderror
        </div>

        <div class="cb-field">
            <label class="cb-label cb-label--required">Time</label>
            <input type="text"
                   name="planned_time"
                   class="cb-input"
                   id="planned-time"
                   inputmode="none"
                   readonly
                   value="{{ old('planned_time', '08:00') }}"
                   required>
            <div class="cb-hint">Operating hours: 07:00 - 19:00</div>
            @error('planned_time')
                <div class="cb-hint cb-hint--error">{{ $message }}</div>
            @enderror
        </div>

        <input type="hidden" name="planned_duration" id="planned-duration" value="{{ old('planned_duration', 60) }}">
        <input type="hidden" name="planned_start" id="planned-start" value="{{ old('planned_start') }}">
    </div>

    <!-- Mini Availability Section -->
    <div class="cb-section">
        <h3 class="cb-section__title">
            <i class="fas fa-clock"></i>
            Live Availability
        </h3>
        <div class="cb-availability-mini" id="mini-availability">
            <div class="cb-availability-mini__placeholder">Select date to see available hours</div>
        </div>
    </div>
</div>
