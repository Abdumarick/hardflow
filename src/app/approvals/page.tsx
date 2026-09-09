'use client';
import React, { useState } from 'react';
import AppLayout, { DashboardLayout } from '@/components/AppLayout';
import KpiCard from '@/components/ui/KpiCard';
import MoneyDisplay from '@/components/ui/MoneyDisplay';
import { CheckSquare, Clock, CheckCircle2, XCircle, AlertTriangle, Tag, Package, Receipt, RotateCcw, Search, User, Calendar, Building2, MessageSquare, Check, X, Eye, TrendingUp, Filter,  } from 'lucide-react';

type ApprovalStatus = 'pending' | 'approved' | 'rejected';
type ApprovalType = 'price-override' | 'stock-adjustment' | 'expense' | 'return';

interface ApprovalItem {
  id: string;
  ref: string;
  type: ApprovalType;
  title: string;
  description: string;
  requestedBy: string;
  branch: string;
  date: string;
  status: ApprovalStatus;
  amount?: number;
  currency?: string;
  urgency: 'low' | 'medium' | 'high';
  notes?: string;
}

const approvals: ApprovalItem[] = [
  { id: 'a001', ref: 'APR-2026-0081', type: 'price-override', title: 'Price Override – Cement 50kg', description: 'Customer requested bulk discount. Selling at TZS 28,000 instead of TZS 32,500', requestedBy: 'Sarah Kimani', branch: 'Main Branch', date: '2026-09-09', status: 'pending', amount: 28000, currency: 'TZS', urgency: 'high', notes: 'Customer buying 200 bags' },
  { id: 'a002', ref: 'APR-2026-0080', type: 'stock-adjustment', title: 'Stock Write-Off – Damaged Tiles', description: 'Write off 45 boxes of cracked ceramic tiles from warehouse flood damage', requestedBy: 'Peter Odhiambo', branch: 'Warehouse', date: '2026-09-09', status: 'pending', amount: 337500, currency: 'TZS', urgency: 'medium', notes: 'Photos attached' },
  { id: 'a003', ref: 'APR-2026-0079', type: 'expense', title: 'Emergency Generator Repair', description: 'Urgent repair needed for warehouse generator – power outage risk', requestedBy: 'James Mwangi', branch: 'Warehouse', date: '2026-09-08', status: 'pending', amount: 1850000, currency: 'TZS', urgency: 'high' },
  { id: 'a004', ref: 'APR-2026-0078', type: 'return', title: 'Sales Return – Defective Pipes', description: 'Customer returning 30 PVC pipes (20mm) – manufacturing defect confirmed', requestedBy: 'Sarah Kimani', branch: 'Main Branch', date: '2026-09-08', status: 'pending', amount: 450000, currency: 'TZS', urgency: 'medium' },
  { id: 'a005', ref: 'APR-2026-0077', type: 'price-override', title: 'Price Override – Steel Rods Bundle', description: 'Contractor price for 10mm steel rods – project discount applied', requestedBy: 'David Osei', branch: 'Main Branch', date: '2026-09-07', status: 'approved', amount: 185000, currency: 'TZS', urgency: 'low' },
  { id: 'a006', ref: 'APR-2026-0076', type: 'stock-adjustment', title: 'Stock Count Correction – Nails', description: 'Physical count shows 120kg surplus vs system. Adjusting upward.', requestedBy: 'Peter Odhiambo', branch: 'Warehouse', date: '2026-09-07', status: 'approved', amount: 0, currency: 'TZS', urgency: 'low' },
  { id: 'a007', ref: 'APR-2026-0075', type: 'expense', title: 'Staff Overtime – September', description: 'Overtime pay for 8 warehouse staff during peak season', requestedBy: 'James Mwangi', branch: 'Warehouse', date: '2026-09-06', status: 'rejected', amount: 960000, currency: 'TZS', urgency: 'medium', notes: 'Rejected: budget exceeded' },
  { id: 'a008', ref: 'APR-2026-0074', type: 'return', title: 'Purchase Return – Wrong Delivery', description: 'Supplier delivered 200mm pipes instead of 150mm. Full return requested.', requestedBy: 'Peter Odhiambo', branch: 'Warehouse', date: '2026-09-06', status: 'approved', amount: 2100000, currency: 'TZS', urgency: 'high' },
  { id: 'a009', ref: 'APR-2026-0073', type: 'price-override', title: 'Price Override – Paint Bulk Order', description: 'NGO project discount – 15% below standard price', requestedBy: 'Sarah Kimani', branch: 'Main Branch', date: '2026-09-05', status: 'pending', amount: 340000, currency: 'TZS', urgency: 'medium' },
  { id: 'a010', ref: 'APR-2026-0072', type: 'stock-adjustment', title: 'Expiry Write-Off – Adhesive', description: 'Expired tile adhesive (24 bags) – past shelf life, cannot sell', requestedBy: 'Peter Odhiambo', branch: 'Warehouse', date: '2026-09-05', status: 'pending', amount: 192000, currency: 'TZS', urgency: 'low' },
];

const typeConfig: Record<ApprovalType, { label: string; icon: React.ReactNode; color: string }> = {
  'price-override':   { label: 'Price Override',    icon: <Tag size={14} />,       color: 'bg-purple-500/10 text-purple-600 border-purple-200' },
  'stock-adjustment': { label: 'Stock Adjustment',  icon: <Package size={14} />,   color: 'bg-blue-500/10 text-blue-600 border-blue-200' },
  'expense':          { label: 'Expense',            icon: <Receipt size={14} />,   color: 'bg-orange-500/10 text-orange-600 border-orange-200' },
  'return':           { label: 'Return',             icon: <RotateCcw size={14} />, color: 'bg-teal-500/10 text-teal-600 border-teal-200' },
};

const statusConfig: Record<ApprovalStatus, { label: string; color: string; icon: React.ReactNode }> = {
  pending:  { label: 'Pending',  color: 'bg-warning/10 text-warning border-warning/20',   icon: <Clock size={13} /> },
  approved: { label: 'Approved', color: 'bg-success/10 text-success border-success/20',   icon: <CheckCircle2 size={13} /> },
  rejected: { label: 'Rejected', color: 'bg-danger/10 text-danger border-danger/20',      icon: <XCircle size={13} /> },
};

const urgencyConfig = {
  high:   'bg-danger/10 text-danger border-danger/20',
  medium: 'bg-warning/10 text-warning border-warning/20',
  low:    'bg-muted text-muted-foreground border-border',
};

export default function ApprovalsPage() {
  const [layout, setLayout] = useState<DashboardLayout>('classic');
  const [activeTab, setActiveTab] = useState<ApprovalType | 'all'>('all');
  const [statusFilter, setStatusFilter] = useState<ApprovalStatus | 'all'>('pending');
  const [search, setSearch] = useState('');
  const [selectedItem, setSelectedItem] = useState<ApprovalItem | null>(null);
  const [actionNote, setActionNote] = useState('');

  const tabs: { key: ApprovalType | 'all'; label: string; icon: React.ReactNode }[] = [
    { key: 'all',              label: 'All',             icon: <CheckSquare size={15} /> },
    { key: 'price-override',   label: 'Price Overrides', icon: <Tag size={15} /> },
    { key: 'stock-adjustment', label: 'Stock Adjustments', icon: <Package size={15} /> },
    { key: 'expense',          label: 'Expenses',        icon: <Receipt size={15} /> },
    { key: 'return',           label: 'Returns',         icon: <RotateCcw size={15} /> },
  ];

  const filtered = approvals.filter(a => {
    const matchTab = activeTab === 'all' || a.type === activeTab;
    const matchStatus = statusFilter === 'all' || a.status === statusFilter;
    const matchSearch = !search || a.title.toLowerCase().includes(search.toLowerCase()) || a.ref.toLowerCase().includes(search.toLowerCase()) || a.requestedBy.toLowerCase().includes(search.toLowerCase());
    return matchTab && matchStatus && matchSearch;
  });

  const pendingCount = approvals.filter(a => a.status === 'pending').length;
  const approvedToday = approvals.filter(a => a.status === 'approved' && a.date === '2026-09-09').length;
  const highUrgency = approvals.filter(a => a.status === 'pending' && a.urgency === 'high').length;
  const totalPendingValue = approvals.filter(a => a.status === 'pending').reduce((s, a) => s + (a.amount || 0), 0);

  return (
    <AppLayout layout={layout} onLayoutChange={setLayout}>
      <div className="p-4 md:p-6 space-y-5">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-bold text-foreground flex items-center gap-2">
              <CheckSquare size={22} className="text-primary" />
              Approval Center
            </h1>
            <p className="text-sm text-muted-foreground mt-0.5">Central hub for price overrides, stock adjustments, expense and return approvals</p>
          </div>
          <div className="flex items-center gap-2">
            {pendingCount > 0 && (
              <span className="flex items-center gap-1.5 px-3 py-1.5 bg-warning/10 text-warning text-sm font-medium rounded-lg border border-warning/20">
                <AlertTriangle size={14} /> {pendingCount} Pending
              </span>
            )}
          </div>
        </div>

        {/* KPIs */}
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <KpiCard title="Pending Approvals" value={String(pendingCount)} icon={<Clock size={18} />} variant="warning" />
          <KpiCard title="High Urgency" value={String(highUrgency)} icon={<AlertTriangle size={18} />} variant="danger" />
          <KpiCard title="Approved Today" value={String(approvedToday)} icon={<CheckCircle2 size={18} />} variant="success" />
          <KpiCard title="Pending Value" value={totalPendingValue} isMoney currency="TZS" icon={<TrendingUp size={18} />} />
        </div>

        {/* Type Tabs */}
        <div className="flex gap-1 p-1 bg-muted/50 rounded-xl border border-border overflow-x-auto">
          {tabs.map(tab => (
            <button
              key={tab.key}
              onClick={() => setActiveTab(tab.key)}
              className={`flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg whitespace-nowrap transition-all ${
                activeTab === tab.key
                  ? 'bg-card text-foreground shadow-sm border border-border'
                  : 'text-muted-foreground hover:text-foreground'
              }`}
            >
              {tab.icon} {tab.label}
              {tab.key !== 'all' && (
                <span className="ml-1 text-xs bg-warning/10 text-warning px-1.5 py-0.5 rounded-full font-bold">
                  {approvals.filter(a => a.type === tab.key && a.status === 'pending').length}
                </span>
              )}
            </button>
          ))}
        </div>

        {/* Search & Status Filter */}
        <div className="flex flex-col sm:flex-row gap-2">
          <div className="relative flex-1">
            <Search size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
            <input
              type="text"
              placeholder="Search by reference, title, or requester..."
              value={search}
              onChange={e => setSearch(e.target.value)}
              className="w-full pl-9 pr-4 py-2 text-sm bg-card border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground placeholder:text-muted-foreground"
            />
          </div>
          <div className="flex gap-1 p-1 bg-muted/50 rounded-lg border border-border">
            {(['all', 'pending', 'approved', 'rejected'] as const).map(s => (
              <button
                key={s}
                onClick={() => setStatusFilter(s)}
                className={`px-3 py-1.5 text-xs font-medium rounded-md capitalize transition-all ${
                  statusFilter === s ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'
                }`}
              >
                {s === 'all' ? 'All' : s.charAt(0).toUpperCase() + s.slice(1)}
              </button>
            ))}
          </div>
        </div>

        {/* Approval Cards */}
        <div className="space-y-2">
          {filtered.length === 0 ? (
            <div className="text-center py-12 text-muted-foreground">
              <CheckCircle2 size={40} className="mx-auto mb-3 opacity-30" />
              <p className="font-medium">No approvals found</p>
              <p className="text-sm mt-1">Try adjusting your filters</p>
            </div>
          ) : (
            filtered.map(item => {
              const typeCfg = typeConfig[item.type];
              const statusCfg = statusConfig[item.status];
              return (
                <div
                  key={item.id}
                  className={`bg-card border rounded-xl p-4 transition-all hover:shadow-sm ${
                    item.status === 'pending' && item.urgency === 'high' ? 'border-danger/30' : 'border-border'
                  }`}
                >
                  <div className="flex flex-col sm:flex-row sm:items-start gap-3">
                    {/* Left */}
                    <div className="flex-1 min-w-0">
                      <div className="flex flex-wrap items-center gap-2 mb-1.5">
                        <span className={`flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full border ${typeCfg.color}`}>
                          {typeCfg.icon} {typeCfg.label}
                        </span>
                        <span className={`flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full border ${statusCfg.color}`}>
                          {statusCfg.icon} {statusCfg.label}
                        </span>
                        {item.urgency === 'high' && (
                          <span className={`text-xs font-medium px-2 py-0.5 rounded-full border ${urgencyConfig.high}`}>
                            High Urgency
                          </span>
                        )}
                        <span className="text-xs text-muted-foreground font-mono">{item.ref}</span>
                      </div>
                      <h3 className="font-semibold text-foreground text-sm">{item.title}</h3>
                      <p className="text-xs text-muted-foreground mt-0.5 line-clamp-2">{item.description}</p>
                      {item.notes && (
                        <p className="text-xs text-muted-foreground mt-1 italic flex items-center gap-1">
                          <MessageSquare size={11} /> {item.notes}
                        </p>
                      )}
                      <div className="flex flex-wrap items-center gap-3 mt-2 text-xs text-muted-foreground">
                        <span className="flex items-center gap-1"><User size={11} /> {item.requestedBy}</span>
                        <span className="flex items-center gap-1"><Building2 size={11} /> {item.branch}</span>
                        <span className="flex items-center gap-1"><Calendar size={11} /> {item.date}</span>
                        {item.amount != null && item.amount > 0 && (
                          <span className="font-semibold text-foreground">
                            <MoneyDisplay amount={item.amount} currency={item.currency || 'TZS'} />
                          </span>
                        )}
                      </div>
                    </div>

                    {/* Actions */}
                    <div className="flex items-center gap-2 shrink-0">
                      <button
                        onClick={() => setSelectedItem(selectedItem?.id === item.id ? null : item)}
                        className="flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg border border-border bg-muted/50 hover:bg-muted text-foreground transition-colors"
                      >
                        <Eye size={13} /> View
                      </button>
                      {item.status === 'pending' && (
                        <>
                          <button className="flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg bg-success/10 text-success border border-success/20 hover:bg-success/20 transition-colors font-medium">
                            <Check size={13} /> Approve
                          </button>
                          <button className="flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg bg-danger/10 text-danger border border-danger/20 hover:bg-danger/20 transition-colors font-medium">
                            <X size={13} /> Reject
                          </button>
                        </>
                      )}
                    </div>
                  </div>

                  {/* Expanded Detail */}
                  {selectedItem?.id === item.id && (
                    <div className="mt-4 pt-4 border-t border-border">
                      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                        <div className="bg-muted/40 rounded-lg p-3">
                          <p className="text-xs text-muted-foreground mb-1">Type</p>
                          <p className="text-sm font-semibold text-foreground">{typeCfg.label}</p>
                        </div>
                        <div className="bg-muted/40 rounded-lg p-3">
                          <p className="text-xs text-muted-foreground mb-1">Requested By</p>
                          <p className="text-sm font-semibold text-foreground">{item.requestedBy}</p>
                        </div>
                        <div className="bg-muted/40 rounded-lg p-3">
                          <p className="text-xs text-muted-foreground mb-1">Branch</p>
                          <p className="text-sm font-semibold text-foreground">{item.branch}</p>
                        </div>
                        <div className="bg-muted/40 rounded-lg p-3">
                          <p className="text-xs text-muted-foreground mb-1">Amount</p>
                          <p className="text-sm font-semibold text-foreground">
                            {item.amount ? <MoneyDisplay amount={item.amount} currency={item.currency || 'TZS'} /> : '—'}
                          </p>
                        </div>
                      </div>
                      {item.status === 'pending' && (
                        <div className="flex flex-col sm:flex-row gap-2">
                          <input
                            type="text"
                            placeholder="Add a note (optional)..."
                            value={actionNote}
                            onChange={e => setActionNote(e.target.value)}
                            className="flex-1 px-3 py-2 text-sm bg-card border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground placeholder:text-muted-foreground"
                          />
                          <button className="flex items-center gap-1.5 px-4 py-2 text-sm rounded-lg bg-success text-white font-medium hover:bg-success/90 transition-colors">
                            <Check size={14} /> Approve
                          </button>
                          <button className="flex items-center gap-1.5 px-4 py-2 text-sm rounded-lg bg-danger text-white font-medium hover:bg-danger/90 transition-colors">
                            <X size={14} /> Reject
                          </button>
                        </div>
                      )}
                    </div>
                  )}
                </div>
              );
            })
          )}
        </div>
      </div>
    </AppLayout>
  );
}
