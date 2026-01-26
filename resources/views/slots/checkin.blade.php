<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Check-in Slot - {{ $slot->truck_number }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome already loaded in layouts/app.blade.php -->
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-qrcode"></i> Slot Check-in</h1>
    </div>

    <div class="container">
        <div class="card">
            <div class="slot-info">
                <div class="slot-number">{{ $slot->truck_number }}</div>
                <span class="status-badge status-{{ $slot->status }}">
                    {{ ucwords(str_replace('_', ' ', $slot->status)) }}
                </span>

                <div class="slot-details">
                    <div class="detail-item">
                        <div class="detail-label">Warehouse</div>
                        <div class="detail-value">{{ $slot->warehouse->code }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Gate</div>
                        <div class="detail-value">{{ $slot->plannedGate->gate_number }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Truck</div>
                        <div class="detail-value">{{ $slot->truck_number }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Type</div>
                        <div class="detail-value">{{ $slot->truck_type }}</div>
                    </div>
                </div>
            </div>

            <div class="qr-container">
                <div id="qrcode"></div>
                <p class="qr-hint">Scan QR Code for Fast Check-in</p>
            </div>

            @if($canCheckin)
            <div class="actions">
                @if($slot->status === 'scheduled')
                    <button class="btn btn-primary" onclick="checkin('arrival')">
                        <i class="fas fa-sign-in-alt"></i> Check-in Arrival
                    </button>
                @elseif(in_array($slot->status, ['waiting']))
                    <button class="btn btn-success" onclick="checkin('start')">
                        <i class="fas fa-play"></i> Start Slot
                    </button>
                @elseif($slot->status === 'in_progress')
                    <button class="btn btn-success" onclick="checkin('complete')">
                        <i class="fas fa-check"></i> Complete
                    </button>
                @endif

                <a href="{{ route('slots.show', ['slotId' => $slot->id]) }}" class="btn btn-secondary">
                    <i class="fas fa-info-circle"></i> Detail
                </a>
            </div>
            @endif
        </div>

        <div id="message" class="message"></div>
        <div id="loading" class="loading">
            <div class="spinner"></div>
            <p>Processing...</p>
        </div>
    </div>

    <!-- QR Code Library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

    <script>
        // Generate QR Code
        const qrcode = new QRCode(document.getElementById("qrcode"), {
            text: "{{ $checkinUrl }}",
            width: 200,
            height: 200,
            colorDark: "#1f2937",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });

        // Check-in function
        async function checkin(action) {
            const loading = document.getElementById('loading');
            const message = document.getElementById('message');

            loading.style.display = 'block';
            message.style.display = 'none';

            try {
                const response = await fetch('{{ route("slots.checkin.store", ["slotId" => $slot->id]) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        action: action
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showMessage(data.message, 'success');

                    // Reload page after delay to show updated status
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showMessage(data.message, 'error');
                }
            } catch (error) {
                showMessage('An error occurred. Please try again.', 'error');
            } finally {
                loading.style.display = 'none';
            }
        }

        function showMessage(text, type) {
            const message = document.getElementById('message');
            message.textContent = text;
            message.className = `message message-${type}`;
            message.style.display = 'block';

            setTimeout(() => {
                message.style.display = 'none';
            }, 5000);
        }

        // Prevent zoom on double tap
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function (event) {
            const now = Date.now();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
    </script>
</body>
</html>
