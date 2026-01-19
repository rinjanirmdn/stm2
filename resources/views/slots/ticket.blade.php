<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ticket - {{ $slot->ticket_number ?? '' }}</title>
    <style>
        @page {
            margin: 0;
            size: 240pt 300pt;
        }

        * {
            margin: 1;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            height: auto;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        .ticket-container {
            width: 90%;
            background: white;
            position: relative;
        }

        .ticket-inner {
            width: 100%;
            border: 0px solid transparent;
            padding: 10pt;
            box-sizing: border-box;
            position: relative;
        }

        .header {
            text-align: center;
            margin-bottom: 8px;
            text-align: center;
        }

        .title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .subtitle {
            font-size: 10px;
            color: #666;
        }

        .ticket-number {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            margin: 10px 0;
            padding: 8px;
            border: 2px solid #000;
            background: transparent;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            font-size: 11px;
        }

        .label {
            font-weight: bold;
        }

        .value {
            text-align: right;
            max-width: 60%;
            word-wrap: break-word;
        }

        .barcode-container {
            text-align: center;
            margin: 10pt 0;
        }

        .barcode {
            margin: 10px auto;
        }

        .barcode-html {
            display: inline-block;
        }

        .barcode-html div {
            display: inline-block;
            vertical-align: top;
        }

        .barcode-html span {
            display: inline-block;
            height: 60px;
        }

        .barcode-wrap {
            display: inline-block;
            padding: 4px 10px;
            background: #ffffff;
        }

        .barcode-note {
            font-size: 9px;
            color: #666;
            margin-top: 5px;
        }

        .footer {
            position: absolute;
            bottom: 10pt;
            left: 10pt;
            right: 10pt;
            text-align: center;
            font-size: 9px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="ticket-container">
        <div class="ticket-inner">
            <div class="header">
                <div class="title">SLOT TICKET DOCKING</div>
                <div class="subtitle">{{ ($slot->warehouse_code ?? '') . ' - ' . ($slot->warehouse_name ?? '') }}</div>
            </div>

            <div class="ticket-number">
                {{ $slot->ticket_number ?? '' }}
            </div>

            <div class="info-row">
                <span class="label">PO/DO Number:</span>
                <span class="value">{{ $slot->truck_number ?? '-' }}</span>
            </div>

            <div class="info-row">
                <span class="label">Vendor:</span>
                <span class="value">{{ $slot->vendor_name ?? '-' }}</span>
            </div>

            <div class="info-row">
                <span class="label">Direction:</span>
                <span class="value">{{ strtoupper((string) ($slot->direction ?? '')) }}</span>
            </div>

            <div class="info-row">
                <span class="label">ETA:</span>
                <span class="value">{{ $slot->planned_start ?? '-' }}</span>
            </div>

            <div class="barcode-container">
                <div class="barcode">
                    @if(!empty($barcodePng))
                        <img
                            src="data:image/png;base64,{{ $barcodePng }}"
                            alt="Barcode"
                            style="width: 180px; height: 46px; display: block; margin: 0 auto;"
                        />
                        <div style="text-align: center; margin-top: 5px; font-size: 10px;">{{ $slot->ticket_number ?? '' }}</div>
                    @elseif(!empty($barcodeHtml))
                        <div class="barcode-wrap">
                            <div class="barcode-html" style="margin: 0 auto;">
                                {!! $barcodeHtml !!}
                            </div>
                        </div>
                        <div style="text-align: center; margin-top: 5px; font-size: 10px;">{{ $slot->ticket_number ?? '' }}</div>
                    @elseif(!empty($barcodeSvg))
                        <div class="barcode-wrap">
                            <div style="margin: 0 auto;">
                                {!! $barcodeSvg !!}
                            </div>
                        </div>
                        <div style="text-align: center; margin-top: 5px; font-size: 10px;">{{ $slot->ticket_number ?? '' }}</div>
                    @else
                        <div style="text-align: center; color: #999; font-size: 10px;">No Ticket Number</div>
                    @endif
                </div>
                <div class="barcode-note">Scan this ticket number with a barcode scanner.</div>
            </div>

            <div class="footer">
                Generated: {{ now()->format('Y-m-d H:i:s') }}
            </div>
        </div>
    </div>

</body>
</html>
