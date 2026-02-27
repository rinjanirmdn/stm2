import React, { useMemo } from 'react';
import { createRoot } from 'react-dom/client';
import {
  PieChart,
  Pie,
  Cell,
  Tooltip,
  ResponsiveContainer,
} from 'recharts';

function readJsonFromScriptTag(id) {
  const el = document.getElementById(id);
  if (!el) return null;
  try {
    return JSON.parse(el.textContent || el.innerText || 'null');
  } catch (_) {
    return null;
  }
}

function tk(name, fallback = '') {
  const v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
  return v || fallback;
}

function tipStyle() {
  return {
    borderRadius: 8,
    border: `1px solid ${tk('--tooltip-border', '#e2e8f0')}`,
    boxShadow: `0 4px 12px ${tk('--tooltip-shadow', 'rgba(0,0,0,0.08)')}`,
    fontSize: 11,
  };
}

function VendorOntimeChart({ performance }) {
  const onTime = Number(performance?.on_time || 0);
  const late = Number(performance?.late || 0);
  const total = onTime + late;
  const onTimePct = total > 0 ? ((onTime / total) * 100).toFixed(1) : '0.0';
  const latePct = total > 0 ? ((late / total) * 100).toFixed(1) : '0.0';

  const chartData = useMemo(() => [
    { name: 'On Time', value: onTime },
    { name: 'Late', value: late },
  ], [onTime, late]);

  const colors = [
    tk('--completed', '#10b981'),
    tk('--cancelled', '#ef4444'),
  ];

  const metrics = [
    { label: 'On Time', value: onTime, sem: 'good' },
    { label: 'Late', value: late, sem: 'bad' },
    { label: 'Total', value: total, sem: 'neutral' },
  ];

  const bgMap = {
    good: { bg: '#ecfdf5', border: '#a7f3d0', text: '#065f46', dot: '#10b981' },
    bad: { bg: '#fef2f2', border: '#fecaca', text: '#991b1b', dot: '#ef4444' },
    neutral: { bg: '#f9fafb', border: '#e5e7eb', text: '#4b5563', dot: '#9ca3af' },
  };

  // Tentukan label dan warna pusat berdasarkan status dominan
  let centerLabel = 'On Time';
  let centerPct = onTimePct;
  let centerColor = tk('--completed', '#10b981');

  if (total === 0) {
    centerLabel = 'On Time';
    centerPct = '0.0';
    centerColor = tk('--completed', '#10b981');
  } else if (late > 0 && onTime === 0) {
    // Hanya ada data Late
    centerLabel = 'Late';
    centerPct = latePct;
    centerColor = tk('--cancelled', '#ef4444');
  } else if (late > onTime) {
    // Mayoritas Late
    centerLabel = 'Late';
    centerPct = latePct;
    centerColor = tk('--cancelled', '#ef4444');
  }

  return (
    <div style={{ display: 'flex', flexDirection: 'column', height: '100%', gap: 8 }}>
      {/* Donut chart with center percentage */}
      <div style={{ position: 'relative', flex: '1 1 0%', minHeight: 120 }}>
        <ResponsiveContainer width="100%" height="100%">
          <PieChart>
            <Pie
              data={chartData}
              cx="50%"
              cy="50%"
              innerRadius="38%"
              outerRadius="60%"
              paddingAngle={3}
              dataKey="value"
              label={({ value, cx: pcx, cy: pcy, midAngle, outerRadius: or }) => {
                if (!value) return null;
                const p = total > 0 ? ((value / total) * 100).toFixed(1) : '0';
                const R = Math.PI / 180;
                const r = or + 14;
                const x = pcx + r * Math.cos(-midAngle * R);
                const y = pcy + r * Math.sin(-midAngle * R);
                const fontSize = Math.max(9, or * 0.15);
                return (
                  <text
                    x={x}
                    y={y}
                    textAnchor={x > pcx ? 'start' : 'end'}
                    dominantBaseline="central"
                    style={{ fontSize, fontWeight: 600, fill: tk('--text-primary', '#1e293b') }}
                  >
                    {p}%
                  </text>
                );
              }}
              labelLine={{ stroke: tk('--chart-axis', '#94a3b8'), strokeWidth: 1 }}
            >
              {chartData.map((_, i) => (
                <Cell key={i} fill={colors[i]} />
              ))}
            </Pie>
            <Tooltip contentStyle={tipStyle()} />
          </PieChart>
        </ResponsiveContainer>
        {/* Center label */}
        <div
          style={{
            position: 'absolute',
            inset: 0,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            pointerEvents: 'none',
            containerType: 'size',
          }}
        >
          <div style={{ textAlign: 'center' }}>
            <div
              style={{
                fontWeight: 700,
                lineHeight: 1.1,
                fontSize: 'min(clamp(16px, 5cqmin, 30px), 10cqmin)',
                color: centerColor,
              }}
            >
              {centerPct}%
            </div>
            <div
              style={{
                fontSize: 'min(clamp(8px, 2.5cqmin, 12px), 5cqmin)',
                color: tk('--text-muted', '#94a3b8'),
                textTransform: 'uppercase',
                letterSpacing: '0.04em',
                marginTop: 2,
              }}
            >
              {centerLabel}
            </div>
          </div>
        </div>
      </div>

      {/* Metric cards */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 6, flexShrink: 0 }}>
        {metrics.map((m, i) => {
          const s = bgMap[m.sem];
          return (
            <div
              key={i}
              style={{
                display: 'flex',
                alignItems: 'center',
                gap: 6,
                padding: '6px 8px',
                borderRadius: 8,
                border: `1px solid ${s.border}`,
                background: s.bg,
              }}
            >
              <span
                style={{
                  width: 8,
                  height: 8,
                  borderRadius: '50%',
                  background: s.dot,
                  flexShrink: 0,
                }}
              />
              <div style={{ minWidth: 0 }}>
                <div style={{ fontSize: 10, fontWeight: 500, color: s.text }}>{m.label}</div>
                <div style={{ fontSize: 14, fontWeight: 700, color: '#1e293b' }}>{m.value}</div>
              </div>
            </div>
          );
        })}
      </div>

      {/* Empty state */}
      {total === 0 && (
        <div
          style={{
            position: 'absolute',
            inset: 0,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            flexDirection: 'column',
            gap: 8,
            color: tk('--text-muted', '#94a3b8'),
            fontSize: 13,
            background: 'rgba(255,255,255,0.85)',
            borderRadius: 8,
          }}
        >
          <i className="fas fa-chart-pie" style={{ fontSize: 28, opacity: 0.4 }}></i>
          <span>No completed data yet</span>
        </div>
      )}
    </div>
  );
}

function mount() {
  const rootEl = document.getElementById('vendor-ontime-react');
  if (!rootEl) return;

  const performance = readJsonFromScriptTag('vendor-ontime-data') || {};

  const root = createRoot(rootEl);
  root.render(
    <div className="w-full" style={{ minHeight: 200, height: '100%', position: 'relative' }}>
      <VendorOntimeChart performance={performance} />
    </div>
  );
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', mount);
} else {
  mount();
}
