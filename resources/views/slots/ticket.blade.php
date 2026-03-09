<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ticket e-DCS - {{ $slot->ticket_number ?? '' }}</title>
    <link rel="stylesheet" href="{{ asset('ticket.css') }}">
</head>
<body>
    <div class="ticket-container">
        <div class="ticket-inner">
            <div class="header">
                <img src="{{ $logoDataUri ?? asset('img/logo-full.png') }}" alt="Logo" class="ticket-logo" />
                <div class="title">Tiket e-Docking</div>
                <div class="subtitle">
                    {{ ($slot->warehouse_code ?? '') . ' - ' . ($slot->warehouse_name ?? '') }}
                </div>
            </div>

            <div class="ticket-number">
                {{ $slot->ticket_number ?? '' }}
            </div>

            <table class="info-table">
                <tr>
                    <td class="label-col">Nomor PO/DO</td>
                    <td class="colon-col">:</td>
                    <td class="value-col">{{ $slot->truck_number ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label-col">Vendor</td>
                    <td class="colon-col">:</td>
                    <td class="value-col">{{ $slot->vendor_name ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label-col">Aktivitas</td>
                    <td class="colon-col">:</td>
                    <td class="value-col">{{ strtoupper((string) ($slot->direction ?? '')) }}</td>
                </tr>
                <tr>
                    <td class="label-col">Gate</td>
                    <td class="colon-col">:</td>
                    <td class="value-col">{{ $gateLetter ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label-col">ETA</td>
                    <td class="colon-col">:</td>
                    <td class="value-col">{{ \Carbon\Carbon::parse($slot->planned_start)->format('d m Y H:i') }}</td>
                </tr>
            </table>

            <div class="barcode-container">
                <div class="barcode">
                    @if(!empty($barcodePng))
                        <img
                            src="data:image/png;base64,{{ $barcodePng }}"
                            alt="Barcode"
                            class="st-barcode-img"
                        />
                        <div class="st-barcode-ticket">{{ $slot->ticket_number ?? '' }}</div>
                    @elseif(!empty($barcodeHtml))
                        <div class="barcode-wrap">
                            <div class="barcode-html st-barcode-center">
                                {!! $barcodeHtml !!}
                            </div>
                        </div>
                        <div class="st-barcode-ticket">{{ $slot->ticket_number ?? '' }}</div>
                    @elseif(!empty($barcodeSvg))
                        <div class="barcode-wrap">
                            <div class="st-barcode-center">
                                {!! $barcodeSvg !!}
                            </div>
                        </div>
                        <div class="st-barcode-ticket">{{ $slot->ticket_number ?? '' }}</div>
                    @else
                        <div class="st-barcode-ticket st-text--muted-light">No Ticket Number</div>
                    @endif
                </div>
                <div class="barcode-note">Scan Nomor Tiket ini dengan Barcode Scanner.</div>
            </div>

            <div class="security-note">
               <strong>Harap serahkan tiket ini kepada petugas keamanan</strong>
            </div>
            <div class="footer st-footer-barcode">
               Dibuat: {{ now()->format('d m Y H:i') }}
            </div>
        </div>
    </div>

</body>
</html>
