'use client';
import React, { useState, useMemo } from 'react';
import Link from 'next/link';
import AppLayout, { DashboardLayout } from '@/components/AppLayout';
import KpiCard from '@/components/ui/KpiCard';

import MoneyDisplay from '@/components/ui/MoneyDisplay';
import { ShoppingBag, Plus, Search, Download, RefreshCw, ChevronUp, ChevronDown, ChevronsUpDown, Eye, Truck, CheckCircle2, Clock, PackageCheck, AlertCircle, X, SlidersHorizontal, FileText,  } from 'lucide-react';

// ─── Types ────────────────────────────────────────────────────────────────────

type PurchaseStatus = 'created' | 'ordered' | 'partially-received' | 'completed';

interface PurchaseOrder {
  id: string;
  poNumber: string;
  supplier: string;
  supplierPhone: string;
  branch: string;
  createdDate: string;
  expectedDate: string;
  status: PurchaseStatus;
  totalItems: number;
  totalAmount: number;
  receivedAmount: number;
  pendingAmount: number;
  createdBy: string;
  notes: string;
}

type SortField = 'poNumber' | 'supplier' | 'createdDate' | 'expectedDate' | 'totalAmount' | 'status';
type SortDir = 'asc' | 'desc';

// ─── Mock Data ─────────────────────────────────────────────────────────────────

const purchaseOrders: PurchaseOrder[] = [
  { id: 'po001', poNumber: 'PO-2026-0091', supplier: 'Bamburi Cement Ltd', supplierPhone: '+255 22 211 0000', branch: 'Main Branch', createdDate: '2026-09-01', expectedDate: '2026-09-08', status: 'partially-received', totalItems: 5, totalAmount: 12400000, receivedAmount: 7440000, pendingAmount: 4960000, createdBy: 'James Mwangi', notes: 'Urgent order for ongoing project' },
  { id: 'po002', poNumber: 'PO-2026-0090', supplier: 'Mabati Rolling Mills', supplierPhone: '+255 22 215 3300', branch: 'Main Branch', createdDate: '2026-08-30', expectedDate: '2026-09-05', status: 'ordered', totalItems: 3, totalAmount: 8750000, receivedAmount: 0, pendingAmount: 8750000, createdBy: 'Sarah Kimani', notes: '' },
  { id: 'po003', poNumber: 'PO-2026-0089', supplier: 'Steel Structures EA', supplierPhone: '+255 754 123 456', branch: 'Warehouse', createdDate: '2026-08-28', expectedDate: '2026-09-03', status: 'completed', totalItems: 8, totalAmount: 21600000, receivedAmount: 21600000, pendingAmount: 0, createdBy: 'James Mwangi', notes: 'Full delivery confirmed' },
  { id: 'po004', poNumber: 'PO-2026-0088', supplier: 'Twiga Cement', supplierPhone: '+255 22 218 0000', branch: 'Main Branch', createdDate: '2026-08-27', expectedDate: '2026-09-02', status: 'completed', totalItems: 4, totalAmount: 9600000, receivedAmount: 9600000, pendingAmount: 0, createdBy: 'Peter Odhiambo', notes: '' },
  { id: 'po005', poNumber: 'PO-2026-0087', supplier: 'Unga Hardware Supplies', supplierPhone: '+255 713 456 789', branch: 'Main Branch', createdDate: '2026-08-25', expectedDate: '2026-09-01', status: 'partially-received', totalItems: 12, totalAmount: 5800000, receivedAmount: 2320000, pendingAmount: 3480000, createdBy: 'Sarah Kimani', notes: 'Supplier confirmed partial delivery' },
  { id: 'po006', poNumber: 'PO-2026-0086', supplier: 'Karibu Paints Ltd', supplierPhone: '+255 22 213 5500', branch: 'Main Branch', createdDate: '2026-08-24', expectedDate: '2026-08-30', status: 'ordered', totalItems: 6, totalAmount: 3200000, receivedAmount: 0, pendingAmount: 3200000, createdBy: 'James Mwangi', notes: '' },
  { id: 'po007', poNumber: 'PO-2026-0085', supplier: 'Simba Plumbing Supplies', supplierPhone: '+255 784 321 654', branch: 'Warehouse', createdDate: '2026-08-22', expectedDate: '2026-08-28', status: 'completed', totalItems: 9, totalAmount: 4150000, receivedAmount: 4150000, pendingAmount: 0, createdBy: 'Peter Odhiambo', notes: '' },
  { id: 'po008', poNumber: 'PO-2026-0084', supplier: 'East Africa Tiles', supplierPhone: '+255 22 219 7700', branch: 'Main Branch', createdDate: '2026-08-20', expectedDate: '2026-08-26', status: 'created', totalItems: 7, totalAmount: 16800000, receivedAmount: 0, pendingAmount: 16800000, createdBy: 'James Mwangi', notes: 'Awaiting supplier confirmation' },
  { id: 'po009', poNumber: 'PO-2026-0083', supplier: 'Bamburi Cement Ltd', supplierPhone: '+255 22 211 0000', branch: 'Warehouse', createdDate: '2026-08-18', expectedDate: '2026-08-24', status: 'completed', totalItems: 3, totalAmount: 7200000, receivedAmount: 7200000, pendingAmount: 0, createdBy: 'Sarah Kimani', notes: '' },
  { id: 'po010', poNumber: 'PO-2026-0082', supplier: 'Mabati Rolling Mills', supplierPhone: '+255 22 215 3300', branch: 'Main Branch', createdDate: '2026-08-15', expectedDate: '2026-08-21', status: 'partially-received', totalItems: 5, totalAmount: 11200000, receivedAmount: 4480000, pendingAmount: 6720000, createdBy: 'James Mwangi', notes: 'Awaiting second delivery batch' },
];

// ─── Status Config ─────────────────────────────────────────────────────────────

const statusConfig: Record<PurchaseStatus, { label: string; color: string; icon: React.ReactNode; step: number }> = {
  created:            { label: 'Created',            color: 'bg-muted text-muted-foreground border border-border',          icon: <FileText size={12} />,    step: 1 },
  ordered:            { label: 'Ordered',            color: 'bg-info/10 text-info border border-info/20',                   icon: <Truck size={12} />,       step: 2 },
  'partially-received': { label: 'Partially Received', color: 'bg-warning/10 text-warning border border-warning/20',       icon: <PackageCheck size={12} />, step: 3 },
  completed:          { label: 'Completed',          color: 'bg-success/10 text-success border border-success/20',          icon: <CheckCircle2 size={12} />, step: 4 },
};

const statusSteps: PurchaseStatus[] = ['created', 'ordered', 'partially-received', 'completed'];

// ─── Status Progress Bar ───────────────────────────────────────────────────────

function StatusProgress({ status }: { status: PurchaseStatus }) {
  const currentStep = statusConfig[status].step;
  return (
    <div className="flex items-center gap-0.5">
      {statusSteps.map((s, i) => {
        const step = statusConfig[s].step;
        const isActive = step <= currentStep;
        const isCurrent = step === currentStep;
        return (
          <React.Fragment key={s}>
            <div
              title={statusConfig[s].label}
              className={`h-1.5 rounded-full transition-all ${
                i === 0 ? 'w-6' : i === statusSteps.length - 1 ? 'w-6' : 'w-5'
              } ${
                isActive
                  ? isCurrent
                    ? status === 'completed' ? 'bg-success' : status === 'partially-received' ? 'bg-warning' : status === 'ordered' ? 'bg-info' : 'bg-muted-foreground'
                    : 'bg-success' :'bg-border'
              }`}
            />
            {i < statusSteps.length - 1 && (
              <div className={`h-px w-1 ${step < currentStep ? 'bg-success' : 'bg-border'}`} />
            )}
          </React.Fragment>
        );
      })}
    </div>
  );
}

// ─── Sort Icon ─────────────────────────────────────────────────────────────────

function SortIcon({ field, sortField, sortDir }: { field: SortField; sortField: SortField; sortDir: SortDir }) {
  if (sortField !== field) return <ChevronsUpDown size={13} className="text-muted-foreground/50" />;
  return sortDir === 'asc' ? <ChevronUp size={13} className="text-primary" /> : <ChevronDown size={13} className="text-primary" />;
}

// ─── Main Page ─────────────────────────────────────────────────────────────────

export default function PurchasesPage() {
  const [layout, setLayout] = useState<DashboardLayout>('classic');
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState<PurchaseStatus | 'all'>('all');
  const [branchFilter, setBranchFilter] = useState('all');
  const [sortField, setSortField] = useState<SortField>('createdDate');
  const [sortDir, setSortDir] = useState<SortDir>('desc');
  const [showFilters, setShowFilters] = useState(false);

  const handleSort = (field: SortField) => {
    if (sortField === field) setSortDir(d => d === 'asc' ? 'desc' : 'asc');
    else { setSortField(field); setSortDir('asc'); }
  };

  const filtered = useMemo(() => {
    let data = [...purchaseOrders];
    if (search) {
      const q = search.toLowerCase();
      data = data.filter(p =>
        p.poNumber.toLowerCase().includes(q) ||
        p.supplier.toLowerCase().includes(q) ||
        p.createdBy.toLowerCase().includes(q)
      );
    }
    if (statusFilter !== 'all') data = data.filter(p => p.status === statusFilter);
    if (branchFilter !== 'all') data = data.filter(p => p.branch === branchFilter);
    data.sort((a, b) => {
      let av: string | number = a[sortField as keyof PurchaseOrder] as string | number;
      let bv: string | number = b[sortField as keyof PurchaseOrder] as string | number;
      if (typeof av === 'string') av = av.toLowerCase();
      if (typeof bv === 'string') bv = bv.toLowerCase();
      if (av < bv) return sortDir === 'asc' ? -1 : 1;
      if (av > bv) return sortDir === 'asc' ? 1 : -1;
      return 0;
    });
    return data;
  }, [search, statusFilter, branchFilter, sortField, sortDir]);

  // KPIs
  const totalOrders = purchaseOrders.length;
  const pendingOrders = purchaseOrders.filter(p => p.status !== 'completed').length;
  const totalValue = purchaseOrders.reduce((s, p) => s + p.totalAmount, 0);
  const pendingValue = purchaseOrders.reduce((s, p) => s + p.pendingAmount, 0);

  return (
    <AppLayout layout={layout} onLayoutChange={setLayout}>
      <div className="p-4 md:p-6 space-y-5">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-bold text-foreground flex items-center gap-2">
              <ShoppingBag size={22} className="text-primary" />
              Purchases
            </h1>
            <p className="text-sm text-muted-foreground mt-0.5">Manage purchase orders and track delivery status</p>
          </div>
          <div className="flex items-center gap-2">
            <button className="btn-ghost text-sm flex items-center gap-1.5 px-3 py-2 rounded-lg border border-border hover:bg-muted transition-colors">
              <Download size={15} /> Export
            </button>
            <Link
              href="/purchases/new"
              className="flex items-center gap-1.5 px-4 py-2 bg-primary text-primary-foreground text-sm font-medium rounded-lg hover:bg-primary/90 transition-colors"
            >
              <Plus size={15} /> New Purchase
            </Link>
          </div>
        </div>

        {/* KPI Cards */}
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <KpiCard title="Total Orders" value={String(totalOrders)} icon={<ShoppingBag size={18} />} trend="neutral" />
          <KpiCard title="Pending Orders" value={String(pendingOrders)} icon={<Clock size={18} />} trend="down" trendValue="Awaiting delivery" />
          <KpiCard title="Total PO Value" value={<MoneyDisplay amount={totalValue} currency="TZS" />} icon={<FileText size={18} />} trend="neutral" />
          <KpiCard title="Pending Value" value={<MoneyDisplay amount={pendingValue} currency="TZS" />} icon={<AlertCircle size={18} />} trend="down" trendValue="Not yet received" />
        </div>

        {/* Status Pipeline */}
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
          {statusSteps.map(s => {
            const count = purchaseOrders.filter(p => p.status === s).length;
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
              placeholder="Search by PO number, supplier, or created by..."
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
                onChange={e => setStatusFilter(e.target.value as PurchaseStatus | 'all')}
                className="text-sm bg-card border border-border rounded-lg px-3 py-1.5 text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30"
              >
                <option value="all">All Statuses</option>
                {statusSteps.map(s => <option key={s} value={s}>{statusConfig[s].label}</option>)}
              </select>
            </div>
            <div className="flex flex-col gap-1">
              <label className="text-xs text-muted-foreground font-medium">Branch</label>
              <select
                value={branchFilter}
                onChange={e => setBranchFilter(e.target.value)}
                className="text-sm bg-card border border-border rounded-lg px-3 py-1.5 text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30"
              >
                <option value="all">All Branches</option>
                <option value="Main Branch">Main Branch</option>
                <option value="Warehouse">Warehouse</option>
              </select>
            </div>
            <div className="flex items-end">
              <button
                onClick={() => { setStatusFilter('all'); setBranchFilter('all'); setSearch(''); }}
                className="text-sm text-muted-foreground hover:text-foreground px-3 py-1.5 rounded-lg border border-border bg-card hover:bg-muted transition-colors"
              >
                Clear All
              </button>
            </div>
          </div>
        )}

        {/* Table */}
        <div className="bg-card border border-border rounded-xl overflow-hidden">
          <div className="flex items-center justify-between px-4 py-3 border-b border-border">
            <p className="text-sm text-muted-foreground">
              Showing <span className="font-semibold text-foreground">{filtered.length}</span> of {totalOrders} orders
            </p>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border bg-muted/30">
                  {([
                    { field: 'poNumber', label: 'PO Number' },
                    { field: 'supplier', label: 'Supplier' },
                    { field: 'createdDate', label: 'Created' },
                    { field: 'expectedDate', label: 'Expected' },
                    { field: 'status', label: 'Status' },
                    { field: null, label: 'Progress' },
                    { field: 'totalAmount', label: 'Total Amount' },
                    { field: null, label: 'Received' },
                    { field: null, label: 'Actions' },
                  ] as { field: SortField | null; label: string }[]).map(col => (
                    <th
                      key={col.label}
                      onClick={() => col.field && handleSort(col.field)}
                      className={`px-4 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide whitespace-nowrap ${col.field ? 'cursor-pointer hover:text-foreground select-none' : ''}`}
                    >
                      <div className="flex items-center gap-1">
                        {col.label}
                        {col.field && <SortIcon field={col.field} sortField={sortField} sortDir={sortDir} />}
                      </div>
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {filtered.length === 0 ? (
                  <tr>
                    <td colSpan={9} className="px-4 py-12 text-center text-muted-foreground text-sm">
                      No purchase orders found matching your filters.
                    </td>
                  </tr>
                ) : (
                  filtered.map(po => {
                    const cfg = statusConfig[po.status];
                    const receivedPct = po.totalAmount > 0 ? Math.round((po.receivedAmount / po.totalAmount) * 100) : 0;
                    return (
                      <tr key={po.id} className="hover:bg-muted/30 transition-colors group">
                        <td className="px-4 py-3 font-mono text-xs font-semibold text-primary whitespace-nowrap">
                          {po.poNumber}
                        </td>
                        <td className="px-4 py-3">
                          <p className="font-medium text-foreground text-sm">{po.supplier}</p>
                          <p className="text-xs text-muted-foreground">{po.supplierPhone}</p>
                        </td>
                        <td className="px-4 py-3 text-xs text-muted-foreground whitespace-nowrap">{po.createdDate}</td>
                        <td className="px-4 py-3 text-xs text-muted-foreground whitespace-nowrap">{po.expectedDate}</td>
                        <td className="px-4 py-3">
                          <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium ${cfg.color}`}>
                            {cfg.icon}
                            {cfg.label}
                          </span>
                        </td>
                        <td className="px-4 py-3">
                          <div className="space-y-1">
                            <StatusProgress status={po.status} />
                            <p className="text-2xs text-muted-foreground">{receivedPct}% received</p>
                          </div>
                        </td>
                        <td className="px-4 py-3 text-right whitespace-nowrap">
                          <MoneyDisplay amount={po.totalAmount} currency="TZS" className="font-semibold text-foreground text-sm" />
                        </td>
                        <td className="px-4 py-3 text-right whitespace-nowrap">
                          <MoneyDisplay amount={po.receivedAmount} currency="TZS" className="text-success text-sm" />
                          {po.pendingAmount > 0 && (
                            <p className="text-2xs text-warning">
                              <MoneyDisplay amount={po.pendingAmount} currency="TZS" /> pending
                            </p>
                          )}
                        </td>
                        <td className="px-4 py-3">
                          <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <Link
                              href={`/purchases/${po.id}`}
                              className="p-1.5 rounded-lg hover:bg-primary/10 text-muted-foreground hover:text-primary transition-colors"
                              title="View Details"
                            >
                              <Eye size={14} />
                            </Link>
                            {po.status !== 'completed' && (
                              <Link
                                href={`/purchases/${po.id}/receive`}
                                className="p-1.5 rounded-lg hover:bg-success/10 text-muted-foreground hover:text-success transition-colors"
                                title="Receive Goods"
                              >
                                <PackageCheck size={14} />
                              </Link>
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
