'use client';
import React, { useState, useMemo } from 'react';
import AppLayout, { DashboardLayout } from '@/components/AppLayout';
import KpiCard from '@/components/ui/KpiCard';

import {
  BarChart2, TrendingUp, TrendingDown, Package, Receipt,
  Download, RefreshCw, Calendar, ChevronDown, FileText,
  ShoppingCart, DollarSign, Layers, AlertTriangle,
  ArrowUpRight, ArrowDownRight, Filter,
} from 'lucide-react';
import {
  AreaChart, Area, BarChart, Bar, LineChart, Line,
  XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer,
  Legend, PieChart, Pie, Cell,
} from 'recharts';

// ─── Types ────────────────────────────────────────────────────────────────────

type DateRange = '7d' | '30d' | '90d' | 'custom';
type ActiveSection = 'sales' | 'profit' | 'inventory' | 'expenses';

// ─── Mock Data ─────────────────────────────────────────────────────────────────

const salesTrendData = [
  { date: '23 Aug', revenue: 6200000, orders: 42, avgOrder: 147619 },
  { date: '24 Aug', revenue: 7850000, orders: 51, avgOrder: 153921 },
  { date: '25 Aug', revenue: 5400000, orders: 38, avgOrder: 142105 },
  { date: '26 Aug', revenue: 8900000, orders: 57, avgOrder: 156140 },
  { date: '27 Aug', revenue: 9200000, orders: 61, avgOrder: 150819 },
  { date: '28 Aug', revenue: 4100000, orders: 29, avgOrder: 141379 },
  { date: '29 Aug', revenue: 3800000, orders: 26, avgOrder: 146153 },
  { date: '30 Aug', revenue: 7600000, orders: 49, avgOrder: 155102 },
  { date: '31 Aug', revenue: 8200000, orders: 54, avgOrder: 151851 },
  { date: '01 Sep', revenue: 9500000, orders: 63, avgOrder: 150793 },
  { date: '02 Sep', revenue: 7100000, orders: 47, avgOrder: 151063 },
  { date: '03 Sep', revenue: 6800000, orders: 44, avgOrder: 154545 },
  { date: '04 Sep', revenue: 10200000, orders: 68, avgOrder: 150000 },
  { date: '05 Sep', revenue: 8450000, orders: 56, avgOrder: 150892 },
];

const salesByCategory = [
  { category: 'Cement', revenue: 18400000, pct: 28 },
  { category: 'Iron Sheets', revenue: 14200000, pct: 22 },
  { category: 'Steel', revenue: 11600000, pct: 18 },
  { category: 'Tiles', revenue: 9800000, pct: 15 },
  { category: 'Plumbing', revenue: 7200000, pct: 11 },
  { category: 'Other', revenue: 4100000, pct: 6 },
];

const profitTrendData = [
  { date: '23 Aug', revenue: 6200000, cogs: 4340000, grossProfit: 1860000, netProfit: 1180000 },
  { date: '24 Aug', revenue: 7850000, cogs: 5495000, grossProfit: 2355000, netProfit: 1490000 },
  { date: '25 Aug', revenue: 5400000, cogs: 3780000, grossProfit: 1620000, netProfit: 1020000 },
  { date: '26 Aug', revenue: 8900000, cogs: 6230000, grossProfit: 2670000, netProfit: 1690000 },
  { date: '27 Aug', revenue: 9200000, cogs: 6440000, grossProfit: 2760000, netProfit: 1750000 },
  { date: '28 Aug', revenue: 4100000, cogs: 2870000, grossProfit: 1230000, netProfit: 780000 },
  { date: '29 Aug', revenue: 3800000, cogs: 2660000, grossProfit: 1140000, netProfit: 720000 },
  { date: '30 Aug', revenue: 7600000, cogs: 5320000, grossProfit: 2280000, netProfit: 1440000 },
  { date: '31 Aug', revenue: 8200000, cogs: 5740000, grossProfit: 2460000, netProfit: 1560000 },
  { date: '01 Sep', revenue: 9500000, cogs: 6650000, grossProfit: 2850000, netProfit: 1800000 },
  { date: '02 Sep', revenue: 7100000, cogs: 4970000, grossProfit: 2130000, netProfit: 1350000 },
  { date: '03 Sep', revenue: 6800000, cogs: 4760000, grossProfit: 2040000, netProfit: 1290000 },
  { date: '04 Sep', revenue: 10200000, cogs: 7140000, grossProfit: 3060000, netProfit: 1940000 },
  { date: '05 Sep', revenue: 8450000, cogs: 5915000, grossProfit: 2535000, netProfit: 1620000 },
];

const inventoryValuationData = [
  { category: 'Cement', items: 12, qty: 1840, costValue: 44160000, retailValue: 55200000 },
  { category: 'Iron Sheets', items: 8, qty: 620, costValue: 21700000, retailValue: 27125000 },
  { category: 'Steel', items: 15, qty: 940, costValue: 29140000, retailValue: 36425000 },
  { category: 'Tiles', items: 22, qty: 3200, costValue: 16000000, retailValue: 20000000 },
  { category: 'Plumbing', items: 34, qty: 2100, costValue: 12600000, retailValue: 15750000 },
  { category: 'Electrical', items: 28, qty: 1560, costValue: 9360000, retailValue: 11700000 },
  { category: 'Paint', items: 18, qty: 840, costValue: 7560000, retailValue: 9450000 },
  { category: 'Tools', items: 41, qty: 680, costValue: 8160000, retailValue: 10200000 },
];

const inventoryTrendData = [
  { month: 'Apr', costValue: 118000000, retailValue: 147500000 },
  { month: 'May', costValue: 124000000, retailValue: 155000000 },
  { month: 'Jun', costValue: 131000000, retailValue: 163750000 },
  { month: 'Jul', costValue: 128000000, retailValue: 160000000 },
  { month: 'Aug', costValue: 135000000, retailValue: 168750000 },
  { month: 'Sep', costValue: 148680000, retailValue: 185850000 },
];

const expenseTrendData = [
  { month: 'Apr', total: 8200000, utilities: 1590000, transport: 1640000, rent: 3500000, other: 1470000 },
  { month: 'May', total: 9100000, utilities: 1760000, transport: 1820000, rent: 3500000, other: 2020000 },
  { month: 'Jun', total: 8750000, utilities: 1690000, transport: 1750000, rent: 3500000, other: 1810000 },
  { month: 'Jul', total: 9400000, utilities: 1820000, transport: 1880000, rent: 3500000, other: 2200000 },
  { month: 'Aug', total: 10200000, utilities: 1970000, transport: 2040000, rent: 3500000, other: 2690000 },
  { month: 'Sep', total: 4330000, utilities: 760000, transport: 420000, rent: 3500000, other: 650000 },
];

const expenseByCategory = [
  { name: 'Rent & Lease', value: 3500000, color: '#6366f1' },
  { name: 'Utilities', value: 1590000, color: '#0ea5e9' },
  { name: 'Transport', value: 420000, color: '#f59e0b' },
  { name: 'Equipment', value: 780000, color: '#10b981' },
  { name: 'Staff', value: 950000, color: '#ec4899' },
  { name: 'Other', value: 1090000, color: '#94a3b8' },
];

// ─── Helpers ──────────────────────────────────────────────────────────────────

const formatM = (v: number) => `${(v / 1_000_000).toFixed(1)}M`;
const formatK = (v: number) => v >= 1_000_000 ? `${(v / 1_000_000).toFixed(1)}M` : `${(v / 1_000).toFixed(0)}K`;

const CustomTooltip = ({ active, payload, label }: { active?: boolean; payload?: { value: number; name: string; color: string }[]; label?: string }) => {
  if (!active || !payload?.length) return null;
  return (
    <div className="bg-card border border-border rounded-xl shadow-lg px-4 py-3 text-sm min-w-[160px]">
      <p className="font-semibold text-foreground mb-2">{label}</p>
      {payload.map((p, i) => (
        <div key={i} className="flex items-center gap-2 mb-1">
          <span className="w-2 h-2 rounded-full shrink-0" style={{ backgroundColor: p.color }} />
          <span className="text-muted-foreground capitalize text-xs">{p.name}:</span>
          <span className="font-semibold text-foreground text-xs ml-auto">TZS {formatM(p.value)}</span>
        </div>
      ))}
    </div>
  );
};

// ─── Section Header ───────────────────────────────────────────────────────────

interface SectionHeaderProps {
  id: ActiveSection;
  title: string;
  subtitle: string;
  icon: React.ReactNode;
  iconBg: string;
  active: ActiveSection;
  onToggle: (id: ActiveSection) => void;
}

function SectionHeader({ id, title, subtitle, icon, iconBg, active, onToggle }: SectionHeaderProps) {
  const isOpen = active === id;
  return (
    <button
      onClick={() => onToggle(id)}
      className={`w-full flex items-center gap-3 px-5 py-4 transition-colors ${isOpen ? 'bg-primary/5 border-b border-primary/20' : 'hover:bg-muted/50'}`}
    >
      <div className={`w-9 h-9 rounded-lg ${iconBg} flex items-center justify-center shrink-0`}>
        {icon}
      </div>
      <div className="flex-1 text-left">
        <p className="font-semibold text-foreground text-sm">{title}</p>
        <p className="text-xs text-muted-foreground">{subtitle}</p>
      </div>
      <ChevronDown size={16} className={`text-muted-foreground transition-transform ${isOpen ? 'rotate-180' : ''}`} />
    </button>
  );
}

// ─── Export Button ────────────────────────────────────────────────────────────

function ExportMenu({ label }: { label: string }) {
  const [open, setOpen] = useState(false);
  return (
    <div className="relative">
      <button
        onClick={() => setOpen(!open)}
        className="btn-outline flex items-center gap-1.5 text-xs px-3 py-1.5"
      >
        <Download size={13} />
        Export
        <ChevronDown size={11} className={`transition-transform ${open ? 'rotate-180' : ''}`} />
      </button>
      {open && (
        <>
          <div className="fixed inset-0 z-10" onClick={() => setOpen(false)} />
          <div className="absolute right-0 top-full mt-1 z-20 bg-card border border-border rounded-xl shadow-xl py-1 min-w-[140px]">
            {['PDF', 'Excel', 'CSV'].map(fmt => (
              <button
                key={fmt}
                onClick={() => setOpen(false)}
                className="w-full flex items-center gap-2 px-3 py-2 text-xs text-foreground hover:bg-muted transition-colors"
              >
                <FileText size={12} className="text-muted-foreground" />
                Export as {fmt}
              </button>
            ))}
          </div>
        </>
      )}
    </div>
  );
}

// ─── Date Range Picker ────────────────────────────────────────────────────────

interface DateRangePickerProps {
  value: DateRange;
  onChange: (v: DateRange) => void;
  customFrom: string;
  customTo: string;
  onCustomFrom: (v: string) => void;
  onCustomTo: (v: string) => void;
}

function DateRangePicker({ value, onChange, customFrom, customTo, onCustomFrom, onCustomTo }: DateRangePickerProps) {
  const options: { label: string; value: DateRange }[] = [
    { label: 'Last 7 days', value: '7d' },
    { label: 'Last 30 days', value: '30d' },
    { label: 'Last 90 days', value: '90d' },
    { label: 'Custom', value: 'custom' },
  ];
  return (
    <div className="flex items-center gap-2 flex-wrap">
      <div className="flex items-center gap-1 bg-muted rounded-lg p-1">
        {options.map(opt => (
          <button
            key={opt.value}
            onClick={() => onChange(opt.value)}
            className={`px-3 py-1 rounded-md text-xs font-medium transition-colors ${
              value === opt.value ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'
            }`}
          >
            {opt.label}
          </button>
        ))}
      </div>
      {value === 'custom' && (
        <div className="flex items-center gap-2">
          <div className="flex items-center gap-1.5 bg-card border border-border rounded-lg px-2 py-1">
            <Calendar size={12} className="text-muted-foreground" />
            <input type="date" value={customFrom} onChange={e => onCustomFrom(e.target.value)}
              className="text-xs bg-transparent text-foreground outline-none" />
          </div>
          <span className="text-xs text-muted-foreground">to</span>
          <div className="flex items-center gap-1.5 bg-card border border-border rounded-lg px-2 py-1">
            <Calendar size={12} className="text-muted-foreground" />
            <input type="date" value={customTo} onChange={e => onCustomTo(e.target.value)}
              className="text-xs bg-transparent text-foreground outline-none" />
          </div>
        </div>
      )}
    </div>
  );
}

// ─── Main Page ────────────────────────────────────────────────────────────────

export default function ReportsPage() {
  const [layout] = useState<DashboardLayout>('classic');
  const [activeSection, setActiveSection] = useState<ActiveSection>('sales');
  const [dateRange, setDateRange] = useState<DateRange>('30d');
  const [customFrom, setCustomFrom] = useState('2026-08-01');
  const [customTo, setCustomTo] = useState('2026-09-05');

  const toggleSection = (id: ActiveSection) => {
    setActiveSection(prev => (prev === id ? prev : id));
  };

  // ── Derived KPIs ──────────────────────────────────────────────────────────

  const salesKpis = useMemo(() => {
    const totalRevenue = salesTrendData.reduce((s, d) => s + d.revenue, 0);
    const totalOrders = salesTrendData.reduce((s, d) => s + d.orders, 0);
    const avgOrder = totalRevenue / totalOrders;
    const topCategory = salesByCategory[0];
    return { totalRevenue, totalOrders, avgOrder, topCategoryRevenue: topCategory.revenue };
  }, []);

  const profitKpis = useMemo(() => {
    const totalRevenue = profitTrendData.reduce((s, d) => s + d.revenue, 0);
    const totalCogs = profitTrendData.reduce((s, d) => s + d.cogs, 0);
    const totalGross = profitTrendData.reduce((s, d) => s + d.grossProfit, 0);
    const totalNet = profitTrendData.reduce((s, d) => s + d.netProfit, 0);
    const grossMargin = (totalGross / totalRevenue) * 100;
    const netMargin = (totalNet / totalRevenue) * 100;
    return { totalRevenue, totalCogs, totalGross, totalNet, grossMargin, netMargin };
  }, []);

  const inventoryKpis = useMemo(() => {
    const totalCost = inventoryValuationData.reduce((s, d) => s + d.costValue, 0);
    const totalRetail = inventoryValuationData.reduce((s, d) => s + d.retailValue, 0);
    const totalItems = inventoryValuationData.reduce((s, d) => s + d.items, 0);
    const totalQty = inventoryValuationData.reduce((s, d) => s + d.qty, 0);
    const potentialProfit = totalRetail - totalCost;
    return { totalCost, totalRetail, totalItems, totalQty, potentialProfit };
  }, []);

  const expenseKpis = useMemo(() => {
    const totalExpenses = expenseTrendData.reduce((s, d) => s + d.total, 0);
    const avgMonthly = totalExpenses / expenseTrendData.length;
    const topCategory = expenseByCategory[0];
    const lastMonth = expenseTrendData[expenseTrendData.length - 2].total;
    const thisMonth = expenseTrendData[expenseTrendData.length - 1].total;
    const momChange = ((thisMonth - lastMonth) / lastMonth) * 100;
    return { totalExpenses, avgMonthly, topCategoryAmount: topCategory.value, momChange };
  }, []);

  return (
    <AppLayout layout={layout}>
      {/* ── Page Header ── */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
            <BarChart2 size={20} className="text-primary" />
          </div>
          <div>
            <h1 className="text-xl font-bold text-foreground">Reports</h1>
            <p className="text-xs text-muted-foreground">Sales, Profit, Inventory & Expense analytics</p>
          </div>
        </div>
        <div className="flex items-center gap-2">
          <button className="btn-outline flex items-center gap-1.5 text-xs px-3 py-1.5">
            <RefreshCw size={13} />
            Refresh
          </button>
          <ExportMenu label="All Reports" />
        </div>
      </div>

      {/* ── Global Date Filter ── */}
      <div className="card px-4 py-3 mb-6 flex flex-col sm:flex-row sm:items-center gap-3">
        <div className="flex items-center gap-2 text-xs font-medium text-muted-foreground shrink-0">
          <Filter size={13} />
          Date Range:
        </div>
        <DateRangePicker
          value={dateRange}
          onChange={setDateRange}
          customFrom={customFrom}
          customTo={customTo}
          onCustomFrom={setCustomFrom}
          onCustomTo={setCustomTo}
        />
      </div>

      {/* ── Report Sections ── */}
      <div className="space-y-4">

        {/* ════════════════════════════════════════════════════════════════════
            1. SALES SUMMARY
        ════════════════════════════════════════════════════════════════════ */}
        <div className="card overflow-hidden">
          <SectionHeader
            id="sales" active={activeSection} onToggle={toggleSection}
            title="Sales Summary"
            subtitle="Revenue, order volume, and category breakdown"
            icon={<ShoppingCart size={16} className="text-primary" />}
            iconBg="bg-primary/10"
          />
          {activeSection === 'sales' && (
            <div className="p-5 space-y-5">
              {/* KPIs */}
              <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <KpiCard title="Total Revenue" value={salesKpis.totalRevenue} isMoney compact
                  icon={<TrendingUp size={16} className="text-primary" />} iconBg="bg-primary/10"
                  change={12.4} changeLabel="vs prev period" />
                <KpiCard title="Total Orders" value={salesKpis.totalOrders} isMoney={false}
                  icon={<ShoppingCart size={16} className="text-info" />} iconBg="bg-info/10"
                  change={8.1} changeLabel="vs prev period" />
                <KpiCard title="Avg Order Value" value={salesKpis.avgOrder} isMoney compact
                  icon={<DollarSign size={16} className="text-success" />} iconBg="bg-success/10"
                  change={3.9} changeLabel="vs prev period" />
                <KpiCard title="Top Category" value={salesKpis.topCategoryRevenue} isMoney compact
                  subtitle="Cement"
                  icon={<Layers size={16} className="text-warning" />} iconBg="bg-warning/10"
                  change={5.2} changeLabel="vs prev period" />
              </div>

              {/* Revenue Trend Chart */}
              <div>
                <div className="flex items-center justify-between mb-3">
                  <p className="text-sm font-semibold text-foreground">Revenue Trend</p>
                  <ExportMenu label="Sales" />
                </div>
                <ResponsiveContainer width="100%" height={240}>
                  <AreaChart data={salesTrendData} margin={{ top: 4, right: 4, bottom: 0, left: 0 }}>
                    <defs>
                      <linearGradient id="gradRevenue" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="5%" stopColor="var(--primary)" stopOpacity={0.2} />
                        <stop offset="95%" stopColor="var(--primary)" stopOpacity={0} />
                      </linearGradient>
                    </defs>
                    <CartesianGrid strokeDasharray="3 3" stroke="var(--border)" />
                    <XAxis dataKey="date" tick={{ fontSize: 11, fill: 'var(--muted-foreground)' }} tickLine={false} axisLine={false} />
                    <YAxis tickFormatter={formatM} tick={{ fontSize: 11, fill: 'var(--muted-foreground)' }} tickLine={false} axisLine={false} width={44} />
                    <Tooltip content={<CustomTooltip />} />
                    <Area type="monotone" dataKey="revenue" name="Revenue" stroke="var(--primary)" strokeWidth={2} fill="url(#gradRevenue)" />
                  </AreaChart>
                </ResponsiveContainer>
              </div>

              {/* Category Breakdown Table */}
              <div>
                <p className="text-sm font-semibold text-foreground mb-3">Revenue by Category</p>
                <div className="overflow-x-auto">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="border-b border-border">
                        <th className="text-left py-2 px-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Category</th>
                        <th className="text-right py-2 px-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Revenue</th>
                        <th className="text-right py-2 px-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Share</th>
                        <th className="py-2 px-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Trend</th>
                      </tr>
                    </thead>
                    <tbody>
                      {salesByCategory.map((row, i) => (
                        <tr key={i} className="border-b border-border/50 hover:bg-muted/30 transition-colors">
                          <td className="py-2.5 px-3 font-medium text-foreground">{row.category}</td>
                          <td className="py-2.5 px-3 text-right font-semibold text-foreground">
                            TZS {(row.revenue / 1_000_000).toFixed(1)}M
                          </td>
                          <td className="py-2.5 px-3 text-right">
                            <div className="flex items-center justify-end gap-2">
                              <div className="w-20 h-1.5 bg-muted rounded-full overflow-hidden">
                                <div className="h-full bg-primary rounded-full" style={{ width: `${row.pct}%` }} />
                              </div>
                              <span className="text-xs text-muted-foreground w-8 text-right">{row.pct}%</span>
                            </div>
                          </td>
                          <td className="py-2.5 px-3">
                            <span className="flex items-center gap-1 text-xs text-success">
                              <ArrowUpRight size={12} /> +{(Math.random() * 10 + 2).toFixed(1)}%
                            </span>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          )}
        </div>

        {/* ════════════════════════════════════════════════════════════════════
            2. PROFIT ANALYSIS
        ════════════════════════════════════════════════════════════════════ */}
        <div className="card overflow-hidden">
          <SectionHeader
            id="profit" active={activeSection} onToggle={toggleSection}
            title="Profit Analysis"
            subtitle="Gross profit, net profit, COGS, and margin trends"
            icon={<TrendingUp size={16} className="text-success" />}
            iconBg="bg-success/10"
          />
          {activeSection === 'profit' && (
            <div className="p-5 space-y-5">
              {/* KPIs */}
              <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <KpiCard title="Gross Profit" value={profitKpis.totalGross} isMoney compact
                  icon={<TrendingUp size={16} className="text-success" />} iconBg="bg-success/10"
                  change={9.3} changeLabel="vs prev period" variant="success" />
                <KpiCard title="Net Profit" value={profitKpis.totalNet} isMoney compact
                  icon={<DollarSign size={16} className="text-primary" />} iconBg="bg-primary/10"
                  change={7.8} changeLabel="vs prev period" />
                <KpiCard title="Gross Margin" value={`${profitKpis.grossMargin.toFixed(1)}%`}
                  icon={<BarChart2 size={16} className="text-info" />} iconBg="bg-info/10"
                  change={1.2} changeLabel="vs prev period" />
                <KpiCard title="Net Margin" value={`${profitKpis.netMargin.toFixed(1)}%`}
                  icon={<Layers size={16} className="text-warning" />} iconBg="bg-warning/10"
                  change={0.8} changeLabel="vs prev period" />
              </div>

              {/* Profit Trend Chart */}
              <div>
                <div className="flex items-center justify-between mb-3">
                  <p className="text-sm font-semibold text-foreground">Revenue vs COGS vs Profit</p>
                  <ExportMenu label="Profit" />
                </div>
                <ResponsiveContainer width="100%" height={260}>
                  <BarChart data={profitTrendData} margin={{ top: 4, right: 4, bottom: 0, left: 0 }} barSize={10}>
                    <CartesianGrid strokeDasharray="3 3" stroke="var(--border)" />
                    <XAxis dataKey="date" tick={{ fontSize: 11, fill: 'var(--muted-foreground)' }} tickLine={false} axisLine={false} />
                    <YAxis tickFormatter={formatM} tick={{ fontSize: 11, fill: 'var(--muted-foreground)' }} tickLine={false} axisLine={false} width={44} />
                    <Tooltip content={<CustomTooltip />} />
                    <Legend wrapperStyle={{ fontSize: 12, color: 'var(--muted-foreground)' }} />
                    <Bar dataKey="revenue" name="Revenue" fill="var(--primary)" opacity={0.7} radius={[3, 3, 0, 0]} />
                    <Bar dataKey="cogs" name="COGS" fill="var(--danger)" opacity={0.7} radius={[3, 3, 0, 0]} />
                    <Bar dataKey="grossProfit" name="Gross Profit" fill="var(--success)" opacity={0.9} radius={[3, 3, 0, 0]} />
                  </BarChart>
                </ResponsiveContainer>
              </div>

              {/* Net Profit Line */}
              <div>
                <p className="text-sm font-semibold text-foreground mb-3">Net Profit Trend</p>
                <ResponsiveContainer width="100%" height={180}>
                  <LineChart data={profitTrendData} margin={{ top: 4, right: 4, bottom: 0, left: 0 }}>
                    <CartesianGrid strokeDasharray="3 3" stroke="var(--border)" />
                    <XAxis dataKey="date" tick={{ fontSize: 11, fill: 'var(--muted-foreground)' }} tickLine={false} axisLine={false} />
                    <YAxis tickFormatter={formatM} tick={{ fontSize: 11, fill: 'var(--muted-foreground)' }} tickLine={false} axisLine={false} width={44} />
                    <Tooltip content={<CustomTooltip />} />
                    <Line type="monotone" dataKey="netProfit" name="Net Profit" stroke="var(--success)" strokeWidth={2.5} dot={{ r: 3, fill: 'var(--success)' }} />
                  </LineChart>
                </ResponsiveContainer>
              </div>
            </div>
          )}
        </div>

        {/* ════════════════════════════════════════════════════════════════════
            3. INVENTORY VALUATION
        ════════════════════════════════════════════════════════════════════ */}
        <div className="card overflow-hidden">
          <SectionHeader
            id="inventory" active={activeSection} onToggle={toggleSection}
            title="Inventory Valuation"
            subtitle="Stock cost value, retail value, and category breakdown"
            icon={<Package size={16} className="text-warning" />}
            iconBg="bg-warning/10"
          />
          {activeSection === 'inventory' && (
            <div className="p-5 space-y-5">
              {/* KPIs */}
              <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <KpiCard title="Total Cost Value" value={inventoryKpis.totalCost} isMoney compact
                  icon={<Package size={16} className="text-warning" />} iconBg="bg-warning/10"
                  change={9.8} changeLabel="vs last month" variant="warning" />
                <KpiCard title="Total Retail Value" value={inventoryKpis.totalRetail} isMoney compact
                  icon={<TrendingUp size={16} className="text-primary" />} iconBg="bg-primary/10"
                  change={9.8} changeLabel="vs last month" />
                <KpiCard title="Potential Profit" value={inventoryKpis.potentialProfit} isMoney compact
                  icon={<DollarSign size={16} className="text-success" />} iconBg="bg-success/10"
                  change={9.8} changeLabel="vs last month" variant="success" />
                <KpiCard title="Total SKUs" value={inventoryKpis.totalItems}
                  icon={<Layers size={16} className="text-info" />} iconBg="bg-info/10"
                  change={2.1} changeLabel="vs last month" />
              </div>

              {/* Valuation Trend */}
              <div>
                <div className="flex items-center justify-between mb-3">
                  <p className="text-sm font-semibold text-foreground">Inventory Value Trend (6 months)</p>
                  <ExportMenu label="Inventory" />
                </div>
                <ResponsiveContainer width="100%" height={220}>
                  <AreaChart data={inventoryTrendData} margin={{ top: 4, right: 4, bottom: 0, left: 0 }}>
                    <defs>
                      <linearGradient id="gradCost" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="5%" stopColor="var(--warning)" stopOpacity={0.2} />
                        <stop offset="95%" stopColor="var(--warning)" stopOpacity={0} />
                      </linearGradient>
                      <linearGradient id="gradRetail" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="5%" stopColor="var(--primary)" stopOpacity={0.2} />
                        <stop offset="95%" stopColor="var(--primary)" stopOpacity={0} />
                      </linearGradient>
                    </defs>
                    <CartesianGrid strokeDasharray="3 3" stroke="var(--border)" />
                    <XAxis dataKey="month" tick={{ fontSize: 11, fill: 'var(--muted-foreground)' }} tickLine={false} axisLine={false} />
                    <YAxis tickFormatter={formatM} tick={{ fontSize: 11, fill: 'var(--muted-foreground)' }} tickLine={false} axisLine={false} width={44} />
                    <Tooltip content={<CustomTooltip />} />
                    <Legend wrapperStyle={{ fontSize: 12, color: 'var(--muted-foreground)' }} />
                    <Area type="monotone" dataKey="retailValue" name="Retail Value" stroke="var(--primary)" strokeWidth={2} fill="url(#gradRetail)" />
                    <Area type="monotone" dataKey="costValue" name="Cost Value" stroke="var(--warning)" strokeWidth={2} fill="url(#gradCost)" />
                  </AreaChart>
                </ResponsiveContainer>
              </div>

              {/* Category Valuation Table */}
              <div>
                <p className="text-sm font-semibold text-foreground mb-3">Valuation by Category</p>
                <div className="overflow-x-auto">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="border-b border-border">
                        <th className="text-left py-2 px-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Category</th>
                        <th className="text-right py-2 px-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">SKUs</th>
                        <th className="text-right py-2 px-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Qty</th>
                        <th className="text-right py-2 px-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Cost Value</th>
                        <th className="text-right py-2 px-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Retail Value</th>
                        <th className="text-right py-2 px-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Margin</th>
                      </tr>
                    </thead>
                    <tbody>
                      {inventoryValuationData.map((row, i) => {
                        const margin = ((row.retailValue - row.costValue) / row.retailValue) * 100;
                        return (
                          <tr key={i} className="border-b border-border/50 hover:bg-muted/30 transition-colors">
                            <td className="py-2.5 px-3 font-medium text-foreground">{row.category}</td>
                            <td className="py-2.5 px-3 text-right text-muted-foreground">{row.items}</td>
                            <td className="py-2.5 px-3 text-right text-muted-foreground">{row.qty.toLocaleString()}</td>
                            <td className="py-2.5 px-3 text-right font-semibold text-foreground">
                              TZS {(row.costValue / 1_000_000).toFixed(1)}M
                            </td>
                            <td className="py-2.5 px-3 text-right font-semibold text-foreground">
                              TZS {(row.retailValue / 1_000_000).toFixed(1)}M
                            </td>
                            <td className="py-2.5 px-3 text-right">
                              <span className="text-xs font-semibold text-success">{margin.toFixed(1)}%</span>
                            </td>
                          </tr>
                        );
                      })}
                    </tbody>
                    <tfoot>
                      <tr className="bg-muted/30">
                        <td className="py-2.5 px-3 font-bold text-foreground">Total</td>
                        <td className="py-2.5 px-3 text-right font-bold text-foreground">{inventoryKpis.totalItems}</td>
                        <td className="py-2.5 px-3 text-right font-bold text-foreground">{inventoryKpis.totalQty.toLocaleString()}</td>
                        <td className="py-2.5 px-3 text-right font-bold text-foreground">
                          TZS {(inventoryKpis.totalCost / 1_000_000).toFixed(1)}M
                        </td>
                        <td className="py-2.5 px-3 text-right font-bold text-foreground">
                          TZS {(inventoryKpis.totalRetail / 1_000_000).toFixed(1)}M
                        </td>
                        <td className="py-2.5 px-3 text-right">
                          <span className="text-xs font-bold text-success">
                            {(((inventoryKpis.totalRetail - inventoryKpis.totalCost) / inventoryKpis.totalRetail) * 100).toFixed(1)}%
                          </span>
                        </td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>
          )}
        </div>

        {/* ════════════════════════════════════════════════════════════════════
            4. EXPENSE BREAKDOWN
        ════════════════════════════════════════════════════════════════════ */}
        <div className="card overflow-hidden">
          <SectionHeader
            id="expenses" active={activeSection} onToggle={toggleSection}
            title="Expense Breakdown"
            subtitle="Spending by category, monthly trends, and budget tracking"
            icon={<Receipt size={16} className="text-danger" />}
            iconBg="bg-danger/10"
          />
          {activeSection === 'expenses' && (
            <div className="p-5 space-y-5">
              {/* KPIs */}
              <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <KpiCard title="Total Expenses" value={expenseKpis.totalExpenses} isMoney compact
                  icon={<Receipt size={16} className="text-danger" />} iconBg="bg-danger/10"
                  change={-8.2} changeLabel="vs prev period" variant="danger" />
                <KpiCard title="Avg Monthly" value={expenseKpis.avgMonthly} isMoney compact
                  icon={<BarChart2 size={16} className="text-warning" />} iconBg="bg-warning/10"
                  change={5.1} changeLabel="vs prev period" />
                <KpiCard title="Largest Category" value={expenseKpis.topCategoryAmount} isMoney compact
                  subtitle="Rent & Lease"
                  icon={<AlertTriangle size={16} className="text-info" />} iconBg="bg-info/10" />
                <KpiCard title="MoM Change" value={`${expenseKpis.momChange.toFixed(1)}%`}
                  icon={expenseKpis.momChange < 0 ? <TrendingDown size={16} className="text-success" /> : <TrendingUp size={16} className="text-danger" />}
                  iconBg={expenseKpis.momChange < 0 ? 'bg-success/10' : 'bg-danger/10'}
                  variant={expenseKpis.momChange < 0 ? 'success' : 'danger'} />
              </div>

              {/* Expense Trend + Pie side by side */}
              <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div className="lg:col-span-2">
                  <div className="flex items-center justify-between mb-3">
                    <p className="text-sm font-semibold text-foreground">Monthly Expense Trend</p>
                    <ExportMenu label="Expenses" />
                  </div>
                  <ResponsiveContainer width="100%" height={240}>
                    <BarChart data={expenseTrendData} margin={{ top: 4, right: 4, bottom: 0, left: 0 }} barSize={22}>
                      <CartesianGrid strokeDasharray="3 3" stroke="var(--border)" />
                      <XAxis dataKey="month" tick={{ fontSize: 11, fill: 'var(--muted-foreground)' }} tickLine={false} axisLine={false} />
                      <YAxis tickFormatter={formatM} tick={{ fontSize: 11, fill: 'var(--muted-foreground)' }} tickLine={false} axisLine={false} width={44} />
                      <Tooltip content={<CustomTooltip />} />
                      <Legend wrapperStyle={{ fontSize: 12, color: 'var(--muted-foreground)' }} />
                      <Bar dataKey="rent" name="Rent" stackId="a" fill="#6366f1" radius={[0, 0, 0, 0]} />
                      <Bar dataKey="utilities" name="Utilities" stackId="a" fill="#0ea5e9" />
                      <Bar dataKey="transport" name="Transport" stackId="a" fill="#f59e0b" />
                      <Bar dataKey="other" name="Other" stackId="a" fill="#94a3b8" radius={[3, 3, 0, 0]} />
                    </BarChart>
                  </ResponsiveContainer>
                </div>

                <div>
                  <p className="text-sm font-semibold text-foreground mb-3">By Category (Sep)</p>
                  <ResponsiveContainer width="100%" height={200}>
                    <PieChart>
                      <Pie data={expenseByCategory} cx="50%" cy="50%" innerRadius={55} outerRadius={85}
                        dataKey="value" nameKey="name" paddingAngle={2}>
                        {expenseByCategory.map((entry, index) => (
                          <Cell key={`cell-${index}`} fill={entry.color} />
                        ))}
                      </Pie>
                      <Tooltip formatter={(value: number) => [`TZS ${(value / 1_000_000).toFixed(2)}M`, '']} />
                    </PieChart>
                  </ResponsiveContainer>
                  <div className="space-y-1.5 mt-2">
                    {expenseByCategory.map((item, i) => (
                      <div key={i} className="flex items-center gap-2">
                        <span className="w-2.5 h-2.5 rounded-sm shrink-0" style={{ backgroundColor: item.color }} />
                        <span className="text-xs text-muted-foreground flex-1 truncate">{item.name}</span>
                        <span className="text-xs font-semibold text-foreground">
                          {((item.value / expenseByCategory.reduce((s, e) => s + e.value, 0)) * 100).toFixed(0)}%
                        </span>
                      </div>
                    ))}
                  </div>
                </div>
              </div>

              {/* Expense Detail Table */}
              <div>
                <p className="text-sm font-semibold text-foreground mb-3">Category Detail</p>
                <div className="overflow-x-auto">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="border-b border-border">
                        <th className="text-left py-2 px-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Category</th>
                        <th className="text-right py-2 px-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Amount</th>
                        <th className="text-right py-2 px-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">% of Total</th>
                        <th className="py-2 px-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Distribution</th>
                        <th className="text-right py-2 px-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">MoM</th>
                      </tr>
                    </thead>
                    <tbody>
                      {expenseByCategory.map((row, i) => {
                        const total = expenseByCategory.reduce((s, e) => s + e.value, 0);
                        const pct = (row.value / total) * 100;
                        const mom = (Math.random() * 20 - 10);
                        return (
                          <tr key={i} className="border-b border-border/50 hover:bg-muted/30 transition-colors">
                            <td className="py-2.5 px-3">
                              <div className="flex items-center gap-2">
                                <span className="w-2.5 h-2.5 rounded-sm shrink-0" style={{ backgroundColor: row.color }} />
                                <span className="font-medium text-foreground">{row.name}</span>
                              </div>
                            </td>
                            <td className="py-2.5 px-3 text-right font-semibold text-foreground">
                              TZS {(row.value / 1_000_000).toFixed(2)}M
                            </td>
                            <td className="py-2.5 px-3 text-right text-muted-foreground text-xs">{pct.toFixed(1)}%</td>
                            <td className="py-2.5 px-3">
                              <div className="w-full h-1.5 bg-muted rounded-full overflow-hidden">
                                <div className="h-full rounded-full" style={{ width: `${pct}%`, backgroundColor: row.color }} />
                              </div>
                            </td>
                            <td className="py-2.5 px-3 text-right">
                              <span className={`flex items-center justify-end gap-1 text-xs font-semibold ${mom < 0 ? 'text-success' : 'text-danger'}`}>
                                {mom < 0 ? <ArrowDownRight size={11} /> : <ArrowUpRight size={11} />}
                                {Math.abs(mom).toFixed(1)}%
                              </span>
                            </td>
                          </tr>
                        );
                      })}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          )}
        </div>

      </div>
    </AppLayout>
  );
}
