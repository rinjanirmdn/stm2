class GateStatusMonitor {
    constructor(options = {}) {
        this.eventSource = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 5000;
        this.gateStatuses = new Map();
        this.options = {
            containerSelector: options.containerSelector || '#gate-status-container',
            updateInterval: options.updateInterval || 2000,
            ...options
        };

        this.init();
    }

    init() {
        this.connect();
        this.setupUI();
    }

    connect() {
        if (this.eventSource) {
            this.eventSource.close();
        }

        console.log('Connecting to gate status stream...');

        try {
            this.eventSource = new EventSource('/api/gate-status/stream');

            this.eventSource.onopen = () => {
                console.log('Connected to gate status stream');
                this.reconnectAttempts = 0;
                this.updateConnectionStatus('connected');
            };

            this.eventSource.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    this.handleUpdate(data);
                } catch (e) {
                    console.error('Error parsing SSE data:', e);
                }
            };

            this.eventSource.onerror = (error) => {
                console.error('SSE error:', error);
                this.updateConnectionStatus('error');
                this.handleReconnect();
            };

        } catch (error) {
            console.error('Error creating EventSource:', error);
            this.handleReconnect();
        }
    }

    handleReconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            console.log(`Attempting to reconnect (${this.reconnectAttempts}/${this.maxReconnectAttempts})...`);

            setTimeout(() => {
                this.connect();
            }, this.reconnectDelay);
        } else {
            console.error('Max reconnection attempts reached');
            this.updateConnectionStatus('disconnected');
        }
    }

    handleUpdate(data) {
        if (data.type === 'gate_status') {
            data.data.forEach(gate => {
                this.gateStatuses.set(gate.id, gate);
                this.updateGateUI(gate);
            });

            this.updateSummaryStats();
        }
    }

    updateGateUI(gate) {
        const gateElement = document.querySelector(`[data-gate-id="${gate.id}"]`);
        if (!gateElement) return;

        // Update status indicator
        const statusIndicator = gateElement.querySelector('.gate-status-indicator');
        if (statusIndicator) {
            statusIndicator.className = `gate-status-indicator status-${gate.status}`;
        }

        // Update status text
        const statusText = gateElement.querySelector('.gate-status-text');
        if (statusText) {
            statusText.textContent = this.getStatusText(gate.status);
        }

        // Update current slot info
        const slotInfo = gateElement.querySelector('.current-slot-info');
        if (slotInfo && gate.current_slot) {
            slotInfo.innerHTML = `
                <div class="slot-po">${gate.current_slos.po_number}</div>
                <div class="slot-time">${this.formatTime(gate.current_slot.planned_start)} - ${this.formatTime(gate.current_slot.planned_finish)}</div>
            `;
        } else if (slotInfo) {
            slotInfo.innerHTML = '<div class="no-slot">Tidak ada slot</div>';
        }

        // Add animation for status change
        gateElement.classList.add('status-updated');
        setTimeout(() => {
            gateElement.classList.remove('status-updated');
        }, 1000);
    }

    updateSummaryStats() {
        const stats = this.calculateStats();

        // Update summary cards
        this.updateStatCard('total-gates', stats.total);
        this.updateStatCard('available-gates', stats.available);
        this.updateStatCard('busy-gates', stats.busy);
        this.updateStatCard('occupied-gates', stats.occupied);
    }

    calculateStats() {
        const stats = {
            total: this.gateStatuses.size,
            available: 0,
            busy: 0,
            occupied: 0,
            reserved: 0
        };

        this.gateStatuses.forEach(gate => {
            stats[gate.status]++;
        });

        return stats;
    }

    updateStatCard(id, value) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value;
            element.classList.add('stat-updated');
            setTimeout(() => {
                element.classList.remove('stat-updated');
            }, 500);
        }
    }

    updateConnectionStatus(status) {
        const indicator = document.getElementById('connection-status');
        if (!indicator) return;

        indicator.className = `connection-indicator status-${status}`;
        indicator.title = `Connection: ${status}`;
    }

    setupUI() {
        // Create connection status indicator if not exists
        if (!document.getElementById('connection-status')) {
            const indicator = document.createElement('div');
            indicator.id = 'connection-status';
            indicator.className = 'connection-indicator status-disconnected';
            indicator.title = 'Connection: disconnected';
            document.body.appendChild(indicator);
        }

        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            .gate-status-indicator {
                width: 12px;
                height: 12px;
                border-radius: 50%;
                display: inline-block;
                margin-right: 8px;
                transition: all 0.3s ease;
            }

            .gate-status-indicator.status-available { background-color: #10b981; }
            .gate-status-indicator.status-busy { background-color: #ef4444; }
            .gate-status-indicator.status-occupied { background-color: #f59e0b; }
            .gate-status-indicator.status-reserved { background-color: #6b7280; }

            .status-updated {
                animation: pulse 1s ease-in-out;
            }

            .stat-updated {
                animation: flash 0.5s ease-in-out;
            }

            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }

            @keyframes flash {
                0% { background-color: rgba(59, 130, 246, 0.1); }
                100% { background-color: transparent; }
            }

            .connection-indicator {
                position: fixed;
                top: 20px;
                right: 20px;
                width: 10px;
                height: 10px;
                border-radius: 50%;
                z-index: 9999;
            }

            .connection-indicator.status-connected { background-color: #10b981; }
            .connection-indicator.status-error { background-color: #ef4444; }
            .connection-indicator.status-disconnected { background-color: #6b7280; }

            .current-slot-info {
                font-size: 0.875rem;
                margin-top: 4px;
            }

            .slot-po {
                font-weight: 600;
                color: #1f2937;
            }

            .slot-time {
                color: #6b7280;
                font-size: 0.75rem;
            }

            .no-slot {
                color: #9ca3af;
                font-style: italic;
            }
        `;
        document.head.appendChild(style);
    }

    getStatusText(status) {
        const statusMap = {
            'available': 'Tersedia',
            'busy': 'Sibuk',
            'occupied': 'Terisi',
            'reserved': 'Direservasi'
        };
        return statusMap[status] || status;
    }

    formatTime(timeString) {
        if (!timeString) return '';
        const date = new Date(timeString);
        return date.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    disconnect() {
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }
    }
}

// Auto-initialize on pages with gate status
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('#gate-status-container') ||
        document.querySelector('[data-gate-id]')) {
        window.gateStatusMonitor = new GateStatusMonitor();
    }
});
