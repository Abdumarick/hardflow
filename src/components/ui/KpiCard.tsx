import React from 'react';
import { TrendingUp, TrendingDown, Minus } from 'lucide-react';
import MoneyDisplay from './MoneyDisplay';

interface KpiCardProps {
  title: string;
  value: number | string;
  isMoney?: boolean;
  currency?: string;
  compact?: boolean;
  change?: number;
  changeLabel?: string;
  icon: React.ReactNode;
  iconBg?: string;
  variant?: 'default' | 'warning' | 'danger' | 'success';
  subtitle?: string;
  className?: string;
}

export default function KpiCard({
  title, value, isMoney = false, currency = 'TZS', compact = true,
  change, changeLabel, icon, iconBg = 'bg-primary/10', variant = 'default',
  subtitle, className = '',
}: KpiCardProps) {
  const variantClass = {
    default: 'card',
    warning: 'card border-warning/30 bg-warning/5',
    danger: 'card border-danger/30 bg-danger/5',
    success: 'card border-success/30 bg-success/5',
  }[variant];

  const trendPositive = change !== undefined && change > 0;
  const trendNegative = change !== undefined && change < 0;

  return (
    <div className={`${variantClass} p-4 flex flex-col gap-3 ${className}`}>
      <div className="flex items-start justify-between gap-2">
        <div>
          <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">{title}</p>
          {subtitle && <p className="text-2xs text-muted-foreground mt-0.5">{subtitle}</p>}
        </div>
        <div className={`w-9 h-9 rounded-lg ${iconBg} flex items-center justify-center shrink-0`}>
          {icon}
        </div>
      </div>

      <div>
        {isMoney ? (
          <MoneyDisplay
            amount={typeof value === 'number' ? value : 0}
            currency={currency}
            compact={compact}
            className="text-2xl font-bold text-foreground"
          />
        ) : (
          <span className="text-2xl font-bold text-foreground tabular-nums">{value}</span>
        )}

        {change !== undefined && (
          <div className={`flex items-center gap-1 mt-1 text-xs font-medium ${
            trendPositive ? 'text-success' : trendNegative ? 'text-danger' : 'text-muted-foreground'
          }`}>
            {trendPositive ? <TrendingUp size={12} /> : trendNegative ? <TrendingDown size={12} /> : <Minus size={12} />}
            <span>{change > 0 ? '+' : ''}{change}%</span>
            {changeLabel && <span className="text-muted-foreground font-normal">{changeLabel}</span>}
          </div>
        )}
      </div>
    </div>
  );
}