'use client';
import React, { useState } from 'react';
import dynamic from 'next/dynamic';
import { ShoppingCart, TrendingUp, Receipt, CreditCard, Package, AlertTriangle, CheckSquare, Wallet, FileText, ArrowDownToLine, ShoppingBag, DollarSign, Search, RefreshCw, Clock, ArrowRight,  } from 'lucide-react';
import KpiCard from '@/components/ui/KpiCard';
import StatusBadge from '@/components/ui/StatusBadge';
import MoneyDisplay from '@/components/ui/MoneyDisplay';

const SalesTrendChart = dynamic(() => import('./SalesTrendChart'), { ssr: false });
const SalesVsExpensesChart = dynamic(() => import('./SalesVsExpensesChart'), { ssr: false });

// Backend integration point: /api/dashboard/command-center

const quickActions = [
  { id: 'qa-newsale', label: 'New Sale', icon: <ShoppingCart size={20} />, color: 'text-primary', bg: 'bg-primary/10', shortcut: 'N' },
  { id: 'qa-quotation', label: 'Quotation', icon: <FileText size={20} />, color: 'text-info', bg: 'bg-info/10', shortcut: 'Q' },
  { id: 'qa-receive', label: 'Receive Stock', icon: <ArrowDownToLine size={20} />, color: 'text-warning', bg: 'bg-warning/10', shortcut: 'R' },
  { id: 'qa-purchase', label: 'New Purchase', icon: <ShoppingBag size={20} />, color: 'text-muted-foreground', bg: 'bg-muted', shortcut: 'P' },
  { id: 'qa-expense', label: 'Record Expense', icon: <Receipt size={20} />, color: 'text-danger', bg: 'bg-danger/10', shortcut: 'E' },
  { id: 'qa-payment', label: 'Receive Payment', icon: <DollarSign size={20} />, color: 'text-success', bg: 'bg-success/10', shortcut: 'M' },
  { id: 'qa-product', label: 'Add Product', icon: <Package size={20} />, color: 'text-accent', bg: 'bg-accent/10', shortcut: 'A' },
  { id: 'qa-stockcheck', label: 'Stock Check', icon: <Search size={20} />, color: 'text-primary', bg: 'bg-primary/10', shortcut: 'S' },
];

const recentTransactions = [
  { id: 'txn-1847', type: 'sale', ref: 'INV-2024-1847', party: 'Juma Builders', amount: 1250000, status: 'paid' as const, time: '14:32' },
  { id: 'txn-1846', type: 'sale', ref: 'INV-2024-1846', party: 'Walk-in', amount: 87500, status: 'paid' as const, time: '14:18' },
  { id: 'txn-exp-041', type: 'expense', ref: 'EXP-041', party: 'Fuel — Vehicle', amount: 180000, status: 'approved' as const, time: '13:40' },
  { id: 'txn-1845', type: 'sale', ref: 'INV-2024-1845', party: 'Fatuma Construction', amount: 3420000, status: 'partial' as const, time: '13:55' },
  { id: 'txn-pay-089', type: 'payment', ref: 'PAY-089', party: 'Karibu Contractors', amount: 800000, status: 'paid' as const, time: '13:10' },
];

const operationalAlerts = [
  { id: 'op-1', category: 'Inventory', icon: <AlertTriangle size={14} />, color: 'text-warning', bg: 'bg-warning/10', title: '32 Low Stock Items', detail: 'Portland Cement, PVC Pipe, Iron Sheet and 29 more', action: 'View Items' },
  { id: 'op-2', category: 'Customer Debt', icon: <CreditCard size={14} />, color: 'text-danger', bg: 'bg-danger/10', title: 'TZS 14.8M Outstanding', detail: '18 overdue accounts — oldest 45 days', action: 'View Debts' },
  { id: 'op-3', category: 'Approvals', icon: <CheckSquare size={14} />, color: 'text-warning', bg: 'bg-warning/10', title: '7 Pending Approvals', detail: '4 high priority — price override, cancellation', action: 'Review' },
  { id: 'op-4', category: 'Purchases', icon: <ShoppingBag size={14} />, color: 'text-info', bg: 'bg-info/10', title: '7 POs Awaiting Receipt', detail: '2 short deliveries need resolution', action: 'Receive' },
];

export default function CommandCenterDashboard() {
  const [dateFilter, setDateFilter] = useState('today');

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h1 className="text-2xl font-bold text-foreground">Command Center</h1>
          <p className="text-sm text-muted-foreground">Karibu Hardware Ltd — Dar es Salaam Main · 05 Sep 2026</p>
        </div>
        <div className="flex items-center gap-2">
          <div className="flex bg-muted rounded-lg p-0.5 border border-border">
            {['today', 'week', 'month'].map(f => (
              <button
                key={`ccdf-${f}`}
                onClick={() => setDateFilter(f)}
                className={`px-3 py-1.5 text-xs font-medium rounded-md transition-all duration-150 capitalize ${
                  dateFilter === f ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'
                }`}
              >
                {f === 'today' ? 'Today' : f === 'week' ? 'This Week' : 'This Month'}
              </button>
            ))}
          </div>
          <button className="btn-ghost p-2">
            <RefreshCw size={16} />
          </button>
          <div className="flex items-center gap-1 text-xs text-muted-foreground bg-muted rounded-lg px-2 py-2 border border-border">
            <Clock size={12} />
            <span className="hidden sm:block">Updated 2 min ago</span>
          </div>
        </div>
      </div>

      {/* Quick Actions */}
      <div className="card p-4">
        <div className="flex items-center justify-between mb-3">
          <h2 className="text-sm font-semibold text-foreground">Quick Actions</h2>
          <p className="text-xs text-muted-foreground">Keyboard shortcuts available</p>
        </div>
        <div className="grid grid-cols-4 sm:grid-cols-8 gap-2">
          {quickActions.map(qa => (
            <button
              key={qa.id}
              className="flex flex-col items-center gap-2 p-3 rounded-xl border border-border hover:border-primary/30 hover:bg-primary/5 transition-all duration-150 group active:scale-95"
            >
              <div className={`w-10 h-10 rounded-xl ${qa.bg} flex items-center justify-center ${qa.color} group-hover:scale-110 transition-transform duration-150`}>
                {qa.icon}
              </div>
              <span className="text-2xs font-semibold text-foreground text-center leading-tight">{qa.label}</span>
              <kbd className="text-2xs bg-muted border border-border px-1 rounded font-mono text-muted-foreground">{qa.shortcut}</kbd>
            </button>
          ))}
        </div>
      </div>

      {/* KPI Row — 4 cards */}
      <div className="grid grid-cols-2 lg:grid-cols-4 xl:grid-cols-4 2xl:grid-cols-4 gap-4">
        <KpiCard
          title="Today's Sales"
          value={8450000}
          isMoney
          change={12.4}
          changeLabel="vs yesterday"
          icon={<ShoppingCart size={18} className="text-primary" />}
          iconBg="bg-primary/10"
          subtitle="126 transactions"
        />
        <KpiCard
          title="Gross Profit"
          value={1620000}
          isMoney
          change={8.2}
          changeLabel="vs yesterday"
          icon={<TrendingUp size={18} className="text-success" />}
          iconBg="bg-success/10"
          variant="success"
        />
        <KpiCard
          title="Outstanding Debt"
          value={14800000}
          isMoney
          compact
          change={3.2}
          changeLabel="vs last week"
          icon={<CreditCard size={18} className="text-warning" />}
          iconBg="bg-warning/10"
          variant="warning"
          subtitle="18 overdue accounts"
        />
        <KpiCard
          title="Cash Balance"
          value={3240000}
          isMoney
          icon={<Wallet size={18} className="text-success" />}
          iconBg="bg-success/10"
          variant="success"
          subtitle="Session reconciled"
        />
      </div>

      {/* KPI Row 2 — 4 cards */}
      <div className="grid grid-cols-2 lg:grid-cols-4 xl:grid-cols-4 2xl:grid-cols-4 gap-4">
        <KpiCard
          title="Today's Expenses"
          value={420000}
          isMoney
          change={-5.1}
          changeLabel="vs yesterday"
          icon={<Receipt size={18} className="text-danger" />}
          iconBg="bg-danger/10"
        />
        <KpiCard
          title="Stock Value"
          value={428500000}
          isMoney
          compact
          icon={<Package size={18} className="text-info" />}
          iconBg="bg-info/10"
          subtitle="10,542 products"
        />
        <KpiCard
          title="Low Stock Items"value={32}
          change={18.5}
          changeLabel="more than yesterday"
          icon={<AlertTriangle size={18} className="text-warning" />}
          iconBg="bg-warning/10"
          variant="warning"
        />
        <KpiCard
          title="Pending Approvals"
          value={7}
          icon={<CheckSquare size={18} className="text-danger" />}
          iconBg="bg-danger/10"
          variant="danger"
          subtitle="4 high priority"
        />
      </div>

      {/* Operational Alerts */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-4 2xl:grid-cols-4 gap-4">
        {operationalAlerts.map(alert => (
          <div key={alert.id} className="card p-4 flex flex-col gap-3">
            <div className="flex items-center gap-2">
              <div className={`w-7 h-7 rounded-lg ${alert.bg} flex items-center justify-center ${alert.color}`}>
                {alert.icon}
              </div>
              <span className="text-2xs font-semibold text-muted-foreground uppercase tracking-wide">{alert.category}</span>
            </div>
            <div>
              <p className="text-sm font-bold text-foreground">{alert.title}</p>
              <p className="text-xs text-muted-foreground mt-0.5 leading-relaxed">{alert.detail}</p>
            </div>
            <button className="text-xs text-primary font-semibold flex items-center gap-1 hover:underline mt-auto">
              {alert.action} <ArrowRight size={11} />
            </button>
          </div>
        ))}
      </div>

      {/* Charts + Recent Transactions */}
      <div className="grid grid-cols-1 lg:grid-cols-3 xl:grid-cols-3 2xl:grid-cols-3 gap-4">
        <div className="lg:col-span-2 card p-4">
          <div className="flex items-center justify-between mb-4">
            <div>
              <h3 className="text-sm font-semibold text-foreground">Sales Trend</h3>
              <p className="text-xs text-muted-foreground">Last 14 days</p>
            </div>
          </div>
          <SalesTrendChart />
        </div>

        <div className="card overflow-hidden">
          <div className="flex items-center justify-between px-4 py-3 border-b border-border">
            <h3 className="text-sm font-semibold text-foreground">Recent Transactions</h3>
            <button className="btn-ghost text-xs py-1 gap-1">All <ArrowRight size={12} /></button>
          </div>
          <div className="divide-y divide-border">
            {recentTransactions.map(txn => (
              <div key={txn.id} className="flex items-center justify-between px-4 py-2.5 hover:bg-muted/50 transition-colors">
                <div className="min-w-0 flex-1">
                  <p className="text-xs font-mono text-primary">{txn.ref}</p>
                  <p className="text-sm font-medium text-foreground truncate">{txn.party}</p>
                  <p className="text-2xs text-muted-foreground capitalize">{txn.type} · {txn.time}</p>
                </div>
                <div className="text-right ml-3 shrink-0">
                  <MoneyDisplay amount={txn.amount} compact className="text-sm" />
                  <div className="mt-0.5">
                    <StatusBadge status={txn.status} />
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Sales vs Expenses */}
      <div className="card p-4">
        <div className="flex items-center justify-between mb-4">
          <div>
            <h3 className="text-sm font-semibold text-foreground">Sales vs Expenses — This Week</h3>
          </div>
        </div>
        <SalesVsExpensesChart />
      </div>
    </div>
  );
}