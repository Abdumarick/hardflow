'use client';
import React, { useState, useMemo } from 'react';
import Link from 'next/link';
import AppLayout, { DashboardLayout } from '@/components/AppLayout';
import KpiCard from '@/components/ui/KpiCard';

import MoneyDisplay from '@/components/ui/MoneyDisplay';
import {
  Receipt, Plus, Search, Download, RefreshCw, ChevronUp, ChevronDown,
  ChevronsUpDown, Eye, Edit2, Trash2, Clock, CheckCircle2, FileText,
  Send, X, SlidersHorizontal, CreditCard, Banknote, Smartphone, Building2,
  AlertCircle, TrendingUp,
} from 'lucide-react';

// ─── Types ────────────────────────────────────────────────────────────────────

type ExpenseStatus = 'draft' | 'pending' | 'approved' | 'posted';
type PaymentMethod = 'cash' | 'bank-transfer' | 'mobile-money' | 'card' | 'petty-cash';

interface Expense {
  id: string;
  expenseNo: string;
  title: string;
  category: string;
  description: string;
  amount: number;
  currency: string;
  paymentMethod: PaymentMethod;
  vendor: string;
  branch: string;
  date: string;
  submittedBy: string;
  approvedBy: string | null;
  status: ExpenseStatus;
  receiptRef: string;
  notes: string;
}

type SortField = 'expenseNo' | 'title' | 'category' | 'amount' | 'date' | 'status';
type SortDir = 'asc' | 'desc';

// ─── Mock Data ─────────────────────────────────────────────────────────────────

const expenses: Expense[] = [
  { id: 'exp001', expenseNo: 'EXP-2026-0041', title: 'Office Stationery & Supplies', category: 'Office Supplies', description: 'Pens, notebooks, printer paper for main office', amount: 185000, currency: 'TZS', paymentMethod: 'petty-cash', vendor: 'Nakumatt Supermarket', branch: 'Main Branch', date: '2026-09-05', submittedBy: 'Sarah Kimani', approvedBy: 'James Mwangi', status: 'posted', receiptRef: 'RCP-0091', notes: '' },
  { id: 'exp002', expenseNo: 'EXP-2026-0040', title: 'Delivery Truck Fuel', category: 'Transport', description: 'Fuel for delivery truck – September week 1', amount: 420000, currency: 'TZS', paymentMethod: 'cash', vendor: 'Total Energies', branch: 'Main Branch', date: '2026-09-04', submittedBy: 'Peter Odhiambo', approvedBy: 'James Mwangi', status: 'approved', receiptRef: 'RCP-0090', notes: 'Fuel receipt attached' },
  { id: 'exp003', expenseNo: 'EXP-2026-0039', title: 'Warehouse Electricity Bill', category: 'Utilities', description: 'TANESCO monthly electricity bill – August 2026', amount: 1250000, currency: 'TZS', paymentMethod: 'bank-transfer', vendor: 'TANESCO', branch: 'Warehouse', date: '2026-09-03', submittedBy: 'James Mwangi', approvedBy: null, status: 'pending', receiptRef: '', notes: 'Awaiting finance approval' },
  { id: 'exp004', expenseNo: 'EXP-2026-0038', title: 'Staff Lunch – Site Visit', category: 'Meals & Entertainment', description: 'Team lunch during Kariakoo site visit', amount: 95000, currency: 'TZS', paymentMethod: 'mobile-money', vendor: 'Mama Ntilie Restaurant', branch: 'Main Branch', date: '2026-09-02', submittedBy: 'Sarah Kimani', approvedBy: null, status: 'pending', receiptRef: 'RCP-0088', notes: '' },
  { id: 'exp005', expenseNo: 'EXP-2026-0037', title: 'Forklift Maintenance', category: 'Equipment Maintenance', description: 'Quarterly service and oil change for warehouse forklift', amount: 780000, currency: 'TZS', paymentMethod: 'bank-transfer', vendor: 'Kibo Engineering', branch: 'Warehouse', date: '2026-09-01', submittedBy: 'Peter Odhiambo', approvedBy: 'James Mwangi', status: 'posted', receiptRef: 'RCP-0087', notes: '' },
  { id: 'exp006', expenseNo: 'EXP-2026-0036', title: 'Internet & Phone Bills', category: 'Utilities', description: 'Vodacom business internet + staff phone allowances', amount: 340000, currency: 'TZS', paymentMethod: 'mobile-money', vendor: 'Vodacom Tanzania', branch: 'Main Branch', date: '2026-08-31', submittedBy: 'James Mwangi', approvedBy: 'James Mwangi', status: 'posted', receiptRef: 'RCP-0086', notes: '' },
  { id: 'exp007', expenseNo: 'EXP-2026-0035', title: 'Security Guard Services', category: 'Security', description: 'Monthly security services – August 2026', amount: 600000, currency: 'TZS', paymentMethod: 'bank-transfer', vendor: 'Shield Security Ltd', branch: 'Main Branch', date: '2026-08-30', submittedBy: 'James Mwangi', approvedBy: null, status: 'draft', receiptRef: '', notes: 'Draft – pending invoice from vendor' },
  { id: 'exp008', expenseNo: 'EXP-2026-0034', title: 'Marketing Banners & Flyers', category: 'Marketing', description: 'Printed banners for September promotion campaign', amount: 230000, currency: 'TZS', paymentMethod: 'cash', vendor: 'Print Masters DSM', branch: 'Main Branch', date: '2026-08-29', submittedBy: 'Sarah Kimani', approvedBy: null, status: 'draft', receiptRef: '', notes: '' },
  { id: 'exp009', expenseNo: 'EXP-2026-0033', title: 'Staff Training – Safety', category: 'Training & Development', description: 'Workplace safety training for warehouse staff', amount: 950000, currency: 'TZS', paymentMethod: 'bank-transfer', vendor: 'SafeWork Tanzania', branch: 'Warehouse', date: '2026-08-28', submittedBy: 'Peter Odhiambo', approvedBy: 'James Mwangi', status: 'approved', receiptRef: 'RCP-0083', notes: '' },
  { id: 'exp010', expenseNo: 'EXP-2026-0032', title: 'Office Cleaning Services', category: 'Facilities', description: 'Monthly cleaning contract – August 2026', amount: 180000, currency: 'TZS', paymentMethod: 'cash', vendor: 'CleanPro Services', branch: 'Main Branch', date: '2026-08-27', submittedBy: 'Sarah Kimani', approvedBy: 'James Mwangi', status: 'posted', receiptRef: 'RCP-0082', notes: '' },
  { id: 'exp011', expenseNo: 'EXP-2026-0031', title: 'Accounting Software License', category: 'Software & IT', description: 'Annual renewal for accounting software', amount: 1800000, currency: 'TZS', paymentMethod: 'card', vendor: 'Sage Africa', branch: 'Main Branch', date: '2026-08-25', submittedBy: 'James Mwangi', approvedBy: 'James Mwangi', status: 'posted', receiptRef: 'RCP-0081', notes: '' },
  { id: 'exp012', expenseNo: 'EXP-2026-0030', title: 'Warehouse Rent – September', category: 'Rent & Lease', description: 'Monthly warehouse rent payment', amount: 3500000, currency: 'TZS', paymentMethod: 'bank-transfer', vendor: 'Karibu Properties Ltd', branch: 'Warehouse', date: '2026-08-24', submittedBy: 'James Mwangi', approvedBy: null, status: 'pending', receiptRef: '', notes: 'Awaiting director approval' },
];

// ─── Config ────────────────────────────────────────────────────────────────────

const statusConfig: Record<ExpenseStatus, { label: string; color: string; icon: React.ReactNode; step: number }> = {
  draft:    { label: 'Draft',    color: 'bg-muted text-muted-foreground border border-border',        icon: <FileText size={13} />,     step: 1 },
  pending:  { label: 'Pending',  color: 'bg-warning/10 text-warning border border-warning/20',        icon: <Clock size={13} />,        step: 2 },
  approved: { label: 'Approved', color: 'bg-info/10 text-info border border-info/20',                 icon: <CheckCircle2 size={13} />, step: 3 },
  posted:   { label: 'Posted',   color: 'bg-success/10 text-success border border-success/20',        icon: <Send size={13} />,         step: 4 },
};

const statusSteps: ExpenseStatus[] = ['draft', 'pending', 'approved', 'posted'];

const paymentMethodConfig: Record<PaymentMethod, { label: string; icon: React.ReactNode }> = {
  cash:           { label: 'Cash',          icon: <Banknote size={13} className="text-success" /> },
  'bank-transfer':{ label: 'Bank Transfer', icon: <Building2 size={13} className="text-info" /> },
  'mobile-money': { label: 'Mobile Money',  icon: <Smartphone size={13} className="text-warning" /> },
  card:           { label: 'Card',          icon: <CreditCard size={13} className="text-primary" /> },
  'petty-cash':   { label: 'Petty Cash',    icon: <Banknote size={13} className="text-muted-foreground" /> },
};

const categories = ['Office Supplies', 'Transport', 'Utilities', 'Meals & Entertainment', 'Equipment Maintenance', 'Security', 'Marketing', 'Training & Development', 'Facilities', 'Software & IT', 'Rent & Lease'];

// ─── Sort Icon ─────────────────────────────────────────────────────────────────

function SortIcon({ field, sortField, sortDir }: { field: SortField; sortField: SortField; sortDir: SortDir }) {
  if (sortField !== field) return <ChevronsUpDown size={13} className="text-muted-foreground/50" />;
  return sortDir === 'asc' ? <ChevronUp size={13} className="text-primary" /> : <ChevronDown size={13} className="text-primary" />;
}

// ─── Main Page ─────────────────────────────────────────────────────────────────

export default function ExpensesPage() {
  const [layout, setLayout] = useState<DashboardLayout>('classic');
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState<ExpenseStatus | 'all'>('all');
  const [categoryFilter, setCategoryFilter] = useState('all');
  const [paymentFilter, setPaymentFilter] = useState<PaymentMethod | 'all'>('all');
  const [sortField, setSortField] = useState<SortField>('date');
  const [sortDir, setSortDir] = useState<SortDir>('desc');
  const [showFilters, setShowFilters] = useState(false);

  const handleSort = (field: SortField) => {
    if (sortField === field) setSortDir(d => d === 'asc' ? 'desc' : 'asc');
    else { setSortField(field); setSortDir('asc'); }
  };

  const filtered = useMemo(() => {
    let data = [...expenses];
    if (search) {
      const q = search.toLowerCase();
      data = data.filter(e =>
        e.expenseNo.toLowerCase().includes(q) ||
        e.title.toLowerCase().includes(q) ||
        e.vendor.toLowerCase().includes(q) ||
        e.submittedBy.toLowerCase().includes(q) ||
        e.category.toLowerCase().includes(q)
      );
    }
    if (statusFilter !== 'all') data = data.filter(e => e.status === statusFilter);
    if (categoryFilter !== 'all') data = data.filter(e => e.category === categoryFilter);
    if (paymentFilter !== 'all') data = data.filter(e => e.paymentMethod === paymentFilter);
    data.sort((a, b) => {
      let av: string | number = a[sortField as keyof Expense] as string | number;
      let bv: string | number = b[sortField as keyof Expense] as string | number;
      if (typeof av === 'string') av = av.toLowerCase();
      if (typeof bv === 'string') bv = bv.toLowerCase();
      if (av < bv) return sortDir === 'asc' ? -1 : 1;
      if (av > bv) return sortDir === 'asc' ? 1 : -1;
      return 0;
    });
    return data;
  }, [search, statusFilter, categoryFilter, paymentFilter, sortField, sortDir]);

  // KPIs
  const totalExpenses = expenses.length;
  const totalAmount = expenses.reduce((s, e) => s + e.amount, 0);
  const pendingApproval = expenses.filter(e => e.status === 'pending').length;
  const postedAmount = expenses.filter(e => e.status === 'posted').reduce((s, e) => s + e.amount, 0);

  return (
    <AppLayout layout={layout} onLayoutChange={setLayout}>
      <div className="p-4 md:p-6 space-y-5">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-bold text-foreground flex items-center gap-2">
              <Receipt size={22} className="text-primary" />
              Expenses
            </h1>
            <p className="text-sm text-muted-foreground mt-0.5">Track, categorize, and manage business expenses with approval workflows</p>
          </div>
          <div className="flex items-center gap-2">
            <button className="flex items-center gap-1.5 px-3 py-2 text-sm rounded-lg border border-border bg-card hover:bg-muted text-foreground transition-colors">
              <Download size={15} /> Export
            </button>
            <Link
              href="/expenses/new"
              className="flex items-center gap-1.5 px-4 py-2 bg-primary text-primary-foreground text-sm font-medium rounded-lg hover:bg-primary/90 transition-colors"
            >
              <Plus size={15} /> New Expense
            </Link>
          </div>
        </div>

        {/* KPI Cards */}
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <KpiCard title="Total Expenses" value={String(totalExpenses)} icon={<Receipt size={18} />} />
          <KpiCard title="Total Amount" value={totalAmount} isMoney currency="TZS" icon={<TrendingUp size={18} />} />
          <KpiCard title="Pending Approval" value={String(pendingApproval)} icon={<Clock size={18} />} variant={pendingApproval > 0 ? 'warning' : 'default'} />
          <KpiCard title="Posted Amount" value={postedAmount} isMoney currency="TZS" icon={<CheckCircle2 size={18} />} variant="success" />
        </div>

        {/* Status Pipeline */}
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
          {statusSteps.map(s => {
            const count = expenses.filter(e => e.status === s).length;
            const cfg = statusConfig[s];
            return (
              <button
                key={s}
                onClick={() => setStatusFilter(statusFilter === s ? 'all' : s)}
                className={`flex items-center gap-3 p-3 rounded-xl border transition-all text-left ${
                  statusFilter === s ? 'ring-2 ring-primary border-primary/30 bg-primary/5' : 'border-border bg-card hover:bg-muted/50'
                }`}
              >
                <div className={`w-9 h-9 rounded-lg flex items-center justify-center ${cfg.color}`}>
                  {cfg.icon}
                </div>
                <div>
                  <p className="text-xs text-muted-foreground">{cfg.label}</p>
                  <p className="text-lg font-bold text-foreground">{count}</p>
                </div>
              </button>
            );
          })}
        </div>

        {/* Search & Filters */}
        <div className="flex flex-col sm:flex-row gap-2">
          <div className="relative flex-1">
            <Search size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
            <input
              type="text"
              placeholder="Search by expense no, title, vendor, or submitted by..."
              value={search}
              onChange={e => setSearch(e.target.value)}
              className="w-full pl-9 pr-4 py-2 text-sm bg-card border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground placeholder:text-muted-foreground"
            />
            {search && (
              <button onClick={() => setSearch('')} className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground">
                <X size={14} />
              </button>
            )}
          </div>
          <button
            onClick={() => setShowFilters(f => !f)}
            className={`flex items-center gap-1.5 px-3 py-2 text-sm rounded-lg border transition-colors ${showFilters ? 'bg-primary/10 border-primary/30 text-primary' : 'border-border bg-card hover:bg-muted text-foreground'}`}
          >
            <SlidersHorizontal size={15} /> Filters
          </button>
          <button className="flex items-center gap-1.5 px-3 py-2 text-sm rounded-lg border border-border bg-card hover:bg-muted text-foreground transition-colors">
            <RefreshCw size={15} />
          </button>
        </div>

        {showFilters && (
          <div className="flex flex-wrap gap-3 p-3 bg-muted/40 rounded-xl border border-border">
            <div className="flex flex-col gap-1">
              <label className="text-xs text-muted-foreground font-medium">Status</label>
              <select
                value={statusFilter}
                onChange={e => setStatusFilter(e.target.value as ExpenseStatus | 'all')}
                className="text-sm bg-card border border-border rounded-lg px-3 py-1.5 text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30"
              >
                <option value="all">All Statuses</option>
                {statusSteps.map(s => <option key={s} value={s}>{statusConfig[s].label}</option>)}
              </select>
            </div>
            <div className="flex flex-col gap-1">
              <label className="text-xs text-muted-foreground font-medium">Category</label>
              <select
                value={categoryFilter}
                onChange={e => setCategoryFilter(e.target.value)}
                className="text-sm bg-card border border-border rounded-lg px-3 py-1.5 text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30"
              >
                <option value="all">All Categories</option>
                {categories.map(c => <option key={c} value={c}>{c}</option>)}
              </select>
            </div>
            <div className="flex flex-col gap-1">
              <label className="text-xs text-muted-foreground font-medium">Payment Method</label>
              <select
                value={paymentFilter}
                onChange={e => setPaymentFilter(e.target.value as PaymentMethod | 'all')}
                className="text-sm bg-card border border-border rounded-lg px-3 py-1.5 text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30"
              >
                <option value="all">All Methods</option>
                {(Object.keys(paymentMethodConfig) as PaymentMethod[]).map(m => (
                  <option key={m} value={m}>{paymentMethodConfig[m].label}</option>
                ))}
              </select>
            </div>
            <div className="flex items-end">
              <button
                onClick={() => { setStatusFilter('all'); setCategoryFilter('all'); setPaymentFilter('all'); }}
                className="text-xs text-muted-foreground hover:text-foreground px-3 py-1.5 rounded-lg border border-border bg-card hover:bg-muted transition-colors"
              >
                Clear Filters
              </button>
            </div>
          </div>
        )}

        {/* Results count */}
        <div className="flex items-center justify-between">
          <p className="text-sm text-muted-foreground">
            Showing <span className="font-semibold text-foreground">{filtered.length}</span> of {expenses.length} expenses
          </p>
        </div>

        {/* Table */}
        <div className="card overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border bg-muted/30">
                  {[
                    { field: 'expenseNo' as SortField, label: 'Expense #' },
                    { field: 'title' as SortField, label: 'Title / Vendor' },
                    { field: 'category' as SortField, label: 'Category' },
                    { field: 'date' as SortField, label: 'Date' },
                    { field: 'amount' as SortField, label: 'Amount' },
                    { field: 'status' as SortField, label: 'Status' },
                  ].map(col => (
                    <th
                      key={col.field}
                      onClick={() => handleSort(col.field)}
                      className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide cursor-pointer hover:text-foreground select-none whitespace-nowrap"
                    >
                      <span className="flex items-center gap-1">
                        {col.label}
                        <SortIcon field={col.field} sortField={sortField} sortDir={sortDir} />
                      </span>
                    </th>
                  ))}
                  <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Payment</th>
                  <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Submitted By</th>
                  <th className="px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {filtered.length === 0 ? (
                  <tr>
                    <td colSpan={9} className="px-4 py-12 text-center text-muted-foreground text-sm">
                      <AlertCircle size={32} className="mx-auto mb-2 opacity-30" />
                      No expenses found matching your filters.
                    </td>
                  </tr>
                ) : (
                  filtered.map(expense => {
                    const cfg = statusConfig[expense.status];
                    const pmCfg = paymentMethodConfig[expense.paymentMethod];
                    return (
                      <tr key={expense.id} className="hover:bg-muted/30 transition-colors">
                        <td className="px-4 py-3 font-mono text-xs text-primary font-semibold whitespace-nowrap">
                          {expense.expenseNo}
                        </td>
                        <td className="px-4 py-3 min-w-[200px]">
                          <p className="font-medium text-foreground truncate max-w-[200px]">{expense.title}</p>
                          <p className="text-xs text-muted-foreground truncate max-w-[200px]">{expense.vendor}</p>
                        </td>
                        <td className="px-4 py-3 whitespace-nowrap">
                          <span className="text-xs px-2 py-1 rounded-md bg-muted text-muted-foreground font-medium">{expense.category}</span>
                        </td>
                        <td className="px-4 py-3 text-muted-foreground whitespace-nowrap text-xs">{expense.date}</td>
                        <td className="px-4 py-3 whitespace-nowrap">
                          <MoneyDisplay amount={expense.amount} currency="TZS" className="text-sm font-bold text-foreground" />
                        </td>
                        <td className="px-4 py-3 whitespace-nowrap">
                          <span className={`inline-flex items-center gap-1 text-xs font-semibold px-2 py-1 rounded-full ${cfg.color}`}>
                            {cfg.icon} {cfg.label}
                          </span>
                        </td>
                        <td className="px-4 py-3 whitespace-nowrap">
                          <span className="inline-flex items-center gap-1 text-xs text-muted-foreground">
                            {pmCfg.icon} {pmCfg.label}
                          </span>
                        </td>
                        <td className="px-4 py-3 whitespace-nowrap">
                          <div>
                            <p className="text-xs font-medium text-foreground">{expense.submittedBy}</p>
                            {expense.approvedBy && (
                              <p className="text-2xs text-muted-foreground">Approved: {expense.approvedBy}</p>
                            )}
                          </div>
                        </td>
                        <td className="px-4 py-3 whitespace-nowrap">
                          <div className="flex items-center justify-end gap-1">
                            <Link
                              href={`/expenses/${expense.id}`}
                              className="p-1.5 rounded-lg hover:bg-muted text-muted-foreground hover:text-foreground transition-colors"
                              title="View"
                            >
                              <Eye size={14} />
                            </Link>
                            {(expense.status === 'draft' || expense.status === 'pending') && (
                              <Link
                                href={`/expenses/${expense.id}/edit`}
                                className="p-1.5 rounded-lg hover:bg-muted text-muted-foreground hover:text-foreground transition-colors"
                                title="Edit"
                              >
                                <Edit2 size={14} />
                              </Link>
                            )}
                            {expense.status === 'draft' && (
                              <button
                                className="p-1.5 rounded-lg hover:bg-danger/10 text-muted-foreground hover:text-danger transition-colors"
                                title="Delete"
                              >
                                <Trash2 size={14} />
                              </button>
                            )}
                          </div>
                        </td>
                      </tr>
                    );
                  })
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
