import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: window.location.hostname,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

// ──────────────────────────────────────────
// Listen for data changes (replaces realtime version polling)
// ──────────────────────────────────────────
window.Echo.channel('data-updates')
    .listen('.data.changed', function (event) {
        // Only auto-refresh pages that explicitly support AJAX reload
        // (e.g., dashboard, slots index, gates index)
        // Pages without ajaxReload will NOT auto-reload to prevent infinite loops
        if (typeof window.ajaxReload === 'function') {
            try {
                window.ajaxReload(false);
            } catch (e) {
                // silently fail
            }
            return;
        }

        // For pages without ajaxReload: dispatch a DOM event so components
        // can optionally react (without full page reload)
        try {
            window.dispatchEvent(new CustomEvent('st:data-changed', { detail: event }));
        } catch (e) { }
    });

// Handle tab visibility change — refresh if pending (only for ajaxReload pages)
document.addEventListener('visibilitychange', function () {
    if (!document.hidden && window.__stPendingRealtimeRefresh) {
        window.__stPendingRealtimeRefresh = false;

        if (typeof window.ajaxReload === 'function') {
            try { window.ajaxReload(false); return; } catch (e) { }
        }
        // Do NOT call window.location.reload() here — prevents infinite loop
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
    var channelName = 'user.' + currentUserId;
    window.Echo.leave(channelName);
    window.Echo.private(channelName)
        .listen('.notification.new', function (notification) {
            // Update badge count
            var badges = document.querySelectorAll('.st-notification-badge, .notification-badge, .vendor-user-menu-badge, .vendor-header__user-menu-badge');
            if (badges && badges.length > 0) {
                badges.forEach(function(badge) {
                    var current = parseInt(badge.textContent || '0', 10);
                    badge.textContent = String((isFinite(current) ? current : 0) + 1);
                });
            } else {
                // Create badge if it doesn't exist
                var bellBtn = document.getElementById('st-notification-btn') || document.getElementById('notification-btn');
                if (bellBtn) {
                    var newBadge = document.createElement('span');
                    // Check which class to use
                    var isVendor = bellBtn.id === 'notification-btn';
                    newBadge.className = isVendor ? 'notification-badge' : 'st-notification-badge';
                    newBadge.textContent = '1';
                    // For vendor, it's specific id
                    if (isVendor) newBadge.id = 'notification-count';
                    bellBtn.appendChild(newBadge);
                }
            }

            // Append to dropdown list cleanly
            var dropdownLists = document.querySelectorAll('.st-notification-list, .notification-list');
            dropdownLists.forEach(function(dropdownList) {
                var isVendorList = dropdownList.classList.contains('notification-list');
                var emptyMsg = dropdownList.querySelector('.st-notification-empty, .notification-empty');
                if (emptyMsg) emptyMsg.remove();
                
                var actionUrl = notification.url || '#';
                var notifId = notification.id || '';
                var colorCls = 'blue';
                if (notification.color === 'red') colorCls = 'red';
                if (notification.color === 'green') colorCls = 'green';
                var iconCls = notification.icon || 'fas fa-info';
                
                // Construct clean item
                var anchor = document.createElement('a');
                anchor.href = actionUrl;
                anchor.className = isVendorList ? 'notification-item notification-item--unread' : 'st-notification-item st-notification-item--unread';
                if (notifId) anchor.setAttribute('data-notification-id', notifId);
                anchor.onclick = function(e) { return window.markAsReadAndGo ? window.markAsReadAndGo(e, notifId, actionUrl) : true; };
                
                var prefix = isVendorList ? 'notification' : 'st-notification';
                var iconHtml = '<div class="' + prefix + '-icon ' + prefix + '-icon--' + colorCls + '"><i class="' + iconCls + '"></i></div>';
                
                var titleContent = notification.title || 'Notification';
                var msgContent = notification.message || '';
                var timeHtml = '<span class="' + prefix + '-time">Just now</span>';
                
                if (isVendorList) {
                    // Vendor format
                    var titleHtml = '<p><strong>' + titleContent + '</strong></p>';
                    var msgHtml = '<p>' + msgContent + '</p>';
                    anchor.innerHTML = iconHtml + '<div class="' + prefix + '-content">' + titleHtml + msgHtml + timeHtml + '</div>';
                } else {
                    // Admin format
                    var titleHtml = '<span class="' + prefix + '-title">' + titleContent + '</span>';
                    var msgHtml = '<span class="' + prefix + '-message">' + msgContent + '</span>';
                    anchor.innerHTML = iconHtml + '<div class="' + prefix + '-content">' + titleHtml + msgHtml + timeHtml + '</div>';
                }
                
                dropdownList.insertBefore(anchor, dropdownList.firstChild);
                
                var items = dropdownList.querySelectorAll('.' + prefix + '-item');
                for (var i = 10; i < items.length; i++) {
                    items[i].remove();
                }
            });


            // Show toast
            var toasts = [];
            var adminToast = document.getElementById('st-notification-toast');
            if (adminToast) toasts.push({ el: adminToast, text: document.getElementById('st-notification-toast-text') });
            
            var vendorToast = document.getElementById('vendor-notification-toast');
            if (vendorToast) toasts.push({ el: vendorToast, text: document.getElementById('vendor-notification-toast-text') });
            
            toasts.forEach(function(t) {
                if (t.el && t.text) {
                    t.text.textContent = (notification.title || 'Notification') + ' - ' + (notification.message || '');
                    t.el.style.display = 'block';

                    setTimeout(function () {
                        t.el.style.display = 'none';
                    }, 5000);
                }
            });

            if (toasts.length > 0) {
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
            }
        });
}
