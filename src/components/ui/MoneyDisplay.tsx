import React from 'react';

interface MoneyDisplayProps {
  amount: number;
  currency?: string;
  className?: string;
  showSign?: boolean;
  compact?: boolean;
}

function formatTZS(amount: number, compact: boolean): string {
  if (compact) {
    if (Math.abs(amount) >= 1_000_000_000) return `${(amount / 1_000_000_000).toFixed(1)}B`;
    if (Math.abs(amount) >= 1_000_000) return `${(amount / 1_000_000).toFixed(1)}M`;
    if (Math.abs(amount) >= 1_000) return `${(amount / 1_000).toFixed(0)}K`;
    return amount.toFixed(0);
  }
  return new Intl.NumberFormat('en-TZ', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(amount);
}

export default function MoneyDisplay({ amount, currency = 'TZS', className = '', showSign = false, compact = false }: MoneyDisplayProps) {
  const formatted = formatTZS(Math.abs(amount), compact);
  const sign = showSign ? (amount >= 0 ? '+' : '-') : amount < 0 ? '-' : '';

  return (
    <span className={`tabular-nums font-semibold ${className}`}>
      <span className="text-muted-foreground font-normal">{currency} </span>
      {sign}{formatted}
    </span>
  );
}