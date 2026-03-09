import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

// ──────────────────────────────────────────
// Listen for data changes (replaces realtime version polling)
// ──────────────────────────────────────────
window.Echo.channel('data-updates')
    .listen('.data.changed', function (event) {
        // If the page has an AJAX reload function, use it (no full page reload)
        if (typeof window.ajaxReload === 'function') {
            try {
                window.ajaxReload(false);
                return;
            } catch (e) {
                // fallback to full reload
            }
        }

        // Full page reload for pages without AJAX reload
        if (!document.hidden) {
            window.location.reload();
        } else {
            // Mark as pending — will refresh when tab becomes visible
            window.__stPendingRealtimeRefresh = true;
        }
    });

// Handle tab visibility change — refresh if pending
document.addEventListener('visibilitychange', function () {
    if (!document.hidden && window.__stPendingRealtimeRefresh) {
        window.__stPendingRealtimeRefresh = false;

        if (typeof window.ajaxReload === 'function') {
            try { window.ajaxReload(false); return; } catch (e) { }
        }
        window.location.reload();
    }
});

// ──────────────────────────────────────────
// Listen for private user notifications (replaces notification polling)
// ──────────────────────────────────────────
var stAppConfigEcho = null;
try {
    // Try admin config first, then vendor config
    var el = document.getElementById('st-app-config') || document.getElementById('st-vendor-config');
    if (el) stAppConfigEcho = JSON.parse(el.textContent || '{}');
} catch (e) { }

var currentUserId = stAppConfigEcho && stAppConfigEcho.userId ? stAppConfigEcho.userId : null;

if (currentUserId) {
    window.Echo.private('user.' + currentUserId)
        .listen('.notification.new', function (notification) {
            // Update badge count
            var badge = document.querySelector('.st-notification-badge');
            if (badge) {
                var current = parseInt(badge.textContent || '0', 10);
                badge.textContent = String((isFinite(current) ? current : 0) + 1);
            } else {
                // Create badge if it doesn't exist
                var bellBtn = document.getElementById('st-notification-btn');
                if (bellBtn) {
                    var newBadge = document.createElement('span');
                    newBadge.className = 'st-notification-badge';
                    newBadge.textContent = '1';
                    bellBtn.appendChild(newBadge);
                }
            }

            // Show toast
            var toast = document.getElementById('st-notification-toast');
            var toastText = document.getElementById('st-notification-toast-text');
            if (toast && toastText) {
                toastText.textContent = (notification.title || 'Notification') + ' - ' + (notification.message || '');
                toast.style.display = 'block';

                // Play notification sound
                try {
                    var ctx = new (window.AudioContext || window.webkitAudioContext)();
                    var osc = ctx.createOscillator();
                    var gain = ctx.createGain();
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.frequency.setValueAtTime(880, ctx.currentTime);
                    osc.frequency.setValueAtTime(1047, ctx.currentTime + 0.1);
                    osc.frequency.setValueAtTime(1319, ctx.currentTime + 0.2);
                    gain.gain.setValueAtTime(0.15, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);
                    osc.start(ctx.currentTime);
                    osc.stop(ctx.currentTime + 0.5);
                } catch (e) { }

                setTimeout(function () {
                    toast.style.display = 'none';
                }, 5000);
            }
        });
}
