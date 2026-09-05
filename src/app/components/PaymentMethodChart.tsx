'use client';
import React from 'react';
import { PieChart, Pie, Cell, Tooltip, ResponsiveContainer, Legend } from 'recharts';

// Backend integration point: replace with /api/reports/payment-methods?date=today
const data = [
  { name: 'Cash', value: 4200000, color: 'var(--success)' },
  { name: 'M-Pesa', value: 2800000, color: 'var(--info)' },
  { name: 'Bank Transfer', value: 1100000, color: 'var(--primary)' },
  { name: 'Credit Sale', value: 350000, color: 'var(--warning)' },
];

const CustomTooltip = ({ active, payload }: { active?: boolean; payload?: { name: string; value: number; payload: { color: string } }[] }) => {
  if (!active || !payload?.length) return null;
  const p = payload[0];
  return (
    <div className="bg-card border border-border rounded-xl shadow-lg px-4 py-3 text-sm">
      <p className="font-semibold text-foreground">{p.name}</p>
      <p className="text-muted-foreground">TZS {(p.value / 1_000_000).toFixed(2)}M</p>
    </div>
  );
};

export default function PaymentMethodChart() {
  return (
    <ResponsiveContainer width="100%" height={180}>
      <PieChart>
        <Pie data={data} cx="50%" cy="50%" innerRadius={50} outerRadius={75} paddingAngle={3} dataKey="value">
          {data.map((entry, i) => (
            <Cell key={`pmc-${i}`} fill={entry.color} />
          ))}
        </Pie>
        <Tooltip content={<CustomTooltip />} />
        <Legend wrapperStyle={{ fontSize: 11, color: 'var(--muted-foreground)' }} />
      </PieChart>
    </ResponsiveContainer>
  );
}