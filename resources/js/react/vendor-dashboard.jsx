import React, { useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  LabelList,
  PieChart,
  Pie,
  Cell,
} from 'recharts';

function readJson(id, fallback) {
  try {
    const el = document.getElementById(id);
    if (!el) return fallback;
    return JSON.parse(el.textContent || '{}') || fallback;
  } catch (e) {
    return fallback;
  }
}

const _tkCache = {};
function tk(name, fallback = '') {
  if (_tkCache[name]) return _tkCache[name];
  try {
    const v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    if (v) _tkCache[name] = v;
    return v || fallback;
  } catch (e) {
    return fallback;
  }
}

function fmtTitle(s) {
  return String(s || '')
    .replace(/_/g, ' ')
    .trim()
    .replace(/\b\w/g, (c) => c.toUpperCase());
}

function useIsNarrow(breakpoint = 420) {
  const [isNarrow, setIsNarrow] = useState(() => {
    try {
      return typeof window !== 'undefined' ? window.innerWidth <= breakpoint : false;
    } catch (e) {
      return false;
    }
  });

  useEffect(() => {
    function onResize() {
      try {
        setIsNarrow(window.innerWidth <= breakpoint);
      } catch (e) {}
    }
    window.addEventListener('resize', onResize);
    return () => window.removeEventListener('resize', onResize);
  }, [breakpoint]);

  return isNarrow;
}

function BarValueLabel({ x, y, width, value, index, data }) {
  const v = Number(value);
  if (!Number.isFinite(v)) return null;
  const fill = (data && data[index] && data[index].color) ? data[index].color : tk('--text-primary', '#0f172a');
  const cx = (Number(x) || 0) + (Number(width) || 0) / 2;
  const cy = (Number(y) || 0) - 6;
  const text = String(v);
  return (
    <text x={cx} y={cy} textAnchor="middle" fontSize={11} fontWeight={700} fill={fill}>
      {text}
    </text>
  );
}

function StatusOverviewChart() {
  const raw = readJson('vendor-status-overview-data', {});
  const s = raw && raw.stats ? raw.stats : {};
  const isNarrow = useIsNarrow(420);

  const data = useMemo(() => {
    const items = [
      { key: 'scheduled', label: 'Scheduled', value: +(s.scheduled || 0), color: tk('--scheduled', '#0ea5e9') },
      { key: 'waiting', label: 'Waiting', value: +(s.waiting || 0), color: tk('--waiting', '#f59e0b') },
      { key: 'in_progress', label: 'In Progress', value: +(s.in_progress || 0), color: tk('--in-progress', '#8b5cf6') },
      { key: 'completed', label: 'Completed', value: +(s.completed || 0), color: tk('--completed', '#10b981') },
    ];
    return items;
  }, [s.completed, s.in_progress, s.scheduled, s.waiting]);

  return (
    <div style={{ width: '100%', height: '100%', minHeight: 160 }}>
      <ResponsiveContainer width="100%" height="100%">
        <BarChart data={data} margin={{ top: 12, right: 6, left: 0, bottom: 6 }}>
          <CartesianGrid strokeDasharray="3 3" stroke={tk('--chart-grid', '#e2e8f0')} />
          <XAxis
            dataKey="label"
            interval={0}
            tick={{ fontSize: isNarrow ? 9 : 11, fill: tk('--text-secondary', '#64748b') }}
            tickMargin={6}
            angle={isNarrow ? -18 : 0}
            textAnchor={isNarrow ? 'end' : 'middle'}
            height={isNarrow ? 34 : 22}
          />
          <YAxis tick={{ fontSize: 11, fill: tk('--text-secondary', '#64748b') }} width={28} allowDecimals={false} />
          <Tooltip contentStyle={{ borderRadius: 10, border: `1px solid ${tk('--border-light', '#e2e8f0')}`, boxShadow: '0 6px 16px rgba(15,23,42,0.12)', fontSize: 12 }} />
          <Bar dataKey="value" radius={[8, 8, 0, 0]}>
            {data.map((d) => (
              <Cell key={d.key} fill={d.color} />
            ))}
            <LabelList dataKey="value" content={(p) => <BarValueLabel {...p} data={data} />} />
          </Bar>
        </BarChart>
      </ResponsiveContainer>
    </div>
  );
}

function DonutCenter({ pct, label = 'On Time' }) {
  return (
    <div
      style={{
        position: 'absolute',
        inset: 0,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        pointerEvents: 'none',
      }}
    >
      <div style={{ textAlign: 'center' }}>
        <div style={{ fontWeight: 800, fontSize: 22, lineHeight: 1, color: tk('--text-primary', '#0f172a') }}>{pct}%</div>
        <div style={{ marginTop: 4, fontWeight: 600, fontSize: 11, color: tk('--text-secondary', '#64748b') }}>{label}</div>
      </div>
    </div>
  );
}

function OnTimeVsLateChart() {
  const perf = readJson('vendor-ontime-data', {});
  const onT = +(perf.on_time || 0);
  const late = +(perf.late || 0);
  const total = onT + late;
  const pct = total > 0 ? ((onT / total) * 100).toFixed(1) : '0.0';

  const colors = [tk('--completed', '#10b981'), tk('--cancelled', '#ef4444')];
  const data = [
    { name: 'On Time', value: onT },
    { name: 'Late', value: late },
  ];

  return (
    <div style={{ width: '100%', height: '100%', minHeight: 220, display: 'flex', flexDirection: 'column', gap: 12 }}>
      <div style={{ position: 'relative', flex: '1 1 0%', minHeight: 180 }}>
        <ResponsiveContainer width="100%" height="100%">
          <PieChart>
            <Pie data={data} cx="50%" cy="50%" innerRadius="58%" outerRadius="78%" paddingAngle={3} dataKey="value">
              {data.map((_, i) => (
                <Cell key={i} fill={colors[i] || '#e2e8f0'} />
              ))}
            </Pie>
            <Tooltip contentStyle={{ borderRadius: 10, border: `1px solid ${tk('--border-light', '#e2e8f0')}`, boxShadow: '0 6px 16px rgba(15,23,42,0.12)', fontSize: 12 }} />
          </PieChart>
        </ResponsiveContainer>
        <DonutCenter pct={pct} />
      </div>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, minmax(0, 1fr))', gap: 10 }}>
        {[
          { label: 'On Time', value: onT, color: colors[0] },
          { label: 'Late', value: late, color: colors[1] },
          { label: 'Total', value: total, color: tk('--text-secondary', '#94a3b8') },
        ].map((m) => (
          <div
            key={m.label}
            style={{
              borderRadius: 12,
              border: `1px solid ${tk('--border-light', '#e2e8f0')}`,
              background: tk('--bg-hover', '#f8fafc'),
              padding: 10,
              display: 'flex',
              alignItems: 'center',
              gap: 8,
              minWidth: 0,
            }}
          >
            <span style={{ width: 10, height: 10, borderRadius: 9999, background: m.color, flexShrink: 0 }} />
            <div style={{ minWidth: 0 }}>
              <div style={{ fontSize: 11, fontWeight: 600, color: tk('--text-secondary', '#64748b') }}>{fmtTitle(m.label)}</div>
              <div style={{ fontSize: 14, fontWeight: 800, color: tk('--text-primary', '#0f172a') }}>{m.value}</div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

function mount(id, node) {
  const el = document.getElementById(id);
  if (!el) return;
  createRoot(el).render(node);
}

mount('vendor-status-overview-react', <StatusOverviewChart />);
mount('vendor-ontime-react', <OnTimeVsLateChart />);
