@extends('layouts.app')

@section('title', 'Arrival - Slot Time Management')
@section('page_title', 'Arrival')

@section('content')
    <div class="st-card" style="margin-bottom:12px;">
        <div style="font-size:12px;color:#6b7280;">Slot #{{ $slot->id }}</div>
        <div style="font-weight:600;">PO: {{ $slot->truck_number ?? '-' }} | Warehouse: {{ $slot->warehouse_name ?? '-' }} | Planned: {{ $slot->planned_start ?? '-' }}</div>
    </div>

    <div class="st-card">
        <form method="POST" action="{{ route('slots.arrival.store', ['slotId' => $slot->id]) }}">
            @csrf

            <div class="st-form-row" style="margin-bottom:12px;">
                <div class="st-form-field">
                    <label class="st-label">Ticket Number <span style="color:#dc2626;">*</span></label>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <input
                            type="text"
                            name="ticket_number"
                            class="st-input"
                            required
                            value="{{ old('ticket_number', $slot->ticket_number ?? '') }}"
                            readonly
                        >
                        @if (!empty($slot->ticket_number) && in_array((string) ($slot->status ?? ''), ['scheduled', 'waiting', 'in_progress'], true))
                            @unless(optional(auth()->user())->hasRole('Operator'))
                            @can('slots.ticket')
                            <a href="{{ route('slots.ticket', ['slotId' => $slot->id]) }}" class="st-btn" style="background:transparent;color:var(--primary);border:1px solid var(--primary);" style="white-space:nowrap;" onclick="event.preventDefault(); if (window.stPrintTicket) window.stPrintTicket(this.href);">Print Ticket</a>
                            @endcan
                            @endunless
                        @endif
                    </div>
                </div>
                <div class="st-form-field">
                    <label class="st-label">Surat Jalan Number <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="sj_number" class="st-input" required value="{{ old('sj_number') }}" placeholder="Masukkan Nomor Surat Jalan...">
                </div>
            </div>

            <div class="st-form-row" style="margin-bottom:12px;">
                <div class="st-form-field" style="position:relative;">
                    <label class="st-label">Truck Type <span style="color:#dc2626;">*</span></label>
                    <input type="text" id="truck_type_search" class="st-input" autocomplete="off" placeholder="Search Truck Type..." required value="{{ old('truck_type') }}">
                    <input type="hidden" name="truck_type" id="truck_type" value="{{ old('truck_type') }}">
                    <div id="truck_type_suggestions" style="display:none;position:absolute;z-index:25;top:100%;left:0;margin-top:2px;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;max-height:200px;overflow:auto;min-width:220px;"></div>
                </div>
            </div>

            <div style="display:flex;gap:8px;">
                <button type="submit" class="st-btn">Save Arrival</button>
                <a href="{{ route('slots.index') }}" class="st-btn" style="background:transparent;color:var(--primary);border:1px solid var(--primary);">Cancel</a>
            </div>
        </form>
    </div>

    <script type="application/json" id="truck_types_json">{!! json_encode(array_values($truckTypes)) !!}</script>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var truckTypesEl = document.getElementById('truck_types_json');
    var truckTypes = [];
    try {
        truckTypes = truckTypesEl ? JSON.parse(truckTypesEl.textContent || '[]') : [];
    } catch (e) {
        truckTypes = [];
    }

    var searchInput = document.getElementById('truck_type_search');
    var hiddenInput = document.getElementById('truck_type');
    var suggestBox = document.getElementById('truck_type_suggestions');

    function closeSuggestions() {
        if (!suggestBox) return;
        suggestBox.style.display = 'none';
        suggestBox.innerHTML = '';
    }

    function renderSuggestions() {
        if (!searchInput || !suggestBox || !hiddenInput) return;
        var q = (searchInput.value || '').toLowerCase().trim();
        var matches = truckTypes.filter(function (t) {
            return !q || String(t).toLowerCase().indexOf(q) !== -1;
        });

        if (matches.length === 0) {
            suggestBox.innerHTML = '<div style="padding:6px 8px;color:#6b7280;">No Truck Types Found</div>';
            suggestBox.style.display = 'block';
            return;
        }

        var html = '';
        matches.forEach(function (t) {
            var label = String(t)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
            var attr = String(t)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
            html += '<div class="truck-type-item" data-value="' + attr + '" style="padding:6px 8px;cursor:pointer;border-bottom:1px solid #f3f4f6;">' + label + '</div>';
        });

        suggestBox.innerHTML = html;
        suggestBox.style.display = 'block';
    }

    if (searchInput && suggestBox && hiddenInput) {
        searchInput.addEventListener('input', function () {
            hiddenInput.value = (searchInput.value || '').trim();
            renderSuggestions();
        });

        searchInput.addEventListener('focus', function () {
            renderSuggestions();
        });

        suggestBox.addEventListener('click', function (e) {
            var item = e.target.closest('.truck-type-item');
            if (!item) return;
            var val = item.getAttribute('data-value') || '';
            hiddenInput.value = val;
            searchInput.value = val;
            closeSuggestions();
        });

        document.addEventListener('click', function (e) {
            var inside = e.target === searchInput || e.target.closest('#truck_type_suggestions');
            if (!inside) {
                closeSuggestions();
            }
        });

        if ((searchInput.value || '').trim() !== '') {
            hiddenInput.value = (searchInput.value || '').trim();
        }
    }
});
</script>
@endpush
