let stGateAutoRefresh = true;

window.toggleAutoRefresh = function () {
    stGateAutoRefresh = !stGateAutoRefresh;

    var statusEl = document.getElementById('refresh-status');
    var btn = document.querySelector('.st-card__actions button');

    if (statusEl) {
        statusEl.textContent = stGateAutoRefresh ? 'Auto Refresh ON' : 'Auto Refresh OFF';
    }
    if (btn) {
        btn.classList.toggle('st-btn--danger', !stGateAutoRefresh);
        btn.classList.toggle('st-btn--outline', stGateAutoRefresh);
    }

    if (!window.gateStatusMonitor) return;
    if (stGateAutoRefresh) {
        window.gateStatusMonitor.connect();
    } else {
        window.gateStatusMonitor.disconnect();
    }
};

document.addEventListener('DOMContentLoaded', function () {
    var container = document.getElementById('gate-status-container');
    if (!container) return;

    if (!window.gateStatusMonitor && typeof window.GateStatusMonitor === 'function') {
        window.gateStatusMonitor = new window.GateStatusMonitor();
    }
    if (!window.gateStatusMonitor) return;

    fetch('/api/gate-status')
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (!data || !data.gates) return;
            data.gates.forEach(function (gate) {
                window.gateStatusMonitor.gateStatuses.set(gate.id, gate);
                window.gateStatusMonitor.updateGateUI(gate);
            });
            window.gateStatusMonitor.updateSummaryStats();
        })
        .catch(function () { });
});
