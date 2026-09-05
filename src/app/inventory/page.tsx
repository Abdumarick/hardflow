'use client';
import React, { useState, useMemo } from 'react';
import AppLayout, { DashboardLayout } from '@/components/AppLayout';
import KpiCard from '@/components/ui/KpiCard';
import StatusBadge from '@/components/ui/StatusBadge';
import MoneyDisplay from '@/components/ui/MoneyDisplay';
import { Warehouse, Package, TrendingDown, AlertTriangle, Clock, Search, Filter, ChevronUp, ChevronDown, ChevronsUpDown, Download, RefreshCw, ArrowUpCircle, ArrowDownCircle, ArrowLeftRight, X, SlidersHorizontal,  } from 'lucide-react';

// ─── Types ────────────────────────────────────────────────────────────────────

type Tab = 'current' | 'movements' | 'low-stock' | 'damaged' | 'expired';

interface StockItem {
  id: string;
  name: string;
  sku: string;
  category: string;
  brand: string;
  unit: string;
  branch: string;
  currentStock: number;
  minStock: number;
  stockValue: number;
  costPrice: number;
  lastMovement: string;
  status: 'in-stock' | 'low-stock' | 'out-of-stock';
}

interface StockMovement {
  id: string;
  date: string;
  product: string;
  sku: string;
  category: string;
  branch: string;
  type: 'in' | 'out' | 'transfer' | 'adjustment';
  qtyIn: number | null;
  qtyOut: number | null;
  balance: number;
  reference: string;
  user: string;
  notes: string;
}

interface DamagedItem {
  id: string;
  date: string;
  product: string;
  sku: string;
  category: string;
  branch: string;
  qty: number;
  unit: string;
  estimatedLoss: number;
  reportedBy: string;
  status: 'pending' | 'approved' | 'written-off';
  notes: string;
}

interface ExpiredItem {
  id: string;
  product: string;
  sku: string;
  category: string;
  branch: string;
  qty: number;
  unit: string;
  expiryDate: string;
  batchNo: string;
  estimatedLoss: number;
  status: 'expiring-soon' | 'expired';
  daysLeft: number;
}

// ─── Mock Data ─────────────────────────────────────────────────────────────────

const stockItems: StockItem[] = [
  { id: 's001', name: 'Portland Cement 50kg', sku: 'CEM-50-PORT', category: 'Cement', brand: 'Bamburi', unit: 'bag', branch: 'Main Branch', currentStock: 248, minStock: 50, costPrice: 24000, stockValue: 5952000, lastMovement: '2026-09-05', status: 'in-stock' },
  { id: 's002', name: 'Iron Sheet 26G 8ft', sku: 'IRN-26-8FT', category: 'Iron Sheets', brand: 'Mabati Rolling Mills', unit: 'sheet', branch: 'Main Branch', currentStock: 94, minStock: 30, costPrice: 35000, stockValue: 3290000, lastMovement: '2026-09-04', status: 'in-stock' },
  { id: 's003', name: 'Round Bar 12mm x 6m', sku: 'RBR-12-6M', category: 'Steel', brand: 'Steel Structures', unit: 'piece', branch: 'Warehouse', currentStock: 42, minStock: 20, costPrice: 31000, stockValue: 1302000, lastMovement: '2026-09-03', status: 'in-stock' },
  { id: 's004', name: 'PVC Pipe 4" x 6m', sku: 'PVC-4-6M', category: 'Pipes', brand: 'Boresha Pipes', unit: 'piece', branch: 'Main Branch', currentStock: 6, minStock: 20, costPrice: 17000, stockValue: 102000, lastMovement: '2026-09-02', status: 'low-stock' },
  { id: 's005', name: 'Binding Wire 2kg', sku: 'BWR-2KG', category: 'Steel', brand: 'Kenya Wire', unit: 'roll', branch: 'Main Branch', currentStock: 12, minStock: 30, costPrice: 6500, stockValue: 78000, lastMovement: '2026-09-01', status: 'low-stock' },
  { id: 's006', name: 'Roofing Nail 4" 5kg', sku: 'NAL-4-5KG', category: 'Tools', brand: 'General', unit: 'packet', branch: 'Warehouse', currentStock: 67, minStock: 20, costPrice: 9000, stockValue: 603000, lastMovement: '2026-09-05', status: 'in-stock' },
  { id: 's007', name: 'Paint Primer 20L', sku: 'PNT-PRM-20', category: 'Paint', brand: 'Crown Paints', unit: 'tin', branch: 'Main Branch', currentStock: 4, minStock: 15, costPrice: 68000, stockValue: 272000, lastMovement: '2026-08-30', status: 'low-stock' },
  { id: 's008', name: 'Timber 2x4 12ft', sku: 'TMB-2X4-12', category: 'Timber', brand: 'Local', unit: 'piece', branch: 'Warehouse', currentStock: 130, minStock: 30, costPrice: 10500, stockValue: 1365000, lastMovement: '2026-09-04', status: 'in-stock' },
  { id: 's009', name: 'Electrical Conduit 20mm', sku: 'ELC-20MM', category: 'Electrical', brand: 'Clipsal', unit: 'piece', branch: 'Main Branch', currentStock: 88, minStock: 30, costPrice: 3200, stockValue: 281600, lastMovement: '2026-09-03', status: 'in-stock' },
  { id: 's010', name: 'Ball Valve 1/2"', sku: 'BLV-HALF', category: 'Plumbing', brand: 'Flomatic', unit: 'piece', branch: 'Main Branch', currentStock: 55, minStock: 20, costPrice: 4800, stockValue: 264000, lastMovement: '2026-09-02', status: 'in-stock' },
  { id: 's011', name: 'Angle Iron 40x40x3mm', sku: 'ANG-40-3', category: 'Steel', brand: 'Steel Structures', unit: 'piece', branch: 'Warehouse', currentStock: 0, minStock: 15, costPrice: 22000, stockValue: 0, lastMovement: '2026-08-28', status: 'out-of-stock' },
  { id: 's012', name: 'Paint Emulsion 20L White', sku: 'PNT-EMU-20W', category: 'Paint', brand: 'Crown Paints', unit: 'tin', branch: 'Main Branch', currentStock: 22, minStock: 10, costPrice: 74000, stockValue: 1628000, lastMovement: '2026-09-05', status: 'in-stock' },
  { id: 's013', name: 'Circuit Breaker 32A', sku: 'CB-32A', category: 'Electrical', brand: 'Schneider', unit: 'piece', branch: 'Main Branch', currentStock: 19, minStock: 10, costPrice: 20000, stockValue: 380000, lastMovement: '2026-09-01', status: 'in-stock' },
  { id: 's014', name: 'Concrete Blocks 6"', sku: 'BLK-6IN', category: 'Cement', brand: 'Local', unit: 'piece', branch: 'Warehouse', currentStock: 1250, minStock: 200, costPrice: 850, stockValue: 1062500, lastMovement: '2026-09-05', status: 'in-stock' },
  { id: 's015', name: 'Gypsum Board 12mm', sku: 'GYP-12MM', category: 'Roofing', brand: 'Gyproc', unit: 'sheet', branch: 'Main Branch', currentStock: 8, minStock: 10, costPrice: 27000, stockValue: 216000, lastMovement: '2026-08-29', status: 'low-stock' },
];

const movements: StockMovement[] = [
  { id: 'm001', date: '2026-09-05 14:32', product: 'Portland Cement 50kg', sku: 'CEM-50-PORT', category: 'Cement', branch: 'Main Branch', type: 'out', qtyIn: null, qtyOut: 20, balance: 248, reference: 'SALE-2026-1042', user: 'James M.', notes: 'Cash sale' },
  { id: 'm002', date: '2026-09-05 11:15', product: 'Iron Sheet 26G 8ft', sku: 'IRN-26-8FT', category: 'Iron Sheets', branch: 'Main Branch', type: 'in', qtyIn: 50, qtyOut: null, balance: 94, reference: 'GRN-2026-0088', user: 'Sarah K.', notes: 'Goods received from Mabati' },
  { id: 'm003', date: '2026-09-04 16:45', product: 'Binding Wire 2kg', sku: 'BWR-2KG', category: 'Steel', branch: 'Main Branch', type: 'out', qtyIn: null, qtyOut: 8, balance: 12, reference: 'SALE-2026-1039', user: 'James M.', notes: 'Credit sale' },
  { id: 'm004', date: '2026-09-04 09:20', product: 'Timber 2x4 12ft', sku: 'TMB-2X4-12', category: 'Timber', branch: 'Warehouse', type: 'transfer', qtyIn: 30, qtyOut: null, balance: 130, reference: 'TRF-2026-0021', user: 'Peter O.', notes: 'Transfer from Warehouse to Main' },
  { id: 'm005', date: '2026-09-03 13:00', product: 'Round Bar 12mm x 6m', sku: 'RBR-12-6M', category: 'Steel', branch: 'Warehouse', type: 'in', qtyIn: 20, qtyOut: null, balance: 42, reference: 'GRN-2026-0085', user: 'Sarah K.', notes: 'Goods received' },
  { id: 'm006', date: '2026-09-03 10:30', product: 'PVC Pipe 4" x 6m', sku: 'PVC-4-6M', category: 'Pipes', branch: 'Main Branch', type: 'out', qtyIn: null, qtyOut: 14, balance: 6, reference: 'SALE-2026-1035', user: 'James M.', notes: 'Bulk order' },
  { id: 'm007', date: '2026-09-02 15:10', product: 'Paint Primer 20L', sku: 'PNT-PRM-20', category: 'Paint', branch: 'Main Branch', type: 'adjustment', qtyIn: null, qtyOut: 2, balance: 4, reference: 'ADJ-2026-0012', user: 'Manager', notes: 'Stock count adjustment' },
  { id: 'm008', date: '2026-09-01 08:45', product: 'Electrical Conduit 20mm', sku: 'ELC-20MM', category: 'Electrical', branch: 'Main Branch', type: 'in', qtyIn: 40, qtyOut: null, balance: 88, reference: 'GRN-2026-0082', user: 'Sarah K.', notes: 'Restocked' },
  { id: 'm009', date: '2026-08-31 14:00', product: 'Ball Valve 1/2"', sku: 'BLV-HALF', category: 'Plumbing', branch: 'Main Branch', type: 'out', qtyIn: null, qtyOut: 10, balance: 55, reference: 'SALE-2026-1028', user: 'James M.', notes: '' },
  { id: 'm010', date: '2026-08-30 11:20', product: 'Angle Iron 40x40x3mm', sku: 'ANG-40-3', category: 'Steel', branch: 'Warehouse', type: 'out', qtyIn: null, qtyOut: 15, balance: 0, reference: 'SALE-2026-1025', user: 'James M.', notes: 'Last stock sold' },
];

const damagedItems: DamagedItem[] = [
  { id: 'd001', date: '2026-09-03', product: 'Gypsum Board 12mm', sku: 'GYP-12MM', category: 'Roofing', branch: 'Warehouse', qty: 5, unit: 'sheet', estimatedLoss: 135000, reportedBy: 'Peter O.', status: 'pending', notes: 'Water damage during storage' },
  { id: 'd002', date: '2026-09-01', product: 'Paint Primer 20L', sku: 'PNT-PRM-20', category: 'Paint', branch: 'Main Branch', qty: 2, unit: 'tin', estimatedLoss: 136000, reportedBy: 'James M.', status: 'approved', notes: 'Tins punctured during unloading' },
  { id: 'd003', date: '2026-08-28', product: 'PVC Pipe 4" x 6m', sku: 'PVC-4-6M', category: 'Pipes', branch: 'Main Branch', qty: 8, unit: 'piece', estimatedLoss: 136000, reportedBy: 'Sarah K.', status: 'written-off', notes: 'Cracked during transport' },
  { id: 'd004', date: '2026-08-25', product: 'Electrical Conduit 20mm', sku: 'ELC-20MM', category: 'Electrical', branch: 'Warehouse', qty: 12, unit: 'piece', estimatedLoss: 38400, reportedBy: 'Peter O.', status: 'approved', notes: 'Crushed by forklift' },
  { id: 'd005', date: '2026-08-20', product: 'Ceiling Board 6mm', sku: 'CLG-6MM', category: 'Timber', branch: 'Main Branch', qty: 4, unit: 'sheet', estimatedLoss: 66000, reportedBy: 'James M.', status: 'pending', notes: 'Moisture damage' },
];

const expiredItems: ExpiredItem[] = [
  { id: 'e001', product: 'Paint Emulsion 20L White', sku: 'PNT-EMU-20W', category: 'Paint', branch: 'Main Branch', qty: 3, unit: 'tin', expiryDate: '2026-09-10', batchNo: 'BATCH-2024-0042', estimatedLoss: 222000, status: 'expiring-soon', daysLeft: 5 },
  { id: 'e002', product: 'Paint Primer 20L', sku: 'PNT-PRM-20', category: 'Paint', branch: 'Warehouse', qty: 2, unit: 'tin', expiryDate: '2026-09-15', batchNo: 'BATCH-2024-0038', estimatedLoss: 136000, status: 'expiring-soon', daysLeft: 10 },
  { id: 'e003', product: 'Adhesive Sealant 300ml', sku: 'ADH-SEL-300', category: 'Tools', branch: 'Main Branch', qty: 10, unit: 'tube', expiryDate: '2026-08-31', batchNo: 'BATCH-2025-0011', estimatedLoss: 85000, status: 'expired', daysLeft: -5 },
  { id: 'e004', product: 'Waterproofing Compound 5L', sku: 'WPF-5L', category: 'Cement', branch: 'Main Branch', qty: 4, unit: 'tin', expiryDate: '2026-08-15', batchNo: 'BATCH-2024-0055', estimatedLoss: 180000, status: 'expired', daysLeft: -21 },
  { id: 'e005', product: 'Tile Adhesive 25kg', sku: 'TLA-25KG', category: 'Cement', branch: 'Warehouse', qty: 6, unit: 'bag', expiryDate: '2026-09-20', batchNo: 'BATCH-2025-0019', estimatedLoss: 144000, status: 'expiring-soon', daysLeft: 15 },
];

// ─── Helpers ──────────────────────────────────────────────────────────────────

const CATEGORIES = ['Cement', 'Iron Sheets', 'Steel', 'Pipes', 'Tools', 'Paint', 'Timber', 'Electrical', 'Plumbing', 'Roofing', 'Fasteners'];
const BRANCHES = ['Main Branch', 'Warehouse', 'Mwanza Branch'];

type SortDir = 'asc' | 'desc' | null;

function SortIcon({ field, sortField, sortDir }: { field: string; sortField: string; sortDir: SortDir }) {
  if (sortField !== field) return <ChevronsUpDown size={13} className="text-muted-foreground/40" />;
  return sortDir === 'asc' ? <ChevronUp size={13} className="text-primary" /> : <ChevronDown size={13} className="text-primary" />;
}

function useSortable<T>(data: T[], defaultField: string) {
  const [sortField, setSortField] = useState(defaultField);
  const [sortDir, setSortDir] = useState<SortDir>('asc');

  const handleSort = (field: string) => {
    if (sortField === field) {
      setSortDir(d => d === 'asc' ? 'desc' : 'asc');
    } else {
      setSortField(field);
      setSortDir('asc');
    }
  };

  const sorted = useMemo(() => {
    return [...data].sort((a: any, b: any) => {
      const av = a[sortField];
      const bv = b[sortField];
      if (av === null || av === undefined) return 1;
      if (bv === null || bv === undefined) return -1;
      const cmp = typeof av === 'string' ? av.localeCompare(bv) : av - bv;
      return sortDir === 'asc' ? cmp : -cmp;
    });
  }, [data, sortField, sortDir]);

  return { sorted, sortField, sortDir, handleSort };
}

// ─── Filter Bar ───────────────────────────────────────────────────────────────

interface FilterState {
  search: string;
  category: string;
  branch: string;
  status: string;
}

function FilterBar({ filters, onChange, statusOptions }: {
  filters: FilterState;
  onChange: (f: FilterState) => void;
  statusOptions: { value: string; label: string }[];
}) {
  return (
    <div className="flex flex-wrap gap-2 items-center">
      <div className="relative flex-1 min-w-[180px] max-w-xs">
        <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
        <input
          type="text"
          placeholder="Search product, SKU…"
          value={filters.search}
          onChange={e => onChange({ ...filters, search: e.target.value })}
          className="input pl-8 h-8 text-sm w-full"
        />
      </div>
      <select
        value={filters.category}
        onChange={e => onChange({ ...filters, category: e.target.value })}
        className="input h-8 text-sm pr-7 min-w-[130px]"
      >
        <option value="">All Categories</option>
        {CATEGORIES.map(c => <option key={c} value={c}>{c}</option>)}
      </select>
      <select
        value={filters.branch}
        onChange={e => onChange({ ...filters, branch: e.target.value })}
        className="input h-8 text-sm pr-7 min-w-[130px]"
      >
        <option value="">All Branches</option>
        {BRANCHES.map(b => <option key={b} value={b}>{b}</option>)}
      </select>
      {statusOptions.length > 0 && (
        <select
          value={filters.status}
          onChange={e => onChange({ ...filters, status: e.target.value })}
          className="input h-8 text-sm pr-7 min-w-[120px]"
        >
          <option value="">All Status</option>
          {statusOptions.map(s => <option key={s.value} value={s.value}>{s.label}</option>)}
        </select>
      )}
      {(filters.search || filters.category || filters.branch || filters.status) && (
        <button
          onClick={() => onChange({ search: '', category: '', branch: '', status: '' })}
          className="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground transition-colors px-2 h-8 rounded border border-border hover:border-foreground/30"
        >
          <X size={12} /> Clear
        </button>
      )}
    </div>
  );
}

// ─── Tab: Current Stock ───────────────────────────────────────────────────────

function CurrentStockTab() {
  const [filters, setFilters] = useState<FilterState>({ search: '', category: '', branch: '', status: '' });

  const filtered = useMemo(() => stockItems.filter(item => {
    if (filters.search) {
      const q = filters.search.toLowerCase();
      if (!item.name.toLowerCase().includes(q) && !item.sku.toLowerCase().includes(q)) return false;
    }
    if (filters.category && item.category !== filters.category) return false;
    if (filters.branch && item.branch !== filters.branch) return false;
    if (filters.status && item.status !== filters.status) return false;
    return true;
  }), [filters]);

  const { sorted, sortField, sortDir, handleSort } = useSortable(filtered, 'name');

  const th = (label: string, field: string) => (
    <th
      className="px-3 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide cursor-pointer hover:text-foreground select-none whitespace-nowrap"
      onClick={() => handleSort(field)}
    >
      <span className="flex items-center gap-1">{label}<SortIcon field={field} sortField={sortField} sortDir={sortDir} /></span>
    </th>
  );

  return (
    <div className="space-y-3">
      <FilterBar
        filters={filters}
        onChange={setFilters}
        statusOptions={[
          { value: 'in-stock', label: 'In Stock' },
          { value: 'low-stock', label: 'Low Stock' },
          { value: 'out-of-stock', label: 'Out of Stock' },
        ]}
      />
      <div className="text-xs text-muted-foreground">{sorted.length} product{sorted.length !== 1 ? 's' : ''} found</div>
      <div className="overflow-x-auto rounded-lg border border-border">
        <table className="w-full text-sm">
          <thead className="bg-muted/40 border-b border-border">
            <tr>
              {th('Product', 'name')}
              {th('SKU', 'sku')}
              {th('Category', 'category')}
              {th('Branch', 'branch')}
              {th('Stock', 'currentStock')}
              {th('Min Stock', 'minStock')}
              {th('Stock Value', 'stockValue')}
              {th('Last Movement', 'lastMovement')}
              <th className="px-3 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide">Status</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-border">
            {sorted.map(item => (
              <tr key={item.id} className="hover:bg-muted/20 transition-colors">
                <td className="px-3 py-2.5">
                  <div className="font-medium text-foreground text-sm">{item.name}</div>
                  <div className="text-xs text-muted-foreground">{item.brand}</div>
                </td>
                <td className="px-3 py-2.5 text-xs font-mono text-muted-foreground">{item.sku}</td>
                <td className="px-3 py-2.5 text-xs text-muted-foreground">{item.category}</td>
                <td className="px-3 py-2.5 text-xs text-muted-foreground">{item.branch}</td>
                <td className="px-3 py-2.5">
                  <span className={`font-semibold tabular-nums ${item.currentStock === 0 ? 'text-danger' : item.currentStock <= item.minStock ? 'text-warning' : 'text-foreground'}`}>
                    {item.currentStock.toLocaleString()}
                  </span>
                  <span className="text-xs text-muted-foreground ml-1">{item.unit}</span>
                </td>
                <td className="px-3 py-2.5 text-xs text-muted-foreground tabular-nums">{item.minStock}</td>
                <td className="px-3 py-2.5">
                  <MoneyDisplay amount={item.stockValue} currency="TZS" compact className="text-sm font-medium text-foreground" />
                </td>
                <td className="px-3 py-2.5 text-xs text-muted-foreground">{item.lastMovement}</td>
                <td className="px-3 py-2.5">
                  <StatusBadge status={item.status} />
                </td>
              </tr>
            ))}
            {sorted.length === 0 && (
              <tr><td colSpan={9} className="px-3 py-8 text-center text-sm text-muted-foreground">No products match the current filters.</td></tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}

// ─── Tab: Stock Movements ─────────────────────────────────────────────────────

const movementTypeConfig = {
  in: { label: 'Stock In', icon: <ArrowUpCircle size={14} />, color: 'text-success' },
  out: { label: 'Stock Out', icon: <ArrowDownCircle size={14} />, color: 'text-danger' },
  transfer: { label: 'Transfer', icon: <ArrowLeftRight size={14} />, color: 'text-info' },
  adjustment: { label: 'Adjustment', icon: <SlidersHorizontal size={14} />, color: 'text-warning' },
};

function StockMovementsTab() {
  const [filters, setFilters] = useState<FilterState>({ search: '', category: '', branch: '', status: '' });

  const filtered = useMemo(() => movements.filter(m => {
    if (filters.search) {
      const q = filters.search.toLowerCase();
      if (!m.product.toLowerCase().includes(q) && !m.sku.toLowerCase().includes(q) && !m.reference.toLowerCase().includes(q)) return false;
    }
    if (filters.category && m.category !== filters.category) return false;
    if (filters.branch && m.branch !== filters.branch) return false;
    if (filters.status && m.type !== filters.status) return false;
    return true;
  }), [filters]);

  const { sorted, sortField, sortDir, handleSort } = useSortable(filtered, 'date');

  const th = (label: string, field: string) => (
    <th className="px-3 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide cursor-pointer hover:text-foreground select-none whitespace-nowrap" onClick={() => handleSort(field)}>
      <span className="flex items-center gap-1">{label}<SortIcon field={field} sortField={sortField} sortDir={sortDir} /></span>
    </th>
  );

  return (
    <div className="space-y-3">
      <FilterBar
        filters={filters}
        onChange={setFilters}
        statusOptions={[
          { value: 'in', label: 'Stock In' },
          { value: 'out', label: 'Stock Out' },
          { value: 'transfer', label: 'Transfer' },
          { value: 'adjustment', label: 'Adjustment' },
        ]}
      />
      <div className="text-xs text-muted-foreground">{sorted.length} movement{sorted.length !== 1 ? 's' : ''} found</div>
      <div className="overflow-x-auto rounded-lg border border-border">
        <table className="w-full text-sm">
          <thead className="bg-muted/40 border-b border-border">
            <tr>
              {th('Date', 'date')}
              {th('Product', 'product')}
              {th('Category', 'category')}
              {th('Branch', 'branch')}
              <th className="px-3 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide">Type</th>
              {th('Qty In', 'qtyIn')}
              {th('Qty Out', 'qtyOut')}
              {th('Balance', 'balance')}
              {th('Reference', 'reference')}
              {th('User', 'user')}
            </tr>
          </thead>
          <tbody className="divide-y divide-border">
            {sorted.map(m => {
              const cfg = movementTypeConfig[m.type];
              return (
                <tr key={m.id} className="hover:bg-muted/20 transition-colors">
                  <td className="px-3 py-2.5 text-xs text-muted-foreground whitespace-nowrap">{m.date}</td>
                  <td className="px-3 py-2.5">
                    <div className="font-medium text-foreground text-sm">{m.product}</div>
                    <div className="text-xs font-mono text-muted-foreground">{m.sku}</div>
                  </td>
                  <td className="px-3 py-2.5 text-xs text-muted-foreground">{m.category}</td>
                  <td className="px-3 py-2.5 text-xs text-muted-foreground">{m.branch}</td>
                  <td className="px-3 py-2.5">
                    <span className={`flex items-center gap-1 text-xs font-medium ${cfg.color}`}>
                      {cfg.icon}{cfg.label}
                    </span>
                  </td>
                  <td className="px-3 py-2.5 text-sm font-semibold text-success tabular-nums">
                    {m.qtyIn !== null ? `+${m.qtyIn}` : '—'}
                  </td>
                  <td className="px-3 py-2.5 text-sm font-semibold text-danger tabular-nums">
                    {m.qtyOut !== null ? `-${m.qtyOut}` : '—'}
                  </td>
                  <td className="px-3 py-2.5 text-sm font-semibold text-foreground tabular-nums">{m.balance}</td>
                  <td className="px-3 py-2.5 text-xs font-mono text-primary">{m.reference}</td>
                  <td className="px-3 py-2.5 text-xs text-muted-foreground">{m.user}</td>
                </tr>
              );
            })}
            {sorted.length === 0 && (
              <tr><td colSpan={10} className="px-3 py-8 text-center text-sm text-muted-foreground">No movements match the current filters.</td></tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}

// ─── Tab: Low Stock ───────────────────────────────────────────────────────────

function LowStockTab() {
  const [filters, setFilters] = useState<FilterState>({ search: '', category: '', branch: '', status: '' });

  const lowStockItems = useMemo(() => stockItems.filter(i => i.status === 'low-stock' || i.status === 'out-of-stock'), []);

  const filtered = useMemo(() => lowStockItems.filter(item => {
    if (filters.search) {
      const q = filters.search.toLowerCase();
      if (!item.name.toLowerCase().includes(q) && !item.sku.toLowerCase().includes(q)) return false;
    }
    if (filters.category && item.category !== filters.category) return false;
    if (filters.branch && item.branch !== filters.branch) return false;
    if (filters.status && item.status !== filters.status) return false;
    return true;
  }), [lowStockItems, filters]);

  const { sorted, sortField, sortDir, handleSort } = useSortable(filtered, 'currentStock');

  const th = (label: string, field: string) => (
    <th className="px-3 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide cursor-pointer hover:text-foreground select-none whitespace-nowrap" onClick={() => handleSort(field)}>
      <span className="flex items-center gap-1">{label}<SortIcon field={field} sortField={sortField} sortDir={sortDir} /></span>
    </th>
  );

  return (
    <div className="space-y-3">
      <FilterBar
        filters={filters}
        onChange={setFilters}
        statusOptions={[
          { value: 'low-stock', label: 'Low Stock' },
          { value: 'out-of-stock', label: 'Out of Stock' },
        ]}
      />
      <div className="text-xs text-muted-foreground">{sorted.length} item{sorted.length !== 1 ? 's' : ''} need attention</div>
      <div className="overflow-x-auto rounded-lg border border-border">
        <table className="w-full text-sm">
          <thead className="bg-muted/40 border-b border-border">
            <tr>
              {th('Product', 'name')}
              {th('SKU', 'sku')}
              {th('Category', 'category')}
              {th('Branch', 'branch')}
              {th('Current Stock', 'currentStock')}
              {th('Min Stock', 'minStock')}
              <th className="px-3 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide whitespace-nowrap">Shortage</th>
              {th('Last Movement', 'lastMovement')}
              <th className="px-3 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide">Status</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-border">
            {sorted.map(item => {
              const shortage = Math.max(0, item.minStock - item.currentStock);
              return (
                <tr key={item.id} className="hover:bg-muted/20 transition-colors">
                  <td className="px-3 py-2.5">
                    <div className="font-medium text-foreground text-sm">{item.name}</div>
                    <div className="text-xs text-muted-foreground">{item.brand}</div>
                  </td>
                  <td className="px-3 py-2.5 text-xs font-mono text-muted-foreground">{item.sku}</td>
                  <td className="px-3 py-2.5 text-xs text-muted-foreground">{item.category}</td>
                  <td className="px-3 py-2.5 text-xs text-muted-foreground">{item.branch}</td>
                  <td className="px-3 py-2.5">
                    <span className={`font-semibold tabular-nums ${item.currentStock === 0 ? 'text-danger' : 'text-warning'}`}>
                      {item.currentStock}
                    </span>
                    <span className="text-xs text-muted-foreground ml-1">{item.unit}</span>
                  </td>
                  <td className="px-3 py-2.5 text-xs text-muted-foreground tabular-nums">{item.minStock}</td>
                  <td className="px-3 py-2.5">
                    <span className="text-sm font-semibold text-danger tabular-nums">-{shortage}</span>
                    <span className="text-xs text-muted-foreground ml-1">{item.unit}</span>
                  </td>
                  <td className="px-3 py-2.5 text-xs text-muted-foreground">{item.lastMovement}</td>
                  <td className="px-3 py-2.5"><StatusBadge status={item.status} /></td>
                </tr>
              );
            })}
            {sorted.length === 0 && (
              <tr><td colSpan={9} className="px-3 py-8 text-center text-sm text-muted-foreground">No low stock items match the current filters.</td></tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}

// ─── Tab: Damaged ─────────────────────────────────────────────────────────────

const damagedStatusMap: Record<string, { label: string; cls: string }> = {
  pending: { label: 'Pending', cls: 'badge-warning' },
  approved: { label: 'Approved', cls: 'badge-info' },
  'written-off': { label: 'Written Off', cls: 'badge-danger' },
};

function DamagedTab() {
  const [filters, setFilters] = useState<FilterState>({ search: '', category: '', branch: '', status: '' });

  const filtered = useMemo(() => damagedItems.filter(item => {
    if (filters.search) {
      const q = filters.search.toLowerCase();
      if (!item.product.toLowerCase().includes(q) && !item.sku.toLowerCase().includes(q)) return false;
    }
    if (filters.category && item.category !== filters.category) return false;
    if (filters.branch && item.branch !== filters.branch) return false;
    if (filters.status && item.status !== filters.status) return false;
    return true;
  }), [filters]);

  const { sorted, sortField, sortDir, handleSort } = useSortable(filtered, 'date');

  const th = (label: string, field: string) => (
    <th className="px-3 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide cursor-pointer hover:text-foreground select-none whitespace-nowrap" onClick={() => handleSort(field)}>
      <span className="flex items-center gap-1">{label}<SortIcon field={field} sortField={sortField} sortDir={sortDir} /></span>
    </th>
  );

  return (
    <div className="space-y-3">
      <FilterBar
        filters={filters}
        onChange={setFilters}
        statusOptions={[
          { value: 'pending', label: 'Pending' },
          { value: 'approved', label: 'Approved' },
          { value: 'written-off', label: 'Written Off' },
        ]}
      />
      <div className="text-xs text-muted-foreground">{sorted.length} damaged report{sorted.length !== 1 ? 's' : ''}</div>
      <div className="overflow-x-auto rounded-lg border border-border">
        <table className="w-full text-sm">
          <thead className="bg-muted/40 border-b border-border">
            <tr>
              {th('Date', 'date')}
              {th('Product', 'product')}
              {th('Category', 'category')}
              {th('Branch', 'branch')}
              {th('Qty', 'qty')}
              {th('Est. Loss', 'estimatedLoss')}
              {th('Reported By', 'reportedBy')}
              <th className="px-3 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide">Notes</th>
              <th className="px-3 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide">Status</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-border">
            {sorted.map(item => {
              const sc = damagedStatusMap[item.status];
              return (
                <tr key={item.id} className="hover:bg-muted/20 transition-colors">
                  <td className="px-3 py-2.5 text-xs text-muted-foreground whitespace-nowrap">{item.date}</td>
                  <td className="px-3 py-2.5">
                    <div className="font-medium text-foreground text-sm">{item.product}</div>
                    <div className="text-xs font-mono text-muted-foreground">{item.sku}</div>
                  </td>
                  <td className="px-3 py-2.5 text-xs text-muted-foreground">{item.category}</td>
                  <td className="px-3 py-2.5 text-xs text-muted-foreground">{item.branch}</td>
                  <td className="px-3 py-2.5 text-sm font-semibold text-danger tabular-nums">{item.qty} <span className="text-xs font-normal text-muted-foreground">{item.unit}</span></td>
                  <td className="px-3 py-2.5">
                    <MoneyDisplay amount={item.estimatedLoss} currency="TZS" compact className="text-sm font-medium text-danger" />
                  </td>
                  <td className="px-3 py-2.5 text-xs text-muted-foreground">{item.reportedBy}</td>
                  <td className="px-3 py-2.5 text-xs text-muted-foreground max-w-[160px] truncate" title={item.notes}>{item.notes || '—'}</td>
                  <td className="px-3 py-2.5"><span className={sc.cls}>{sc.label}</span></td>
                </tr>
              );
            })}
            {sorted.length === 0 && (
              <tr><td colSpan={9} className="px-3 py-8 text-center text-sm text-muted-foreground">No damaged items match the current filters.</td></tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}

// ─── Tab: Expired ─────────────────────────────────────────────────────────────

function ExpiredTab() {
  const [filters, setFilters] = useState<FilterState>({ search: '', category: '', branch: '', status: '' });

  const filtered = useMemo(() => expiredItems.filter(item => {
    if (filters.search) {
      const q = filters.search.toLowerCase();
      if (!item.product.toLowerCase().includes(q) && !item.sku.toLowerCase().includes(q) && !item.batchNo.toLowerCase().includes(q)) return false;
    }
    if (filters.category && item.category !== filters.category) return false;
    if (filters.branch && item.branch !== filters.branch) return false;
    if (filters.status && item.status !== filters.status) return false;
    return true;
  }), [filters]);

  const { sorted, sortField, sortDir, handleSort } = useSortable(filtered, 'daysLeft');

  const th = (label: string, field: string) => (
    <th className="px-3 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide cursor-pointer hover:text-foreground select-none whitespace-nowrap" onClick={() => handleSort(field)}>
      <span className="flex items-center gap-1">{label}<SortIcon field={field} sortField={sortField} sortDir={sortDir} /></span>
    </th>
  );

  return (
    <div className="space-y-3">
      <FilterBar
        filters={filters}
        onChange={setFilters}
        statusOptions={[
          { value: 'expiring-soon', label: 'Expiring Soon' },
          { value: 'expired', label: 'Expired' },
        ]}
      />
      <div className="text-xs text-muted-foreground">{sorted.length} item{sorted.length !== 1 ? 's' : ''} flagged</div>
      <div className="overflow-x-auto rounded-lg border border-border">
        <table className="w-full text-sm">
          <thead className="bg-muted/40 border-b border-border">
            <tr>
              {th('Product', 'product')}
              {th('SKU', 'sku')}
              {th('Category', 'category')}
              {th('Branch', 'branch')}
              {th('Qty', 'qty')}
              {th('Batch No.', 'batchNo')}
              {th('Expiry Date', 'expiryDate')}
              {th('Days Left', 'daysLeft')}
              {th('Est. Loss', 'estimatedLoss')}
              <th className="px-3 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide">Status</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-border">
            {sorted.map(item => (
              <tr key={item.id} className="hover:bg-muted/20 transition-colors">
                <td className="px-3 py-2.5">
                  <div className="font-medium text-foreground text-sm">{item.product}</div>
                </td>
                <td className="px-3 py-2.5 text-xs font-mono text-muted-foreground">{item.sku}</td>
                <td className="px-3 py-2.5 text-xs text-muted-foreground">{item.category}</td>
                <td className="px-3 py-2.5 text-xs text-muted-foreground">{item.branch}</td>
                <td className="px-3 py-2.5 text-sm font-semibold tabular-nums">{item.qty} <span className="text-xs font-normal text-muted-foreground">{item.unit}</span></td>
                <td className="px-3 py-2.5 text-xs font-mono text-muted-foreground">{item.batchNo}</td>
                <td className="px-3 py-2.5 text-xs text-muted-foreground whitespace-nowrap">{item.expiryDate}</td>
                <td className="px-3 py-2.5">
                  <span className={`text-sm font-semibold tabular-nums ${item.daysLeft < 0 ? 'text-danger' : item.daysLeft <= 7 ? 'text-warning' : 'text-foreground'}`}>
                    {item.daysLeft < 0 ? `${Math.abs(item.daysLeft)}d ago` : `${item.daysLeft}d`}
                  </span>
                </td>
                <td className="px-3 py-2.5">
                  <MoneyDisplay amount={item.estimatedLoss} currency="TZS" compact className="text-sm font-medium text-danger" />
                </td>
                <td className="px-3 py-2.5">
                  <span className={item.status === 'expired' ? 'badge-danger' : 'badge-warning'}>
                    {item.status === 'expired' ? 'Expired' : 'Expiring Soon'}
                  </span>
                </td>
              </tr>
            ))}
            {sorted.length === 0 && (
              <tr><td colSpan={10} className="px-3 py-8 text-center text-sm text-muted-foreground">No expired items match the current filters.</td></tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}

// ─── Main Page ────────────────────────────────────────────────────────────────

const tabs: { id: Tab; label: string; icon: React.ReactNode; count: number; countColor?: string }[] = [
  { id: 'current', label: 'Current Stock', icon: <Package size={15} />, count: stockItems.length },
  { id: 'movements', label: 'Stock Movements', icon: <ArrowLeftRight size={15} />, count: movements.length },
  { id: 'low-stock', label: 'Low Stock', icon: <TrendingDown size={15} />, count: stockItems.filter(i => i.status === 'low-stock' || i.status === 'out-of-stock').length, countColor: 'text-warning' },
  { id: 'damaged', label: 'Damaged', icon: <AlertTriangle size={15} />, count: damagedItems.length, countColor: 'text-danger' },
  { id: 'expired', label: 'Expired', icon: <Clock size={15} />, count: expiredItems.length, countColor: 'text-danger' },
];

const totalStockValue = stockItems.reduce((s, i) => s + i.stockValue, 0);
const totalProducts = stockItems.length;
const lowStockCount = stockItems.filter(i => i.status === 'low-stock' || i.status === 'out-of-stock').length;
const outOfStockCount = stockItems.filter(i => i.status === 'out-of-stock').length;

export default function InventoryPage() {
  const [layout] = useState<DashboardLayout>('classic');
  const [activeTab, setActiveTab] = useState<Tab>('current');

  return (
    <AppLayout layout={layout}>
      <div className="space-y-5">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-bold text-foreground flex items-center gap-2">
              <Warehouse size={22} className="text-primary" />
              Inventory Hub
            </h1>
            <p className="text-sm text-muted-foreground mt-0.5">Real-time stock overview across all branches</p>
          </div>
          <div className="flex items-center gap-2">
            <button className="btn-outline h-8 px-3 text-sm flex items-center gap-1.5">
              <RefreshCw size={13} /> Refresh
            </button>
            <button className="btn-outline h-8 px-3 text-sm flex items-center gap-1.5">
              <Download size={13} /> Export
            </button>
          </div>
        </div>

        {/* KPI Cards */}
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <KpiCard
            title="Total Products"
            value={totalProducts}
            icon={<Package size={18} className="text-primary" />}
            iconBg="bg-primary/10"
            subtitle="Across all branches"
          />
          <KpiCard
            title="Stock Value"
            value={totalStockValue}
            isMoney
            currency="TZS"
            icon={<Warehouse size={18} className="text-success" />}
            iconBg="bg-success/10"
            variant="success"
          />
          <KpiCard
            title="Low Stock Items"
            value={lowStockCount}
            icon={<TrendingDown size={18} className="text-warning" />}
            iconBg="bg-warning/10"
            variant="warning"
            subtitle="Need reordering"
          />
          <KpiCard
            title="Out of Stock"
            value={outOfStockCount}
            icon={<AlertTriangle size={18} className="text-danger" />}
            iconBg="bg-danger/10"
            variant="danger"
            subtitle="Immediate action needed"
          />
        </div>

        {/* Tabs */}
        <div className="card p-0 overflow-hidden">
          {/* Tab bar */}
          <div className="flex overflow-x-auto border-b border-border bg-muted/20">
            {tabs.map(tab => (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                className={[
                  'flex items-center gap-2 px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors shrink-0',
                  activeTab === tab.id
                    ? 'border-primary text-primary bg-background' :'border-transparent text-muted-foreground hover:text-foreground hover:bg-muted/40',
                ].join(' ')}
              >
                {tab.icon}
                {tab.label}
                <span className={`text-xs font-bold px-1.5 py-0.5 rounded-full bg-muted ${tab.countColor || 'text-muted-foreground'}`}>
                  {tab.count}
                </span>
              </button>
            ))}
          </div>

          {/* Tab content */}
          <div className="p-4">
            {activeTab === 'current' && <CurrentStockTab />}
            {activeTab === 'movements' && <StockMovementsTab />}
            {activeTab === 'low-stock' && <LowStockTab />}
            {activeTab === 'damaged' && <DamagedTab />}
            {activeTab === 'expired' && <ExpiredTab />}
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
