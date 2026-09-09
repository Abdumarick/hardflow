'use client';
import React, { useState, useMemo } from 'react';
import AppLayout from '@/components/AppLayout';
import KpiCard from '@/components/ui/KpiCard';
import StatusBadge from '@/components/ui/StatusBadge';

import { ArrowDownCircle, ArrowUpDown, ArrowLeftRight, Trash2, Plus, Search, X, ChevronDown, ChevronUp, ChevronsUpDown, CheckCircle2, Clock, XCircle, FileText, Warehouse, Eye, Check, Ban, Download, RefreshCw,  } from 'lucide-react';

// ─── Types ────────────────────────────────────────────────────────────────────

type MovementType = 'stock-in' | 'adjustment' | 'transfer' | 'write-off';
type ApprovalStatus = 'draft' | 'pending' | 'approved' | 'rejected';

interface Movement {
  id: string;
  date: string;
  type: MovementType;
  product: string;
  sku: string;
  category: string;
  fromBranch: string;
  toBranch: string;
  qty: number;
  unit: string;
  unitCost: number;
  totalValue: number;
  reference: string;
  reasonCode: string;
  notes: string;
  requestedBy: string;
  approvedBy: string | null;
  approvalStatus: ApprovalStatus;
  poNumber?: string;
}

// ─── Mock Data ─────────────────────────────────────────────────────────────────

const REASON_CODES: Record<MovementType, string[]> = {
  'stock-in': ['PO Receipt', 'Return from Customer', 'Production Output', 'Opening Stock', 'Donation/Gift'],
  'adjustment': ['Stock Count Variance', 'Damaged Goods', 'System Error Correction', 'Expired Stock', 'Quality Rejection'],
  'transfer': ['Branch Replenishment', 'Warehouse Consolidation', 'Emergency Transfer', 'Seasonal Rebalancing'],
  'write-off': ['Expired', 'Damaged Beyond Repair', 'Theft/Loss', 'Obsolete Stock', 'Natural Disaster'],
};

const BRANCHES = ['Main Branch', 'Warehouse', 'Mwanza Branch'];
const CATEGORIES = ['Cement', 'Iron Sheets', 'Steel', 'Pipes', 'Tools', 'Paint', 'Timber', 'Electrical', 'Plumbing', 'Roofing'];

const movements: Movement[] = [
  { id: 'MV-2026-001', date: '2026-09-07 09:15', type: 'stock-in', product: 'Portland Cement 50kg', sku: 'CEM-50-PORT', category: 'Cement', fromBranch: 'Supplier', toBranch: 'Main Branch', qty: 200, unit: 'bag', unitCost: 24000, totalValue: 4800000, reference: 'GRN-2026-0091', reasonCode: 'PO Receipt', notes: 'Received from Bamburi Cement', requestedBy: 'Sarah K.', approvedBy: 'Manager', approvalStatus: 'approved', poNumber: 'PO-2026-0045' },
  { id: 'MV-2026-002', date: '2026-09-07 08:30', type: 'transfer', product: 'Iron Sheet 26G 8ft', sku: 'IRN-26-8FT', category: 'Iron Sheets', fromBranch: 'Warehouse', toBranch: 'Main Branch', qty: 50, unit: 'sheet', unitCost: 35000, totalValue: 1750000, reference: 'TRF-2026-0028', reasonCode: 'Branch Replenishment', notes: 'Main branch running low', requestedBy: 'James M.', approvedBy: null, approvalStatus: 'pending', },
  { id: 'MV-2026-003', date: '2026-09-06 16:45', type: 'adjustment', product: 'Binding Wire 2kg', sku: 'BWR-2KG', category: 'Steel', fromBranch: 'Main Branch', toBranch: 'Main Branch', qty: -5, unit: 'roll', unitCost: 6500, totalValue: 32500, reference: 'ADJ-2026-0015', reasonCode: 'Stock Count Variance', notes: 'Physical count shows 5 less than system', requestedBy: 'Peter O.', approvedBy: 'Manager', approvalStatus: 'approved', },
  { id: 'MV-2026-004', date: '2026-09-06 14:00', type: 'write-off', product: 'Gypsum Board 12mm', sku: 'GYP-12MM', category: 'Roofing', fromBranch: 'Warehouse', toBranch: 'Warehouse', qty: 8, unit: 'sheet', unitCost: 27000, totalValue: 216000, reference: 'WO-2026-0009', reasonCode: 'Damaged Beyond Repair', notes: 'Water damage during heavy rains', requestedBy: 'Peter O.', approvedBy: null, approvalStatus: 'pending', },
  { id: 'MV-2026-005', date: '2026-09-06 11:20', type: 'stock-in', product: 'Round Bar 12mm x 6m', sku: 'RBR-12-6M', category: 'Steel', fromBranch: 'Supplier', toBranch: 'Warehouse', qty: 40, unit: 'piece', unitCost: 31000, totalValue: 1240000, reference: 'GRN-2026-0090', reasonCode: 'PO Receipt', notes: 'Steel Structures delivery', requestedBy: 'Sarah K.', approvedBy: 'Manager', approvalStatus: 'approved', poNumber: 'PO-2026-0043' },
  { id: 'MV-2026-006', date: '2026-09-05 15:30', type: 'adjustment', product: 'Paint Primer 20L', sku: 'PNT-PRM-20', category: 'Paint', fromBranch: 'Main Branch', toBranch: 'Main Branch', qty: -2, unit: 'tin', unitCost: 68000, totalValue: 136000, reference: 'ADJ-2026-0014', reasonCode: 'Damaged Goods', notes: 'Tins punctured during unloading', requestedBy: 'James M.', approvedBy: null, approvalStatus: 'rejected', },
  { id: 'MV-2026-007', date: '2026-09-05 10:00', type: 'transfer', product: 'Timber 2x4 12ft', sku: 'TMB-2X4-12', category: 'Timber', fromBranch: 'Warehouse', toBranch: 'Mwanza Branch', qty: 60, unit: 'piece', unitCost: 10500, totalValue: 630000, reference: 'TRF-2026-0027', reasonCode: 'Branch Replenishment', notes: 'Mwanza branch monthly restock', requestedBy: 'Manager', approvedBy: 'Manager', approvalStatus: 'approved', },
  { id: 'MV-2026-008', date: '2026-09-04 13:45', type: 'write-off', product: 'Adhesive Sealant 300ml', sku: 'ADH-SEL-300', category: 'Tools', fromBranch: 'Main Branch', toBranch: 'Main Branch', qty: 10, unit: 'tube', unitCost: 8500, totalValue: 85000, reference: 'WO-2026-0008', reasonCode: 'Expired', notes: 'Past expiry date, batch BATCH-2025-0011', requestedBy: 'James M.', approvedBy: 'Manager', approvalStatus: 'approved', },
  { id: 'MV-2026-009', date: '2026-09-04 09:00', type: 'stock-in', product: 'PVC Pipe 4" x 6m', sku: 'PVC-4-6M', category: 'Pipes', fromBranch: 'Supplier', toBranch: 'Main Branch', qty: 30, unit: 'piece', unitCost: 17000, totalValue: 510000, reference: 'GRN-2026-0089', reasonCode: 'PO Receipt', notes: 'Boresha Pipes delivery', requestedBy: 'Sarah K.', approvedBy: 'Manager', approvalStatus: 'approved', poNumber: 'PO-2026-0041' },
  { id: 'MV-2026-010', date: '2026-09-03 16:00', type: 'adjustment', product: 'Electrical Conduit 20mm', sku: 'ELC-20MM', category: 'Electrical', fromBranch: 'Warehouse', toBranch: 'Warehouse', qty: -12, unit: 'piece', unitCost: 3200, totalValue: 38400, reference: 'ADJ-2026-0013', reasonCode: 'Damaged Goods', notes: 'Crushed by forklift', requestedBy: 'Peter O.', approvedBy: null, approvalStatus: 'draft', },
  { id: 'MV-2026-011', date: '2026-09-03 11:30', type: 'transfer', product: 'Ball Valve 1/2"', sku: 'BLV-HALF', category: 'Plumbing', fromBranch: 'Warehouse', toBranch: 'Main Branch', qty: 20, unit: 'piece', unitCost: 4800, totalValue: 96000, reference: 'TRF-2026-0026', reasonCode: 'Emergency Transfer', notes: 'Urgent request from Main Branch', requestedBy: 'James M.', approvedBy: 'Manager', approvalStatus: 'approved', },
  { id: 'MV-2026-012', date: '2026-09-02 14:20', type: 'stock-in', product: 'Roofing Nail 4" 5kg', sku: 'NAL-4-5KG', category: 'Tools', fromBranch: 'Supplier', toBranch: 'Warehouse', qty: 50, unit: 'packet', unitCost: 9000, totalValue: 450000, reference: 'GRN-2026-0088', reasonCode: 'PO Receipt', notes: 'General hardware supplier', requestedBy: 'Sarah K.', approvedBy: 'Manager', approvalStatus: 'approved', poNumber: 'PO-2026-0039' },
];

// ─── Helpers ──────────────────────────────────────────────────────────────────

type SortDir = 'asc' | 'desc' | null;

function SortIcon({ field, sortField, sortDir }: { field: string; sortField: string; sortDir: SortDir }) {
  if (sortField !== field) return <ChevronsUpDown size={13} className="text-muted-foreground/40" />;
  return sortDir === 'asc' ? <ChevronUp size={13} className="text-primary" /> : <ChevronDown size={13} className="text-primary" />;
}

function useSortable<T>(data: T[], defaultField: string) {
  const [sortField, setSortField] = useState(defaultField);
  const [sortDir, setSortDir] = useState<SortDir>('desc');
  const handleSort = (field: string) => {
    if (sortField === field) setSortDir(d => d === 'asc' ? 'desc' : 'asc');
    else { setSortField(field); setSortDir('asc'); }
  };
  const sorted = useMemo(() => [...data].sort((a: any, b: any) => {
    const av = a[sortField]; const bv = b[sortField];
    if (av == null) return 1; if (bv == null) return -1;
    const cmp = typeof av === 'string' ? av.localeCompare(bv) : av - bv;
    return sortDir === 'asc' ? cmp : -cmp;
  }), [data, sortField, sortDir]);
  return { sorted, sortField, sortDir, handleSort };
}

const typeConfig: Record<MovementType, { label: string; color: string; icon: React.ReactNode; bg: string }> = {
  'stock-in':   { label: 'Stock In',   color: 'text-success', bg: 'bg-success/10',  icon: <ArrowDownCircle size={14} /> },
  'adjustment': { label: 'Adjustment', color: 'text-warning', bg: 'bg-warning/10',  icon: <ArrowUpDown size={14} /> },
  'transfer':   { label: 'Transfer',   color: 'text-info',    bg: 'bg-info/10',     icon: <ArrowLeftRight size={14} /> },
  'write-off':  { label: 'Write-Off',  color: 'text-danger',  bg: 'bg-danger/10',   icon: <Trash2 size={14} /> },
};

const approvalConfig: Record<ApprovalStatus, { label: string; variant: 'success' | 'warning' | 'danger' | 'info' | 'default' }> = {
  draft:    { label: 'Draft',    variant: 'default' },
  pending:  { label: 'Pending',  variant: 'warning' },
  approved: { label: 'Approved', variant: 'success' },
  rejected: { label: 'Rejected', variant: 'danger' },
};

// ─── New Movement Modal ────────────────────────────────────────────────────────

interface NewMovementModalProps {
  onClose: () => void;
}

function NewMovementModal({ onClose }: NewMovementModalProps) {
  const [type, setType] = useState<MovementType>('stock-in');
  const [form, setForm] = useState({
    product: '', sku: '', category: '', fromBranch: 'Supplier', toBranch: 'Main Branch',
    qty: '', unitCost: '', reasonCode: '', notes: '', poNumber: '',
  });

  const reasons = REASON_CODES[type];

  const set = (k: string, v: string) => setForm(f => ({ ...f, [k]: v }));

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
      <div className="bg-card border border-border rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-border">
          <div>
            <h2 className="text-base font-bold text-foreground">Record Inventory Movement</h2>
            <p className="text-xs text-muted-foreground mt-0.5">Fill in details and submit for approval</p>
          </div>
          <button onClick={onClose} className="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-muted transition-colors text-muted-foreground hover:text-foreground">
            <X size={16} />
          </button>
        </div>

        <div className="p-6 space-y-5">
          {/* Movement Type */}
          <div>
            <label className="block text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-2">Movement Type</label>
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
              {(Object.keys(typeConfig) as MovementType[]).map(t => {
                const cfg = typeConfig[t];
                return (
                  <button
                    key={t}
                    onClick={() => { setType(t); set('reasonCode', ''); set('fromBranch', t === 'stock-in' ? 'Supplier' : 'Main Branch'); }}
                    className={`flex flex-col items-center gap-1.5 p-3 rounded-lg border-2 transition-all text-xs font-semibold ${
                      type === t ? `border-current ${cfg.color} ${cfg.bg}` : 'border-border text-muted-foreground hover:border-border/80 hover:bg-muted/50'
                    }`}
                  >
                    <span className={type === t ? cfg.color : ''}>{cfg.icon}</span>
                    {cfg.label}
                  </button>
                );
              })}
            </div>
          </div>

          {/* Product Info */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-1.5">Product Name *</label>
              <input value={form.product} onChange={e => set('product', e.target.value)} placeholder="e.g. Portland Cement 50kg" className="input w-full h-9 text-sm" />
            </div>
            <div>
              <label className="block text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-1.5">SKU</label>
              <input value={form.sku} onChange={e => set('sku', e.target.value)} placeholder="e.g. CEM-50-PORT" className="input w-full h-9 text-sm" />
            </div>
            <div>
              <label className="block text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-1.5">Category</label>
              <select value={form.category} onChange={e => set('category', e.target.value)} className="input w-full h-9 text-sm">
                <option value="">Select category</option>
                {CATEGORIES.map(c => <option key={c} value={c}>{c}</option>)}
              </select>
            </div>
            {type === 'stock-in' && (
              <div>
                <label className="block text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-1.5">PO Number</label>
                <input value={form.poNumber} onChange={e => set('poNumber', e.target.value)} placeholder="e.g. PO-2026-0045" className="input w-full h-9 text-sm" />
              </div>
            )}
          </div>

          {/* Branch Info */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-1.5">
                {type === 'stock-in' ? 'Supplier / Source' : type === 'transfer' ? 'From Branch' : 'Branch'}
              </label>
              {type === 'stock-in' ? (
                <input value={form.fromBranch} onChange={e => set('fromBranch', e.target.value)} placeholder="Supplier name" className="input w-full h-9 text-sm" />
              ) : (
                <select value={form.fromBranch} onChange={e => set('fromBranch', e.target.value)} className="input w-full h-9 text-sm">
                  {BRANCHES.map(b => <option key={b} value={b}>{b}</option>)}
                </select>
              )}
            </div>
            {(type === 'stock-in' || type === 'transfer') && (
              <div>
                <label className="block text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-1.5">To Branch</label>
                <select value={form.toBranch} onChange={e => set('toBranch', e.target.value)} className="input w-full h-9 text-sm">
                  {BRANCHES.map(b => <option key={b} value={b}>{b}</option>)}
                </select>
              </div>
            )}
          </div>

          {/* Qty & Cost */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-1.5">Quantity *</label>
              <input type="number" value={form.qty} onChange={e => set('qty', e.target.value)} placeholder="0" min="1" className="input w-full h-9 text-sm" />
            </div>
            <div>
              <label className="block text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-1.5">Unit Cost (TZS)</label>
              <input type="number" value={form.unitCost} onChange={e => set('unitCost', e.target.value)} placeholder="0" min="0" className="input w-full h-9 text-sm" />
            </div>
          </div>

          {/* Reason Code */}
          <div>
            <label className="block text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-1.5">Reason Code *</label>
            <select value={form.reasonCode} onChange={e => set('reasonCode', e.target.value)} className="input w-full h-9 text-sm">
              <option value="">Select reason</option>
              {reasons.map(r => <option key={r} value={r}>{r}</option>)}
            </select>
          </div>

          {/* Notes */}
          <div>
            <label className="block text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-1.5">Notes</label>
            <textarea value={form.notes} onChange={e => set('notes', e.target.value)} placeholder="Additional details or context…" rows={3} className="input w-full text-sm resize-none py-2" />
          </div>
        </div>

        {/* Footer */}
        <div className="flex items-center justify-between gap-3 px-6 py-4 border-t border-border bg-muted/30">
          <p className="text-xs text-muted-foreground flex items-center gap-1.5">
            <Clock size={12} /> Will be submitted for manager approval
          </p>
          <div className="flex gap-2">
            <button onClick={onClose} className="btn-ghost h-9 px-4 text-sm">Cancel</button>
            <button
              onClick={onClose}
              className="btn-primary h-9 px-5 text-sm flex items-center gap-1.5"
            >
              <FileText size={14} /> Submit for Approval
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

// ─── Movement Detail Drawer ────────────────────────────────────────────────────

function MovementDetailDrawer({ movement, onClose, onApprove, onReject }: {
  movement: Movement;
  onClose: () => void;
  onApprove: (id: string) => void;
  onReject: (id: string) => void;
}) {
  const cfg = typeConfig[movement.type];
  const apCfg = approvalConfig[movement.approvalStatus];

  return (
    <div className="fixed inset-0 z-50 flex justify-end">
      <div className="absolute inset-0 bg-black/40 backdrop-blur-sm" onClick={onClose} />
      <div className="relative bg-card border-l border-border w-full max-w-md h-full overflow-y-auto shadow-2xl flex flex-col">
        {/* Header */}
        <div className="flex items-start justify-between px-5 py-4 border-b border-border">
          <div>
            <div className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ${cfg.bg} ${cfg.color} mb-2`}>
              {cfg.icon} {cfg.label}
            </div>
            <h3 className="text-sm font-bold text-foreground">{movement.id}</h3>
            <p className="text-xs text-muted-foreground mt-0.5">{movement.date}</p>
          </div>
          <button onClick={onClose} className="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-muted text-muted-foreground hover:text-foreground transition-colors mt-1">
            <X size={16} />
          </button>
        </div>

        <div className="flex-1 p-5 space-y-5">
          {/* Approval Status */}
          <div className={`rounded-lg p-3 flex items-center gap-3 ${
            movement.approvalStatus === 'approved' ? 'bg-success/10 border border-success/20' :
            movement.approvalStatus === 'rejected' ? 'bg-danger/10 border border-danger/20' :
            movement.approvalStatus === 'pending'? 'bg-warning/10 border border-warning/20' : 'bg-muted border border-border'
          }`}>
            {movement.approvalStatus === 'approved' ? <CheckCircle2 size={18} className="text-success shrink-0" /> :
             movement.approvalStatus === 'rejected' ? <XCircle size={18} className="text-danger shrink-0" /> :
             movement.approvalStatus === 'pending'  ? <Clock size={18} className="text-warning shrink-0" /> :
             <FileText size={18} className="text-muted-foreground shrink-0" />}
            <div>
              <p className="text-xs font-bold text-foreground">{apCfg.label}</p>
              <p className="text-2xs text-muted-foreground">
                {movement.approvalStatus === 'approved' ? `Approved by ${movement.approvedBy}` :
                 movement.approvalStatus === 'pending'  ? 'Awaiting manager approval' :
                 movement.approvalStatus === 'rejected'? 'Rejected — needs revision' : 'Saved as draft'}
              </p>
            </div>
          </div>

          {/* Product */}
          <div>
            <p className="text-2xs font-semibold text-muted-foreground uppercase tracking-wide mb-2">Product</p>
            <div className="card p-3 space-y-1.5">
              <p className="text-sm font-semibold text-foreground">{movement.product}</p>
              <p className="text-xs text-muted-foreground">SKU: {movement.sku} · {movement.category}</p>
            </div>
          </div>

          {/* Movement Details */}
          <div>
            <p className="text-2xs font-semibold text-muted-foreground uppercase tracking-wide mb-2">Movement Details</p>
            <div className="card p-3 space-y-2.5">
              <div className="flex justify-between text-xs">
                <span className="text-muted-foreground">From</span>
                <span className="font-medium text-foreground">{movement.fromBranch}</span>
              </div>
              <div className="flex justify-between text-xs">
                <span className="text-muted-foreground">To</span>
                <span className="font-medium text-foreground">{movement.toBranch}</span>
              </div>
              <div className="flex justify-between text-xs">
                <span className="text-muted-foreground">Quantity</span>
                <span className={`font-bold ${movement.qty < 0 ? 'text-danger' : 'text-success'}`}>
                  {movement.qty > 0 ? '+' : ''}{movement.qty} {movement.unit}
                </span>
              </div>
              <div className="flex justify-between text-xs">
                <span className="text-muted-foreground">Unit Cost</span>
                <span className="font-medium text-foreground">TZS {movement.unitCost.toLocaleString()}</span>
              </div>
              <div className="flex justify-between text-xs border-t border-border pt-2">
                <span className="text-muted-foreground font-semibold">Total Value</span>
                <span className="font-bold text-foreground">TZS {movement.totalValue.toLocaleString()}</span>
              </div>
            </div>
          </div>

          {/* Reference & Reason */}
          <div>
            <p className="text-2xs font-semibold text-muted-foreground uppercase tracking-wide mb-2">Reference & Reason</p>
            <div className="card p-3 space-y-2.5">
              <div className="flex justify-between text-xs">
                <span className="text-muted-foreground">Reference</span>
                <span className="font-mono text-xs font-medium text-foreground">{movement.reference}</span>
              </div>
              {movement.poNumber && (
                <div className="flex justify-between text-xs">
                  <span className="text-muted-foreground">PO Number</span>
                  <span className="font-mono text-xs font-medium text-primary">{movement.poNumber}</span>
                </div>
              )}
              <div className="flex justify-between text-xs">
                <span className="text-muted-foreground">Reason Code</span>
                <span className="font-medium text-foreground">{movement.reasonCode}</span>
              </div>
              <div className="flex justify-between text-xs">
                <span className="text-muted-foreground">Requested By</span>
                <span className="font-medium text-foreground">{movement.requestedBy}</span>
              </div>
              {movement.notes && (
                <div className="pt-1 border-t border-border">
                  <p className="text-2xs text-muted-foreground mb-1">Notes</p>
                  <p className="text-xs text-foreground">{movement.notes}</p>
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Approval Actions */}
        {movement.approvalStatus === 'pending' && (
          <div className="px-5 py-4 border-t border-border bg-muted/30 flex gap-2">
            <button
              onClick={() => { onReject(movement.id); onClose(); }}
              className="flex-1 flex items-center justify-center gap-1.5 h-9 rounded-lg border border-danger/30 text-danger text-sm font-semibold hover:bg-danger/10 transition-colors"
            >
              <Ban size={14} /> Reject
            </button>
            <button
              onClick={() => { onApprove(movement.id); onClose(); }}
              className="flex-1 flex items-center justify-center gap-1.5 h-9 rounded-lg bg-success text-white text-sm font-semibold hover:bg-success/90 transition-colors"
            >
              <Check size={14} /> Approve
            </button>
          </div>
        )}
      </div>
    </div>
  );
}

// ─── Main Page ─────────────────────────────────────────────────────────────────

export default function InventoryMovementsPage() {
  const [activeType, setActiveType] = useState<MovementType | 'all'>('all');
  const [activeStatus, setActiveStatus] = useState<ApprovalStatus | 'all'>('all');
  const [search, setSearch] = useState('');
  const [showModal, setShowModal] = useState(false);
  const [selectedMovement, setSelectedMovement] = useState<Movement | null>(null);
  const [data, setData] = useState<Movement[]>(movements);

  const filtered = useMemo(() => data.filter(m => {
    if (activeType !== 'all' && m.type !== activeType) return false;
    if (activeStatus !== 'all' && m.approvalStatus !== activeStatus) return false;
    if (search) {
      const q = search.toLowerCase();
      if (!m.product.toLowerCase().includes(q) && !m.sku.toLowerCase().includes(q) && !m.reference.toLowerCase().includes(q)) return false;
    }
    return true;
  }), [data, activeType, activeStatus, search]);

  const { sorted, sortField, sortDir, handleSort } = useSortable(filtered, 'date');

  const handleApprove = (id: string) => {
    setData(prev => prev.map(m => m.id === id ? { ...m, approvalStatus: 'approved', approvedBy: 'Manager' } : m));
  };
  const handleReject = (id: string) => {
    setData(prev => prev.map(m => m.id === id ? { ...m, approvalStatus: 'rejected' } : m));
  };

  // KPIs
  const totalStockIn   = data.filter(m => m.type === 'stock-in' && m.approvalStatus === 'approved').reduce((s, m) => s + m.totalValue, 0);
  const totalTransfers = data.filter(m => m.type === 'transfer').length;
  const pendingApprovals = data.filter(m => m.approvalStatus === 'pending').length;
  const writeOffValue  = data.filter(m => m.type === 'write-off' && m.approvalStatus === 'approved').reduce((s, m) => s + m.totalValue, 0);

  const th = (label: string, field: string) => (
    <th
      className="px-3 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide cursor-pointer hover:text-foreground select-none whitespace-nowrap"
      onClick={() => handleSort(field)}
    >
      <span className="flex items-center gap-1">{label}<SortIcon field={field} sortField={sortField} sortDir={sortDir} /></span>
    </th>
  );

  return (
    <AppLayout>
      <div className="p-4 sm:p-6 space-y-5">
        {/* Page Header */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-bold text-foreground">Inventory Movements</h1>
            <p className="text-sm text-muted-foreground mt-0.5">Track stock-ins, adjustments, transfers, and write-offs</p>
          </div>
          <div className="flex items-center gap-2">
            <button className="btn-ghost h-9 px-3 text-sm flex items-center gap-1.5">
              <Download size={14} /> Export
            </button>
            <button
              onClick={() => setShowModal(true)}
              className="btn-primary h-9 px-4 text-sm flex items-center gap-1.5"
            >
              <Plus size={14} /> New Movement
            </button>
          </div>
        </div>

        {/* KPI Cards */}
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <KpiCard
            title="Stock-In Value"
            value={totalStockIn}
            isMoney
            icon={<ArrowDownCircle size={18} className="text-success" />}
            iconBg="bg-success/10"
            variant="success"
            subtitle="Approved this month"
          />
          <KpiCard
            title="Pending Approvals"
            value={pendingApprovals}
            icon={<Clock size={18} className="text-warning" />}
            iconBg="bg-warning/10"
            variant={pendingApprovals > 0 ? 'warning' : 'default'}
            subtitle="Awaiting review"
          />
          <KpiCard
            title="Transfers"
            value={totalTransfers}
            icon={<ArrowLeftRight size={18} className="text-info" />}
            iconBg="bg-info/10"
            subtitle="All branches"
          />
          <KpiCard
            title="Write-Off Value"
            value={writeOffValue}
            isMoney
            icon={<Trash2 size={18} className="text-danger" />}
            iconBg="bg-danger/10"
            variant={writeOffValue > 0 ? 'danger' : 'default'}
            subtitle="Approved write-offs"
          />
        </div>

        {/* Filters */}
        <div className="card p-4 space-y-3">
          {/* Type Tabs */}
          <div className="flex flex-wrap gap-2">
            {(['all', 'stock-in', 'adjustment', 'transfer', 'write-off'] as const).map(t => {
              const cfg = t !== 'all' ? typeConfig[t] : null;
              const isActive = activeType === t;
              return (
                <button
                  key={t}
                  onClick={() => setActiveType(t)}
                  className={`flex items-center gap-1.5 px-3 h-8 rounded-lg text-xs font-semibold transition-all border ${
                    isActive
                      ? cfg ? `${cfg.bg} ${cfg.color} border-current/30` : 'bg-primary/10 text-primary border-primary/30' :'border-border text-muted-foreground hover:text-foreground hover:bg-muted/50'
                  }`}
                >
                  {cfg && <span className={isActive ? cfg.color : ''}>{cfg.icon}</span>}
                  {t === 'all' ? 'All Types' : typeConfig[t as MovementType].label}
                  <span className={`text-2xs px-1.5 py-0.5 rounded-full ${isActive ? 'bg-current/10' : 'bg-muted'}`}>
                    {t === 'all' ? data.length : data.filter(m => m.type === t).length}
                  </span>
                </button>
              );
            })}
          </div>

          {/* Search + Status */}
          <div className="flex flex-wrap gap-2 items-center">
            <div className="relative flex-1 min-w-[200px] max-w-sm">
              <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
              <input
                type="text"
                placeholder="Search product, SKU, reference…"
                value={search}
                onChange={e => setSearch(e.target.value)}
                className="input pl-8 h-8 text-sm w-full"
              />
            </div>
            <div className="flex gap-1.5">
              {(['all', 'draft', 'pending', 'approved', 'rejected'] as const).map(s => (
                <button
                  key={s}
                  onClick={() => setActiveStatus(s)}
                  className={`px-2.5 h-8 rounded-lg text-xs font-semibold transition-all border ${
                    activeStatus === s
                      ? s === 'all' ? 'bg-foreground text-background border-foreground'
                        : s === 'approved' ? 'bg-success/10 text-success border-success/30'
                        : s === 'pending'  ? 'bg-warning/10 text-warning border-warning/30'
                        : s === 'rejected'? 'bg-danger/10 text-danger border-danger/30' :'bg-muted text-foreground border-border' :'border-border text-muted-foreground hover:text-foreground hover:bg-muted/50'
                  }`}
                >
                  {s === 'all' ? 'All Status' : s.charAt(0).toUpperCase() + s.slice(1)}
                </button>
              ))}
            </div>
            {(search || activeType !== 'all' || activeStatus !== 'all') && (
              <button
                onClick={() => { setSearch(''); setActiveType('all'); setActiveStatus('all'); }}
                className="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground transition-colors px-2 h-8 rounded border border-border hover:border-foreground/30"
              >
                <X size={12} /> Clear
              </button>
            )}
          </div>
        </div>

        {/* Movements Table */}
        <div className="card overflow-hidden">
          <div className="flex items-center justify-between px-4 py-3 border-b border-border">
            <p className="text-sm font-semibold text-foreground">
              {sorted.length} movement{sorted.length !== 1 ? 's' : ''}
            </p>
            <button className="flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground transition-colors">
              <RefreshCw size={12} /> Refresh
            </button>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead className="bg-muted/50 border-b border-border">
                <tr>
                  {th('Date', 'date')}
                  {th('Type', 'type')}
                  {th('Product', 'product')}
                  {th('From → To', 'fromBranch')}
                  {th('Qty', 'qty')}
                  {th('Value', 'totalValue')}
                  {th('Reason', 'reasonCode')}
                  {th('Reference', 'reference')}
                  {th('Status', 'approvalStatus')}
                  <th className="px-3 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {sorted.length === 0 ? (
                  <tr>
                    <td colSpan={10} className="px-4 py-12 text-center text-muted-foreground text-sm">
                      No movements found matching your filters.
                    </td>
                  </tr>
                ) : sorted.map(m => {
                  const cfg = typeConfig[m.type];
                  const apCfg = approvalConfig[m.approvalStatus];
                  return (
                    <tr key={m.id} className="hover:bg-muted/30 transition-colors">
                      <td className="px-3 py-3 text-xs text-muted-foreground whitespace-nowrap">
                        {m.date.split(' ')[0]}<br />
                        <span className="text-2xs">{m.date.split(' ')[1]}</span>
                      </td>
                      <td className="px-3 py-3">
                        <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold ${cfg.bg} ${cfg.color}`}>
                          {cfg.icon} {cfg.label}
                        </span>
                      </td>
                      <td className="px-3 py-3">
                        <p className="text-xs font-semibold text-foreground whitespace-nowrap max-w-[160px] truncate">{m.product}</p>
                        <p className="text-2xs text-muted-foreground font-mono">{m.sku}</p>
                      </td>
                      <td className="px-3 py-3 text-xs text-muted-foreground whitespace-nowrap">
                        <span className="text-foreground">{m.fromBranch}</span>
                        <span className="mx-1 text-muted-foreground">→</span>
                        <span className="text-foreground">{m.toBranch}</span>
                      </td>
                      <td className="px-3 py-3">
                        <span className={`text-sm font-bold tabular-nums ${m.qty < 0 ? 'text-danger' : 'text-success'}`}>
                          {m.qty > 0 ? '+' : ''}{m.qty}
                        </span>
                        <span className="text-2xs text-muted-foreground ml-1">{m.unit}</span>
                      </td>
                      <td className="px-3 py-3 text-xs font-semibold text-foreground whitespace-nowrap tabular-nums">
                        TZS {m.totalValue.toLocaleString()}
                      </td>
                      <td className="px-3 py-3 text-xs text-muted-foreground whitespace-nowrap max-w-[120px] truncate">
                        {m.reasonCode}
                      </td>
                      <td className="px-3 py-3">
                        <span className="font-mono text-xs text-muted-foreground">{m.reference}</span>
                        {m.poNumber && <p className="text-2xs text-primary font-mono">{m.poNumber}</p>}
                      </td>
                      <td className="px-3 py-3">
                        <StatusBadge status={apCfg.variant} label={apCfg.label} />
                      </td>
                      <td className="px-3 py-3">
                        <div className="flex items-center gap-1">
                          <button
                            onClick={() => setSelectedMovement(m)}
                            className="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-muted text-muted-foreground hover:text-foreground transition-colors"
                            title="View details"
                          >
                            <Eye size={13} />
                          </button>
                          {m.approvalStatus === 'pending' && (
                            <>
                              <button
                                onClick={() => handleApprove(m.id)}
                                className="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-success/10 text-muted-foreground hover:text-success transition-colors"
                                title="Approve"
                              >
                                <Check size={13} />
                              </button>
                              <button
                                onClick={() => handleReject(m.id)}
                                className="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-danger/10 text-muted-foreground hover:text-danger transition-colors"
                                title="Reject"
                              >
                                <Ban size={13} />
                              </button>
                            </>
                          )}
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {/* Modals */}
      {showModal && <NewMovementModal onClose={() => setShowModal(false)} />}
      {selectedMovement && (
        <MovementDetailDrawer
          movement={selectedMovement}
          onClose={() => setSelectedMovement(null)}
          onApprove={handleApprove}
          onReject={handleReject}
        />
      )}
    </AppLayout>
  );
}
