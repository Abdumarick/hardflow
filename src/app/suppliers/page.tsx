'use client';
import React, { useState, useMemo } from 'react';
import AppLayout, { DashboardLayout } from '@/components/AppLayout';
import KpiCard from '@/components/ui/KpiCard';
import MoneyDisplay from '@/components/ui/MoneyDisplay';

import { Truck, Search, Plus, Download, Phone, Mail, MapPin, Star, TrendingUp, Clock, CheckCircle2, ChevronRight, X, FileText, ShoppingBag, CreditCard, Calendar, Building2, Hash, Edit, BarChart2, Banknote, PackageCheck, AlertCircle,  } from 'lucide-react';

// ─── Types ────────────────────────────────────────────────────────────────────

type SupplierStatus = 'active' | 'inactive' | 'on-hold';
type PaymentTerms = 'cod' | 'net-7' | 'net-15' | 'net-30' | 'net-60' | 'prepaid';
type SupplierCategory = 'cement' | 'steel' | 'tiles' | 'paints' | 'plumbing' | 'electrical' | 'hardware' | 'timber';
type POStatus = 'created' | 'ordered' | 'partially-received' | 'completed';

interface Supplier {
  id: string;
  name: string;
  category: SupplierCategory;
  contactPerson: string;
  phone: string;
  altPhone: string;
  email: string;
  location: string;
  address: string;
  status: SupplierStatus;
  paymentTerms: PaymentTerms;
  creditLimit: number;
  outstanding: number;
  taxPin: string;
  bankName: string;
  bankAccount: string;
  joinDate: string;
  notes: string;
  // Performance
  totalOrders: number;
  totalValue: number;
  onTimeDeliveryRate: number;
  qualityRating: number;
  avgLeadDays: number;
  returnRate: number;
  lastOrderDate: string;
}

interface PurchaseOrder {
  id: string;
  poNumber: string;
  date: string;
  expectedDate: string;
  status: POStatus;
  totalItems: number;
  totalAmount: number;
  receivedAmount: number;
  branch: string;
}

// ─── Mock Data ─────────────────────────────────────────────────────────────────

const suppliers: Supplier[] = [
  {
    id: 'sup-001', name: 'Bamburi Cement Ltd', category: 'cement',
    contactPerson: 'George Mwangi', phone: '+255 22 211 0000', altPhone: '+255 754 100 200',
    email: 'sales@bamburi.co.tz', location: 'Dar es Salaam', address: 'Industrial Area, Pugu Road, Dar es Salaam',
    status: 'active', paymentTerms: 'net-30', creditLimit: 20000000, outstanding: 7200000,
    taxPin: 'TIN-100-234-567', bankName: 'CRDB Bank', bankAccount: '01J1500000000',
    joinDate: '2021-03-10', notes: 'Primary cement supplier. Bulk discount available above 500 bags.',
    totalOrders: 48, totalValue: 185000000, onTimeDeliveryRate: 94, qualityRating: 4.7,
    avgLeadDays: 3, returnRate: 0.8, lastOrderDate: '2026-09-01',
  },
  {
    id: 'sup-002', name: 'Mabati Rolling Mills', category: 'steel',
    contactPerson: 'Sarah Njoroge', phone: '+255 22 215 3300', altPhone: '+255 722 315 330',
    email: 'orders@mabati.co.tz', location: 'Dar es Salaam', address: 'Mikocheni Light Industrial, Dar es Salaam',
    status: 'active', paymentTerms: 'net-15', creditLimit: 15000000, outstanding: 8750000,
    taxPin: 'TIN-200-345-678', bankName: 'NMB Bank', bankAccount: '40710000000',
    joinDate: '2020-07-22', notes: 'Exclusive supplier for roofing sheets. Minimum order 50 sheets.',
    totalOrders: 62, totalValue: 320000000, onTimeDeliveryRate: 88, qualityRating: 4.5,
    avgLeadDays: 5, returnRate: 1.2, lastOrderDate: '2026-08-30',
  },
  {
    id: 'sup-003', name: 'Steel Structures EA', category: 'steel',
    contactPerson: 'James Otieno', phone: '+255 754 123 456', altPhone: '',
    email: 'james@steelstructures.co.tz', location: 'Arusha', address: 'Arusha Industrial Zone, Plot 12',
    status: 'active', paymentTerms: 'net-30', creditLimit: 25000000, outstanding: 0,
    taxPin: 'TIN-300-456-789', bankName: 'Stanbic Bank', bankAccount: '9060000000',
    joinDate: '2022-01-15', notes: 'Structural steel and fabrication. Lead time 7–10 days for custom orders.',
    totalOrders: 29, totalValue: 142000000, onTimeDeliveryRate: 97, qualityRating: 4.9,
    avgLeadDays: 7, returnRate: 0.3, lastOrderDate: '2026-08-28',
  },
  {
    id: 'sup-004', name: 'Twiga Cement', category: 'cement',
    contactPerson: 'Amina Hassan', phone: '+255 22 218 0000', altPhone: '+255 713 218 000',
    email: 'amina.h@twigacement.co.tz', location: 'Dar es Salaam', address: 'Kurasini, Dar es Salaam',
    status: 'active', paymentTerms: 'cod', creditLimit: 0, outstanding: 0,
    taxPin: 'TIN-400-567-890', bankName: 'KCB Bank', bankAccount: '1234567890',
    joinDate: '2021-11-05', notes: 'Cash on delivery only. Competitive pricing for bulk orders.',
    totalOrders: 35, totalValue: 98000000, onTimeDeliveryRate: 91, qualityRating: 4.3,
    avgLeadDays: 2, returnRate: 0.5, lastOrderDate: '2026-08-27',
  },
  {
    id: 'sup-005', name: 'Karibu Paints Ltd', category: 'paints',
    contactPerson: 'Peter Kamau', phone: '+255 22 213 5500', altPhone: '+255 784 135 500',
    email: 'peter.k@karibupaints.co.tz', location: 'Dar es Salaam', address: 'Chang\'ombe Industrial, Dar es Salaam',
    status: 'active', paymentTerms: 'net-7', creditLimit: 5000000, outstanding: 3200000,
    taxPin: 'TIN-500-678-901', bankName: 'Equity Bank', bankAccount: '0023456789',
    joinDate: '2023-04-18', notes: 'Full range of interior and exterior paints. Seasonal promotions available.',
    totalOrders: 21, totalValue: 42000000, onTimeDeliveryRate: 85, qualityRating: 4.1,
    avgLeadDays: 4, returnRate: 2.1, lastOrderDate: '2026-08-24',
  },
  {
    id: 'sup-006', name: 'Simba Plumbing Supplies', category: 'plumbing',
    contactPerson: 'Grace Wanjiku', phone: '+255 784 321 654', altPhone: '',
    email: 'grace@simbaplumbing.co.tz', location: 'Mombasa', address: 'Mombasa Road Industrial Park',
    status: 'active', paymentTerms: 'net-30', creditLimit: 8000000, outstanding: 0,
    taxPin: 'TIN-600-789-012', bankName: 'Absa Bank', bankAccount: '4056789012',
    joinDate: '2022-08-30', notes: 'Wide range of PVC and CPVC fittings. Free delivery above 2M orders.',
    totalOrders: 18, totalValue: 38000000, onTimeDeliveryRate: 96, qualityRating: 4.6,
    avgLeadDays: 6, returnRate: 0.9, lastOrderDate: '2026-08-22',
  },
  {
    id: 'sup-007', name: 'East Africa Tiles', category: 'tiles',
    contactPerson: 'Hassan Ally', phone: '+255 22 219 7700', altPhone: '+255 756 197 700',
    email: 'hassan@eatiles.co.tz', location: 'Dar es Salaam', address: 'Ubungo Industrial Area, Dar es Salaam',
    status: 'on-hold', paymentTerms: 'prepaid', creditLimit: 0, outstanding: 0,
    taxPin: 'TIN-700-890-123', bankName: 'DTB Bank', bankAccount: '0078901234',
    joinDate: '2023-09-12', notes: 'On hold due to quality issues in last shipment. Under review.',
    totalOrders: 11, totalValue: 62000000, onTimeDeliveryRate: 72, qualityRating: 3.2,
    avgLeadDays: 10, returnRate: 5.8, lastOrderDate: '2026-08-20',
  },
  {
    id: 'sup-008', name: 'Unga Hardware Supplies', category: 'hardware',
    contactPerson: 'David Omondi', phone: '+255 713 456 789', altPhone: '+255 713 456 790',
    email: 'david@ungahardware.co.tz', location: 'Nairobi', address: 'Industrial Area, Nairobi',
    status: 'inactive', paymentTerms: 'net-60', creditLimit: 10000000, outstanding: 3480000,
    taxPin: 'TIN-800-901-234', bankName: 'I&M Bank', bankAccount: '0089012345',
    joinDate: '2020-02-14', notes: 'Inactive since August 2026. Outstanding balance pending resolution.',
    totalOrders: 44, totalValue: 210000000, onTimeDeliveryRate: 79, qualityRating: 3.8,
    avgLeadDays: 12, returnRate: 3.4, lastOrderDate: '2026-08-25',
  },
];

const supplierPOs: Record<string, PurchaseOrder[]> = {
  'sup-001': [
    { id: 'po001', poNumber: 'PO-2026-0091', date: '2026-09-01', expectedDate: '2026-09-08', status: 'partially-received', totalItems: 5, totalAmount: 12400000, receivedAmount: 7440000, branch: 'Main Branch' },
    { id: 'po009', poNumber: 'PO-2026-0083', date: '2026-08-18', expectedDate: '2026-08-24', status: 'completed', totalItems: 3, totalAmount: 7200000, receivedAmount: 7200000, branch: 'Warehouse' },
    { id: 'po015', poNumber: 'PO-2026-0071', date: '2026-07-30', expectedDate: '2026-08-05', status: 'completed', totalItems: 4, totalAmount: 9600000, receivedAmount: 9600000, branch: 'Main Branch' },
    { id: 'po022', poNumber: 'PO-2026-0058', date: '2026-07-10', expectedDate: '2026-07-17', status: 'completed', totalItems: 6, totalAmount: 14400000, receivedAmount: 14400000, branch: 'Main Branch' },
  ],
  'sup-002': [
    { id: 'po002', poNumber: 'PO-2026-0090', date: '2026-08-30', expectedDate: '2026-09-05', status: 'ordered', totalItems: 3, totalAmount: 8750000, receivedAmount: 0, branch: 'Main Branch' },
    { id: 'po010', poNumber: 'PO-2026-0082', date: '2026-08-15', expectedDate: '2026-08-21', status: 'partially-received', totalItems: 5, totalAmount: 11200000, receivedAmount: 4480000, branch: 'Main Branch' },
    { id: 'po018', poNumber: 'PO-2026-0065', date: '2026-07-20', expectedDate: '2026-07-27', status: 'completed', totalItems: 4, totalAmount: 9800000, receivedAmount: 9800000, branch: 'Warehouse' },
  ],
  'sup-003': [
    { id: 'po003', poNumber: 'PO-2026-0089', date: '2026-08-28', expectedDate: '2026-09-03', status: 'completed', totalItems: 8, totalAmount: 21600000, receivedAmount: 21600000, branch: 'Warehouse' },
    { id: 'po016', poNumber: 'PO-2026-0070', date: '2026-07-28', expectedDate: '2026-08-04', status: 'completed', totalItems: 5, totalAmount: 15000000, receivedAmount: 15000000, branch: 'Main Branch' },
  ],
  'sup-004': [
    { id: 'po004', poNumber: 'PO-2026-0088', date: '2026-08-27', expectedDate: '2026-09-02', status: 'completed', totalItems: 4, totalAmount: 9600000, receivedAmount: 9600000, branch: 'Main Branch' },
    { id: 'po011', poNumber: 'PO-2026-0079', date: '2026-08-10', expectedDate: '2026-08-14', status: 'completed', totalItems: 3, totalAmount: 7200000, receivedAmount: 7200000, branch: 'Main Branch' },
  ],
  'sup-005': [
    { id: 'po006', poNumber: 'PO-2026-0086', date: '2026-08-24', expectedDate: '2026-08-30', status: 'ordered', totalItems: 6, totalAmount: 3200000, receivedAmount: 0, branch: 'Main Branch' },
    { id: 'po019', poNumber: 'PO-2026-0062', date: '2026-07-15', expectedDate: '2026-07-22', status: 'completed', totalItems: 4, totalAmount: 2100000, receivedAmount: 2100000, branch: 'Main Branch' },
  ],
  'sup-006': [
    { id: 'po007', poNumber: 'PO-2026-0085', date: '2026-08-22', expectedDate: '2026-08-28', status: 'completed', totalItems: 9, totalAmount: 4150000, receivedAmount: 4150000, branch: 'Warehouse' },
    { id: 'po020', poNumber: 'PO-2026-0060', date: '2026-07-12', expectedDate: '2026-07-18', status: 'completed', totalItems: 6, totalAmount: 3200000, receivedAmount: 3200000, branch: 'Main Branch' },
  ],
  'sup-007': [
    { id: 'po008', poNumber: 'PO-2026-0084', date: '2026-08-20', expectedDate: '2026-08-26', status: 'created', totalItems: 7, totalAmount: 16800000, receivedAmount: 0, branch: 'Main Branch' },
  ],
  'sup-008': [
    { id: 'po005', poNumber: 'PO-2026-0087', date: '2026-08-25', expectedDate: '2026-09-01', status: 'partially-received', totalItems: 12, totalAmount: 5800000, receivedAmount: 2320000, branch: 'Main Branch' },
    { id: 'po021', poNumber: 'PO-2026-0055', date: '2026-07-05', expectedDate: '2026-07-17', status: 'completed', totalItems: 8, totalAmount: 9200000, receivedAmount: 9200000, branch: 'Warehouse' },
  ],
};

// ─── Config ────────────────────────────────────────────────────────────────────

const categoryColors: Record<SupplierCategory, string> = {
  cement:     'bg-amber-100 text-amber-700',
  steel:      'bg-blue-100 text-blue-700',
  tiles:      'bg-purple-100 text-purple-700',
  paints:     'bg-pink-100 text-pink-700',
  plumbing:   'bg-cyan-100 text-cyan-700',
  electrical: 'bg-yellow-100 text-yellow-700',
  hardware:   'bg-orange-100 text-orange-700',
  timber:     'bg-green-100 text-green-700',
};

const paymentTermsLabel: Record<PaymentTerms, string> = {
  cod:      'Cash on Delivery',
  'net-7':  'Net 7 Days',
  'net-15': 'Net 15 Days',
  'net-30': 'Net 30 Days',
  'net-60': 'Net 60 Days',
  prepaid:  'Prepaid',
};

const statusConfig: Record<SupplierStatus, { label: string; color: string }> = {
  active:    { label: 'Active',    color: 'bg-success/10 text-success border border-success/20' },
  inactive:  { label: 'Inactive',  color: 'bg-muted text-muted-foreground border border-border' },
  'on-hold': { label: 'On Hold',   color: 'bg-warning/10 text-warning border border-warning/20' },
};

const poStatusConfig: Record<POStatus, { label: string; color: string; icon: React.ReactNode }> = {
  created:              { label: 'Created',            color: 'bg-muted text-muted-foreground border border-border',        icon: <FileText size={11} /> },
  ordered:              { label: 'Ordered',            color: 'bg-info/10 text-info border border-info/20',                 icon: <Truck size={11} /> },
  'partially-received': { label: 'Partial',            color: 'bg-warning/10 text-warning border border-warning/20',       icon: <PackageCheck size={11} /> },
  completed:            { label: 'Completed',          color: 'bg-success/10 text-success border border-success/20',       icon: <CheckCircle2 size={11} /> },
};

// ─── Star Rating ───────────────────────────────────────────────────────────────

function StarRating({ value }: { value: number }) {
  return (
    <div className="flex items-center gap-0.5">
      {[1, 2, 3, 4, 5].map(i => (
        <Star
          key={i}
          size={12}
          className={i <= Math.round(value) ? 'text-warning fill-warning' : 'text-border fill-border'}
        />
      ))}
      <span className="text-xs text-muted-foreground ml-1">{value.toFixed(1)}</span>
    </div>
  );
}

// ─── Performance Bar ───────────────────────────────────────────────────────────

function PerformanceBar({ value, max = 100, color }: { value: number; max?: number; color: string }) {
  const pct = Math.min((value / max) * 100, 100);
  return (
    <div className="flex items-center gap-2">
      <div className="flex-1 h-1.5 bg-border rounded-full overflow-hidden">
        <div className={`h-full rounded-full ${color}`} style={{ width: `${pct}%` }} />
      </div>
      <span className="text-xs font-medium text-foreground w-8 text-right">{value}%</span>
    </div>
  );
}

// ─── Supplier Detail Panel ─────────────────────────────────────────────────────

function SupplierDetailPanel({ supplier, onClose }: { supplier: Supplier; onClose: () => void }) {
  const [activeTab, setActiveTab] = useState<'overview' | 'payment' | 'performance' | 'orders'>('overview');
  const pos = supplierPOs[supplier.id] ?? [];

  const tabs = [
    { id: 'overview',    label: 'Overview',    icon: <Building2 size={14} /> },
    { id: 'payment',     label: 'Payment',     icon: <CreditCard size={14} /> },
    { id: 'performance', label: 'Performance', icon: <BarChart2 size={14} /> },
    { id: 'orders',      label: 'PO History',  icon: <ShoppingBag size={14} /> },
  ] as const;

  const completedPOs = pos.filter(p => p.status === 'completed').length;
  const activePOs    = pos.filter(p => p.status !== 'completed').length;

  return (
    <div className="fixed inset-0 z-50 flex">
      {/* Backdrop */}
      <div className="flex-1 bg-black/40" onClick={onClose} />

      {/* Panel */}
      <div className="w-full max-w-xl bg-card border-l border-border flex flex-col h-full overflow-hidden shadow-2xl">
        {/* Header */}
        <div className="flex items-start justify-between px-5 py-4 border-b border-border">
          <div className="flex items-start gap-3">
            <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
              <Truck size={18} className="text-primary" />
            </div>
            <div>
              <h2 className="text-base font-bold text-foreground">{supplier.name}</h2>
              <div className="flex items-center gap-2 mt-0.5">
                <span className={`text-2xs font-semibold px-2 py-0.5 rounded-full capitalize ${categoryColors[supplier.category]}`}>
                  {supplier.category}
                </span>
                <span className={`text-2xs font-semibold px-2 py-0.5 rounded-full ${statusConfig[supplier.status].color}`}>
                  {statusConfig[supplier.status].label}
                </span>
              </div>
            </div>
          </div>
          <button onClick={onClose} className="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-muted transition-colors text-muted-foreground">
            <X size={16} />
          </button>
        </div>

        {/* Tabs */}
        <div className="flex border-b border-border px-4">
          {tabs.map(t => (
            <button
              key={t.id}
              onClick={() => setActiveTab(t.id)}
              className={`flex items-center gap-1.5 px-3 py-2.5 text-xs font-medium border-b-2 transition-colors ${
                activeTab === t.id
                  ? 'border-primary text-primary' :'border-transparent text-muted-foreground hover:text-foreground'
              }`}
            >
              {t.icon}{t.label}
            </button>
          ))}
        </div>

        {/* Content */}
        <div className="flex-1 overflow-y-auto p-5 space-y-5">

          {/* ── Overview Tab ── */}
          {activeTab === 'overview' && (
            <>
              {/* Contact Details */}
              <section>
                <h3 className="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-3">Contact Details</h3>
                <div className="space-y-2.5">
                  <div className="flex items-center gap-2.5">
                    <div className="w-7 h-7 rounded-lg bg-muted flex items-center justify-center shrink-0">
                      <Phone size={13} className="text-muted-foreground" />
                    </div>
                    <div>
                      <p className="text-sm font-medium text-foreground">{supplier.phone}</p>
                      {supplier.altPhone && <p className="text-xs text-muted-foreground">{supplier.altPhone}</p>}
                    </div>
                  </div>
                  <div className="flex items-center gap-2.5">
                    <div className="w-7 h-7 rounded-lg bg-muted flex items-center justify-center shrink-0">
                      <Mail size={13} className="text-muted-foreground" />
                    </div>
                    <p className="text-sm text-foreground">{supplier.email}</p>
                  </div>
                  <div className="flex items-center gap-2.5">
                    <div className="w-7 h-7 rounded-lg bg-muted flex items-center justify-center shrink-0">
                      <MapPin size={13} className="text-muted-foreground" />
                    </div>
                    <div>
                      <p className="text-sm font-medium text-foreground">{supplier.location}</p>
                      <p className="text-xs text-muted-foreground">{supplier.address}</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-2.5">
                    <div className="w-7 h-7 rounded-lg bg-muted flex items-center justify-center shrink-0">
                      <Hash size={13} className="text-muted-foreground" />
                    </div>
                    <div>
                      <p className="text-xs text-muted-foreground">Contact Person</p>
                      <p className="text-sm font-medium text-foreground">{supplier.contactPerson}</p>
                    </div>
                  </div>
                </div>
              </section>

              {/* Business Info */}
              <section>
                <h3 className="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-3">Business Info</h3>
                <div className="grid grid-cols-2 gap-3">
                  {[
                    { label: 'Tax PIN', value: supplier.taxPin },
                    { label: 'Member Since', value: new Date(supplier.joinDate).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) },
                    { label: 'Bank', value: supplier.bankName },
                    { label: 'Account No.', value: supplier.bankAccount },
                  ].map(item => (
                    <div key={item.label} className="bg-muted/40 rounded-lg p-3">
                      <p className="text-2xs text-muted-foreground mb-0.5">{item.label}</p>
                      <p className="text-xs font-semibold text-foreground">{item.value}</p>
                    </div>
                  ))}
                </div>
              </section>

              {/* Notes */}
              {supplier.notes && (
                <section>
                  <h3 className="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2">Notes</h3>
                  <p className="text-sm text-muted-foreground bg-muted/40 rounded-lg p-3 leading-relaxed">{supplier.notes}</p>
                </section>
              )}
            </>
          )}

          {/* ── Payment Tab ── */}
          {activeTab === 'payment' && (
            <>
              <section>
                <h3 className="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-3">Payment Terms</h3>
                <div className="bg-primary/5 border border-primary/20 rounded-xl p-4 flex items-center gap-3">
                  <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                    <Calendar size={18} className="text-primary" />
                  </div>
                  <div>
                    <p className="text-base font-bold text-foreground">{paymentTermsLabel[supplier.paymentTerms]}</p>
                    <p className="text-xs text-muted-foreground mt-0.5">
                      {supplier.paymentTerms === 'cod' ? 'Payment required at time of delivery' :
                       supplier.paymentTerms === 'prepaid' ? 'Full payment required before order processing' :
                       `Invoice due within ${supplier.paymentTerms.replace('net-', '')} days of receipt`}
                    </p>
                  </div>
                </div>
              </section>

              <section>
                <h3 className="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-3">Credit Account</h3>
                <div className="grid grid-cols-2 gap-3">
                  <div className="bg-muted/40 rounded-lg p-3">
                    <p className="text-2xs text-muted-foreground mb-1">Credit Limit</p>
                    <p className="text-sm font-bold text-foreground">
                      {supplier.creditLimit > 0 ? <MoneyDisplay amount={supplier.creditLimit} /> : '—'}
                    </p>
                  </div>
                  <div className="bg-muted/40 rounded-lg p-3">
                    <p className="text-2xs text-muted-foreground mb-1">Outstanding</p>
                    <p className={`text-sm font-bold ${supplier.outstanding > 0 ? 'text-danger' : 'text-success'}`}>
                      {supplier.outstanding > 0 ? <MoneyDisplay amount={supplier.outstanding} /> : 'Clear'}
                    </p>
                  </div>
                  <div className="bg-muted/40 rounded-lg p-3">
                    <p className="text-2xs text-muted-foreground mb-1">Credit Used</p>
                    {supplier.creditLimit > 0 ? (
                      <>
                        <p className="text-sm font-bold text-foreground">
                          {Math.round((supplier.outstanding / supplier.creditLimit) * 100)}%
                        </p>
                        <div className="mt-1.5 h-1.5 bg-border rounded-full overflow-hidden">
                          <div
                            className={`h-full rounded-full ${
                              supplier.outstanding / supplier.creditLimit > 0.8 ? 'bg-danger' :
                              supplier.outstanding / supplier.creditLimit > 0.5 ? 'bg-warning' : 'bg-success'
                            }`}
                            style={{ width: `${Math.min((supplier.outstanding / supplier.creditLimit) * 100, 100)}%` }}
                          />
                        </div>
                      </>
                    ) : <p className="text-sm font-bold text-muted-foreground">N/A</p>}
                  </div>
                  <div className="bg-muted/40 rounded-lg p-3">
                    <p className="text-2xs text-muted-foreground mb-1">Bank</p>
                    <p className="text-xs font-semibold text-foreground">{supplier.bankName}</p>
                    <p className="text-2xs text-muted-foreground">{supplier.bankAccount}</p>
                  </div>
                </div>
              </section>

              <section>
                <h3 className="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-3">Lifetime Spend</h3>
                <div className="bg-muted/40 rounded-xl p-4 flex items-center justify-between">
                  <div>
                    <p className="text-2xs text-muted-foreground">Total Purchased</p>
                    <p className="text-xl font-bold text-foreground mt-0.5"><MoneyDisplay amount={supplier.totalValue} /></p>
                  </div>
                  <div className="text-right">
                    <p className="text-2xs text-muted-foreground">Total Orders</p>
                    <p className="text-xl font-bold text-foreground mt-0.5">{supplier.totalOrders}</p>
                  </div>
                </div>
              </section>
            </>
          )}

          {/* ── Performance Tab ── */}
          {activeTab === 'performance' && (
            <>
              <section>
                <h3 className="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-3">Quality Rating</h3>
                <div className="bg-muted/40 rounded-xl p-4 flex items-center gap-4">
                  <div className="text-3xl font-bold text-foreground">{supplier.qualityRating.toFixed(1)}</div>
                  <div>
                    <StarRating value={supplier.qualityRating} />
                    <p className="text-xs text-muted-foreground mt-1">Based on {supplier.totalOrders} orders</p>
                  </div>
                </div>
              </section>

              <section>
                <h3 className="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-3">Delivery Performance</h3>
                <div className="space-y-3">
                  <div>
                    <div className="flex items-center justify-between mb-1">
                      <span className="text-xs text-muted-foreground">On-Time Delivery Rate</span>
                    </div>
                    <PerformanceBar
                      value={supplier.onTimeDeliveryRate}
                      color={supplier.onTimeDeliveryRate >= 90 ? 'bg-success' : supplier.onTimeDeliveryRate >= 75 ? 'bg-warning' : 'bg-danger'}
                    />
                  </div>
                  <div>
                    <div className="flex items-center justify-between mb-1">
                      <span className="text-xs text-muted-foreground">Return / Rejection Rate</span>
                    </div>
                    <div className="flex items-center gap-2">
                      <div className="flex-1 h-1.5 bg-border rounded-full overflow-hidden">
                        <div
                          className={`h-full rounded-full ${supplier.returnRate <= 1 ? 'bg-success' : supplier.returnRate <= 3 ? 'bg-warning' : 'bg-danger'}`}
                          style={{ width: `${Math.min(supplier.returnRate * 10, 100)}%` }}
                        />
                      </div>
                      <span className="text-xs font-medium text-foreground w-8 text-right">{supplier.returnRate}%</span>
                    </div>
                  </div>
                </div>
              </section>

              <section>
                <h3 className="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-3">Key Metrics</h3>
                <div className="grid grid-cols-2 gap-3">
                  {[
                    { label: 'Avg Lead Time', value: `${supplier.avgLeadDays} days`, icon: <Clock size={14} className="text-info" />, color: 'bg-info/10' },
                    { label: 'Total Orders', value: supplier.totalOrders.toString(), icon: <ShoppingBag size={14} className="text-primary" />, color: 'bg-primary/10' },
                    { label: 'On-Time Rate', value: `${supplier.onTimeDeliveryRate}%`, icon: <CheckCircle2 size={14} className="text-success" />, color: 'bg-success/10' },
                    { label: 'Return Rate', value: `${supplier.returnRate}%`, icon: <AlertCircle size={14} className="text-danger" />, color: 'bg-danger/10' },
                  ].map(m => (
                    <div key={m.label} className="bg-muted/40 rounded-lg p-3 flex items-center gap-2.5">
                      <div className={`w-8 h-8 rounded-lg ${m.color} flex items-center justify-center shrink-0`}>{m.icon}</div>
                      <div>
                        <p className="text-2xs text-muted-foreground">{m.label}</p>
                        <p className="text-sm font-bold text-foreground">{m.value}</p>
                      </div>
                    </div>
                  ))}
                </div>
              </section>

              <section>
                <h3 className="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2">Last Order</h3>
                <p className="text-sm text-foreground bg-muted/40 rounded-lg px-3 py-2">
                  {new Date(supplier.lastOrderDate).toLocaleDateString('en-GB', { day: '2-digit', month: 'long', year: 'numeric' })}
                </p>
              </section>
            </>
          )}

          {/* ── PO History Tab ── */}
          {activeTab === 'orders' && (
            <>
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <div className="text-center">
                    <p className="text-lg font-bold text-foreground">{pos.length}</p>
                    <p className="text-2xs text-muted-foreground">Total POs</p>
                  </div>
                  <div className="w-px h-8 bg-border" />
                  <div className="text-center">
                    <p className="text-lg font-bold text-success">{completedPOs}</p>
                    <p className="text-2xs text-muted-foreground">Completed</p>
                  </div>
                  <div className="w-px h-8 bg-border" />
                  <div className="text-center">
                    <p className="text-lg font-bold text-warning">{activePOs}</p>
                    <p className="text-2xs text-muted-foreground">Active</p>
                  </div>
                </div>
              </div>

              <div className="space-y-2">
                {pos.length === 0 ? (
                  <div className="text-center py-8 text-muted-foreground text-sm">No purchase orders found</div>
                ) : pos.map(po => {
                  const cfg = poStatusConfig[po.status];
                  const pct = po.totalAmount > 0 ? Math.round((po.receivedAmount / po.totalAmount) * 100) : 0;
                  return (
                    <div key={po.id} className="border border-border rounded-xl p-3.5 hover:border-primary/30 hover:bg-primary/2 transition-colors">
                      <div className="flex items-start justify-between gap-2 mb-2">
                        <div>
                          <p className="text-sm font-semibold text-foreground">{po.poNumber}</p>
                          <p className="text-xs text-muted-foreground">{po.branch} · {new Date(po.date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })}</p>
                        </div>
                        <span className={`flex items-center gap-1 text-2xs font-semibold px-2 py-0.5 rounded-full ${cfg.color}`}>
                          {cfg.icon}{cfg.label}
                        </span>
                      </div>
                      <div className="flex items-center justify-between text-xs text-muted-foreground mb-2">
                        <span>{po.totalItems} items</span>
                        <span className="font-semibold text-foreground"><MoneyDisplay amount={po.totalAmount} /></span>
                      </div>
                      {po.status === 'partially-received' && (
                        <div>
                          <div className="flex items-center justify-between text-2xs text-muted-foreground mb-1">
                            <span>Received</span><span>{pct}%</span>
                          </div>
                          <div className="h-1.5 bg-border rounded-full overflow-hidden">
                            <div className="h-full bg-warning rounded-full" style={{ width: `${pct}%` }} />
                          </div>
                        </div>
                      )}
                    </div>
                  );
                })}
              </div>
            </>
          )}
        </div>

        {/* Footer actions */}
        <div className="border-t border-border px-5 py-3 flex items-center gap-2">
          <button className="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-medium bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
            <Edit size={13} /> Edit Supplier
          </button>
          <button className="flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-medium border border-border rounded-lg hover:bg-muted transition-colors text-foreground">
            <ShoppingBag size={13} /> New PO
          </button>
        </div>
      </div>
    </div>
  );
}

// ─── Main Page ─────────────────────────────────────────────────────────────────

export default function SuppliersPage() {
  const [layout] = useState<DashboardLayout>('classic');
  const [search, setSearch] = useState('');
  const [categoryFilter, setCategoryFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [termsFilter, setTermsFilter] = useState('');
  const [selectedSupplier, setSelectedSupplier] = useState<Supplier | null>(null);

  const filtered = useMemo(() => {
    return suppliers.filter(s => {
      if (search) {
        const q = search.toLowerCase();
        if (!s.name.toLowerCase().includes(q) && !s.contactPerson.toLowerCase().includes(q) && !s.location.toLowerCase().includes(q)) return false;
      }
      if (categoryFilter && s.category !== categoryFilter) return false;
      if (statusFilter && s.status !== statusFilter) return false;
      if (termsFilter && s.paymentTerms !== termsFilter) return false;
      return true;
    });
  }, [search, categoryFilter, statusFilter, termsFilter]);

  const activeCount    = suppliers.filter(s => s.status === 'active').length;
  const totalOutstanding = suppliers.reduce((sum, s) => sum + s.outstanding, 0);
  const totalValue     = suppliers.reduce((sum, s) => sum + s.totalValue, 0);
  const avgOnTime      = Math.round(suppliers.reduce((sum, s) => sum + s.onTimeDeliveryRate, 0) / suppliers.length);

  return (
    <AppLayout layout={layout}>
      <div className="space-y-5">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-bold text-foreground">Supplier Directory</h1>
            <p className="text-sm text-muted-foreground mt-0.5">{suppliers.length} suppliers · {activeCount} active</p>
          </div>
          <div className="flex items-center gap-2">
            <button className="flex items-center gap-1.5 px-3 py-2 text-xs font-medium border border-border rounded-lg hover:bg-muted transition-colors text-foreground">
              <Download size={14} /> Export
            </button>
            <button className="flex items-center gap-1.5 px-3 py-2 text-xs font-medium bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
              <Plus size={14} /> Add Supplier
            </button>
          </div>
        </div>

        {/* KPI Cards */}
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <KpiCard
            title="Active Suppliers"
            value={activeCount.toString()}
            subtitle={`${suppliers.length} total`}
            icon={<Truck size={18} />}
            trend={{ value: 2, direction: 'up', label: 'vs last month' }}
          />
          <KpiCard
            title="Total Spend (YTD)"
            value={<MoneyDisplay amount={totalValue} />}
            subtitle="All suppliers"
            icon={<Banknote size={18} />}
            trend={{ value: 12, direction: 'up', label: 'vs last year' }}
          />
          <KpiCard
            title="Outstanding Payables"
            value={<MoneyDisplay amount={totalOutstanding} />}
            subtitle="Across all suppliers"
            icon={<CreditCard size={18} />}
            trend={{ value: 5, direction: 'down', label: 'vs last month' }}
          />
          <KpiCard
            title="Avg On-Time Rate"
            value={`${avgOnTime}%`}
            subtitle="Delivery performance"
            icon={<TrendingUp size={18} />}
            trend={{ value: 3, direction: 'up', label: 'vs last quarter' }}
          />
        </div>

        {/* Filters */}
        <div className="flex flex-wrap items-center gap-2">
          <div className="relative flex-1 min-w-[200px] max-w-xs">
            <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
            <input
              type="text"
              placeholder="Search suppliers..."
              value={search}
              onChange={e => setSearch(e.target.value)}
              className="w-full pl-8 pr-3 py-2 text-sm bg-card border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
            />
          </div>
          <select
            value={categoryFilter}
            onChange={e => setCategoryFilter(e.target.value)}
            className="px-3 py-2 text-sm bg-card border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground"
          >
            <option value="">All Categories</option>
            {(['cement','steel','tiles','paints','plumbing','electrical','hardware','timber'] as SupplierCategory[]).map(c => (
              <option key={c} value={c} className="capitalize">{c.charAt(0).toUpperCase() + c.slice(1)}</option>
            ))}
          </select>
          <select
            value={statusFilter}
            onChange={e => setStatusFilter(e.target.value)}
            className="px-3 py-2 text-sm bg-card border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground"
          >
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="on-hold">On Hold</option>
          </select>
          <select
            value={termsFilter}
            onChange={e => setTermsFilter(e.target.value)}
            className="px-3 py-2 text-sm bg-card border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground"
          >
            <option value="">All Payment Terms</option>
            {(Object.entries(paymentTermsLabel) as [PaymentTerms, string][]).map(([k, v]) => (
              <option key={k} value={k}>{v}</option>
            ))}
          </select>
          {(search || categoryFilter || statusFilter || termsFilter) && (
            <button
              onClick={() => { setSearch(''); setCategoryFilter(''); setStatusFilter(''); setTermsFilter(''); }}
              className="flex items-center gap-1 px-2 py-2 text-xs text-muted-foreground hover:text-foreground transition-colors"
            >
              <X size={13} /> Clear
            </button>
          )}
        </div>

        {/* Supplier Table */}
        <div className="bg-card border border-border rounded-xl overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border bg-muted/30">
                  <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground">Supplier</th>
                  <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground">Category</th>
                  <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground">Contact</th>
                  <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground">Payment Terms</th>
                  <th className="text-right px-4 py-3 text-xs font-semibold text-muted-foreground">Outstanding</th>
                  <th className="text-center px-4 py-3 text-xs font-semibold text-muted-foreground">On-Time</th>
                  <th className="text-center px-4 py-3 text-xs font-semibold text-muted-foreground">Rating</th>
                  <th className="text-center px-4 py-3 text-xs font-semibold text-muted-foreground">Status</th>
                  <th className="px-4 py-3" />
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {filtered.length === 0 ? (
                  <tr>
                    <td colSpan={9} className="text-center py-12 text-muted-foreground text-sm">
                      No suppliers match your filters
                    </td>
                  </tr>
                ) : filtered.map(s => (
                  <tr
                    key={s.id}
                    className="hover:bg-muted/30 transition-colors cursor-pointer"
                    onClick={() => setSelectedSupplier(s)}
                  >
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-2.5">
                        <div className="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                          <Truck size={14} className="text-primary" />
                        </div>
                        <div>
                          <p className="font-semibold text-foreground text-sm">{s.name}</p>
                          <p className="text-xs text-muted-foreground">{s.contactPerson}</p>
                        </div>
                      </div>
                    </td>
                    <td className="px-4 py-3">
                      <span className={`text-2xs font-semibold px-2 py-0.5 rounded-full capitalize ${categoryColors[s.category]}`}>
                        {s.category}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <p className="text-xs text-foreground">{s.phone}</p>
                      <p className="text-xs text-muted-foreground">{s.location}</p>
                    </td>
                    <td className="px-4 py-3">
                      <p className="text-xs text-foreground">{paymentTermsLabel[s.paymentTerms]}</p>
                    </td>
                    <td className="px-4 py-3 text-right">
                      {s.outstanding > 0 ? (
                        <span className="text-xs font-semibold text-danger"><MoneyDisplay amount={s.outstanding} /></span>
                      ) : (
                        <span className="text-xs text-success font-medium">Clear</span>
                      )}
                    </td>
                    <td className="px-4 py-3 text-center">
                      <span className={`text-xs font-semibold ${
                        s.onTimeDeliveryRate >= 90 ? 'text-success' :
                        s.onTimeDeliveryRate >= 75 ? 'text-warning' : 'text-danger'
                      }`}>{s.onTimeDeliveryRate}%</span>
                    </td>
                    <td className="px-4 py-3 text-center">
                      <div className="flex items-center justify-center gap-0.5">
                        <Star size={11} className="text-warning fill-warning" />
                        <span className="text-xs font-semibold text-foreground">{s.qualityRating.toFixed(1)}</span>
                      </div>
                    </td>
                    <td className="px-4 py-3 text-center">
                      <span className={`text-2xs font-semibold px-2 py-0.5 rounded-full ${statusConfig[s.status].color}`}>
                        {statusConfig[s.status].label}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <ChevronRight size={14} className="text-muted-foreground" />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <div className="px-4 py-3 border-t border-border flex items-center justify-between">
            <p className="text-xs text-muted-foreground">Showing {filtered.length} of {suppliers.length} suppliers</p>
          </div>
        </div>
      </div>

      {/* Detail Panel */}
      {selectedSupplier && (
        <SupplierDetailPanel
          supplier={selectedSupplier}
          onClose={() => setSelectedSupplier(null)}
        />
      )}
    </AppLayout>
  );
}
