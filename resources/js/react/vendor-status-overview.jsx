import React, { useMemo } from 'react';
import { createRoot } from 'react-dom/client';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  Cell,
  LabelList,
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

const statusLabels = {
  scheduled: 'Scheduled',
  waiting: 'Waiting',
  in_progress: 'In Progress',
  completed: 'Completed',
};

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

function VendorStatusOverviewChart({ stats }) {
  const data = useMemo(() => {
    const scheduled = Number(stats?.scheduled || 0);
    const waiting = Number(stats?.waiting || 0);
    const inProgress = Number(stats?.in_progress || 0);
    const completed = Number(stats?.completed || 0);

    return [
      { name: 'scheduled', value: scheduled, fill: tk('--scheduled', '#6b7280') },
      { name: 'waiting', value: waiting, fill: tk('--waiting', '#d97706') },
      { name: 'in_progress', value: inProgress, fill: tk('--in-progress', '#0284c7') },
      { name: 'completed', value: completed, fill: tk('--completed', '#059669') },
    ];
  }, [stats]);

  return (
    <div className="w-full h-full">
      <ResponsiveContainer width="100%" height="100%">
        <BarChart
          data={data}
          margin={{ top: 20, right: 2, left: 0, bottom: 0 }}
          barCategoryGap="45%"
        >
          <CartesianGrid strokeDasharray="3 3" stroke={tk('--chart-grid', '#f1f5f9')} />
          <XAxis
            dataKey="name"
            interval={0}
            height={45}
            tick={({ x, y, payload }) => {
              const name = payload?.value || '';
              const item = data.find((d) => d.name === name);
              const clr = item?.fill || tk('--chart-axis', '#94a3b8');
              const label = statusLabels[String(name).toLowerCase()] || String(name).replace('_', ' ');
              return (
                <g transform={`translate(${x},${y})`}>
                  <circle cx={0} cy={6} r={3} fill={clr} />
                  <text x={0} y={16} textAnchor="middle" style={{ fontSize: 10, fontWeight: 500, fill: clr }}>
                    {label}
                  </text>
                </g>
              );
            }}
          />
          <YAxis tick={{ fontSize: 10, fill: tk('--chart-axis', '#94a3b8') }} width={28} />
          <Tooltip contentStyle={tipStyle()} />
          <Bar dataKey="value" radius={[4, 4, 0, 0]} barSize={18} maxBarSize={20}>
            <LabelList
              dataKey="value"
              position="top"
              style={{ fontSize: 10, fontWeight: 600, fill: tk('--text-secondary', '#64748b') }}
              formatter={(v) => (v > 0 ? v : '')}
            />
            {data.map((e, i) => (
              <Cell key={i} fill={e.fill} />
            ))}
          </Bar>
        </BarChart>
      </ResponsiveContainer>
    </div>
  );
}

function mount() {
  const rootEl = document.getElementById('vendor-status-overview-react');
  if (!rootEl) return;

  const payload = readJsonFromScriptTag('vendor-status-overview-data') || {};
  const stats = payload.stats || payload;

  const root = createRoot(rootEl);
  root.render(
    <div className="w-full" style={{ minHeight: 140, height: '100%' }}>
      <VendorStatusOverviewChart stats={stats} />
    </div>
  );
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', mount);
} else {
  mount();
}
