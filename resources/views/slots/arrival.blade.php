@extends('layouts.app')

@section('title', 'Arrival - e-Docking Control System')
@section('page_title', 'Arrival')

@section('content')
    <div class="st-card st-mb-16 st-border-l-4" style="border-left-color: var(--primary);">
        <div class="st-flex st-justify-between st-align-center st-mb-12">
            <h3 class="st-m-0 st-text-16">Arrival Registration</h3>
            <span class="st-badge st-badge--primary st-text--sm">Ref #{{ $slot->id }}</span>
        </div>
        <div class="st-form-row--grid-3 st-text--sm">
            <div class="st-flex st-align-center st-gap-8">
                <div class="st-icon-circle st-bg-slate-100 st-text--slate"><i class="fas fa-truck"></i></div>
                <div>
                    <div class="st-text--xs st-text--muted">PO / DO</div>
                    <div class="st-font-semibold">{{ $slot->truck_number ?? '-' }}</div>
                </div>
            </div>
            <div class="st-flex st-align-center st-gap-8">
                <div class="st-icon-circle st-bg-slate-100 st-text--slate"><i class="fas fa-warehouse"></i></div>
                <div>
                    <div class="st-text--xs st-text--muted">Warehouse</div>
                    <div class="st-font-semibold">{{ $slot->warehouse_name ?? '-' }}</div>
                </div>
            </div>
            <div class="st-flex st-align-center st-gap-8">
                <div class="st-icon-circle st-bg-slate-100 st-text--slate"><i class="fas fa-clock"></i></div>
                <div>
                    <div class="st-text--xs st-text--muted">Planned ETA</div>
                    <div class="st-flex st-flex-col st-gap-2 st-mt-2">
                        @if(isset($slot->planned_start))
                            @php $eta = \Carbon\Carbon::parse($slot->planned_start); @endphp
                            <div class="st-font-semibold st-flex st-align-center st-gap-6"><i class="far fa-calendar-alt st-text--slate st-text-12"></i> {{ $eta->format('d-m-Y') }}</div>
                            <div class="st-font-semibold st-flex st-align-center st-gap-6"><i class="far fa-clock st-text--slate st-text-12"></i> {{ $eta->format('H:i') }}</div>
                        @else
                            <div class="st-font-semibold">-</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
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
                            @can('slots.ticket')
                            <a href="{{ route('slots.ticket', ['slotId' => $slot->id]) }}" class="st-btn st-btn--outline-primary st-btn--pad-md" title="Print Ticket" onclick="event.preventDefault(); if (window.stPrintTicket) window.stPrintTicket(this.href);">
                                <i class="fas fa-print"></i>
                            </a>
                            @endcan
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

                <div class="st-border st-border-slate-200 st-rounded-10 st-p-20 st-mb-16 st-bg-white st-shadow-sm">
                    <div class="st-font-semibold st-mb-16 st-text-16 st-border-b st-border-slate-100 st-pb-10">
                        <i class="fas fa-list-alt st-text--muted st-mr-8"></i> Booking Information
                    </div>
                    <div class="st-form-row--grid-2 st-gap-24">
                        <div class="st-flex st-flex-col st-gap-16">
                            <div>
                                <div class="st-text--sm st-text--muted st-mb-4">Supplier / Vendor</div>
                                <div class="st-font-semibold st-text-15">{{ $slot->vendor_name ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="st-text--sm st-text--muted st-mb-4">PO/DO Number</div>
                                <div class="st-font-semibold st-text-15">{{ $slot->po_number ?? $slot->truck_number ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="st-text--sm st-text--muted st-mb-4">Activity Direction</div>
                                <div class="st-font-semibold st-text-15 st-text--primary">{{ ucfirst($slot->direction ?? '-') }}</div>
                            </div>
                        </div>
                        <div class="st-flex st-flex-col st-gap-16">
                            <div>
                                <div class="st-text--sm st-text--muted st-mb-4">Warehouse</div>
                                <div class="st-font-semibold st-text-15">{{ $slot->warehouse_name ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="st-text--sm st-text--muted st-mb-4">Assigned Gate</div>
                                <div class="st-font-semibold st-text-15">{{ app(\App\Services\SlotService::class)->getGateDisplayName($slot->planned_gate_warehouse_code ?? '', $slot->planned_gate_number ?? '') }}</div>
                            </div>
                            <div>
                                <div class="st-text--sm st-text--muted st-mb-4">Planned ETA</div>
                                <div class="st-flex st-flex-col st-gap-4">
                                    @if(isset($slot->planned_start))
                                        @php $etaDetail = \Carbon\Carbon::parse($slot->planned_start); @endphp
                                        <div class="st-font-semibold st-text-15 st-flex st-align-center st-gap-8"><i class="far fa-calendar-alt st-text--muted st-text-14"></i> {{ $etaDetail->format('d-m-Y') }}</div>
                                        <div class="st-font-semibold st-text-15 st-flex st-align-center st-gap-8"><i class="far fa-clock st-text--muted st-text-14"></i> {{ $etaDetail->format('H:i') }}</div>
                                    @else
                                        <div class="st-font-semibold st-text-15">-</div>
                                    @endif
                                </div>
                            </div>
                        </div>
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

