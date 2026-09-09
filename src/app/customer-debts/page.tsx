'use client';
import React, { useState, useMemo } from 'react';
import Link from 'next/link';
import AppLayout, { DashboardLayout } from '@/components/AppLayout';
import MoneyDisplay from '@/components/ui/MoneyDisplay';
import KpiCard from '@/components/ui/KpiCard';

import { AlertTriangle, CreditCard, Search, Download, Phone, Mail, MapPin, ChevronRight, Clock, CheckCircle2, XCircle, TrendingUp, Users, ArrowUpDown, Banknote, CalendarClock, ShieldAlert,  } from 'lucide-react';

interface Debtor {
  id: string;
  name: string;
  type: 'retail' | 'wholesale' | 'contractor';
  phone: string;
  email: string;
  location: string;
  contactPerson: string;
  creditLimit: number;
  outstanding: number;
  overdue: number;
  overduedays: number;
  lastPayment: string | null;
  lastSale: string;
  status: 'active' | 'inactive';
  paymentStatus: 'current' | 'overdue' | 'critical' | 'written-off';
  invoiceCount: number;
}

const debtors: Debtor[] = [
  {
    id: 'cust-006', name: 'Nyumba Bora Developers', type: 'contractor',
    phone: '+255 742 876 543', email: 'info@nyumbabora.co.tz', location: 'Dar es Salaam',
    contactPerson: 'Ahmed Salim', creditLimit: 12000000, outstanding: 7800000, overdue: 3200000,
    overduedays: 42, lastPayment: '2026-08-10', lastSale: '2026-08-30',
    status: 'active', paymentStatus: 'critical', invoiceCount: 6,
  },
  {
    id: 'cust-004', name: 'Simba Hardware Supplies', type: 'wholesale',
    phone: '+255 713 654 321', email: 'simba@simbahardware.co.tz', location: 'Dodoma',
    contactPerson: 'John Simba', creditLimit: 8000000, outstanding: 4500000, overdue: 1200000,
    overduedays: 18, lastPayment: '2026-08-25', lastSale: '2026-09-02',
    status: 'active', paymentStatus: 'overdue', invoiceCount: 4,
  },
  {
    id: 'cust-001', name: 'Karibu Construction Ltd', type: 'contractor',
    phone: '+255 712 345 678', email: 'info@karibuconstruction.co.tz', location: 'Dar es Salaam',
    contactPerson: 'David Kariuki', creditLimit: 5000000, outstanding: 2850000, overdue: 850000,
    overduedays: 7, lastPayment: '2026-09-02', lastSale: '2026-09-04',
    status: 'active', paymentStatus: 'overdue', invoiceCount: 2,
  },
  {
    id: 'cust-012', name: 'Kilimanjaro Hardware', type: 'wholesale',
    phone: '+255 755 678 901', email: 'kili@kilihardware.co.tz', location: 'Moshi',
    contactPerson: 'Peter Moshi', creditLimit: 5000000, outstanding: 2200000, overdue: 500000,
    overduedays: 12, lastPayment: '2026-08-20', lastSale: '2026-08-29',
    status: 'active', paymentStatus: 'overdue', invoiceCount: 3,
  },
  {
    id: 'cust-009', name: 'Grace Kimani', type: 'retail',
    phone: '+255 768 901 234', email: 'grace.k@gmail.com', location: 'Arusha',
    contactPerson: 'Grace Kimani', creditLimit: 400000, outstanding: 400000, overdue: 400000,
    overduedays: 54, lastPayment: null, lastSale: '2026-07-15',
    status: 'active', paymentStatus: 'critical', invoiceCount: 1,
  },
  {
    id: 'cust-003', name: 'Fatuma Mwangi', type: 'retail',
    phone: '+255 765 432 109', email: 'fatuma.m@gmail.com', location: 'Mwanza',
    contactPerson: 'Fatuma Mwangi', creditLimit: 500000, outstanding: 320000, overdue: 320000,
    overduedays: 10, lastPayment: '2026-08-18', lastSale: '2026-08-28',
    status: 'active', paymentStatus: 'overdue', invoiceCount: 1,
  },
  {
    id: 'cust-010', name: 'Coastal Builders Ltd', type: 'contractor',
    phone: '+255 714 567 890', email: 'info@coastalbuilders.co.tz', location: 'Tanga',
    contactPerson: 'Ali Hassan', creditLimit: 4000000, outstanding: 1850000, overdue: 0,
    overduedays: 0, lastPayment: '2026-09-01', lastSale: '2026-09-03',
    status: 'active', paymentStatus: 'current', invoiceCount: 2,
  },
  {
    id: 'cust-002', name: 'Juma Builders & Co', type: 'wholesale',
    phone: '+255 754 987 654', email: 'juma@jumabuilders.co.tz', location: 'Arusha',
    contactPerson: 'Juma Hassan', creditLimit: 3000000, outstanding: 1200000, overdue: 0,
    overduedays: 0, lastPayment: '2026-09-03', lastSale: '2026-09-03',
    status: 'active', paymentStatus: 'current', invoiceCount: 1,
  },
  {
    id: 'cust-007', name: 'Hassan Ally', type: 'retail',
    phone: '+255 756 234 567', email: 'hassan.ally@gmail.com', location: 'Zanzibar',
    contactPerson: 'Hassan Ally', creditLimit: 300000, outstanding: 150000, overdue: 0,
    overduedays: 0, lastPayment: '2026-09-01', lastSale: '2026-09-04',
    status: 'active', paymentStatus: 'current', invoiceCount: 1,
  },
  {
    id: 'cust-011', name: 'Amina Stores', type: 'retail',
    phone: '+255 777 345 678', email: 'amina.stores@gmail.com', location: 'Morogoro',
    contactPerson: 'Amina Juma', creditLimit: 250000, outstanding: 80000, overdue: 0,
    overduedays: 0, lastPayment: '2026-09-02', lastSale: '2026-09-02',
    status: 'active', paymentStatus: 'current', invoiceCount: 1,
  },
];

const paymentStatusConfig: Record<string, { label: string; icon: React.ReactNode; className: string; rowClass: string }> = {
  current: {
    label: 'Current',
    icon: <CheckCircle2 size={13} />,
    className: 'bg-success/10 text-success',
    rowClass: '',
  },
  overdue: {
    label: 'Overdue',
    icon: <Clock size={13} />,
    className: 'bg-warning/10 text-warning',
    rowClass: 'bg-warning/3',
  },
  critical: {
    label: 'Critical',
    icon: <ShieldAlert size={13} />,
    className: 'bg-danger/10 text-danger',
    rowClass: 'bg-danger/3',
  },
  'written-off': {
    label: 'Written Off',
    icon: <XCircle size={13} />,
    className: 'bg-muted text-muted-foreground',
    rowClass: 'opacity-60',
  },
};

const typeColors: Record<string, string> = {
  retail: 'bg-info/10 text-info',
  wholesale: 'bg-primary/10 text-primary',
  contractor: 'bg-warning/10 text-warning',
};

type SortKey = 'outstanding' | 'overdue' | 'overduedays' | 'creditLimit';

export default function CustomerDebtsPage() {
  const [layout] = useState<DashboardLayout>('classic');
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [typeFilter, setTypeFilter] = useState('');
  const [sortKey, setSortKey] = useState<SortKey>('overdue');
  const [sortDir, setSortDir] = useState<'asc' | 'desc'>('desc');
  const [activeView, setActiveView] = useState<'all' | 'overdue' | 'critical' | 'current'>('all');

  const handleSort = (key: SortKey) => {
    if (sortKey === key) {
      setSortDir(d => (d === 'asc' ? 'desc' : 'asc'));
    } else {
      setSortKey(key);
      setSortDir('desc');
    }
  };

  const filtered = useMemo(() => {
    let list = debtors.filter(d => {
      if (search) {
        const q = search.toLowerCase();
        if (!d.name.toLowerCase().includes(q) && !d.phone.includes(q) && !d.location.toLowerCase().includes(q)) return false;
      }
      if (statusFilter && d.paymentStatus !== statusFilter) return false;
      if (typeFilter && d.type !== typeFilter) return false;
      if (activeView === 'overdue' && d.paymentStatus !== 'overdue') return false;
      if (activeView === 'critical' && d.paymentStatus !== 'critical') return false;
      if (activeView === 'current' && d.paymentStatus !== 'current') return false;
      return true;
    });

    list = [...list].sort((a, b) => {
      const av = a[sortKey];
      const bv = b[sortKey];
      return sortDir === 'asc' ? av - bv : bv - av;
    });

    return list;
  }, [search, statusFilter, typeFilter, sortKey, sortDir, activeView]);

  const totalOutstanding = debtors.reduce((s, d) => s + d.outstanding, 0);
  const totalOverdue = debtors.reduce((s, d) => s + d.overdue, 0);
  const criticalCount = debtors.filter(d => d.paymentStatus === 'critical').length;
  const overdueCount = debtors.filter(d => d.paymentStatus === 'overdue').length;
  const currentCount = debtors.filter(d => d.paymentStatus === 'current').length;
  const avgCreditUsedPct = Math.round(
    debtors.reduce((s, d) => s + (d.outstanding / d.creditLimit) * 100, 0) / debtors.length
  );

  const viewTabs = [
    { id: 'all', label: `All (${debtors.length})` },
    { id: 'critical', label: `Critical (${criticalCount})` },
    { id: 'overdue', label: `Overdue (${overdueCount})` },
    { id: 'current', label: `Current (${currentCount})` },
  ] as const;

  const SortBtn = ({ col }: { col: SortKey }) => (
    <button onClick={() => handleSort(col)} className="ml-1 inline-flex items-center opacity-50 hover:opacity-100 transition-opacity">
      <ArrowUpDown size={11} />
    </button>
  );

  return (
    <AppLayout layout={layout}>
      <div className="space-y-5">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-bold text-foreground">Customer Debts</h1>
            <p className="text-sm text-muted-foreground mt-0.5">
              {debtors.length} debtors · Avg credit utilisation {avgCreditUsedPct}%
            </p>
          </div>
          <div className="flex items-center gap-2">
            <button className="btn-outline flex items-center gap-2 text-sm px-3 py-2">
              <Download size={15} /> Export
            </button>
            <Link href="/customers" className="btn-primary flex items-center gap-2 text-sm px-3 py-2">
              <Users size={15} /> All Customers
            </Link>
          </div>
        </div>

        {/* KPI Row */}
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <KpiCard
            title="Total Outstanding"
            value={totalOutstanding}
            isMoney
            icon={<CreditCard size={18} className="text-warning" />}
            iconBg="bg-warning/10"
            variant="warning"
            subtitle={`${debtors.length} debtors`}
          />
          <KpiCard
            title="Total Overdue"
            value={totalOverdue}
            isMoney
            icon={<AlertTriangle size={18} className="text-danger" />}
            iconBg="bg-danger/10"
            variant="danger"
            subtitle={`${criticalCount + overdueCount} customers`}
          />
          <KpiCard
            title="Critical Accounts"
            value={criticalCount}
            icon={<ShieldAlert size={18} className="text-danger" />}
            iconBg="bg-danger/10"
            variant="danger"
            subtitle="30+ days overdue"
          />
          <KpiCard
            title="Current Accounts"
            value={currentCount}
            icon={<TrendingUp size={18} className="text-success" />}
            iconBg="bg-success/10"
            variant="success"
            subtitle="No overdue balance"
          />
        </div>

        {/* Critical Alert Banner */}
        {criticalCount > 0 && (
          <div className="flex items-start gap-3 px-4 py-3 rounded-lg bg-danger/5 border border-danger/20">
            <ShieldAlert size={16} className="text-danger shrink-0 mt-0.5" />
            <div className="flex-1">
              <p className="text-sm font-semibold text-danger">
                {criticalCount} account{criticalCount > 1 ? 's' : ''} require immediate attention
              </p>
              <p className="text-xs text-muted-foreground mt-0.5">
                These customers have balances overdue by 30+ days. Consider sending payment reminders or restricting credit.
              </p>
            </div>
          </div>
        )}

        {/* View Tabs + Filters */}
        <div className="card p-3 space-y-3">
          {/* View tabs */}
          <div className="flex gap-1 flex-wrap">
            {viewTabs.map(tab => (
              <button
                key={tab.id}
                onClick={() => setActiveView(tab.id)}
                className={`px-3 py-1.5 rounded-md text-xs font-semibold transition-colors ${
                  activeView === tab.id
                    ? 'bg-primary text-primary-foreground'
                    : 'bg-muted/50 text-muted-foreground hover:bg-muted hover:text-foreground'
                }`}
              >
                {tab.label}
              </button>
            ))}
          </div>

          {/* Search + Filters */}
          <div className="flex flex-col sm:flex-row gap-2">
            <div className="relative flex-1">
              <Search size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
              <input
                type="text"
                placeholder="Search by name, phone, location..."
                value={search}
                onChange={e => setSearch(e.target.value)}
                className="input pl-9 w-full text-sm"
              />
            </div>
            <div className="flex gap-2 flex-wrap">
              <select value={typeFilter} onChange={e => setTypeFilter(e.target.value)} className="input text-sm min-w-[130px]">
                <option value="">All Types</option>
                <option value="retail">Retail</option>
                <option value="wholesale">Wholesale</option>
                <option value="contractor">Contractor</option>
              </select>
              <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)} className="input text-sm min-w-[140px]">
                <option value="">All Payment Status</option>
                <option value="current">Current</option>
                <option value="overdue">Overdue</option>
                <option value="critical">Critical</option>
              </select>
            </div>
          </div>
        </div>

        {/* Debtors Table */}
        <div className="card overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border bg-muted/30">
                  <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Customer</th>
                  <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide hidden md:table-cell">Type</th>
                  <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide hidden lg:table-cell">Contact</th>
                  <th className="text-right px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">
                    Credit Limit <SortBtn col="creditLimit" />
                  </th>
                  <th className="text-right px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">
                    Outstanding <SortBtn col="outstanding" />
                  </th>
                  <th className="text-right px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide hidden sm:table-cell">
                    Overdue <SortBtn col="overdue" />
                  </th>
                  <th className="text-center px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide hidden md:table-cell">
                    Days <SortBtn col="overduedays" />
                  </th>
                  <th className="text-center px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Status</th>
                  <th className="px-4 py-3 w-10"></th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {filtered.map(debtor => {
                  const cfg = paymentStatusConfig[debtor.paymentStatus];
                  const usedPct = Math.min(100, Math.round((debtor.outstanding / debtor.creditLimit) * 100));
                  return (
                    <tr key={debtor.id} className={`hover:bg-muted/20 transition-colors group ${cfg.rowClass}`}>
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-3">
                          <div className={`w-8 h-8 rounded-full flex items-center justify-center shrink-0 ${
                            debtor.paymentStatus === 'critical' ? 'bg-danger/10' :
                            debtor.paymentStatus === 'overdue' ? 'bg-warning/10' : 'bg-primary/10'
                          }`}>
                            <span className={`text-xs font-bold ${
                              debtor.paymentStatus === 'critical' ? 'text-danger' :
                              debtor.paymentStatus === 'overdue' ? 'text-warning' : 'text-primary'
                            }`}>
                              {debtor.name.split(' ').map(w => w[0]).slice(0, 2).join('')}
                            </span>
                          </div>
                          <div className="min-w-0">
                            <Link href={`/customers/${debtor.id}`} className="font-semibold text-foreground hover:text-primary transition-colors block truncate">
                              {debtor.name}
                            </Link>
                            <div className="flex items-center gap-2 mt-0.5">
                              <p className="text-xs text-muted-foreground flex items-center gap-1">
                                <MapPin size={10} /> {debtor.location}
                              </p>
                              <span className="text-xs text-muted-foreground">·</span>
                              <p className="text-xs text-muted-foreground">{debtor.invoiceCount} invoice{debtor.invoiceCount > 1 ? 's' : ''}</p>
                            </div>
                          </div>
                        </div>
                      </td>
                      <td className="px-4 py-3 hidden md:table-cell">
                        <span className={`text-xs font-semibold px-2 py-0.5 rounded-full capitalize ${typeColors[debtor.type]}`}>
                          {debtor.type}
                        </span>
                      </td>
                      <td className="px-4 py-3 hidden lg:table-cell">
                        <p className="text-xs text-foreground flex items-center gap-1">
                          <Phone size={11} className="text-muted-foreground shrink-0" /> {debtor.phone}
                        </p>
                        <p className="text-xs text-muted-foreground flex items-center gap-1 mt-0.5">
                          <Mail size={11} className="shrink-0" /> {debtor.email}
                        </p>
                      </td>
                      <td className="px-4 py-3 text-right">
                        <div>
                          <MoneyDisplay amount={debtor.creditLimit} compact className="text-sm" />
                          <div className="mt-1 h-1 bg-muted rounded-full overflow-hidden w-16 ml-auto">
                            <div
                              className={`h-full rounded-full ${usedPct >= 90 ? 'bg-danger' : usedPct >= 70 ? 'bg-warning' : 'bg-success'}`}
                              style={{ width: `${usedPct}%` }}
                            />
                          </div>
                          <p className="text-2xs text-muted-foreground mt-0.5">{usedPct}% used</p>
                        </div>
                      </td>
                      <td className="px-4 py-3 text-right">
                        <MoneyDisplay amount={debtor.outstanding} compact className="text-sm font-semibold text-warning" />
                      </td>
                      <td className="px-4 py-3 text-right hidden sm:table-cell">
                        {debtor.overdue > 0 ? (
                          <MoneyDisplay amount={debtor.overdue} compact className="text-sm font-semibold text-danger" />
                        ) : (
                          <span className="text-sm text-muted-foreground">—</span>
                        )}
                      </td>
                      <td className="px-4 py-3 text-center hidden md:table-cell">
                        {debtor.overduedays > 0 ? (
                          <span className={`text-xs font-bold ${debtor.overduedays >= 30 ? 'text-danger' : 'text-warning'}`}>
                            {debtor.overduedays}d
                          </span>
                        ) : (
                          <span className="text-xs text-muted-foreground">—</span>
                        )}
                      </td>
                      <td className="px-4 py-3 text-center">
                        <span className={`inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full ${cfg.className}`}>
                          {cfg.icon}
                          <span className="hidden sm:inline">{cfg.label}</span>
                        </span>
                      </td>
                      <td className="px-4 py-3">
                        <Link
                          href={`/customers/${debtor.id}`}
                          className="p-1.5 rounded-md hover:bg-muted transition-colors flex items-center justify-center text-muted-foreground hover:text-foreground"
                        >
                          <ChevronRight size={16} />
                        </Link>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
            {filtered.length === 0 && (
              <div className="text-center py-12 text-muted-foreground">
                <CreditCard size={32} className="mx-auto mb-2 opacity-30" />
                <p className="text-sm">No debtors found matching your filters.</p>
              </div>
            )}
          </div>

          {/* Footer */}
          <div className="px-4 py-3 border-t border-border bg-muted/20 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <p className="text-xs text-muted-foreground">Showing {filtered.length} of {debtors.length} debtors</p>
            <div className="flex items-center gap-4 text-xs text-muted-foreground">
              <span className="flex items-center gap-1">
                <span className="w-2 h-2 rounded-full bg-danger inline-block" /> Critical: {criticalCount}
              </span>
              <span className="flex items-center gap-1">
                <span className="w-2 h-2 rounded-full bg-warning inline-block" /> Overdue: {overdueCount}
              </span>
              <span className="flex items-center gap-1">
                <span className="w-2 h-2 rounded-full bg-success inline-block" /> Current: {currentCount}
              </span>
            </div>
          </div>
        </div>

        {/* Aging Summary */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
          {/* Aging Buckets */}
          <div className="card p-5">
            <h3 className="text-sm font-semibold text-foreground mb-4 flex items-center gap-2">
              <CalendarClock size={15} className="text-primary" /> Debt Aging Analysis
            </h3>
            <div className="space-y-3">
              {[
                { label: 'Current (0 days)', amount: debtors.filter(d => d.overduedays === 0).reduce((s, d) => s + d.outstanding, 0), count: debtors.filter(d => d.overduedays === 0).length, color: 'bg-success' },
                { label: '1–15 days', amount: debtors.filter(d => d.overduedays > 0 && d.overduedays <= 15).reduce((s, d) => s + d.overdue, 0), count: debtors.filter(d => d.overduedays > 0 && d.overduedays <= 15).length, color: 'bg-info' },
                { label: '16–30 days', amount: debtors.filter(d => d.overduedays > 15 && d.overduedays <= 30).reduce((s, d) => s + d.overdue, 0), count: debtors.filter(d => d.overduedays > 15 && d.overduedays <= 30).length, color: 'bg-warning' },
                { label: '31–60 days', amount: debtors.filter(d => d.overduedays > 30 && d.overduedays <= 60).reduce((s, d) => s + d.overdue, 0), count: debtors.filter(d => d.overduedays > 30 && d.overduedays <= 60).length, color: 'bg-danger' },
                { label: '60+ days', amount: debtors.filter(d => d.overduedays > 60).reduce((s, d) => s + d.overdue, 0), count: debtors.filter(d => d.overduedays > 60).length, color: 'bg-danger' },
              ].map(bucket => (
                <div key={bucket.label} className="flex items-center gap-3">
                  <div className="w-28 shrink-0">
                    <p className="text-xs text-muted-foreground">{bucket.label}</p>
                  </div>
                  <div className="flex-1">
                    <div className="h-2 bg-muted rounded-full overflow-hidden">
                      <div
                        className={`h-full rounded-full ${bucket.color}`}
                        style={{ width: totalOutstanding > 0 ? `${Math.min(100, (bucket.amount / totalOutstanding) * 100)}%` : '0%' }}
                      />
                    </div>
                  </div>
                  <div className="text-right w-28 shrink-0">
                    <MoneyDisplay amount={bucket.amount} compact className="text-xs font-semibold text-foreground" />
                    <p className="text-2xs text-muted-foreground">{bucket.count} cust.</p>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Top Debtors */}
          <div className="card p-5">
            <h3 className="text-sm font-semibold text-foreground mb-4 flex items-center gap-2">
              <Banknote size={15} className="text-primary" /> Top 5 Outstanding Balances
            </h3>
            <div className="space-y-3">
              {[...debtors]
                .sort((a, b) => b.outstanding - a.outstanding)
                .slice(0, 5)
                .map((d, i) => {
                  const cfg = paymentStatusConfig[d.paymentStatus];
                  return (
                    <div key={d.id} className="flex items-center gap-3">
                      <span className="text-xs font-bold text-muted-foreground w-5 shrink-0">#{i + 1}</span>
                      <div className="flex-1 min-w-0">
                        <Link href={`/customers/${d.id}`} className="text-sm font-semibold text-foreground hover:text-primary transition-colors truncate block">
                          {d.name}
                        </Link>
                        <div className="flex items-center gap-2 mt-0.5">
                          <span className={`inline-flex items-center gap-1 text-2xs font-semibold px-1.5 py-0.5 rounded-full ${cfg.className}`}>
                            {cfg.icon} {cfg.label}
                          </span>
                          <span className="text-2xs text-muted-foreground">{d.location}</span>
                        </div>
                      </div>
                      <div className="text-right shrink-0">
                        <MoneyDisplay amount={d.outstanding} compact className="text-sm font-bold text-warning" />
                        <p className="text-2xs text-muted-foreground mt-0.5">
                          {Math.round((d.outstanding / d.creditLimit) * 100)}% of limit
                        </p>
                      </div>
                    </div>
                  );
                })}
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
