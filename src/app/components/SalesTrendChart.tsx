'use client';
import React from 'react';
import {
  AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip,
  ResponsiveContainer, Legend,
} from 'recharts';

// Backend integration point: replace with API call to /api/reports/sales-trend?days=14
const salesTrendData = [
  { date: '23 Aug', sales: 6200000, profit: 1180000 },
  { date: '24 Aug', sales: 7850000, profit: 1490000 },
  { date: '25 Aug', sales: 5400000, profit: 1020000 },
  { date: '26 Aug', sales: 8900000, profit: 1690000 },
  { date: '27 Aug', sales: 9200000, profit: 1750000 },
  { date: '28 Aug', sales: 4100000, profit: 780000 },
  { date: '29 Aug', sales: 3800000, profit: 720000 },
  { date: '30 Aug', sales: 7600000, profit: 1440000 },
  { date: '31 Aug', sales: 8200000, profit: 1560000 },
  { date: '01 Sep', sales: 9500000, profit: 1800000 },
  { date: '02 Sep', sales: 7100000, profit: 1350000 },
  { date: '03 Sep', sales: 6800000, profit: 1290000 },
  { date: '04 Sep', sales: 10200000, profit: 1940000 },
  { date: '05 Sep', sales: 8450000, profit: 1620000 },
];

const formatM = (v: number) => `${(v / 1_000_000).toFixed(1)}M`;

const CustomTooltip = ({ active, payload, label }: { active?: boolean; payload?: { value: number; name: string; color: string }[]; label?: string }) => {
  if (!active || !payload?.length) return null;
  return (
    <div className="bg-card border border-border rounded-xl shadow-lg px-4 py-3 text-sm">
      <p className="font-semibold text-foreground mb-2">{label}</p>
      {payload.map((p, i) => (
        <div key={`tt-${i}`} className="flex items-center gap-2 mb-1">
          <span className="w-2 h-2 rounded-full" style={{ backgroundColor: p.color }} />
          <span className="text-muted-foreground capitalize">{p.name}:</span>
          <span className="font-semibold text-foreground">TZS {formatM(p.value)}</span>
        </div>
      ))}
    </div>
  );
};

export default function SalesTrendChart() {
  return (
    <ResponsiveContainer width="100%" height={240}>
      <AreaChart data={salesTrendData} margin={{ top: 4, right: 4, bottom: 0, left: 0 }}>
        <defs>
          <linearGradient id="gradSales" x1="0" y1="0" x2="0" y2="1">
            <stop offset="5%" stopColor="var(--primary)" stopOpacity={0.2} />
            <stop offset="95%" stopColor="var(--primary)" stopOpacity={0} />
          </linearGradient>
          <linearGradient id="gradProfit" x1="0" y1="0" x2="0" y2="1">
            <stop offset="5%" stopColor="var(--success)" stopOpacity={0.2} />
            <stop offset="95%" stopColor="var(--success)" stopOpacity={0} />
          </linearGradient>
        </defs>
        <CartesianGrid strokeDasharray="3 3" stroke="var(--border)" />
        <XAxis dataKey="date" tick={{ fontSize: 11, fill: 'var(--muted-foreground)' }} tickLine={false} axisLine={false} />
        <YAxis tickFormatter={formatM} tick={{ fontSize: 11, fill: 'var(--muted-foreground)' }} tickLine={false} axisLine={false} width={44} />
        <Tooltip content={<CustomTooltip />} />
        <Legend wrapperStyle={{ fontSize: 12, color: 'var(--muted-foreground)' }} />
        <Area type="monotone" dataKey="sales" name="Sales" stroke="var(--primary)" strokeWidth={2} fill="url(#gradSales)" />
        <Area type="monotone" dataKey="profit" name="Profit" stroke="var(--success)" strokeWidth={2} fill="url(#gradProfit)" />
      </AreaChart>
    </ResponsiveContainer>
  );
}