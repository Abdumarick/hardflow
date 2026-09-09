'use client';
import React, { useState } from 'react';
import AppLayout, { DashboardLayout } from '@/components/AppLayout';
import KpiCard from '@/components/ui/KpiCard';
import MoneyDisplay from '@/components/ui/MoneyDisplay';
import { RotateCcw, Plus, Search, ShoppingCart, ShoppingBag, Clock, CheckCircle2, XCircle, User, Building2, Calendar, Package, Eye, Check, X, MessageSquare, ChevronRight, FileText,  } from 'lucide-react';

type ReturnType = 'sales' | 'purchase';
type ReturnStatus = 'draft' | 'pending' | 'approved' | 'processed' | 'rejected';
type ReturnReason = 'defective' | 'wrong-item' | 'excess-quantity' | 'customer-change' | 'expired' | 'damaged-transit' | 'other';

interface ReturnItem {
  productName: string;
  sku: string;
  qty: number;
  unitPrice: number;
  total: number;
}

interface ReturnRecord {
  id: string;
  ref: string;
  type: ReturnType;
  status: ReturnStatus;
  reason: ReturnReason;
  originalRef: string;
  party: string;
  branch: string;
  date: string;
  requestedBy: string;
  approvedBy: string | null;
  items: ReturnItem[];
  totalAmount: number;
  currency: string;
  notes?: string;
}

const returns: ReturnRecord[] = [
  {
    id: 'r001', ref: 'RET-2026-0031', type: 'sales', status: 'pending', reason: 'defective',
    originalRef: 'INV-2026-1241', party: 'Karibu Contractors Ltd', branch: 'Main Branch',
    date: '2026-09-09', requestedBy: 'Sarah Kimani', approvedBy: null,
    items: [{ productName: 'PVC Pipe 20mm', sku: 'PVC-020', qty: 30, unitPrice: 15000, total: 450000 }],
    totalAmount: 450000, currency: 'TZS', notes: 'Manufacturing defect – confirmed by supplier',
  },
  {
    id: 'r002', ref: 'RET-2026-0030', type: 'purchase', status: 'approved', reason: 'wrong-item',
    originalRef: 'PO-2026-0089', party: 'Simba Hardware Suppliers', branch: 'Warehouse',
    date: '2026-09-08', requestedBy: 'Peter Odhiambo', approvedBy: 'James Mwangi',
    items: [
      { productName: 'Steel Rod 200mm', sku: 'STL-200', qty: 50, unitPrice: 8500, total: 425000 },
      { productName: 'Steel Rod 150mm', sku: 'STL-150', qty: 50, unitPrice: 7200, total: 360000 },
    ],
    totalAmount: 785000, currency: 'TZS', notes: 'Delivered 200mm instead of 150mm',
  },
  {
    id: 'r003', ref: 'RET-2026-0029', type: 'sales', status: 'processed', reason: 'customer-change',
    originalRef: 'INV-2026-1238', party: 'Mwangi Building Supplies', branch: 'Main Branch',
    date: '2026-09-07', requestedBy: 'David Osei', approvedBy: 'James Mwangi',
    items: [{ productName: 'Ceramic Tiles 30x30', sku: 'TIL-3030', qty: 20, unitPrice: 12500, total: 250000 }],
    totalAmount: 250000, currency: 'TZS',
  },
  {
    id: 'r004', ref: 'RET-2026-0028', type: 'purchase', status: 'pending', reason: 'damaged-transit',
    originalRef: 'PO-2026-0085', party: 'East Africa Cement Co.', branch: 'Warehouse',
    date: '2026-09-07', requestedBy: 'Peter Odhiambo', approvedBy: null,
    items: [{ productName: 'Portland Cement 50kg', sku: 'CEM-50', qty: 40, unitPrice: 32500, total: 1300000 }],
    totalAmount: 1300000, currency: 'TZS', notes: 'Bags damaged during transit – photos attached',
  },
  {
    id: 'r005', ref: 'RET-2026-0027', type: 'sales', status: 'rejected', reason: 'other',
    originalRef: 'INV-2026-1230', party: 'Juma Hardware Store', branch: 'Kariakoo Branch',
    date: '2026-09-06', requestedBy: 'Mary Achieng', approvedBy: 'James Mwangi',
    items: [{ productName: 'Paint – White 20L', sku: 'PNT-W20', qty: 5, unitPrice: 45000, total: 225000 }],
    totalAmount: 225000, currency: 'TZS', notes: 'Rejected: return window expired (>14 days)',
  },
  {
    id: 'r006', ref: 'RET-2026-0026', type: 'purchase', status: 'draft', reason: 'excess-quantity',
    originalRef: 'PO-2026-0082', party: 'Nairobi Tile Imports', branch: 'Warehouse',
    date: '2026-09-05', requestedBy: 'Peter Odhiambo', approvedBy: null,
    items: [{ productName: 'Granite Tiles 60x60', sku: 'TIL-GR60', qty: 15, unitPrice: 28000, total: 420000 }],
    totalAmount: 420000, currency: 'TZS', notes: 'Received 15 extra boxes – returning surplus',
  },
];

const statusConfig: Record<ReturnStatus, { label: string; color: string; icon: React.ReactNode; step: number }> = {
  draft:     { label: 'Draft',     color: 'bg-muted text-muted-foreground border-border',          icon: <FileText size={13} />,     step: 1 },
  pending:   { label: 'Pending',   color: 'bg-warning/10 text-warning border-warning/20',          icon: <Clock size={13} />,        step: 2 },
  approved:  { label: 'Approved',  color: 'bg-info/10 text-info border-info/20',                   icon: <CheckCircle2 size={13} />, step: 3 },
  processed: { label: 'Processed', color: 'bg-success/10 text-success border-success/20',          icon: <RotateCcw size={13} />,    step: 4 },
  rejected:  { label: 'Rejected',  color: 'bg-danger/10 text-danger border-danger/20',             icon: <XCircle size={13} />,      step: 5 },
};

const reasonLabels: Record<ReturnReason, string> = {
  'defective':        'Defective Product',
  'wrong-item':       'Wrong Item Delivered',
  'excess-quantity':  'Excess Quantity',
  'customer-change':  'Customer Changed Mind',
  'expired':          'Expired / Past Shelf Life',
  'damaged-transit':  'Damaged in Transit',
  'other':            'Other',
};

export default function ReturnsPage() {
  const [layout, setLayout] = useState<DashboardLayout>('classic');
  const [typeFilter, setTypeFilter] = useState<ReturnType | 'all'>('all');
  const [statusFilter, setStatusFilter] = useState<ReturnStatus | 'all'>('all');
  const [search, setSearch] = useState('');
  const [expandedId, setExpandedId] = useState<string | null>(null);
  const [actionNote, setActionNote] = useState('');

  const filtered = returns.filter(r => {
    const matchType = typeFilter === 'all' || r.type === typeFilter;
    const matchStatus = statusFilter === 'all' || r.status === statusFilter;
    const matchSearch = !search || r.ref.toLowerCase().includes(search.toLowerCase()) || r.party.toLowerCase().includes(search.toLowerCase()) || r.originalRef.toLowerCase().includes(search.toLowerCase());
    return matchType && matchStatus && matchSearch;
  });

  const totalReturns = returns.length;
  const pendingCount = returns.filter(r => r.status === 'pending').length;
  const salesReturnsValue = returns.filter(r => r.type === 'sales').reduce((s, r) => s + r.totalAmount, 0);
  const purchaseReturnsValue = returns.filter(r => r.type === 'purchase').reduce((s, r) => s + r.totalAmount, 0);

  return (
    <AppLayout layout={layout} onLayoutChange={setLayout}>
      <div className="p-4 md:p-6 space-y-5">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-bold text-foreground flex items-center gap-2">
              <RotateCcw size={22} className="text-primary" />
              Returns Management
            </h1>
            <p className="text-sm text-muted-foreground mt-0.5">Manage sales and purchase returns with full approval workflow</p>
          </div>
          <div className="flex items-center gap-2">
            <button className="flex items-center gap-1.5 px-4 py-2 bg-primary text-primary-foreground text-sm font-medium rounded-lg hover:bg-primary/90 transition-colors">
              <Plus size={15} /> New Return
            </button>
          </div>
        </div>

        {/* KPIs */}
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <KpiCard title="Total Returns" value={String(totalReturns)} icon={<RotateCcw size={18} />} />
          <KpiCard title="Pending Approval" value={String(pendingCount)} icon={<Clock size={18} />} variant={pendingCount > 0 ? 'warning' : 'default'} />
          <KpiCard title="Sales Returns" value={salesReturnsValue} isMoney currency="TZS" icon={<ShoppingCart size={18} />} variant="danger" />
          <KpiCard title="Purchase Returns" value={purchaseReturnsValue} isMoney currency="TZS" icon={<ShoppingBag size={18} />} variant="warning" />
        </div>

        {/* Type Switcher */}
        <div className="flex gap-2">
          <div className="flex gap-1 p-1 bg-muted/50 rounded-xl border border-border">
            {(['all', 'sales', 'purchase'] as const).map(t => (
              <button
                key={t}
                onClick={() => setTypeFilter(t)}
                className={`flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-lg transition-all ${
                  typeFilter === t ? 'bg-card text-foreground shadow-sm border border-border' : 'text-muted-foreground hover:text-foreground'
                }`}
              >
                {t === 'sales' && <ShoppingCart size={14} />}
                {t === 'purchase' && <ShoppingBag size={14} />}
                {t === 'all' ? 'All Returns' : t === 'sales' ? 'Sales Returns' : 'Purchase Returns'}
              </button>
            ))}
          </div>
        </div>

        {/* Status Pipeline */}
        <div className="grid grid-cols-3 sm:grid-cols-5 gap-2">
          {(['draft', 'pending', 'approved', 'processed', 'rejected'] as ReturnStatus[]).map(s => {
            const count = returns.filter(r => r.status === s && (typeFilter === 'all' || r.type === typeFilter)).length;
            const cfg = statusConfig[s];
            return (
              <button
                key={s}
                onClick={() => setStatusFilter(statusFilter === s ? 'all' : s)}
                className={`flex items-center gap-2 p-3 rounded-xl border transition-all text-left ${
                  statusFilter === s ? 'ring-2 ring-primary border-primary/30 bg-primary/5' : 'border-border bg-card hover:bg-muted/50'
                }`}
              >
                <div className={`w-8 h-8 rounded-lg flex items-center justify-center ${cfg.color}`}>
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

        {/* Search */}
        <div className="relative">
          <Search size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
          <input
            type="text"
            placeholder="Search by reference, party, or original order..."
            value={search}
            onChange={e => setSearch(e.target.value)}
            className="w-full pl-9 pr-4 py-2 text-sm bg-card border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground placeholder:text-muted-foreground"
          />
        </div>

        {/* Returns List */}
        <div className="space-y-2">
          {filtered.length === 0 ? (
            <div className="text-center py-12 text-muted-foreground">
              <RotateCcw size={40} className="mx-auto mb-3 opacity-30" />
              <p className="font-medium">No returns found</p>
              <p className="text-sm mt-1">Try adjusting your filters</p>
            </div>
          ) : (
            filtered.map(ret => {
              const statusCfg = statusConfig[ret.status];
              const isExpanded = expandedId === ret.id;
              return (
                <div key={ret.id} className="bg-card border border-border rounded-xl overflow-hidden">
                  <div className="p-4">
                    <div className="flex flex-col sm:flex-row sm:items-start gap-3">
                      <div className="flex-1 min-w-0">
                        <div className="flex flex-wrap items-center gap-2 mb-1.5">
                          <span className={`flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full border ${
                            ret.type === 'sales' ?'bg-blue-500/10 text-blue-600 border-blue-200' :'bg-orange-500/10 text-orange-600 border-orange-200'
                          }`}>
                            {ret.type === 'sales' ? <ShoppingCart size={12} /> : <ShoppingBag size={12} />}
                            {ret.type === 'sales' ? 'Sales Return' : 'Purchase Return'}
                          </span>
                          <span className={`flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full border ${statusCfg.color}`}>
                            {statusCfg.icon} {statusCfg.label}
                          </span>
                          <span className="text-xs font-mono text-muted-foreground">{ret.ref}</span>
                        </div>
                        <h3 className="font-semibold text-foreground text-sm">{ret.party}</h3>
                        <p className="text-xs text-muted-foreground mt-0.5">
                          Reason: <span className="font-medium">{reasonLabels[ret.reason]}</span>
                          {' · '}Original: <span className="font-mono">{ret.originalRef}</span>
                        </p>
                        {ret.notes && (
                          <p className="text-xs text-muted-foreground mt-1 italic flex items-center gap-1">
                            <MessageSquare size={11} /> {ret.notes}
                          </p>
                        )}
                        <div className="flex flex-wrap items-center gap-3 mt-2 text-xs text-muted-foreground">
                          <span className="flex items-center gap-1"><User size={11} /> {ret.requestedBy}</span>
                          <span className="flex items-center gap-1"><Building2 size={11} /> {ret.branch}</span>
                          <span className="flex items-center gap-1"><Calendar size={11} /> {ret.date}</span>
                          <span className="flex items-center gap-1"><Package size={11} /> {ret.items.length} item{ret.items.length > 1 ? 's' : ''}</span>
                        </div>
                      </div>
                      <div className="flex items-center gap-2 shrink-0">
                        <div className="text-right mr-2">
                          <p className="text-xs text-muted-foreground">Total</p>
                          <p className="font-bold text-foreground"><MoneyDisplay amount={ret.totalAmount} currency={ret.currency} /></p>
                        </div>
                        <button
                          onClick={() => setExpandedId(isExpanded ? null : ret.id)}
                          className="flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg border border-border bg-muted/50 hover:bg-muted text-foreground transition-colors"
                        >
                          <Eye size={13} /> {isExpanded ? 'Hide' : 'View'}
                        </button>
                        {ret.status === 'pending' && (
                          <>
                            <button className="flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg bg-success/10 text-success border border-success/20 hover:bg-success/20 transition-colors font-medium">
                              <Check size={13} /> Approve
                            </button>
                            <button className="flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg bg-danger/10 text-danger border border-danger/20 hover:bg-danger/20 transition-colors font-medium">
                              <X size={13} /> Reject
                            </button>
                          </>
                        )}
                        {ret.status === 'approved' && (
                          <button className="flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg bg-info/10 text-info border border-info/20 hover:bg-info/20 transition-colors font-medium">
                            <RotateCcw size={13} /> Process
                          </button>
                        )}
                        {ret.status === 'draft' && (
                          <button className="flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg bg-warning/10 text-warning border border-warning/20 hover:bg-warning/20 transition-colors font-medium">
                            <ChevronRight size={13} /> Submit
                          </button>
                        )}
                      </div>
                    </div>
                  </div>

                  {/* Expanded Items */}
                  {isExpanded && (
                    <div className="border-t border-border bg-muted/20 p-4">
                      <h4 className="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-3">Return Items</h4>
                      <div className="space-y-2">
                        {ret.items.map((item, idx) => (
                          <div key={idx} className="flex items-center justify-between bg-card border border-border rounded-lg px-3 py-2">
                            <div>
                              <p className="text-sm font-medium text-foreground">{item.productName}</p>
                              <p className="text-xs text-muted-foreground font-mono">{item.sku}</p>
                            </div>
                            <div className="flex items-center gap-6 text-sm">
                              <div className="text-right">
                                <p className="text-xs text-muted-foreground">Qty</p>
                                <p className="font-semibold text-foreground">{item.qty}</p>
                              </div>
                              <div className="text-right">
                                <p className="text-xs text-muted-foreground">Unit Price</p>
                                <p className="font-semibold text-foreground"><MoneyDisplay amount={item.unitPrice} currency={ret.currency} /></p>
                              </div>
                              <div className="text-right">
                                <p className="text-xs text-muted-foreground">Total</p>
                                <p className="font-bold text-foreground"><MoneyDisplay amount={item.total} currency={ret.currency} /></p>
                              </div>
                            </div>
                          </div>
                        ))}
                      </div>
                      {ret.status === 'pending' && (
                        <div className="flex gap-2 mt-4">
                          <input
                            type="text"
                            placeholder="Add approval note (optional)..."
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
