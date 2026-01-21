@extends('vendor.layouts.vendor')

@section('title', 'Confirm Booking - Vendor Portal')

@section('content')
<div class="vendor-card">
    <div class="vendor-card__header">
        <h1 class="vendor-card__title">
            <i class="fas fa-check-circle"></i>
            Confirm Rescheduled Booking
        </h1>
        <a href="{{ route('vendor.bookings.show', $booking->id) }}" class="vendor-btn vendor-btn--secondary">
            <i class="fas fa-arrow-left"></i>
            Back
        </a>
    </div>

    <div class="vendor-alert vendor-alert--info">
        <i class="fas fa-info-circle"></i>
        <div>
            <strong>Admin Has Rescheduled Your Booking.</strong>
            <p style="margin: 0.25rem 0 0;">Please review the new schedule below and confirm, reject, or propose a different time.</p>
        </div>
    </div>

    <!-- Schedule Comparison -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin: 1.5rem 0;">
        <!-- Original Request -->
        <div style="padding: 1.25rem; background: #fee2e2; border-radius: 12px; border: 1px solid #fecaca;">
            <h3 style="margin: 0 0 1rem; color: #991b1b; font-size: 0.875rem; font-weight: 600;">
                <i class="fas fa-times-circle"></i>
                Your Original Request
            </h3>
            <table style="width: 100%; font-size: 0.875rem;">
                <tr>
                    <td style="padding: 0.25rem 0; color: #7f1d1d;">Date</td>
                    <td style="padding: 0.25rem 0; font-weight: 500; text-align: right;">
                        {{ $booking->original_planned_start?->format('d M Y') ?? $booking->requested_at?->format('d M Y') ?? '-' }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.25rem 0; color: #7f1d1d;">Time</td>
                    <td style="padding: 0.25rem 0; font-weight: 500; text-align: right;">
                        {{ $booking->original_planned_start?->format('H:i') ?? '-' }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.25rem 0; color: #7f1d1d;">Gate</td>
                    <td style="padding: 0.25rem 0; font-weight: 500; text-align: right;">
                        {{ $booking->originalPlannedGate?->name ?? 'Auto-assign' }}
                    </td>
                </tr>
            </table>
        </div>

        <!-- Arrow -->
        <div style="display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-arrow-right" style="font-size: 2rem; color: #3b82f6;"></i>
        </div>

        <!-- New Schedule -->
        <div style="padding: 1.25rem; background: #dcfce7; border-radius: 12px; border: 1px solid #86efac;">
            <h3 style="margin: 0 0 1rem; color: #166534; font-size: 0.875rem; font-weight: 600;">
                <i class="fas fa-check-circle"></i>
                New Schedule (Admin Proposed)
            </h3>
            <table style="width: 100%; font-size: 0.875rem;">
                <tr>
                    <td style="padding: 0.25rem 0; color: #14532d;">Date</td>
                    <td style="padding: 0.25rem 0; font-weight: 600; text-align: right;">
                        {{ $booking->planned_start?->format('d M Y') ?? '-' }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.25rem 0; color: #14532d;">Time</td>
                    <td style="padding: 0.25rem 0; font-weight: 600; text-align: right;">
                        {{ $booking->planned_start?->format('H:i') ?? '-' }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.25rem 0; color: #14532d;">Duration</td>
                    <td style="padding: 0.25rem 0; font-weight: 600; text-align: right;">
                        {{ $booking->planned_duration }} Min
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.25rem 0; color: #14532d;">Gate</td>
                    <td style="padding: 0.25rem 0; font-weight: 600; text-align: right;">
                        {{ $booking->plannedGate?->name ?? 'TBD' }}
                    </td>
                </tr>
            </table>
        </div>
    </div>

    @if($booking->approval_notes)
    <div style="padding: 1rem; background: #f8fafc; border-radius: 10px; margin-bottom: 1.5rem;">
        <strong style="color: #374151;">Admin Notes:</strong>
        <p style="margin: 0.5rem 0 0; color: #64748b;">{{ $booking->approval_notes }}</p>
    </div>
    @endif

    <!-- Action Buttons -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 2rem;">
        <!-- Confirm -->
        <form method="POST" action="{{ route('vendor.bookings.confirm.store', $booking->id) }}">
            @csrf
            <input type="hidden" name="action" value="confirm">
            <button type="submit" class="vendor-btn vendor-btn--success" style="width: 100%; padding: 1.25rem; font-size: 1rem;">
                <i class="fas fa-check"></i>
                Accept New Schedule
            </button>
        </form>

        <!-- Reject -->
        <button type="button" class="vendor-btn vendor-btn--danger" style="width: 100%; padding: 1.25rem; font-size: 1rem;" onclick="document.getElementById('reject-form').style.display='block'; this.style.display='none';">
            <i class="fas fa-times"></i>
            Reject & Cancel
        </button>

        <!-- Propose New -->
        <button type="button" class="vendor-btn vendor-btn--primary" style="width: 100%; padding: 1.25rem; font-size: 1rem;" onclick="document.getElementById('propose-form').style.display='block';">
            <i class="fas fa-calendar-alt"></i>
            Propose Different Time
        </button>
    </div>

    <!-- Reject Form (Hidden) -->
    <form method="POST" action="{{ route('vendor.bookings.confirm.store', $booking->id) }}" id="reject-form" style="display: none; margin-top: 1.5rem; padding: 1.5rem; background: #fee2e2; border-radius: 12px;">
        @csrf
        <input type="hidden" name="action" value="reject">
        
        <h3 style="margin: 0 0 1rem; color: #991b1b;">
            <i class="fas fa-times-circle"></i>
            Reject Booking
        </h3>
        
        <div class="vendor-form-group">
            <label class="vendor-form-label">Reason for Rejection <span style="color: #ef4444;">*</span></label>
            <textarea name="reason" class="vendor-form-textarea" rows="3" required 
                      placeholder="Please Explain Why You're Rejecting This Schedule..."></textarea>
        </div>
        
        <div style="display: flex; gap: 1rem;">
            <button type="submit" class="vendor-btn vendor-btn--danger">
                <i class="fas fa-times"></i>
                Confirm Rejection
            </button>
            <button type="button" class="vendor-btn vendor-btn--secondary" onclick="document.getElementById('reject-form').style.display='none';">
                Cancel
            </button>
        </div>
    </form>

    <!-- Propose New Schedule Form (Hidden) -->
    <form method="POST" action="{{ route('vendor.bookings.confirm.store', $booking->id) }}" id="propose-form" style="display: none; margin-top: 1.5rem; padding: 1.5rem; background: #dbeafe; border-radius: 12px;">
        @csrf
        <input type="hidden" name="action" value="propose">
        
        <h3 style="margin: 0 0 1rem; color: #1e40af;">
            <i class="fas fa-calendar-alt"></i>
            Propose New Schedule
        </h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div class="vendor-form-group" style="margin-bottom: 0;">
                <label class="vendor-form-label">Date <span style="color: #ef4444;">*</span></label>
                <input type="date" name="planned_date" class="vendor-form-input" required
                       min="{{ date('Y-m-d') }}" value="{{ $booking->planned_start?->format('Y-m-d') }}">
            </div>
            
            <div class="vendor-form-group" style="margin-bottom: 0;">
                <label class="vendor-form-label">Time <span style="color: #ef4444;">*</span></label>
                <input type="time" name="planned_time" class="vendor-form-input" required
                       min="07:00" max="22:00" value="{{ $booking->planned_start?->format('H:i') }}">
            </div>
            
            <div class="vendor-form-group" style="margin-bottom: 0;">
                <label class="vendor-form-label">Duration (Min) <span style="color: #ef4444;">*</span></label>
                <input type="number" name="planned_duration" class="vendor-form-input" required
                       min="30" max="480" step="10" value="{{ $booking->planned_duration }}">
            </div>
            
            <div class="vendor-form-group" style="margin-bottom: 0;">
                <label class="vendor-form-label">Gate (Optional)</label>
                <select name="planned_gate_id" class="vendor-form-select">
                    <option value="">Auto-assign</option>
                    @foreach($gates[$booking->warehouse_id] ?? [] as $gate)
                        <option value="{{ $gate->id }}" {{ $booking->planned_gate_id == $gate->id ? 'selected' : '' }}>
                            {{ $gate->name ?? 'Gate ' . $gate->gate_number }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        
        <div class="vendor-form-group" style="margin-top: 1rem;">
            <label class="vendor-form-label">Notes (Optional)</label>
            <textarea name="notes" class="vendor-form-textarea" rows="2" 
                      placeholder="Any Additional Notes..."></textarea>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 1rem;">
            <button type="submit" class="vendor-btn vendor-btn--primary">
                <i class="fas fa-paper-plane"></i>
                Submit New Proposal
            </button>
            <button type="button" class="vendor-btn vendor-btn--secondary" onclick="document.getElementById('propose-form').style.display='none';">
                Cancel
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.location && window.location.hash === '#propose') {
        var el = document.getElementById('propose-form');
        if (el) {
            el.style.display = 'block';
            try { el.scrollIntoView({ behavior: 'smooth', block: 'start' }); } catch (e) {}
        }
    }
});
</script>
@endsection
