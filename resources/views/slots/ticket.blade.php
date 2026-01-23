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

        .info-table {
            width: 100%;
            margin: 10px 0;
            border-collapse: collapse;
        }

        .info-table td {
            vertical-align: top;
            padding: 2px 0;
            font-size: 11px;
        }

        .label-col {
            width: 85px;
            font-weight: bold;
        }

        .colon-col {
            width: 10px;
            font-weight: bold;
            text-align: center;
        }

        .value-col {
            text-align: left;
            padding-left: 5px;
        }

        /* ... existing styles ... */
        .barcode-container {
            text-align: center;
            margin: 10pt 0;
        }
        /* ... */
    </style>
</head>
<body>
    <div class="ticket-container">
        <div class="ticket-inner">
            <div class="header">
                <div class="title">Slot Ticket Docking</div>
                <div class="subtitle">
                    {{ ($slot->warehouse_code ?? '') . ' - ' . ($slot->warehouse_name ?? '') }}
                </div>
                @php
                    $gateLetter = isset($slot->ticket_number) && strlen($slot->ticket_number) > 0 ? substr($slot->ticket_number, 0, 1) : '-';
                @endphp
            </div>

            <div class="ticket-number">
                {{ $slot->ticket_number ?? '' }}
            </div>

            <table class="info-table">
                <tr>
                    <td class="label-col">PO/DO Number</td>
                    <td class="colon-col">:</td>
                    <td class="value-col">{{ $slot->truck_number ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label-col">Vendor</td>
                    <td class="colon-col">:</td>
                    <td class="value-col">{{ $slot->vendor_name ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label-col">Activity</td>
                    <td class="colon-col">:</td>
                    <td class="value-col">{{ strtoupper((string) ($slot->direction ?? '')) }}</td>
                </tr>
                <tr>
                    <td class="label-col">Gate</td>
                    <td class="colon-col">:</td>
                    <td class="value-col">{{ $gateLetter }}</td>
                </tr>
                <tr>
                    <td class="label-col">ETA</td>
                    <td class="colon-col">:</td>
                    <td class="value-col">{{ $slot->planned_start ?? '-' }}</td>
                </tr>
            </table>

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
                <div class="barcode-note">Scan This Ticket Number with a Barcode Scanner.</div>
            </div>

            <div class="footer" style="text-align: center; position: absolute; bottom: 0; width: 100%;">
               Generated: {{ now()->format('Y-m-d H:i:s') }}
            </div>
        </div>
    </div>

</body>
</html>
