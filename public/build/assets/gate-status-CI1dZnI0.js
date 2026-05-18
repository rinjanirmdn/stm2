class i{constructor(t={}){this.eventSource=null,this.reconnectAttempts=0,this.maxReconnectAttempts=5,this.reconnectDelay=5e3,this.gateStatuses=new Map,this.options={containerSelector:t.containerSelector||"#gate-status-container",updateInterval:t.updateInterval||2e3,...t},this.init()}init(){this.connect(),this.setupUI()}connect(){this.eventSource&&this.eventSource.close(),console.log("Connecting to gate status stream...");try{this.eventSource=new EventSource("/api/gate-status/stream"),this.eventSource.onopen=()=>{console.log("Connected to gate status stream"),this.reconnectAttempts=0,this.updateConnectionStatus("connected")},this.eventSource.onmessage=t=>{try{const e=JSON.parse(t.data);this.handleUpdate(e)}catch(e){console.error("Error parsing SSE data:",e)}},this.eventSource.onerror=t=>{console.error("SSE error:",t),this.updateConnectionStatus("error"),this.handleReconnect()}}catch(t){console.error("Error creating EventSource:",t),this.handleReconnect()}}handleReconnect(){this.reconnectAttempts<this.maxReconnectAttempts?(this.reconnectAttempts++,console.log(`Attempting to reconnect (${this.reconnectAttempts}/${this.maxReconnectAttempts})...`),setTimeout(()=>{this.connect()},this.reconnectDelay)):(console.error("Max reconnection attempts reached"),this.updateConnectionStatus("disconnected"))}handleUpdate(t){t.type==="gate_status"&&(t.data.forEach(e=>{const s=e.id_gates||e.id;this.gateStatuses.set(s,e),this.updateGateUI(e)}),this.updateSummaryStats())}updateGateUI(t){const e=t.id_gates||t.id,s=document.querySelector(`[data-gate-id="${e}"]`);if(!s)return;const o=s.querySelector(".gate-status-indicator");o&&(o.className=`gate-status-indicator status-${t.status}`);const a=s.querySelector(".gate-status-text");a&&(a.textContent=this.getStatusText(t.status));const n=s.querySelector(".current-slot-info");if(n&&t.current_slot){const c=t.current_slot.id_slots||t.current_slot.id;n.innerHTML=`
                <div class="slot-number">Slot #${c}</div>
                <div class="slot-ticket">${t.current_slot.ticket_number||t.current_slot.po_number||"-"}</div>
                <div class="slot-time">${this.formatTime(t.current_slot.planned_start)} - ${this.formatTime(t.current_slot.planned_finish)}</div>
            `}else n&&(n.innerHTML='<div class="no-slot">No slot</div>');s.classList.add("status-updated"),setTimeout(()=>{s.classList.remove("status-updated")},1e3)}updateSummaryStats(){const t=this.calculateStats();this.updateStatCard("total-gates",t.total),this.updateStatCard("available-gates",t.available),this.updateStatCard("busy-gates",t.busy),this.updateStatCard("occupied-gates",t.occupied)}calculateStats(){const t={total:this.gateStatuses.size,available:0,busy:0,occupied:0,reserved:0};return this.gateStatuses.forEach(e=>{t[e.status]++}),t}updateStatCard(t,e){const s=document.getElementById(t);s&&(s.textContent=e,s.classList.add("stat-updated"),setTimeout(()=>{s.classList.remove("stat-updated")},500))}updateConnectionStatus(t){const e=document.getElementById("connection-status");e&&(e.className=`connection-indicator status-${t}`,e.title=`Connection: ${t}`)}setupUI(){if(!document.getElementById("connection-status")){const e=document.createElement("div");e.id="connection-status",e.className="connection-indicator status-disconnected",e.title="Connection: disconnected",document.body.appendChild(e)}const t=document.createElement("style");t.textContent=`
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
        `,document.head.appendChild(t)}getStatusText(t){return{available:"Tersedia",busy:"Sibuk",occupied:"Terisi",reserved:"Direservasi"}[t]||t}formatTime(t){return t?new Date(t).toLocaleTimeString("id-ID",{hour:"2-digit",minute:"2-digit"}):""}disconnect(){this.eventSource&&(this.eventSource.close(),this.eventSource=null)}}document.addEventListener("DOMContentLoaded",()=>{(document.querySelector("#gate-status-container")||document.querySelector("[data-gate-id]"))&&(window.gateStatusMonitor=new i)});
