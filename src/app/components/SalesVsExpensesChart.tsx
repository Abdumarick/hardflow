'use client';
import React from 'react';
import {
  BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip,
  ResponsiveContainer, Legend,
} from 'recharts';

// Backend integration point: replace with /api/reports/sales-vs-expenses?period=7days
const data = [
  { day: 'Mon', sales: 7850000, expenses: 380000 },
  { day: 'Tue', sales: 5400000, expenses: 520000 },
  { day: 'Wed', sales: 9200000, expenses: 410000 },
  { day: 'Thu', sales: 8900000, expenses: 670000 },
  { day: 'Fri', sales: 10200000, expenses: 490000 },
  { day: 'Sat', sales: 6800000, expenses: 320000 },
  { day: 'Sun', sales: 3800000, expenses: 180000 },
];

const formatM = (v: number) => `${(v / 1_000_000).toFixed(1)}M`;

const CustomTooltip = ({ active, payload, label }: { active?: boolean; payload?: { value: number; name: string; color: string }[]; label?: string }) => {
  if (!active || !payload?.length) return null;
  return (
    <div className="bg-card border border-border rounded-xl shadow-lg px-4 py-3 text-sm">
      <p className="font-semibold text-foreground mb-2">{label}</p>
      {payload.map((p, i) => (
        <div key={`btt-${i}`} className="flex items-center gap-2 mb-1">
          <span className="w-2 h-2 rounded-full" style={{ backgroundColor: p.color }} />
          <span className="text-muted-foreground">{p.name}:</span>
          <span className="font-semibold">TZS {formatM(p.value)}</span>
        </div>
      ))}
    </div>
  );
};

export default function SalesVsExpensesChart() {
  return (
    <ResponsiveContainer width="100%" height={220}>
      <BarChart data={data} margin={{ top: 4, right: 4, bottom: 0, left: 0 }} barCategoryGap="30%">
        <CartesianGrid strokeDasharray="3 3" stroke="var(--border)" vertical={false} />
        <XAxis dataKey="day" tick={{ fontSize: 11, fill: 'var(--muted-foreground)' }} tickLine={false} axisLine={false} />
        <YAxis tickFormatter={formatM} tick={{ fontSize: 11, fill: 'var(--muted-foreground)' }} tickLine={false} axisLine={false} width={44} />
        <Tooltip content={<CustomTooltip />} />
        <Legend wrapperStyle={{ fontSize: 12, color: 'var(--muted-foreground)' }} />
        <Bar dataKey="sales" name="Sales" fill="var(--primary)" radius={[4, 4, 0, 0]} />
        <Bar dataKey="expenses" name="Expenses" fill="var(--danger)" radius={[4, 4, 0, 0]} opacity={0.8} />
      </BarChart>
    </ResponsiveContainer>
  );
}