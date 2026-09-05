'use client';
import React, { useState } from 'react';
import dynamic from 'next/dynamic';
import {
  ShoppingCart, TrendingUp, Receipt, CreditCard, Package,
  AlertTriangle, CheckSquare, Wallet, ArrowRight, Clock,
  Eye, ChevronRight, MoreHorizontal,
} from 'lucide-react';
import KpiCard from '@/components/ui/KpiCard';
import StatusBadge from '@/components/ui/StatusBadge';
import MoneyDisplay from '@/components/ui/MoneyDisplay';

const SalesTrendChart = dynamic(() => import('./SalesTrendChart'), { ssr: false });
const SalesVsExpensesChart = dynamic(() => import('./SalesVsExpensesChart'), { ssr: false });
const PaymentMethodChart = dynamic(() => import('./PaymentMethodChart'), { ssr: false });

// Backend integration point: replace all mock data with API calls to /api/dashboard/summary

const recentSales = [
  { id: 'sale-1847', customer: 'Juma Builders & Co', items: 4, amount: 1250000, status: 'paid' as const, payMethod: 'Cash', time: '14:32' },
  { id: 'sale-1846', customer: 'Walk-in Customer', items: 2, amount: 87500, status: 'paid' as const, payMethod: 'M-Pesa', time: '14:18' },
  { id: 'sale-1845', customer: 'Fatuma Construction', items: 7, amount: 3420000, status: 'partial' as const, payMethod: 'Credit', time: '13:55' },
  { id: 'sale-1844', customer: 'Karibu Contractors', items: 3, amount: 680000, status: 'paid' as const, payMethod: 'Bank Transfer', time: '13:22' },
  { id: 'sale-1843', customer: 'Ali & Sons Hardware', items: 1, amount: 45000, status: 'paid' as const, payMethod: 'Cash', time: '12:48' },
  { id: 'sale-1842', customer: 'Mwangi Properties', items: 12, amount: 5800000, status: 'pending' as const, payMethod: 'Credit', time: '12:10' },
];

const lowStockItems = [
  { id: 'prod-042', name: 'Portland Cement 50kg', sku: 'CEM-50-PORT', stock: 8, minStock: 50, unit: 'bags' },
  { id: 'prod-118', name: 'PVC Pipe 4" x 6m', sku: 'PVC-4-6M', stock: 6, minStock: 20, unit: 'pcs' },
  { id: 'prod-207', name: 'Binding Wire 2kg', sku: 'BWR-2KG', stock: 12, minStock: 30, unit: 'rolls' },
  { id: 'prod-315', name: 'Iron Sheet 30G 8ft', sku: 'IRN-30-8FT', stock: 3, minStock: 25, unit: 'sheets' },
  { id: 'prod-089', name: 'Paint Primer 20L', sku: 'PNT-PRM-20', stock: 4, minStock: 15, unit: 'tins' },
];

const pendingApprovals = [
  { id: 'apr-001', type: 'Price Override', user: 'Cashier Ali Hassan', detail: 'Iron Sheet 30G — 15% below min price', amount: 42000, time: '25 min ago', risk: 'medium' },
  { id: 'apr-002', type: 'Stock Adjustment', user: 'Storekeeper Amina', detail: 'Cement 50kg — adjust -20 bags (damaged)', amount: null, time: '1.5 hrs ago', risk: 'low' },
  { id: 'apr-003', type: 'Expense Approval', user: 'Manager Baraka', detail: 'Vehicle fuel — TZS 180,000', amount: 180000, time: '2 hrs ago', risk: 'low' },
  { id: 'apr-004', type: 'Sale Cancellation', user: 'Cashier Ali Hassan', detail: 'INV-2024-1839 — customer changed mind', amount: 320000, time: '3 hrs ago', risk: 'high' },
  { id: 'apr-005', type: 'Return Approval', user: 'Salesperson Grace', detail: 'PVC Pipe — 10 pcs return request', amount: 75000, time: '4 hrs ago', risk: 'medium' },
];

const customerDebts = [
  { id: 'cust-011', name: 'Mwangi Properties', debt: 5800000, overdue: 5800000, days: 45 },
  { id: 'cust-024', name: 'Juma Builders & Co', debt: 2450000, overdue: 0, days: 0 },
  { id: 'cust-038', name: 'Karibu Contractors', debt: 1920000, overdue: 1920000, days: 22 },
  { id: 'cust-055', name: 'Fatuma Construction', debt: 1340000, overdue: 0, days: 0 },
  { id: 'cust-067', name: 'Ali & Sons Hardware', debt: 890000, overdue: 890000, days: 31 },
];

export default function ClassicDashboard() {
  const [dateFilter, setDateFilter] = useState('today');

  return (
    <div className="space-y-4 sm:space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h1 className="text-xl sm:text-2xl font-bold text-foreground">Business Dashboard</h1>
          <p className="text-xs sm:text-sm text-muted-foreground mt-0.5">Karibu Hardware Ltd — Dar es Salaam Main · Saturday, 05 Sep 2026</p>
        </div>
        <div className="flex items-center gap-2 flex-wrap">
          <div className="flex bg-muted rounded-lg p-0.5 border border-border">
            {['today', 'week', 'month'].map(f => (
              <button
                key={`df-${f}`}
                onClick={() => setDateFilter(f)}
                className={`px-2.5 sm:px-3 py-1.5 text-xs font-medium rounded-md transition-all duration-150 capitalize ${
                  dateFilter === f ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'
                }`}
              >
                {f === 'today' ? 'Today' : f === 'week' ? 'Week' : 'Month'}
              </button>
            ))}
          </div>
          <div className="flex items-center gap-1 text-xs text-muted-foreground bg-muted rounded-lg px-2.5 sm:px-3 py-2 border border-border">
            <Clock size={12} />
            <span className="hidden sm:inline">Updated 2 min ago</span>
            <span className="sm:hidden">2m ago</span>
          </div>
        </div>
      </div>

      {/* KPI Bento Grid */}
      <div className="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        {/* Hero: Today's Sales — spans 2 cols */}
        <div className="col-span-2">
          <KpiCard
            title="Today's Sales"
            value={8450000}
            isMoney
            change={12.4}
            changeLabel="vs yesterday"
            icon={<ShoppingCart size={18} className="text-primary" />}
            iconBg="bg-primary/10"
            variant="default"
            subtitle="126 transactions completed"
            className="h-full"
          />
        </div>
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
          title="Expenses"
          value={420000}
          isMoney
          change={-5.1}
          changeLabel="vs yesterday"
          icon={<Receipt size={18} className="text-danger" />}
          iconBg="bg-danger/10"
        />
        <KpiCard
          title="Outstanding Debt"
          value={14800000}
          isMoney
          change={3.2}
          changeLabel="vs last week"
          icon={<CreditCard size={18} className="text-warning" />}
          iconBg="bg-warning/10"
          variant="warning"
          subtitle="18 overdue accounts"
        />
        <KpiCard
          title="Stock Value"
          value={428500000}
          isMoney
          compact
          change={-1.4}
          changeLabel="vs last week"
          icon={<Package size={18} className="text-info" />}
          iconBg="bg-info/10"
          subtitle="10,542 products tracked"
        />
        <KpiCard
          title="Low Stock"
          value={32}
          change={18.5}
          changeLabel="more than yesterday"
          icon={<AlertTriangle size={18} className="text-warning" />}
          iconBg="bg-warning/10"
          variant="warning"
          subtitle="Requires reorder attention"
        />
        <KpiCard
          title="Approvals"
          value={7}
          change={0}
          changeLabel="unchanged"
          icon={<CheckSquare size={18} className="text-danger" />}
          iconBg="bg-danger/10"
          variant="danger"
          subtitle="4 high priority"
        />
        <KpiCard
          title="Cash Balance"
          value={3240000}
          isMoney
          change={0}
          changeLabel="reconciled"
          icon={<Wallet size={18} className="text-success" />}
          iconBg="bg-success/10"
          variant="success"
          subtitle="Session open since 08:00"
        />
      </div>

      {/* Charts row */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {/* Sales Trend — spans 2 */}
        <div className="lg:col-span-2 card p-4">
          <div className="flex items-center justify-between mb-4">
            <div>
              <h3 className="text-sm font-semibold text-foreground">Sales & Profit Trend</h3>
              <p className="text-xs text-muted-foreground">Last 14 days</p>
            </div>
            <button className="btn-ghost text-xs py-1">
              <Eye size={12} /> View Report
            </button>
          </div>
          <SalesTrendChart />
        </div>

        {/* Payment Methods */}
        <div className="card p-4">
          <div className="flex items-center justify-between mb-4">
            <div>
              <h3 className="text-sm font-semibold text-foreground">Payment Methods</h3>
              <p className="text-xs text-muted-foreground">Today&apos;s breakdown</p>
            </div>
          </div>
          <PaymentMethodChart />
          <div className="mt-3 space-y-1.5">
            {[
              { label: 'Cash', value: 4200000, color: 'bg-success' },
              { label: 'M-Pesa', value: 2800000, color: 'bg-info' },
              { label: 'Bank Transfer', value: 1100000, color: 'bg-primary' },
              { label: 'Credit', value: 350000, color: 'bg-warning' },
            ].map(pm => (
              <div key={`pm-${pm.label}`} className="flex items-center justify-between text-xs">
                <div className="flex items-center gap-2">
                  <span className={`w-2 h-2 rounded-full ${pm.color}`} />
                  <span className="text-muted-foreground">{pm.label}</span>
                </div>
                <MoneyDisplay amount={pm.value} compact className="text-xs text-foreground" />
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Sales vs Expenses + Recent Sales */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div className="card p-4">
          <div className="flex items-center justify-between mb-4">
            <div>
              <h3 className="text-sm font-semibold text-foreground">Sales vs Expenses</h3>
              <p className="text-xs text-muted-foreground">This week</p>
            </div>
          </div>
          <SalesVsExpensesChart />
        </div>

        {/* Recent Sales */}
        <div className="lg:col-span-2 card overflow-hidden">
          <div className="flex items-center justify-between px-4 py-3 border-b border-border">
            <h3 className="text-sm font-semibold text-foreground">Recent Sales</h3>
            <button className="btn-ghost text-xs py-1 gap-1">
              View all <ArrowRight size={12} />
            </button>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full min-w-[500px]">
              <thead className="bg-muted/50">
                <tr>
                  <th className="table-th">Invoice</th>
                  <th className="table-th">Customer</th>
                  <th className="table-th">Amount</th>
                  <th className="table-th hidden sm:table-cell">Payment</th>
                  <th className="table-th">Status</th>
                  <th className="table-th hidden sm:table-cell">Time</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {recentSales.map(sale => (
                  <tr key={sale.id} className="table-row-hover">
                    <td className="table-td font-mono text-xs text-primary">#{sale.id}</td>
                    <td className="table-td font-medium text-sm max-w-[120px] truncate">{sale.customer}</td>
                    <td className="table-td">
                      <MoneyDisplay amount={sale.amount} compact className="text-sm" />
                    </td>
                    <td className="table-td text-muted-foreground hidden sm:table-cell">{sale.payMethod}</td>
                    <td className="table-td">
                      <StatusBadge status={sale.status} />
                    </td>
                    <td className="table-td text-muted-foreground hidden sm:table-cell">{sale.time}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {/* Low Stock + Pending Approvals + Customer Debts */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {/* Low Stock */}
        <div className="card overflow-hidden">
          <div className="flex items-center justify-between px-4 py-3 border-b border-border bg-warning/5">
            <div className="flex items-center gap-2">
              <AlertTriangle size={14} className="text-warning" />
              <h3 className="text-sm font-semibold text-foreground">Low Stock Alerts</h3>
            </div>
            <span className="badge-warning">32 items</span>
          </div>
          <div className="divide-y divide-border">
            {lowStockItems.map(item => (
              <div key={item.id} className="flex items-center justify-between px-4 py-2.5 hover:bg-muted/50 transition-colors">
                <div className="min-w-0 flex-1">
                  <p className="text-sm font-medium text-foreground truncate">{item.name}</p>
                  <p className="text-2xs text-muted-foreground font-mono">{item.sku}</p>
                </div>
                <div className="text-right ml-3 shrink-0">
                  <p className="text-sm font-bold text-danger tabular-nums">{item.stock} {item.unit}</p>
                  <p className="text-2xs text-muted-foreground">min: {item.minStock}</p>
                </div>
              </div>
            ))}
          </div>
          <div className="px-4 py-2 border-t border-border">
            <button className="text-xs text-primary font-semibold hover:underline flex items-center gap-1">
              View all 32 low stock items <ChevronRight size={12} />
            </button>
          </div>
        </div>

        {/* Pending Approvals */}
        <div className="card overflow-hidden">
          <div className="flex items-center justify-between px-4 py-3 border-b border-border bg-danger/5">
            <div className="flex items-center gap-2">
              <CheckSquare size={14} className="text-danger" />
              <h3 className="text-sm font-semibold text-foreground">Pending Approvals</h3>
            </div>
            <span className="badge-danger">7 pending</span>
          </div>
          <div className="divide-y divide-border">
            {pendingApprovals.map(apr => (
              <div key={apr.id} className="px-4 py-3 hover:bg-muted/50 transition-colors">
                <div className="flex items-start justify-between gap-2">
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 flex-wrap">
                      <span className="text-xs font-semibold text-foreground">{apr.type}</span>
                      <span className={`badge text-2xs ${
                        apr.risk === 'high' ? 'badge-danger' :
                        apr.risk === 'medium' ? 'badge-warning' : 'badge-muted'
                      }`}>
                        {apr.risk}
                      </span>
                    </div>
                    <p className="text-xs text-muted-foreground truncate mt-0.5">{apr.detail}</p>
                    <p className="text-2xs text-muted-foreground mt-0.5">{apr.user} · {apr.time}</p>
                  </div>
                  <button className="btn-ghost p-1 shrink-0">
                    <MoreHorizontal size={14} />
                  </button>
                </div>
                <div className="flex gap-2 mt-2">
                  <button className="text-2xs font-semibold text-success bg-success/10 px-2.5 py-1 rounded-md hover:bg-success/20 transition-colors">
                    Approve
                  </button>
                  <button className="text-2xs font-semibold text-danger bg-danger/10 px-2.5 py-1 rounded-md hover:bg-danger/20 transition-colors">
                    Reject
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Customer Debts */}
        <div className="card overflow-hidden">
          <div className="flex items-center justify-between px-4 py-3 border-b border-border">
            <div className="flex items-center gap-2">
              <CreditCard size={14} className="text-warning" />
              <h3 className="text-sm font-semibold text-foreground">Customer Debts</h3>
            </div>
            <span className="badge-warning">TZS 14.8M</span>
          </div>
          <div className="divide-y divide-border">
            {customerDebts.map(c => (
              <div key={c.id} className="flex items-center justify-between px-4 py-2.5 hover:bg-muted/50 transition-colors">
                <div className="min-w-0 flex-1">
                  <p className="text-sm font-medium text-foreground truncate">{c.name}</p>
                  {c.overdue > 0 && (
                    <p className="text-2xs text-danger font-semibold">
                      TZS {(c.overdue / 1_000_000).toFixed(1)}M overdue · {c.days} days
                    </p>
                  )}
                </div>
                <div className="text-right ml-3 shrink-0">
                  <MoneyDisplay amount={c.debt} compact className="text-sm" />
                  {c.overdue > 0 && <p className="text-2xs text-danger">⚠ overdue</p>}
                </div>
              </div>
            ))}
          </div>
          <div className="px-4 py-2 border-t border-border">
            <button className="text-xs text-primary font-semibold hover:underline flex items-center gap-1">
              View full debt report <ChevronRight size={12} />
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}