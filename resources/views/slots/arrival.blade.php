@extends('layouts.app')

@section('title', 'Arrival - Slot Time Management')
@section('page_title', 'Arrival')

@section('content')
    <div class="st-card st-mb-12">
        <div class="st-text--sm st-text--muted">Slot #{{ $slot->id }}</div>
        <div class="st-font-semibold">PO: {{ $slot->truck_number ?? '-' }} | Warehouse: {{ $slot->warehouse_name ?? '-' }} | Planned: {{ $slot->planned_start ?? '-' }}</div>
    </div>

    <div class="st-card">
        <form method="POST" action="{{ route('slots.arrival.store', ['slotId' => $slot->id]) }}">
            @csrf

            <div class="st-form-row st-form-field--mb-12">
                <div class="st-form-field">
                    <label class="st-label">Scan Ticket / Manual Input <span class="st-text--danger-dark">*</span></label>
                    <div class="st-flex st-gap-8 st-align-center">
                        <input
                            type="text"
                            name="ticket_number"
                            class="st-input"
                            required
                            value="{{ old('ticket_number') }}"
                            placeholder="Scan barcode or enter ticket number..."
                        >
                        <button type="button" id="btn_scan_ticket" class="st-btn st-btn--secondary st-btn--pad-md st-nowrap" title="Scan via Camera">
                            <i class="fas fa-camera"></i>
                        </button>
                        @if (!empty($slot->ticket_number) && in_array((string) ($slot->status ?? ''), ['scheduled', 'waiting', 'in_progress'], true))
                            @unless(optional(auth()->user())->hasRole('Operator'))
                            @can('slots.ticket')
                            <a href="{{ route('slots.ticket', ['slotId' => $slot->id]) }}" class="st-btn st-btn--outline-primary st-btn--pad-md" title="Print Ticket" onclick="event.preventDefault(); if (window.stPrintTicket) window.stPrintTicket(this.href);">
                                <i class="fas fa-print"></i>
                            </a>
                            @endcan
                            @endunless
                        @endif
                    </div>
                    <div id="ticket_match_hint" class="st-text--small st-text--danger st-mt-6 st-hidden-soft"></div>
                    <div id="scan_camera_wrap" class="st-hidden-soft st-mt-8 st-border st-rounded-8 st-p-8 st-bg-slate-50">
                        <div class="st-flex st-align-center st-gap-8 st-justify-between">
                            <div class="st-text--sm st-text--slate">Point camera at ticket barcode/QR.</div>
                            <button type="button" id="btn_scan_stop" class="st-btn st-btn--secondary st-btn--xs st-btn--pad-sm" title="Stop Camera">
                                <i class="fas fa-stop"></i>
                            </button>
                        </div>
                        <video id="scan_camera" class="st-w-full st-maxw-360 st-mt-8 st-rounded-6 st-scale-x-neg" autoplay muted playsinline></video>
                        <div id="scan_qr_reader" class="st-w-full st-maxw-360 st-mt-8 st-rounded-6 st-hidden-soft st-scale-x-neg"></div>
                        <div id="scan_camera_status" class="st-text--small st-text--muted st-mt-6"></div>
                    </div>
                    <div class="st-text--small st-text--muted st-mt-4">After ticket is filled, detail form will appear.</div>
                </div>
            </div>

            <div id="arrival_details" class="st-hidden-soft">

                <div class="st-border st-rounded-8 st-p-12 st-bg-slate-50 st-mb-12">
                    <div class="st-font-semibold st-mb-8">Slot Details</div>
                    <div class="st-text--sm st-text--slate st-grid-auto-200">
                        <div><strong>PO/DO:</strong> {{ $slot->po_number ?? $slot->truck_number ?? '-' }}</div>
                        <div><strong>Supplier:</strong> {{ $slot->vendor_name ?? '-' }}</div>
                        <div><strong>Warehouse:</strong> {{ $slot->warehouse_name ?? '-' }}</div>
                        <div><strong>Direction:</strong> {{ ucfirst($slot->direction ?? '-') }}</div>
                        <div><strong>Planned Start:</strong> {{ $slot->planned_start ?? '-' }}</div>
                        <div><strong>Planned Gate:</strong> {{ app(\App\Services\SlotService::class)->getGateDisplayName($slot->planned_gate_warehouse_code ?? '', $slot->planned_gate_number ?? '') }}</div>
                    </div>
                </div>

            <div class="st-form-actions">
                <button type="submit" class="st-btn st-btn--pad-lg" title="Save Arrival">
                    <i class="fas fa-save"></i>
                    <span class="st-ml-6">Save</span>
                </button>
                <a href="{{ route('slots.index') }}" class="st-btn st-btn--outline-primary st-btn--pad-lg" title="Cancel">
                    <i class="fas fa-times"></i>
                    <span class="st-ml-6">Cancel</span>
                </a>
            </div>
        </form>
    </div>

@endsection

@push('scripts')
<script src="{{ asset('js/vendor/html5-qrcode.min.js') }}"></script>
<script type="application/json" id="slots_arrival_config">{!! json_encode([
    'expectedTicket' => (string) ($slot->ticket_number ?? ''),
]) !!}</script>
@vite(['resources/js/pages/slots-arrival.js'])
@endpush

