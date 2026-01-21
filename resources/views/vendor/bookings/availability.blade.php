@extends('vendor.layouts.vendor')

@section('title', 'Slot Availability - Vendor Portal')

@section('content')
<div class="vendor-card">
    <div class="vendor-card__header">
        <h1 class="vendor-card__title">
            <i class="fas fa-calendar-alt"></i>
            Slot Availability
        </h1>
        <a href="{{ route('vendor.bookings.create') }}" class="vendor-btn vendor-btn--primary">
            <i class="fas fa-plus"></i>
            New Booking
        </a>
    </div>

    <!-- Filters -->
    <form method="GET" action="{{ route('vendor.availability') }}" style="margin-bottom: 1.5rem;">
        <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
            <div class="vendor-form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                <label class="vendor-form-label">Warehouse</label>
                <select name="warehouse_id" class="vendor-form-select" id="warehouse_select" onchange="this.form.submit()">
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ $selectedWarehouse?->id == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->wh_code }} - {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="vendor-form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                <label class="vendor-form-label">Date</label>
                <input type="date" name="date" class="vendor-form-input" id="date_select" 
                       value="{{ $selectedDate }}" min="{{ date('Y-m-d') }}" onchange="this.form.submit()">
            </div>
        </div>
    </form>

    <!-- Calendar Legend -->
    <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; margin-bottom: 1.5rem; font-size: 0.875rem;">
        <span style="display: flex; align-items: center; gap: 0.5rem;">
            <span style="width: 24px; height: 24px; background: #f0fdf4; border: 2px solid #86efac; border-radius: 4px;"></span>
            Available
        </span>
        <span style="display: flex; align-items: center; gap: 0.5rem;">
            <span style="width: 24px; height: 24px; background: #fef3c7; border: 2px solid #fcd34d; border-radius: 4px;"></span>
            Pending Approval
        </span>
        <span style="display: flex; align-items: center; gap: 0.5rem;">
            <span style="width: 24px; height: 24px; background: #dcfce7; border: 2px solid #22c55e; border-radius: 4px;"></span>
            Scheduled
        </span>
        <span style="display: flex; align-items: center; gap: 0.5rem;">
            <span style="width: 24px; height: 24px; background: #dbeafe; border: 2px solid #3b82f6; border-radius: 4px;"></span>
            Arrived
        </span>
        <span style="display: flex; align-items: center; gap: 0.5rem;">
            <span style="width: 24px; height: 24px; background: #ede9fe; border: 2px solid #8b5cf6; border-radius: 4px;"></span>
            In Progress
        </span>
        <span style="display: flex; align-items: center; gap: 0.5rem;">
            <span style="width: 24px; height: 24px; background: #fce7f3; border: 2px solid #ec4899; border-radius: 4px;"></span>
            Needs Confirmation
        </span>
    </div>

    <!-- Timeline Calendar -->
    <div id="calendar-container" style="overflow-x: auto;">
        <div style="text-align: center; padding: 3rem; color: #64748b;">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p style="margin-top: 1rem;">Loading Availability...</p>
        </div>
    </div>
</div>

<!-- Operating Hours Info -->
<div class="vendor-card">
    <div class="vendor-card__header">
        <h2 class="vendor-card__title">
            <i class="fas fa-info-circle"></i>
            Booking Information
        </h2>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
        <div style="padding: 1rem; background: #f8fafc; border-radius: 10px;">
            <h4 style="margin: 0 0 0.5rem; color: #1e293b;">
                <i class="fas fa-clock" style="color: #3b82f6;"></i>
                Operating Hours
            </h4>
            <p style="margin: 0; color: #64748b;">
                <strong>07:00 - 23:00</strong> daily
            </p>
        </div>
        
        <div style="padding: 1rem; background: #f8fafc; border-radius: 10px;">
            <h4 style="margin: 0 0 0.5rem; color: #1e293b;">
                <i class="fas fa-hourglass-half" style="color: #10b981;"></i>
                Slot Duration
            </h4>
            <p style="margin: 0; color: #64748b;">
                Minimum <strong>30 Minutes</strong>, Based on Truck Type
            </p>
        </div>
        
        <div style="padding: 1rem; background: #f8fafc; border-radius: 10px;">
            <h4 style="margin: 0 0 0.5rem; color: #1e293b;">
                <i class="fas fa-check-double" style="color: #8b5cf6;"></i>
                Approval Process
            </h4>
            <p style="margin: 0; color: #64748b;">
                Admin Will Review and Approve Your Booking
            </p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const warehouseId = '{{ $selectedWarehouse?->id }}';
    const selectedDate = '{{ $selectedDate }}';
    
    if (warehouseId && selectedDate) {
        loadCalendar(warehouseId, selectedDate);
    }

    function loadCalendar(warehouseId, date) {
        const container = document.getElementById('calendar-container');
        
        fetch(`{{ route('vendor.ajax.calendar_slots') }}?warehouse_id=${warehouseId}&date=${date}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.gates) {
                    renderCalendar(data.gates, date);
                } else {
                    container.innerHTML = '<p style="text-align: center; color: #ef4444; padding: 2rem;">Failed to Load Availability</p>';
                }
            })
            .catch(error => {
                container.innerHTML = '<p style="text-align: center; color: #ef4444; padding: 2rem;">Error Loading Availability</p>';
            });
    }

    function renderCalendar(gates, date) {
        const container = document.getElementById('calendar-container');
        const hours = [];
        
        for (let h = 7; h < 23; h++) {
            hours.push(h.toString().padStart(2, '0') + ':00');
            hours.push(h.toString().padStart(2, '0') + ':30');
        }

        const statusColors = {
            'pending_approval': { bg: '#fef3c7', border: '#fcd34d', text: '#92400e' },
            'scheduled': { bg: '#dcfce7', border: '#22c55e', text: '#166534' },
            'arrived': { bg: '#dbeafe', border: '#3b82f6', text: '#1e40af' },
            'waiting': { bg: '#fef3c7', border: '#f59e0b', text: '#92400e' },
            'in_progress': { bg: '#ede9fe', border: '#8b5cf6', text: '#5b21b6' },
            'pending_vendor_confirmation': { bg: '#fce7f3', border: '#ec4899', text: '#9d174d' },
            'completed': { bg: '#f1f5f9', border: '#94a3b8', text: '#475569' },
        };

        let html = `
            <table style="width: 100%; border-collapse: collapse; min-width: ${100 + gates.length * 150}px;">
                <thead>
                    <tr>
                        <th style="padding: 1rem; background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); color: white; position: sticky; left: 0; z-index: 10; width: 80px; text-align: center; border-radius: 8px 0 0 0;">
                            Time
                        </th>
        `;

        gates.forEach((g, i) => {
            const isLast = i === gates.length - 1;
            html += `
                <th style="padding: 1rem; background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); color: white; min-width: 150px; text-align: center; ${isLast ? 'border-radius: 0 8px 0 0;' : ''}">
                    <i class="fas fa-door-open"></i>
                    ${g.gate.name}
                </th>
            `;
        });

        html += '</tr></thead><tbody>';

        // Track which cells are spanned
        const spannedCells = {};
        gates.forEach((g, gIdx) => {
            spannedCells[gIdx] = {};
        });

        hours.forEach((hour, hIdx) => {
            html += `<tr>`;
            html += `
                <td style="padding: 0.75rem; background: #f8fafc; font-weight: 600; color: #374151; text-align: center; position: sticky; left: 0; z-index: 5; border-bottom: 1px solid #e5e7eb;">
                    ${hour}
                </td>
            `;

            gates.forEach((g, gIdx) => {
                // Check if this cell is spanned
                if (spannedCells[gIdx][hIdx]) {
                    return; // Skip - already covered by rowspan
                }

                // Find slot that starts at this hour
                const slot = g.slots.find(s => s.start_time === hour);
                
                if (slot) {
                    // Calculate rows to span (each row is 30 min)
                    const rowspan = Math.max(1, Math.ceil(slot.duration / 30));
                    
                    // Mark cells as spanned
                    for (let i = 1; i < rowspan; i++) {
                        if (hIdx + i < hours.length) {
                            spannedCells[gIdx][hIdx + i] = true;
                        }
                    }

                    const colors = statusColors[slot.status] || { bg: '#f3f4f6', border: '#d1d5db', text: '#374151' };
                    
                    html += `
                        <td rowspan="${rowspan}" style="padding: 0.5rem; background: ${colors.bg}; border: 2px solid ${colors.border}; border-radius: 8px; vertical-align: top;">
                            <div style="font-weight: 600; color: ${colors.text}; font-size: 0.75rem;">
                                ${slot.start_time} - ${slot.end_time}
                            </div>
                            <div style="font-size: 0.7rem; color: ${colors.text}; opacity: 0.8; margin-top: 0.25rem;">
                                ${slot.vendor_name}
                            </div>
                            <div style="margin-top: 0.5rem;">
                                <span style="display: inline-block; padding: 0.125rem 0.5rem; background: ${colors.border}; color: white; border-radius: 9999px; font-size: 0.625rem; font-weight: 600;">
                                    ${slot.status_label}
                                </span>
                            </div>
                        </td>
                    `;
                } else {
                    // Check if occupied by slot from earlier
                    const occupiedSlot = g.slots.find(s => {
                        const startIdx = hours.indexOf(s.start_time);
                        if (startIdx === -1) return false;
                        const endIdx = startIdx + Math.ceil(s.duration / 30);
                        return hIdx > startIdx && hIdx < endIdx;
                    });

                    if (!occupiedSlot) {
                        html += `
                            <td style="padding: 0.75rem; background: #f0fdf4; border: 1px dashed #86efac; text-align: center; cursor: pointer; transition: all 0.2s;" 
                                onclick="bookSlot('${date}', '${hour}', ${g.gate.id})"
                                onmouseover="this.style.background='#dcfce7'"
                                onmouseout="this.style.background='#f0fdf4'">
                                <i class="fas fa-plus" style="color: #22c55e; opacity: 0.5;"></i>
                            </td>
                        `;
                    }
                }
            });

            html += '</tr>';
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    }

    // Function to book a slot
    window.bookSlot = function(date, time, gateId) {
        const url = new URL('{{ route("vendor.bookings.create") }}', window.location.origin);
        url.searchParams.set('date', date);
        url.searchParams.set('time', time);
        url.searchParams.set('gate_id', gateId);
        url.searchParams.set('warehouse_id', '{{ $selectedWarehouse?->id }}');
        window.location.href = url.toString();
    };
});
</script>
@endpush
