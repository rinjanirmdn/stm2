import React, { useState, useMemo, useEffect, useRef, useCallback } from 'react';
import { createRoot } from 'react-dom/client';
import {
  BarChart, Bar, PieChart, Pie, Cell, LabelList,
  XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer,
  ComposedChart, Line
} from 'recharts';
import {
  ChevronLeft, ChevronRight, Clock, Target,
  Truck, Calendar, BarChart3, RotateCcw,
  ArrowDownLeft, ArrowUpRight, Timer, Gauge, Layers
} from 'lucide-react';

function getData() { return window.__DASHBOARD_DATA__ || {}; }

/* ── jQuery picker helpers ── */
const $ = () => window.jQuery;
const moment = () => window.moment;
const fmtDisplay = (iso) => { if (!iso) return ''; const p = iso.split('-'); return p.length===3 ? `${p[2]}-${p[1]}-${p[0]}` : iso; };

/** Wait for jQuery + daterangepicker to be available, then call fn */
function whenJQReady(fn) {
  if ($() && $().fn && $().fn.daterangepicker && moment()) { fn(); return; }
  let tries = 0;
  const t = setInterval(() => {
    tries++;
    if (($() && $().fn && $().fn.daterangepicker && moment()) || tries > 30) {
      clearInterval(t);
      if ($() && $().fn.daterangepicker) fn();
    }
  }, 150);
}

/** Single Date Picker bridged to jQuery daterangepicker (MD style) */
function JQDatePicker({ value, onChange, label }) {
  const ref = useRef(null);
  const cbRef = useRef(onChange);
  cbRef.current = onChange;

  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    whenJQReady(() => {
      if (el.getAttribute('data-jq-init') === '1') return;
      el.setAttribute('data-jq-init', '1');
      el.setAttribute('data-st-datepicker', '1'); // prevent main.js double-init
      try { el.type = 'text'; } catch(e) {}
      el.setAttribute('readonly', 'readonly');
      el.value = fmtDisplay(value);
      $()(el).daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        autoApply: true,
        locale: { format: 'DD-MM-YYYY' },
        minYear: 2020,
        maxYear: parseInt(moment()().format('YYYY'), 10) + 2,
        startDate: value ? moment()(value, 'YYYY-MM-DD') : moment()(),
      }, function(start) {
        const iso = start.format('YYYY-MM-DD');
        el.value = start.format('DD-MM-YYYY');
        cbRef.current(iso);
      });
    });
  }, []);

  return (
    <div className="flex flex-col gap-0.5">
      {label && <label className="text-[11px] text-gray-500 font-medium">{label}</label>}
      <input ref={ref} type="text" defaultValue={fmtDisplay(value)} readOnly
        className="text-[13px] border border-gray-200 rounded-lg px-2.5 py-1.5 bg-white text-gray-700 outline-none focus:border-sky-400 cursor-pointer w-36" />
    </div>
  );
}

/** Range Picker bridged to jQuery daterangepicker (MD style with presets) */
function JQRangePicker({ startValue, endValue, onApply }) {
  const ref = useRef(null);
  const cbRef = useRef(onApply);
  cbRef.current = onApply;

  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    whenJQReady(() => {
      if (el.getAttribute('data-jq-init') === '1') return;
      el.setAttribute('data-jq-init', '1');
      const start = startValue ? moment()(startValue, 'YYYY-MM-DD') : moment()().startOf('month');
      const end = endValue ? moment()(endValue, 'YYYY-MM-DD') : moment()();
      $()(el).daterangepicker({
        startDate: start,
        endDate: end,
        ranges: {
          'Today': [moment()(), moment()()],
          'Yesterday': [moment()().subtract(1,'days'), moment()().subtract(1,'days')],
          'Last 7 Days': [moment()().subtract(6,'days'), moment()()],
          'Last 30 Days': [moment()().subtract(29,'days'), moment()()],
          'This Month': [moment()().startOf('month'), moment()().endOf('month')],
          'Last Month': [moment()().subtract(1,'month').startOf('month'), moment()().subtract(1,'month').endOf('month')]
        },
        locale: { format: 'DD-MM-YYYY' },
        alwaysShowCalendars: true,
      }, function(s, e) {
        el.querySelector('span').textContent = s.format('DD-MM-YYYY') + ' — ' + e.format('DD-MM-YYYY');
        cbRef.current(s.format('YYYY-MM-DD'), e.format('YYYY-MM-DD'));
      });
      el.querySelector('span').textContent = start.format('DD-MM-YYYY') + ' — ' + end.format('DD-MM-YYYY');
    });
  }, []);

  return (
    <div ref={ref} className="inline-flex items-center gap-2 px-3 py-1.5 bg-white border border-gray-200 rounded-lg cursor-pointer hover:border-sky-300 hover:bg-sky-50/30 transition-all text-[12px] text-gray-700">
      <Calendar size={14} className="text-sky-600 shrink-0" />
      <span>{fmtDisplay(startValue)} — {fmtDisplay(endValue)}</span>
    </div>
  );
}

/* ── Shared UI ── */
function Card({ children, className = '' }) {
  return <div className={`bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col ${className}`}>{children}</div>;
}
function CardH({ children, className = '' }) {
  return <div className={`border-b border-gray-100 flex items-center justify-between gap-2 flex-wrap shrink-0 ${className}`} style={{padding:'var(--ds-pad) var(--ds-pad)'}}>{children}</div>;
}
function CardB({ children, className = '' }) {
  return <div className={`flex-1 flex flex-col ${className}`} style={{padding:'var(--ds-pad)'}}>{children}</div>;
}
function Badge({ children, color = 'gray' }) {
  const c = { green:'bg-emerald-50 text-emerald-700 border-emerald-200', red:'bg-red-50 text-red-700 border-red-200', blue:'bg-sky-50 text-sky-700 border-sky-200', orange:'bg-orange-50 text-orange-700 border-orange-200', yellow:'bg-amber-50 text-amber-700 border-amber-200', gray:'bg-gray-50 text-gray-600 border-gray-200', purple:'bg-purple-50 text-purple-700 border-purple-200' };
  return <span className={`inline-flex items-center px-2 py-0.5 font-semibold rounded-md border ${c[color]||c.gray}`} style={{fontSize:'var(--ds-tiny)'}}>{children}</span>;
}
function StatCard({ label, value, tip }) {
  return (
    <div className="bg-white rounded-lg border border-gray-200 text-center relative group" style={{padding:'var(--ds-pad)'}}>
      {tip && <div className="absolute top-1 right-1 text-gray-300 group-hover:text-gray-500 cursor-help" title={tip}><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg></div>}
      <div className="text-gray-500 mb-0.5" style={{fontSize:'var(--ds-metric-label)'}}>{label}</div>
      <div className="font-bold text-gray-900" style={{fontSize:'var(--ds-metric-value)'}}>{typeof value === 'number' ? value.toLocaleString() : value}</div>
    </div>
  );
}
function MiniMetric({ label, value }) {
  return (
    <div className="text-center bg-white rounded-lg border border-gray-300 shadow-sm" style={{padding:'var(--ds-pad)'}}>
      <div className="text-gray-500 uppercase leading-tight break-words" style={{fontSize:'var(--ds-tiny)'}} title={label}>{label}</div>
      <div className="font-bold text-gray-800 leading-tight" style={{fontSize:'var(--ds-body)'}}>{typeof value === 'number' ? value.toLocaleString() : value}</div>
    </div>
  );
}
function SelectInput({ value, onChange, children, className = '' }) {
  return <select value={value} onChange={e => onChange(e.target.value)} className={`border border-gray-200 rounded-lg bg-white text-gray-700 outline-none focus:border-sky-400 focus:ring-1 focus:ring-sky-100 ${className}`} style={{fontSize:'var(--ds-body)',padding:'var(--ds-gap) var(--ds-pad)'}}>{children}</select>;
}
function InputField({ label, type = 'text', value, onChange, className = '', ...rest }) {
  return (
    <div className="flex flex-col gap-0.5">
      {label && <label className="text-[11px] text-gray-500 font-medium">{label}</label>}
      <input type={type} value={value} onChange={e => onChange(e.target.value)} className={`text-[13px] border border-gray-200 rounded-lg px-2.5 py-1.5 bg-white text-gray-700 outline-none focus:border-sky-400 focus:ring-1 focus:ring-sky-100 w-full ${className}`} {...rest} />
    </div>
  );
}
function SearchInput({ value, onChange, placeholder = 'Search...' }) {
  return (
    <div className="relative">
      <svg className="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
      <input type="text" value={value} onChange={e => onChange(e.target.value)} placeholder={placeholder} className="text-[13px] border border-gray-200 rounded-lg pl-8 pr-3 py-1.5 bg-white text-gray-700 outline-none focus:border-sky-400 focus:ring-1 focus:ring-sky-100 w-full" />
    </div>
  );
}
function FilterPill({ label, active, onClick, count }) {
  return (
    <button onClick={onClick} className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-medium transition-all border ${active ? 'bg-sky-50 text-sky-700 border-sky-200' : 'bg-white text-gray-500 border-gray-200 hover:bg-gray-50 hover:border-gray-300'}`}>
      {label}{count !== undefined && <span className={`text-[10px] px-1 rounded-full ${active ? 'bg-sky-200 text-sky-800' : 'bg-gray-100 text-gray-500'}`}>{count}</span>}
    </button>
  );
}
const fmtMin = (m) => { if (!m && m !== 0) return '-'; const n = parseFloat(m); if (isNaN(n)) return '-'; const h = Math.floor(n / 60); const min = Math.round(n % 60); return h > 0 ? `${h}h ${min}m` : `${min}m`; };
/** Safely convert any value to array (handles PHP objects/collections serialized as {}) */
const toArr = (v) => { if (Array.isArray(v)) return v; if (v && typeof v === 'object') return Object.values(v); return []; };

/* ── Design Tokens — read from CSS :root variables (single source of truth) ── */
const _tokenCache = {};
function tk(name) {
  if (_tokenCache[name]) return _tokenCache[name];
  const v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
  if (v) _tokenCache[name] = v;
  return v || '';
}
/** Lazily-built color maps — read once from CSS vars, cached */
let _STATUS_COLORS = null;
function getStatusColors() {
  if (_STATUS_COLORS) return _STATUS_COLORS;
  _STATUS_COLORS = {
    pending:     { accent: tk('--pending'),     bg: tk('--pending-bg'),     border: tk('--pending-border'),     text: tk('--pending-text'),     label: tk('--pending-label')     },
    scheduled:   { accent: tk('--scheduled'),   bg: tk('--scheduled-bg'),   border: tk('--scheduled-border'),   text: tk('--scheduled-text'),   label: tk('--scheduled-label')   },
    waiting:     { accent: tk('--waiting'),      bg: tk('--waiting-bg'),     border: tk('--waiting-border'),     text: tk('--waiting-text'),     label: tk('--waiting-label')     },
    in_progress: { accent: tk('--in-progress'), bg: tk('--in-progress-bg'), border: tk('--in-progress-border'), text: tk('--in-progress-text'), label: tk('--in-progress-label') },
    completed:   { accent: tk('--completed'),   bg: tk('--completed-bg'),   border: tk('--completed-border'),   text: tk('--completed-text'),   label: tk('--completed-label')   },
    cancelled:   { accent: tk('--cancelled'),   bg: tk('--cancelled-bg'),   border: tk('--cancelled-border'),   text: tk('--cancelled-text'),   label: tk('--cancelled-label')   },
  };
  return _STATUS_COLORS;
}
const COLORS_DIR = () => [tk('--inbound') || '#0284c7', tk('--outbound') || '#ea580c'];
const COLORS_KPI = () => [tk('--completed') || '#10b981', tk('--cancelled') || '#ef4444'];
const tipStyle = () => ({ borderRadius: 8, border: `1px solid ${tk('--tooltip-border') || '#e2e8f0'}`, boxShadow: `0 4px 12px ${tk('--tooltip-shadow') || 'rgba(0,0,0,0.08)'}`, fontSize: 11 });

/* CSS vars (--ds-*) defined in dashboard-react.css handle responsive font sizing globally */
const STAT_TIPS = { Pending:'Booking requests awaiting approval.', Scheduled:'Slots scheduled, truck not arrived.', Waiting:'Truck arrived, waiting in queue.', 'In Progress':'Loading/Unloading in progress.', Completed:'Process finished.', Cancel:'Cancelled slots.', Total:'Total slots in selected range.' };
/** Build current URL params from data + overrides (for history state) */
function buildParams(currentData, overrides) {
  const params = new URLSearchParams();
  const keys = ['range_start','range_end','timeline_date','timeline_from','timeline_to','schedule_date','schedule_from','schedule_to','activity_date','activity_warehouse','activity_user'];
  keys.forEach(k => { if (currentData[k]) params.set(k, currentData[k]); });
  Object.entries(overrides).forEach(([k,v]) => { if (v !== undefined && v !== null) params.set(k, v); });
  return params;
}

/** Fetch dashboard data via AJAX, update React state, update URL silently */
async function fetchDashboard(currentData, overrides, setData, setLoading) {
  const params = buildParams(currentData, overrides);
  setLoading(true);
  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const res = await fetch('/dashboard/data?' + params.toString(), {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...(csrfToken ? {'X-CSRF-TOKEN': csrfToken} : {}) },
    });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    const json = await res.json();
    setData(json);
    // Update URL silently so refresh/bookmark preserves filters
    const newUrl = window.location.pathname + '?' + params.toString();
    window.history.replaceState(null, '', newUrl);
  } catch (e) {
    console.error('[Dashboard] Fetch error:', e);
  } finally {
    setLoading(false);
  }
}

/** Legacy fallback — only used if AJAX fails catastrophically */
function navToParams(params) {
  const u = new URL(window.location.href);
  Object.entries(params).forEach(([k,v]) => { if (v !== undefined && v !== null) u.searchParams.set(k, v); });
  window.location.href = u.toString();
}

/* ================================================================
   SLIDE 0 — ANALYTICS OVERVIEW
   ================================================================ */
/* Chart font sizes read from CSS vars via getComputedStyle at render */
function csVar(name) {
  if (typeof document === 'undefined') return 14;
  const el = document.getElementById('react-dashboard') || document.documentElement;
  const v = getComputedStyle(el).getPropertyValue(name).trim();
  return parseFloat(v) || 14;
}

function AnalyticsSlide({ data }) {
  const { pendingRange=0, scheduledRange=0, waitingRange=0, activeRange=0, completedStatusRange=0, cancelledRange=0, totalAllRange=0, inboundRange=0, outboundRange=0, directionByGate={} } = data;
  const trendDays = toArr(data.trendDays), trendCounts = toArr(data.trendCounts), trendInbound = toArr(data.trendInbound), trendOutbound = toArr(data.trendOutbound);
  const [dirGate, setDirGate] = useState('all');
  const stats = [
    { label:'Pending', value:pendingRange }, { label:'Scheduled', value:scheduledRange },
    { label:'Waiting', value:waitingRange }, { label:'In Progress', value:activeRange },
    { label:'Completed', value:completedStatusRange }, { label:'Cancel', value:cancelledRange },
    { label:'Total', value:totalAllRange },
  ];
  const trendData = useMemo(() => (trendDays||[]).map((d,i) => ({ day:String(d).split('-').pop(), completed:+(trendCounts[i]||0), inbound:+(trendInbound[i]||0), outbound:+(trendOutbound[i]||0) })), [trendDays,trendCounts,trendInbound,trendOutbound]);
  const dirData = useMemo(() => {
    const g = directionByGate?.[dirGate] || directionByGate?.all || directionByGate?.All || {};
    const inV = +(g.inbound||0) || (dirGate==='all' ? +inboundRange : 0);
    const outV = +(g.outbound||0) || (dirGate==='all' ? +outboundRange : 0);
    const t = inV+outV;
    return { items:[{name:'Inbound',value:inV,pct:t?((inV/t)*100).toFixed(1):0},{name:'Outbound',value:outV,pct:t?((outV/t)*100).toFixed(1):0}], inV, outV, total:t };
  }, [directionByGate,dirGate,inboundRange,outboundRange]);
  const gateOpts = useMemo(() => Object.keys(directionByGate||{}).filter(k=>k!=='all'&&k!=='All'), [directionByGate]);

  return (
    <div className="gap-2 flex flex-col flex-1">
      {/* Stat cards - responsive grid: 2 cols on mobile, 4 on md, 7 on lg */}
      <div className="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2">
        {stats.map(s => <StatCard key={s.label} label={s.label} value={s.value} tip={STAT_TIPS[s.label]} />)}
      </div>

      {/* Charts row */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-2 flex-1">
        {/* Completed Trend */}
        <Card className="lg:col-span-2 flex flex-col overflow-hidden">
          <CardH>
            <h3 className="font-semibold text-gray-800" style={{fontSize:'var(--ds-title)'}}>Completed Trend</h3>
            <div className="flex items-center gap-3 flex-wrap" style={{fontSize:'var(--ds-tiny)'}}>
              <span className="flex items-center gap-1 text-gray-500"><span className="w-2.5 h-2.5 rounded-sm bg-sky-600 inline-block shrink-0"></span> In</span>
              <span className="flex items-center gap-1 text-gray-500"><span className="w-2.5 h-2.5 rounded-sm bg-orange-600 inline-block shrink-0"></span> Out</span>
              <span className="flex items-center gap-1 text-gray-500"><span className="w-2.5 h-0.5 bg-green-600 inline-block rounded shrink-0"></span> Done</span>
            </div>
          </CardH>
          <CardB className="p-2 flex flex-col" style={{flex:'1 1 0%'}}>
            <div className="flex w-full" style={{flex:'1 1 0%',minHeight:140}}>
              <div className="flex items-center shrink-0 -mr-1">
                <span className="text-gray-400 font-medium" style={{fontSize:'var(--ds-chart-label)',writingMode:'vertical-rl',transform:'rotate(180deg)'}}>Completed Count</span>
              </div>
              <div className="flex-1 flex flex-col min-w-0">
                <ResponsiveContainer width="100%" height="100%">
                  <ComposedChart data={trendData} margin={{top:16,right:8,left:0,bottom:0}}>
                    <CartesianGrid strokeDasharray="3 3" stroke={tk('--chart-grid')}/>
                    <XAxis dataKey="day" tick={{fontSize:csVar('--ds-chart-tick'),fill:tk('--chart-axis')}} interval="preserveStartEnd"/>
                    <YAxis tick={{fontSize:csVar('--ds-chart-tick'),fill:tk('--chart-axis')}} width={28}/>
                    <Tooltip contentStyle={tipStyle()}/>
                    <Bar dataKey="inbound" stackId="s" fill={tk('--inbound')} radius={[0,0,3,3]} name="Inbound">
                      <LabelList dataKey="inbound" position="center" style={{fontSize:csVar('--ds-chart-label'),fill:'#fff',fontWeight:600}} formatter={v=>v>0?v:''}/>
                    </Bar>
                    <Bar dataKey="outbound" stackId="s" fill={tk('--outbound')} radius={[3,3,0,0]} name="Outbound">
                      <LabelList dataKey="outbound" position="center" style={{fontSize:csVar('--ds-chart-label'),fill:'#fff',fontWeight:600}} formatter={v=>v>0?v:''}/>
                    </Bar>
                    <Line type="monotone" dataKey="completed" stroke={tk('--chart-line-completed')} strokeWidth={2} dot={{r:2,fill:tk('--chart-line-completed')}} name="Completed">
                      <LabelList dataKey="completed" position="top" style={{fontSize:csVar('--ds-chart-label'),fill:tk('--chart-line-completed'),fontWeight:700}} formatter={v=>v>0?v:''}/>
                    </Line>
                  </ComposedChart>
                </ResponsiveContainer>
                <div className="text-center text-gray-400 font-medium" style={{fontSize:'var(--ds-chart-label)'}}>Date</div>
              </div>
            </div>
          </CardB>
        </Card>

        {/* Direction */}
        <Card className="flex flex-col overflow-hidden">
          <CardH>
            <h3 className="font-semibold text-gray-800" style={{fontSize:'var(--ds-title)'}}>Direction</h3>
            <SelectInput value={dirGate} onChange={setDirGate}>
              <option value="all">All Gates</option>
              {gateOpts.map(g=><option key={g} value={g}>{g}</option>)}
            </SelectInput>
          </CardH>
          <CardB className="flex flex-col p-3 gap-2" style={{flex:'1 1 0%'}}>
            <div className="relative w-full" style={{flex:'1 1 0%',minHeight:100}}>
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie data={dirData.items} cx="50%" cy="50%" innerRadius="35%" outerRadius="57%" paddingAngle={3} dataKey="value"
                    label={({value,pct,cx:pcx,cy:pcy,midAngle,outerRadius:or})=>{
                      if(!value) return null;
                      const R=Math.PI/180, r=or+14;
                      const x=pcx+r*Math.cos(-midAngle*R), y=pcy+r*Math.sin(-midAngle*R);
                      const fontSize = Math.max(10, or * 0.18);
                      return <text x={x} y={y} textAnchor={x>pcx?'start':'end'} dominantBaseline="central" style={{fontSize,fontWeight:700,fill:tk('--text-primary')}}>{pct}%</text>;
                    }}
                    labelLine={{stroke:tk('--chart-axis'),strokeWidth:1}}>
                    {dirData.items.map((_,i)=><Cell key={i} fill={COLORS_DIR()[i]}/>)}
                  </Pie>
                  <Tooltip/>
                </PieChart>
              </ResponsiveContainer>
              <div className="absolute inset-0 flex items-center justify-center pointer-events-none" style={{containerType:'size'}}>
                <div className="text-center">
                  <div className="font-bold text-gray-800 leading-tight" style={{fontSize:'min(clamp(18px, 6cqmin, 36px), 12cqmin)'}}>{dirData.total}</div>
                  <div className="text-gray-400 uppercase tracking-wide" style={{fontSize:'min(clamp(9px, 2.5cqmin, 14px), 5cqmin)'}}>Total</div>
                </div>
              </div>
            </div>
            <div className="grid grid-cols-2 gap-2 shrink-0">
              <div className="flex items-center gap-2 bg-sky-50 rounded-lg px-2 py-2 border border-sky-100">
                <span className="w-2.5 h-2.5 rounded-full bg-sky-600 shrink-0"></span>
                <div className="min-w-0">
                  <div className="text-sky-600 font-medium" style={{fontSize:'var(--ds-tiny)'}}>Inbound</div>
                  <div className="font-bold text-gray-800" style={{fontSize:'var(--ds-body)'}}>{dirData.inV}</div>
                </div>
              </div>
              <div className="flex items-center gap-2 bg-orange-50 rounded-lg px-2 py-2 border border-orange-100">
                <span className="w-2.5 h-2.5 rounded-full bg-orange-600 shrink-0"></span>
                <div className="min-w-0">
                  <div className="text-orange-600 font-medium" style={{fontSize:'var(--ds-tiny)'}}>Outbound</div>
                  <div className="font-bold text-gray-800" style={{fontSize:'var(--ds-body)'}}>{dirData.outV}</div>
                </div>
              </div>
            </div>
          </CardB>
        </Card>
      </div>
    </div>
  );
}

/* ================================================================
   SLIDE 1 — BOTTLENECK & PERFORMANCE
   ================================================================ */
function BottleneckSlide({ data }) {
  const { bottleneckThresholdMinutes=30, avgLeadMinutes=0, avgProcessMinutes=0 } = data;
  const bottleneckLabels = toArr(data.bottleneckLabels), bottleneckValues = toArr(data.bottleneckValues), bottleneckDirections = toArr(data.bottleneckDirections), avgTimesByTruckType = toArr(data.avgTimesByTruckType);
  const [bnDir, setBnDir] = useState('all');
  const chartData = useMemo(() => {
    return (bottleneckLabels||[]).map((l,i)=>({name:l,value:+(bottleneckValues[i]||0),dir:bottleneckDirections[i]||''})).filter(d=> bnDir==='all' || d.dir.toLowerCase()===bnDir);
  }, [bottleneckLabels,bottleneckValues,bottleneckDirections,bnDir]);
  const topItem = chartData[0];

  return (
    <div className="flex flex-col gap-2 flex-1">
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-2 flex-1">
        <Card className="flex flex-col">
          <CardH>
            <h3 className="text-sm font-semibold text-gray-800">Bottleneck (Avg Waiting)</h3>
            <SelectInput value={bnDir} onChange={setBnDir}>
              <option value="all">All</option><option value="inbound">Inbound</option><option value="outbound">Outbound</option>
            </SelectInput>
          </CardH>
          <CardB className="flex flex-col" style={{flex:'1 1 0%'}}>
            <div style={{flex:'1 1 0%',minHeight:180}}>
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={chartData.slice(0,20)} layout="vertical" margin={{top:5,right:20,left:70,bottom:5}}>
                  <CartesianGrid strokeDasharray="3 3" stroke={tk('--chart-grid')}/>
                  <XAxis type="number" tick={{fontSize:10,fill:tk('--chart-axis')}}/>
                  <YAxis dataKey="name" type="category" tick={{fontSize:9,fill:tk('--text-secondary')}} width={65}/>
                  <Tooltip formatter={v=>`${(+v).toFixed(1)} min`} contentStyle={tipStyle()}/>
                  <Bar dataKey="value" fill={tk('--chart-bottleneck')} radius={[0,6,6,0]} name="Avg Waiting (min)"/>
                </BarChart>
              </ResponsiveContainer>
            </div>
            <div className="grid grid-cols-3 gap-1.5 mt-2">
              <MiniMetric label="Top Wait" value={topItem?.name||'-'}/>
              <MiniMetric label="Avg Wait" value={topItem ? `${topItem.value.toFixed(1)}m` : '-'}/>
              <MiniMetric label="Threshold" value={`${bottleneckThresholdMinutes}m`}/>
            </div>
            <div className="text-[10px] text-gray-400 mt-1">Top 20, Threshold {bottleneckThresholdMinutes} Min</div>
          </CardB>
        </Card>

        <Card className="flex flex-col">
          <CardH><h3 className="text-sm font-semibold text-gray-800">Performance by Truck Type</h3></CardH>
          <CardB className="flex-1 overflow-y-auto flex flex-col">
            <div className="grid grid-cols-2 gap-2 flex-1" style={{gridAutoRows:'1fr'}}>
              {(avgTimesByTruckType||[]).map((t,i) => {
                const label = (t.truck_type||'-').replace(/^Container\s+/i,'').replace(/Wingbox/i,'WB');
                return (
                  <div key={i} className="border border-gray-200 rounded-lg p-2 flex flex-col">
                    <div className="flex items-center justify-between mb-1 shrink-0">
                      <span className="font-semibold text-gray-700 truncate" style={{fontSize:'var(--ds-small)'}} title={t.truck_type}>{label}</span>
                      <span className="text-gray-400 bg-gray-100 rounded px-1 py-0.5" style={{fontSize:'var(--ds-tiny)'}}>{t.total_count||0}</span>
                    </div>
                    <div className="grid grid-cols-2 gap-1 flex-1" style={{gridAutoRows:'1fr'}}>
                      <div className="bg-sky-50 rounded px-1 text-center flex flex-col items-center justify-center">
                        <div className="text-sky-600 font-medium" style={{fontSize:'var(--ds-tiny)'}}>Lead</div>
                        <div className="font-bold text-sky-800" style={{fontSize:'var(--ds-body)'}}>{t.total_count > 0 ? fmtMin(t.avg_lead_minutes) : '-'}</div>
                      </div>
                      <div className="bg-emerald-50 rounded px-1 text-center flex flex-col items-center justify-center">
                        <div className="text-emerald-600 font-medium" style={{fontSize:'var(--ds-tiny)'}}>Process</div>
                        <div className="font-bold text-emerald-800" style={{fontSize:'var(--ds-body)'}}>{t.total_count > 0 ? fmtMin(t.avg_process_minutes) : '-'}</div>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
            <div className="grid grid-cols-2 gap-2 mt-2 pt-2 border-t border-gray-100">
              <MiniMetric label="Avg Lead" value={fmtMin(avgLeadMinutes)}/>
              <MiniMetric label="Avg Process" value={fmtMin(avgProcessMinutes)}/>
            </div>
          </CardB>
        </Card>
      </div>
    </div>
  );
}

/* ================================================================
   SLIDE 2 — KPI
   ================================================================ */
function KPISlide({ data }) {
  const { onTimeDir={}, targetDir={}, completionRate=0, completionTotalSlots=0, completionCompletedSlots=0 } = data;
  const [kpiDir, setKpiDir] = useState('all');
  const src = kpiDir === 'all' ? (onTimeDir?.all||{}) : (onTimeDir?.[kpiDir]||{});
  const tSrc = kpiDir === 'all' ? (targetDir?.all||{}) : (targetDir?.[kpiDir]||{});
  const onT = +(src.on_time||0), late = +(src.late||0), otTotal = onT+late;
  const ach = +(tSrc.achieve||0), notA = +(tSrc.not_achieve||0), tTotal = ach+notA;
  const onTimePct = otTotal > 0 ? ((onT/otTotal)*100).toFixed(1) : '0.0';
  const targetPct = tTotal > 0 ? ((ach/tTotal)*100).toFixed(1) : '0.0';

  const kpiBgMap = { good:'bg-emerald-50 border-emerald-100 text-emerald-700', bad:'bg-red-50 border-red-100 text-red-700', neutral:'bg-gray-50 border-gray-200 text-gray-600' };
  const kpiDotMap = { good:'bg-emerald-500', bad:'bg-red-500', neutral:'bg-gray-400' };
  const kpiSemantic = (i, len) => i === 0 ? 'good' : i === len - 1 ? 'neutral' : 'bad';

  function KpiCard({ title, pct, pctColor, chartData, colors, metrics }) {
    return (
      <Card className="flex flex-col">
        <CardH><h3 style={{fontSize:'var(--ds-title)'}} className="font-semibold text-gray-800">{title}</h3></CardH>
        <CardB className="flex flex-col" style={{flex:'1 1 0%',gap:'var(--ds-gap)'}}>
          {/* Donut with center pct + outer labels */}
          <div className="relative w-full" style={{flex:'1 1 0%',minHeight:100}}>
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <Pie data={chartData} cx="50%" cy="50%" innerRadius="35%" outerRadius="57%" paddingAngle={3} dataKey="value"
                  label={({value,cx:pcx,cy:pcy,midAngle,outerRadius:or,payload})=>{
                    if(!value) return null;
                    const total = chartData.reduce((s,d)=>s+(+d.value||0),0);
                    const p = total > 0 ? ((value/total)*100).toFixed(1) : '0';
                    const R=Math.PI/180, r=or+14;
                    const x=pcx+r*Math.cos(-midAngle*R), y=pcy+r*Math.sin(-midAngle*R);
                    const fontSize = Math.max(8, or * 0.14);
                    return <text x={x} y={y} textAnchor={x>pcx?'start':'end'} dominantBaseline="central" style={{fontSize,fontWeight:600,fill:tk('--text-primary')}}>{p}%</text>;
                  }}
                  labelLine={{stroke:tk('--chart-axis'),strokeWidth:1}}>
                  {chartData.map((_,i)=><Cell key={i} fill={colors[i]||tk('--border-light')}/>)}
                </Pie>
                <Tooltip/>
              </PieChart>
            </ResponsiveContainer>
            <div className="absolute inset-0 flex items-center justify-center pointer-events-none" style={{containerType:'size'}}>
              <div className="text-center">
                <div className="font-bold leading-tight" style={{fontSize:'min(clamp(14px, 4.5cqmin, 28px), 9cqmin)',color:pctColor}}>{pct}%</div>
              </div>
            </div>
          </div>
          {/* Colored legend cards matching chart colors */}
          <div className="grid grid-cols-3 w-full shrink-0" style={{gap:'var(--ds-gap)'}}>
            {metrics.map((m,i)=>{
              const sem = kpiSemantic(i, metrics.length);
              return (
              <div key={i} className={`flex items-center gap-1.5 rounded-lg border ${kpiBgMap[sem]}`} style={{padding:'var(--ds-pad)'}}>
                <span className={`w-2 h-2 rounded-full shrink-0 ${kpiDotMap[sem]}`}></span>
                <div className="min-w-0">
                  <div className="font-medium" style={{fontSize:'var(--ds-tiny)'}}>{m[0]}</div>
                  <div className="font-bold text-gray-800" style={{fontSize:'var(--ds-body)'}}>{m[1]}</div>
                </div>
              </div>
              );
            })}
          </div>
        </CardB>
      </Card>
    );
  }

  return (
    <div className="flex flex-col gap-2 flex-1">
      <div className="flex items-center gap-2">
        <span className="text-xs text-gray-500 font-medium">KPI</span>
        <SelectInput value={kpiDir} onChange={setKpiDir}>
          <option value="all">All Direction</option><option value="inbound">Inbound</option><option value="outbound">Outbound</option>
        </SelectInput>
      </div>
      <div className="grid grid-cols-1 md:grid-cols-3 gap-2 flex-1">
        <KpiCard title="On Time vs Late" pct={onTimePct} pctColor={tk('--completed')}
          chartData={[{name:'On Time',value:onT},{name:'Late',value:late}]}
          colors={COLORS_KPI()} metrics={[['On Time',onT],['Late',late],['Total',otTotal]]}/>
        <KpiCard title="Target Achievement" pct={targetPct} pctColor={tk('--completed')}
          chartData={[{name:'Achieved',value:ach},{name:'Not Achieved',value:notA}]}
          colors={COLORS_KPI()} metrics={[['Achieve',ach],['Not Achieve',notA],['Total',tTotal]]}/>
        <KpiCard title="Completion Rate" pct={parseFloat(completionRate||0).toFixed(1)} pctColor={tk('--completed')}
          chartData={[{name:'Completed',value:+completionCompletedSlots},{name:'Remaining',value:Math.max(0,+completionTotalSlots - +completionCompletedSlots)}]}
          colors={COLORS_KPI()} metrics={[['Completed',+completionCompletedSlots],['Remaining',Math.max(0,+completionTotalSlots - +completionCompletedSlots)],['Total',+completionTotalSlots]]}/>
      </div>
    </div>
  );
}

/* ================================================================
   SLIDE 3 — TIMELINE (24h)
   ================================================================ */
function TimelineSlide({ data, onFilter }) {
  const { timelineBlocksByGate={}, timeline_date='', timeline_from='', timeline_to='', today='' } = data;
  const gateCards = toArr(data.gateCards);
  const [tip, setTip] = useState(null); // {x,y,block}
  const [tlDate, setTlDate] = useState(timeline_date || today);
  const [shift, setShift] = useState(() => {
    if (timeline_from==='07:00'&&timeline_to==='15:00') return 'shift1';
    if (timeline_from==='15:00'&&timeline_to==='23:00') return 'shift2';
    if (timeline_from==='23:00'&&timeline_to==='07:00') return 'shift3';
    if (timeline_from==='00:00'&&timeline_to==='23:59') return 'full';
    return 'shift1';
  });
  const shiftRanges = { full:['00:00','23:59'], shift1:['07:00','15:00'], shift2:['15:00','23:00'], shift3:['23:00','07:00'] };
  const [fromH, toH] = shiftRanges[shift] || shiftRanges.shift1;

  // Sync local state when data changes from AJAX
  useEffect(() => { setTlDate(timeline_date || today); }, [timeline_date, today]);

  const hours = useMemo(() => {
    const f = parseInt(fromH.split(':')[0],10), t = parseInt(toH.split(':')[0],10);
    const arr=[]; let h=f;
    for (let i=0;i<25;i++) { arr.push(h%24); if (h%24===t%24 && i>0) break; h++; }
    return arr;
  }, [fromH,toH]);

  const applyTimeline = () => {
    const [sf,st] = shiftRanges[shift]||shiftRanges.shift1;
    if (onFilter) onFilter({ timeline_date:tlDate, timeline_from:sf, timeline_to:st });
  };

  return (
    <div className="flex flex-col gap-2 flex-1">
      <Card>
        <CardB className="py-2 px-3">
          <div className="flex items-end gap-3 flex-wrap">
            <JQDatePicker label="Date" value={tlDate} onChange={setTlDate} />
            <div className="flex flex-col gap-0.5">
              <label className="text-[11px] text-gray-500 font-medium">Shift</label>
              <SelectInput value={shift} onChange={setShift}>
                <option value="full">24 Hours</option>
                <option value="shift1">Shift 1 (07-15)</option>
                <option value="shift2">Shift 2 (15-23)</option>
                <option value="shift3">Shift 3 (23-07)</option>
              </SelectInput>
            </div>
            <button onClick={applyTimeline} className="px-4 py-1.5 text-[13px] font-medium text-white bg-sky-600 rounded-lg hover:bg-sky-700 transition-colors shadow-sm">Apply</button>
            <button onClick={()=>{setTlDate(today); setShift('shift1'); onFilter({ timeline_date:today, timeline_from:'07:00', timeline_to:'15:00' });}} className="px-4 py-1.5 text-[13px] font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors shadow-sm">Reset</button>
          </div>
        </CardB>
      </Card>
      <Card className="flex flex-col flex-1">
        <CardH>
          <h3 className="text-[13px] font-semibold text-gray-800">Timeline — {tlDate}</h3>
          <div className="flex items-center gap-3 text-[11px] text-gray-500">
            <span className="flex items-center gap-1"><span className="w-2.5 h-2.5 rounded-sm bg-gray-400 inline-block"></span> Scheduled</span>
            <span className="flex items-center gap-1"><span className="w-2.5 h-2.5 rounded-sm bg-amber-400 inline-block"></span> Waiting</span>
            <span className="flex items-center gap-1"><span className="w-2.5 h-2.5 rounded-sm bg-sky-500 inline-block"></span> In Progress</span>
            <span className="flex items-center gap-1"><span className="w-2.5 h-2.5 rounded-sm bg-emerald-500 inline-block"></span> Completed</span>
          </div>
        </CardH>
        <CardB className="overflow-auto flex flex-col" style={{flex:'1 1 0%',padding:'var(--ds-pad)'}}>
          <div className="min-w-[700px] flex flex-col flex-1">
            {/* Timeline header */}
            <div className="flex border-b border-gray-200 pb-1.5 mb-1 sticky top-0 bg-white z-10 shrink-0">
              <div className="w-28 shrink-0 font-semibold text-gray-500 px-2" style={{fontSize:'var(--ds-small)'}}>Gate</div>
              <div className="w-12 shrink-0 font-semibold text-gray-500 text-center" style={{fontSize:'var(--ds-small)'}}>Lane</div>
              <div className="flex flex-1">
                {hours.map((h,i)=><div key={i} className="flex-1 text-center font-medium text-gray-400 border-l border-gray-100" style={{fontSize:'var(--ds-tiny)'}}>{String(h).padStart(2,'0')}:00</div>)}
              </div>
            </div>
            {/* Gate rows - each row stretches to fill */}
            {(gateCards||[]).map((gate,gi) => {
              const gid = gate.gate_id || gi;
              const blocks = timelineBlocksByGate?.[gid] || [];
              const title = (gate.title||gate.gate_name||`Gate ${gi+1}`).replace(/^Warehouse\s*\d*\s*-\s*/i,'');
              const planned = blocks.filter(b=>b.lane==='planned');
              const actual = blocks.filter(b=>b.lane==='actual');
              const totalMin = hours.length * 60;
              const renderBlocks = (list) => list.map((b,bi)=>{
                const l=+(b.left||0), w=Math.max(+(b.width||1),1);
                const cls={scheduled:'bg-gray-400',waiting:'bg-amber-400',in_progress:'bg-sky-500',completed:'bg-emerald-500'};
                return <div key={bi} className={`absolute ${cls[b.status]||'bg-gray-400'} rounded text-white truncate px-1 flex items-center h-full shadow-sm cursor-pointer`}
                  style={{fontSize:'var(--ds-chart-label)',left:`${(l/totalMin)*100}%`,width:`${Math.max((w/totalMin)*100,0.6)}%`}}
                  onMouseEnter={(e)=>setTip({x:e.clientX,y:e.clientY,block:b})}
                  onMouseMove={(e)=>setTip(prev=>prev?{...prev,x:e.clientX,y:e.clientY}:null)}
                  onMouseLeave={()=>setTip(null)}
                >{b.po_number||`#${b.id||''}`}</div>;
              });
              return (
                <div key={gi} className="flex border-b border-gray-100 hover:bg-sky-50/20 transition-colors" style={{flex:'1 1 0%',minHeight:40}}>
                  <div className="w-28 shrink-0 px-2 flex flex-col justify-center">
                    <div className="font-semibold text-gray-700 leading-tight truncate" style={{fontSize:'var(--ds-body)'}} title={gate.title||gate.gate_name}>{title}</div>
                    <div className="text-gray-400 mt-0.5" style={{fontSize:'var(--ds-tiny)'}}>{gate.status_label||'Idle'}</div>
                  </div>
                  <div className="w-12 shrink-0 flex flex-col py-1 text-gray-400 text-center" style={{fontSize:'var(--ds-tiny)',gap:'var(--ds-gap)'}}>
                    <div className="text-sky-600 font-medium flex items-center justify-center" style={{flex:'1 1 0%',minHeight:12}}>Plan</div>
                    <div className="text-emerald-600 font-medium flex items-center justify-center" style={{flex:'1 1 0%',minHeight:12}}>Act</div>
                  </div>
                  <div className="flex-1 flex flex-col py-1" style={{gap:'var(--ds-gap)'}}>
                    <div className="relative bg-sky-50/70 rounded overflow-hidden border border-sky-100/50" style={{flex:'1 1 0%',minHeight:12}}>{renderBlocks(planned)}</div>
                    <div className="relative bg-emerald-50/70 rounded overflow-hidden border border-emerald-100/50" style={{flex:'1 1 0%',minHeight:12}}>{renderBlocks(actual)}</div>
                  </div>
                </div>
              );
            })}
            {(gateCards||[]).length===0 && <div className="text-center text-gray-400 py-10" style={{fontSize:'var(--ds-body)'}}>No gate data available</div>}
          </div>
        </CardB>
      </Card>

      {/* Custom floating tooltip */}
      {tip && tip.block && (()=>{
        const b = tip.block;
        const realStatus = b.slot_status || b.status || '';
        const stLabel = realStatus.replace(/_/g,' ');
        const sc = getStatusColors();
        const raw = sc[realStatus] || sc.scheduled;
        const th = { ...raw, icon: raw.accent, headerBg: raw.bg, badgeBg: raw.border, badgeText: raw.text };
        const laneLabel = b.lane === 'planned' ? 'Planned' : 'Actual';
        const fmtTime = (t) => { if (!t) return ''; try { return t.substring(11,16)||t; } catch(e) { return t; } };
        const timeStart = fmtTime(b.lane==='planned' ? b.planned_start : (b.actual_start||b.arrival_time||b.planned_start));
        const timeEnd = fmtTime(b.lane==='planned' ? b.planned_end : (b.actual_finish||b.planned_end));
        const tipW = 240, tipH = 180, pad = 12;
        let tx = tip.x + 14, ty = tip.y - tipH - 8;
        if (tx + tipW + pad > window.innerWidth) tx = tip.x - tipW - 14;
        if (tx < pad) tx = pad;
        if (ty < pad) ty = tip.y + 18;
        if (ty + tipH + pad > window.innerHeight) ty = window.innerHeight - tipH - pad;
        return (
          <div className="fixed z-[9999] pointer-events-none" style={{left:tx,top:ty}}>
            <div className="rounded-xl shadow-lg text-left overflow-hidden" style={{minWidth:220,maxWidth:300,background:th.bg,border:`1px solid ${th.border}`}}>
              {/* Header */}
              <div style={{padding:'10px 14px 8px',borderBottom:`1px solid ${th.border}`,background:th.headerBg}}>
                <div className="flex items-center justify-between gap-2">
                  <span className="font-bold" style={{fontSize:14,color:th.text}}>{b.po_number||`#${b.id||'-'}`}</span>
                  <span className="uppercase font-semibold tracking-wide" style={{fontSize:9,color:th.badgeText,background:th.badgeBg,padding:'2px 8px',borderRadius:6}}>{stLabel}</span>
                </div>
                {b.vendor_name && <div className="truncate mt-0.5" style={{fontSize:11,color:th.label,maxWidth:240}}>{b.vendor_name}</div>}
              </div>
              {/* Body */}
              <div style={{padding:'8px 14px 10px',display:'flex',flexDirection:'column',gap:5}}>
                <div className="flex items-center gap-2">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke={th.icon} strokeWidth="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                  <span style={{fontSize:11,color:th.label}}>Lane</span>
                  <span className="font-medium ml-auto" style={{fontSize:11,color:th.text}}>{laneLabel}</span>
                </div>
                {b.gate_label && <div className="flex items-center gap-2">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke={th.icon} strokeWidth="2"><path d="M3 21h18M3 7v1a3 3 0 006 0V7m0 0V4h6v3m0 0v1a3 3 0 006 0V7"/></svg>
                  <span style={{fontSize:11,color:th.label}}>Gate</span>
                  <span className="font-medium ml-auto" style={{fontSize:11,color:th.text}}>{b.gate_label}</span>
                </div>}
                {b.direction && <div className="flex items-center gap-2">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke={th.icon} strokeWidth="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                  <span style={{fontSize:11,color:th.label}}>Direction</span>
                  <span className="font-medium ml-auto capitalize" style={{fontSize:11,color:th.text}}>{b.direction}</span>
                </div>}
                {(timeStart||timeEnd) && <div className="flex items-center gap-2">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke={th.icon} strokeWidth="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                  <span style={{fontSize:11,color:th.label}}>Time</span>
                  <span className="font-medium ml-auto font-mono" style={{fontSize:11,color:th.text}}>{timeStart||'--:--'} — {timeEnd||'--:--'}</span>
                </div>}
                {b.waiting_minutes > 0 && <div className="flex items-center gap-2">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke={th.icon} strokeWidth="2"><path d="M5 22h14M5 2h14M12 6v6l2.5 2.5"/></svg>
                  <span style={{fontSize:11,color:th.label}}>Waiting</span>
                  <span className="font-medium ml-auto" style={{fontSize:11,color:tk('--waiting')}}>{b.waiting_minutes} min</span>
                </div>}
              </div>
            </div>
          </div>
        );
      })()}
    </div>
  );
}

/* ================================================================
   SLIDE 4 — SCHEDULE
   ================================================================ */
function ScheduleSlide({ data, onFilter }) {
  const { schedule_date='', processStatusCounts={}, today='', schedule_from='', schedule_to='' } = data;
  const schedule = toArr(data.schedule);
  const [schDate, setSchDate] = useState(schedule_date || today);
  const [schShift, setSchShift] = useState(() => {
    if (schedule_from==='07:00'&&schedule_to==='15:00') return 'shift1';
    if (schedule_from==='15:00'&&schedule_to==='23:00') return 'shift2';
    if (schedule_from==='23:00'&&schedule_to==='07:00') return 'shift3';
    return 'shift1';
  });
  const shiftRanges = { full:['00:00','23:59'], shift1:['07:00','15:00'], shift2:['15:00','23:00'], shift3:['23:00','07:00'] };

  // Sync local state when data changes from AJAX
  useEffect(() => { setSchDate(schedule_date || today); }, [schedule_date, today]);

  const applySchedule = () => {
    const [sf,st] = shiftRanges[schShift]||shiftRanges.shift1;
    if (onFilter) onFilter({ schedule_date:schDate, schedule_from:sf, schedule_to:st });
  };

  /* ── Client-side filtering ── */
  const [statusFilter, setStatusFilter] = useState('all');
  const [search, setSearch] = useState('');

  const scMap = getStatusColors();
  const statusAbbr = { pending:'PND', scheduled:'SCH', waiting:'WAIT', 'in progress':'IN PR', completed:'CMPLT', cancelled:'CXL' };
  const statusChartData = useMemo(() => Object.entries(processStatusCounts||{}).map(([k,v])=>({name:k.replace(/_/g,' '),key:k,value:+v,fill:(scMap[k]||scMap.scheduled).accent})), [processStatusCounts]);
  const badgeMap = { scheduled:'blue', waiting:'yellow', arrived:'yellow', active:'purple', in_progress:'purple', completed:'green', cancelled:'red', pending:'orange', pending_approval:'orange' };

  const allRows = useMemo(() => schedule.filter(r => r.id || r.is_pending_booking || r.po_number || r.ticket_number || r.request_number), [schedule]);

  // Status counts for pills
  const statusCounts = useMemo(() => {
    const c = {};
    allRows.forEach(r => {
      const s = (r.status||'scheduled')==='arrived'?'waiting':(r.status||'scheduled');
      c[s] = (c[s]||0) + 1;
    });
    return c;
  }, [allRows]);

  // Filtered rows
  const rows = useMemo(() => {
    let filtered = allRows;
    if (statusFilter !== 'all') {
      filtered = filtered.filter(r => {
        const s = (r.status||'scheduled')==='arrived'?'waiting':(r.status||'scheduled');
        return s === statusFilter;
      });
    }
    if (search.trim()) {
      const q = search.toLowerCase();
      filtered = filtered.filter(r =>
        (r.po_number||'').toLowerCase().includes(q) ||
        (r.ticket_number||'').toLowerCase().includes(q) ||
        (r.request_number||'').toLowerCase().includes(q) ||
        (r.vendor_name||'').toLowerCase().includes(q) ||
        (r.supplier_name||'').toLowerCase().includes(q) ||
        (r.warehouse_name||'').toLowerCase().includes(q) ||
        (r.gate_label||'').toLowerCase().includes(q)
      );
    }
    return filtered;
  }, [allRows, statusFilter, search]);

  const statuses = ['pending','scheduled','waiting','in_progress','completed','cancelled'];

  return (
    <div className="flex flex-col gap-2 flex-1">
      {/* Filters bar */}
      <Card>
        <CardB className="py-2 px-3">
          <div className="flex items-end gap-3 flex-wrap">
            <JQDatePicker label="Date" value={schDate} onChange={setSchDate} />
            <div className="flex flex-col gap-0.5">
              <label className="text-[11px] text-gray-500 font-medium">Shift</label>
              <SelectInput value={schShift} onChange={setSchShift}>
                <option value="full">24 Hours</option>
                <option value="shift1">Shift 1 (07-15)</option>
                <option value="shift2">Shift 2 (15-23)</option>
                <option value="shift3">Shift 3 (23-07)</option>
              </SelectInput>
            </div>
            <button onClick={applySchedule} className="px-4 py-1.5 text-[13px] font-medium text-white bg-sky-600 rounded-lg hover:bg-sky-700 transition-colors shadow-sm">Apply</button>
            <button onClick={()=>{setSchDate(today); setSchShift('shift1'); onFilter({ schedule_date:today, schedule_from:'07:00', schedule_to:'15:00' });}} className="px-4 py-1.5 text-[13px] font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors shadow-sm">Reset</button>
          </div>
        </CardB>
      </Card>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-2 flex-1">
        {/* Left: Status Chart */}
        <Card className="flex flex-col">
          <CardH><h3 className="text-[13px] font-semibold text-gray-800">Status Overview</h3></CardH>
          <CardB className="flex flex-col" style={{flex:'1 1 0%'}}>
            <div style={{flex:'1 1 0%',minHeight:140}}>
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={statusChartData} margin={{top:20,right:2,left:0,bottom:0}}>
                <CartesianGrid strokeDasharray="3 3" stroke={tk('--chart-grid')}/>
                <XAxis dataKey="name" interval={0} height={45}
                  tick={({x,y,payload})=>{
                    const name = payload.value||'';
                    const item = statusChartData.find(d=>d.name===name);
                    const clr = item?.fill || tk('--chart-axis');
                    const short = statusAbbr[name.toLowerCase()] || name;
                    return (
                      <g transform={`translate(${x},${y})`}>
                        <circle cx={0} cy={6} r={3} fill={clr}/>
                        <text x={0} y={16} textAnchor="middle" style={{fontSize:9,fontWeight:500,fill:clr}}>{short}</text>
                      </g>
                    );
                  }}/>
                <YAxis tick={{fontSize:csVar('--ds-chart-tick'),fill:tk('--chart-axis')}} width={28}/>
                <Tooltip contentStyle={tipStyle()}/>
                <Bar dataKey="value" radius={[4,4,0,0]}>
                  <LabelList dataKey="value" position="top" style={{fontSize:10,fontWeight:600,fill:tk('--text-secondary')}} formatter={v=>v>0?v:''}/>
                  {statusChartData.map((e,i)=><Cell key={i} fill={e.fill}/>)}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
            </div>
            <div className="grid grid-cols-3 gap-1.5 mt-2">
              {Object.entries(processStatusCounts||{}).map(([k,v])=>{
                const c = (scMap[k]||scMap.scheduled).accent;
                return (
                  <div key={k} className="text-center bg-gray-50 rounded-lg" style={{padding:'var(--ds-pad)',borderLeft:`3px solid ${c}`}}>
                    <div className="uppercase leading-tight break-words" style={{fontSize:'var(--ds-tiny)',color:c}} title={k.replace(/_/g,' ')}>{k.replace(/_/g,' ')}</div>
                    <div className="font-bold text-gray-800 leading-tight" style={{fontSize:'var(--ds-body)'}}>{v}</div>
                  </div>
                );
              })}
            </div>
          </CardB>
        </Card>

        {/* Right: Schedule Table with client-side filters */}
        <Card className="lg:col-span-2 flex flex-col">
          <CardH className="flex-col !items-stretch gap-2">
            <div className="flex items-center justify-between">
              <div>
                <h3 className="text-[13px] font-semibold text-gray-800">Schedule</h3>
                <p className="text-[11px] text-gray-400">{schedule_date||today} — {rows.length} of {allRows.length} entries</p>
              </div>
              <div className="w-48">
                <SearchInput value={search} onChange={setSearch} placeholder="Search PO, vendor..." />
              </div>
            </div>
            <div className="flex items-center gap-1 flex-wrap">
              <FilterPill label="All" active={statusFilter==='all'} onClick={()=>setStatusFilter('all')} count={allRows.length} />
              {statuses.map(s => {
                const cnt = statusCounts[s]||0;
                if (cnt === 0) return null;
                return <FilterPill key={s} label={s.replace(/_/g,' ')} active={statusFilter===s} onClick={()=>setStatusFilter(s)} count={cnt} />;
              })}
            </div>
          </CardH>
          <CardB className="overflow-auto p-0 flex-1">
            <table className="w-full text-[12px]">
              <thead className="sticky top-0 bg-gray-50 z-10">
                <tr>
                  <th className="text-left py-2.5 px-3 font-semibold text-gray-500 text-[11px] uppercase tracking-wider">PO / Ticket</th>
                  <th className="text-left py-2.5 px-3 font-semibold text-gray-500 text-[11px] uppercase tracking-wider">Vendor</th>
                  <th className="text-left py-2.5 px-3 font-semibold text-gray-500 text-[11px] uppercase tracking-wider hidden lg:table-cell">Warehouse</th>
                  <th className="text-left py-2.5 px-3 font-semibold text-gray-500 text-[11px] uppercase tracking-wider hidden md:table-cell">Gate</th>
                  <th className="text-left py-2.5 px-3 font-semibold text-gray-500 text-[11px] uppercase tracking-wider">ETA</th>
                  <th className="text-left py-2.5 px-3 font-semibold text-gray-500 text-[11px] uppercase tracking-wider">Status</th>
                </tr>
              </thead>
              <tbody>
                {rows.length > 0 ? rows.map((row,i) => {
                  const st = row.status||'scheduled';
                  const label = st==='arrived'?'waiting':st;
                  return (
                    <tr key={i} className="border-b border-gray-100 hover:bg-sky-50/30 transition-colors">
                      <td className="py-2 px-3 font-medium text-gray-800">{row.po_number||row.ticket_number||row.request_number||'-'}</td>
                      <td className="py-2 px-3 text-gray-600 max-w-[160px] truncate">{row.vendor_name||row.supplier_name||'-'}</td>
                      <td className="py-2 px-3 text-gray-600 hidden lg:table-cell">{row.warehouse_name||'-'}</td>
                      <td className="py-2 px-3 text-gray-600 hidden md:table-cell">{row.gate_label||'-'}</td>
                      <td className="py-2 px-3 text-gray-600 font-mono">{row.eta||'-'}</td>
                      <td className="py-2 px-3"><Badge color={badgeMap[label]||'gray'}>{label.replace(/_/g,' ')}</Badge></td>
                    </tr>
                  );
                }) : (
                  <tr><td colSpan={6} className="text-center text-gray-400 py-10">
                    {search || statusFilter!=='all' ? 'No matching entries' : 'No schedule data for this filter'}
                  </td></tr>
                )}
              </tbody>
            </table>
          </CardB>
        </Card>
      </div>
    </div>
  );
}

/* ================================================================
   MAIN DASHBOARD — SLIDE CAROUSEL
   ================================================================ */
const SLIDES = [
  { id:'analytics', label:'Analytics' },
  { id:'kpi', label:'KPI' },
  { id:'bottleneck', label:'Bottleneck' },
  { id:'timeline', label:'Timeline' },
  { id:'schedule', label:'Schedule' },
];

function Dashboard() {
  const [data, setData] = useState(() => getData());
  const [loading, setLoading] = useState(false);
  const [active, setActive] = useState(0);
  const total = SLIDES.length;
  const prev = () => setActive(a => (a - 1 + total) % total);
  const next = () => setActive(a => (a + 1) % total);

  /** Apply filter params via AJAX (no page reload) */
  const onFilter = useCallback((params) => {
    fetchDashboard(data, params, setData, setLoading);
  }, [data]);

  useEffect(() => {
    const handler = (e) => {
      if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT' || e.target.tagName === 'TEXTAREA') return;
      if (e.key === 'ArrowLeft') prev();
      if (e.key === 'ArrowRight') next();
    };
    window.addEventListener('keydown', handler);
    return () => window.removeEventListener('keydown', handler);
  }, []);

  const renderSlide = () => {
    try {
      switch(active) {
        case 0: return <AnalyticsSlide data={data}/>;
        case 1: return <KPISlide data={data}/>;
        case 2: return <BottleneckSlide data={data}/>;
        case 3: return <TimelineSlide data={data} onFilter={onFilter}/>;
        case 4: return <ScheduleSlide data={data} onFilter={onFilter}/>;
        default: return null;
      }
    } catch(e) {
      console.error('[Dashboard] Slide render error:', e);
      return <div className="p-6 text-center text-red-600"><strong>Slide Error:</strong> {e.message}</div>;
    }
  };

  const ICONS = [BarChart3, Target, Gauge, Layers, Calendar];

  return (
    <div className="flex flex-col relative pb-2 flex-1">
      {/* Loading overlay */}
      {loading && (
        <div className="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-50 flex items-center justify-center rounded-xl">
          <div className="flex items-center gap-2 bg-white px-4 py-2.5 rounded-lg shadow-lg border border-gray-200">
            <div className="w-4 h-4 border-2 border-sky-600 border-t-transparent rounded-full animate-spin"></div>
            <span className="text-[13px] text-gray-600 font-medium">Loading...</span>
          </div>
        </div>
      )}

      {/* Slide Nav Bar - responsive: wraps on small screens */}
      <div className="flex flex-wrap items-center gap-2 mb-2 bg-white rounded-xl border border-gray-200 px-2 py-1.5 shadow-sm">
        <div className="flex items-center gap-1.5 shrink-0">
          <JQRangePicker startValue={data.range_start||''} endValue={data.range_end||''} onApply={(s,e) => onFilter({ range_start:s, range_end:e })} />
          <a href="/dashboard" className="text-[11px] text-sky-600 hover:text-sky-700 font-medium flex items-center gap-1 no-underline"><RotateCcw size={11}/> Reset</a>
        </div>
        <div className="flex items-center gap-1 flex-1 justify-center">
          <button onClick={prev} className="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-all shrink-0"><ChevronLeft size={16}/></button>
          <div className="flex items-center gap-0.5 bg-gray-50 rounded-lg p-0.5 overflow-x-auto">
            {SLIDES.map((s,i)=>{
              const Icon = ICONS[i] || BarChart3;
              return (
                <button key={s.id} onClick={()=>setActive(i)} className={`flex items-center gap-1 px-2 py-1.5 rounded-lg text-[12px] font-medium transition-all shrink-0 ${i===active ? 'bg-white text-sky-700 shadow-sm border border-gray-200' : 'text-gray-500 hover:text-gray-700 hover:bg-white/60 border border-transparent'}`}>
                  <Icon size={13} />
                  <span className="hidden md:inline">{s.label}</span>
                </button>
              );
            })}
          </div>
          <button onClick={next} className="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-all shrink-0"><ChevronRight size={16}/></button>
        </div>
        <div className="text-[12px] font-medium text-gray-500 shrink-0">{active+1}/{total}</div>
      </div>

      {/* Slide Content */}
      <div className="flex-1 flex flex-col">
        {renderSlide()}
      </div>
    </div>
  );
}

/* ── Error Boundary ── */
class ErrorBoundary extends React.Component {
  constructor(props) { super(props); this.state = { error: null }; }
  static getDerivedStateFromError(error) { return { error }; }
  componentDidCatch(error, info) { console.error('[Dashboard Error]', error, info); }
  render() {
    if (this.state.error) {
      return (
        <div className="p-6 text-center">
          <h2 className="text-lg font-bold text-red-600 mb-2">Dashboard Error</h2>
          <pre className="text-xs text-left bg-red-50 p-4 rounded-lg border border-red-200 overflow-auto max-h-[300px] text-red-800">{String(this.state.error?.message || this.state.error)}{'\n'}{String(this.state.error?.stack || '')}</pre>
          <button onClick={() => window.location.reload()} className="mt-3 px-4 py-2 text-sm bg-sky-600 text-white rounded-lg hover:bg-sky-700">Reload</button>
        </div>
      );
    }
    return this.props.children;
  }
}

/* ── Mount ── */
const el = document.getElementById('react-dashboard');
if (el) {
  try {
    createRoot(el).render(<ErrorBoundary><Dashboard/></ErrorBoundary>);
  } catch (e) {
    console.error('[Dashboard] Mount error:', e);
    el.innerHTML = '<div style="padding:24px;color:#dc2626;font-size:14px;"><strong>Dashboard Error:</strong> ' + e.message + '<br><pre style="font-size:11px;margin-top:8px;background:#fef2f2;padding:12px;border-radius:8px;overflow:auto">' + e.stack + '</pre></div>';
  }
} else {
  console.warn('[Dashboard] #react-dashboard element not found');
}
