'use client';
import React, { useState } from 'react';
import Link from 'next/link';
import AppLayout, { DashboardLayout } from '@/components/AppLayout';
import StatusBadge from '@/components/ui/StatusBadge';
import MoneyDisplay from '@/components/ui/MoneyDisplay';
import { ArrowLeft, Phone, Mail, MapPin, CreditCard, AlertTriangle, TrendingUp, FileText, Edit, ShoppingCart, Banknote, ReceiptText, CheckCircle2, Calendar, User, Hash, Printer, Download,  } from 'lucide-react';

// ─── Mock data ────────────────────────────────────────────────────────────────
interface SaleRecord {
  id: string;
  date: string;
  invoiceNo: string;
  items: number;
  total: number;
  paid: number;
  balance: number;
  status: 'paid' | 'partial' | 'credit' | 'cancelled';
}

interface PaymentRecord {
  id: string;
  date: string;
  receiptNo: string;
  method: 'cash' | 'mobile-money' | 'card';
  amount: number;
  reference: string;
  allocatedTo: string;
}

interface StatementEntry {
  id: string;
  date: string;
  description: string;
  type: 'debit' | 'credit';
  debit: number;
  credit: number;
  balance: number;
  reference: string;
}

const customerData = {
  'cust-001': {
    id: 'cust-001',
    name: 'Karibu Construction Ltd',
    type: 'contractor' as const,
    phone: '+255 712 345 678',
    altPhone: '+255 712 345 679',
    email: 'info@karibuconstruction.co.tz',
    location: 'Dar es Salaam',
    address: 'Plot 45, Mikocheni B, Dar es Salaam',
    contactPerson: 'David Kariuki',
    creditLimit: 5000000,
    outstanding: 2850000,
    overdue: 850000,
    creditUsed: 2850000,
    lastSale: '2026-09-04',
    status: 'active' as const,
    totalPurchases: 48500000,
    totalPayments: 45650000,
    joinDate: '2023-03-15',
    notes: 'Preferred contractor customer. Usually pays within 30 days.',
  },
};

const salesHistory: SaleRecord[] = [
  { id: 'sale-001', date: '2026-09-04', invoiceNo: 'INV-2026-0892', items: 8, total: 1250000, paid: 1250000, balance: 0, status: 'paid' },
  { id: 'sale-002', date: '2026-09-01', invoiceNo: 'INV-2026-0871', items: 12, total: 2850000, paid: 2000000, balance: 850000, status: 'partial' },
  { id: 'sale-003', date: '2026-08-28', invoiceNo: 'INV-2026-0845', items: 5, total: 980000, paid: 0, balance: 980000, status: 'credit' },
  { id: 'sale-004', date: '2026-08-22', invoiceNo: 'INV-2026-0812', items: 20, total: 4200000, paid: 4200000, balance: 0, status: 'paid' },
  { id: 'sale-005', date: '2026-08-15', invoiceNo: 'INV-2026-0789', items: 3, total: 650000, paid: 650000, balance: 0, status: 'paid' },
  { id: 'sale-006', date: '2026-08-08', invoiceNo: 'INV-2026-0754', items: 15, total: 3100000, paid: 3100000, balance: 0, status: 'paid' },
  { id: 'sale-007', date: '2026-07-30', invoiceNo: 'INV-2026-0721', items: 7, total: 1420000, paid: 1420000, balance: 0, status: 'paid' },
  { id: 'sale-008', date: '2026-07-22', invoiceNo: 'INV-2026-0698', items: 9, total: 2200000, paid: 2200000, balance: 0, status: 'paid' },
];

const paymentHistory: PaymentRecord[] = [
  { id: 'pay-001', date: '2026-09-04', receiptNo: 'RCP-2026-0445', method: 'mobile-money', amount: 1250000, reference: 'M-PESA TXN: ABC123', allocatedTo: 'INV-2026-0892' },
  { id: 'pay-002', date: '2026-09-02', receiptNo: 'RCP-2026-0438', method: 'cash', amount: 2000000, reference: 'Cash payment', allocatedTo: 'INV-2026-0871' },
  { id: 'pay-003', date: '2026-08-22', receiptNo: 'RCP-2026-0412', method: 'card', amount: 4200000, reference: 'VISA ****4521', allocatedTo: 'INV-2026-0812' },
  { id: 'pay-004', date: '2026-08-15', receiptNo: 'RCP-2026-0398', method: 'mobile-money', amount: 650000, reference: 'M-PESA TXN: DEF456', allocatedTo: 'INV-2026-0789' },
  { id: 'pay-005', date: '2026-08-08', receiptNo: 'RCP-2026-0381', method: 'cash', amount: 3100000, reference: 'Cash payment', allocatedTo: 'INV-2026-0754' },
  { id: 'pay-006', date: '2026-07-30', receiptNo: 'RCP-2026-0362', method: 'mobile-money', amount: 1420000, reference: 'M-PESA TXN: GHI789', allocatedTo: 'INV-2026-0721' },
];

const statementEntries: StatementEntry[] = [
  { id: 'st-001', date: '2026-09-04', description: 'Sale — INV-2026-0892', type: 'debit', debit: 1250000, credit: 0, balance: 2850000, reference: 'INV-2026-0892' },
  { id: 'st-002', date: '2026-09-04', description: 'Payment received — RCP-2026-0445', type: 'credit', debit: 0, credit: 1250000, balance: 1600000, reference: 'RCP-2026-0445' },
  { id: 'st-003', date: '2026-09-01', description: 'Sale — INV-2026-0871', type: 'debit', debit: 2850000, credit: 0, balance: 2850000, reference: 'INV-2026-0871' },
  { id: 'st-004', date: '2026-09-02', description: 'Partial payment — RCP-2026-0438', type: 'credit', debit: 0, credit: 2000000, balance: 850000, reference: 'RCP-2026-0438' },
  { id: 'st-005', date: '2026-08-28', description: 'Sale on credit — INV-2026-0845', type: 'debit', debit: 980000, credit: 0, balance: 1830000, reference: 'INV-2026-0845' },
  { id: 'st-006', date: '2026-08-22', description: 'Sale — INV-2026-0812', type: 'debit', debit: 4200000, credit: 0, balance: 5030000, reference: 'INV-2026-0812' },
  { id: 'st-007', date: '2026-08-22', description: 'Payment received — RCP-2026-0412', type: 'credit', debit: 0, credit: 4200000, balance: 830000, reference: 'RCP-2026-0412' },
  { id: 'st-008', date: '2026-08-15', description: 'Sale — INV-2026-0789', type: 'debit', debit: 650000, credit: 0, balance: 1480000, reference: 'INV-2026-0789' },
  { id: 'st-009', date: '2026-08-15', description: 'Payment received — RCP-2026-0398', type: 'credit', debit: 0, credit: 650000, balance: 830000, reference: 'RCP-2026-0398' },
  { id: 'st-010', date: '2026-08-08', description: 'Sale — INV-2026-0754', type: 'debit', debit: 3100000, credit: 0, balance: 3930000, reference: 'INV-2026-0754' },
  { id: 'st-011', date: '2026-08-08', description: 'Payment received — RCP-2026-0381', type: 'credit', debit: 0, credit: 3100000, balance: 830000, reference: 'RCP-2026-0381' },
];

const methodColors: Record<string, string> = {
  cash: 'bg-success/10 text-success',
  'mobile-money': 'bg-info/10 text-info',
  card: 'bg-primary/10 text-primary',
};

const saleStatusConfig: Record<string, { label: string; className: string }> = {
  paid: { label: 'Paid', className: 'badge-success' },
  partial: { label: 'Partial', className: 'badge-warning' },
  credit: { label: 'Credit', className: 'badge-danger' },
  cancelled: { label: 'Cancelled', className: 'badge-muted' },
};

type Tab = 'overview' | 'sales' | 'payments' | 'statement';

export default function CustomerProfilePage({ params }: { params: { id: string } }) {
  const [layout] = useState<DashboardLayout>('classic');
  const [activeTab, setActiveTab] = useState<Tab>('overview');

  // Use cust-001 as default for demo; real app would fetch by params.id
  const customer = customerData['cust-001'];
  const creditUsedPct = Math.min(100, Math.round((customer.creditUsed / customer.creditLimit) * 100));

  const tabs: { id: Tab; label: string; icon: React.ReactNode }[] = [
    { id: 'overview', label: 'Overview', icon: <User size={15} /> },
    { id: 'sales', label: 'Sales History', icon: <ShoppingCart size={15} /> },
    { id: 'payments', label: 'Payment History', icon: <Banknote size={15} /> },
    { id: 'statement', label: 'Statement', icon: <ReceiptText size={15} /> },
  ];

  return (
    <AppLayout layout={layout}>
      <div className="space-y-5">
        {/* Back + Header */}
        <div className="flex items-start gap-3">
          <Link href="/customers" className="mt-1 p-1.5 rounded-md hover:bg-muted transition-colors text-muted-foreground hover:text-foreground">
            <ArrowLeft size={18} />
          </Link>
          <div className="flex-1 min-w-0">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div>
                <div className="flex items-center gap-2 flex-wrap">
                  <h1 className="text-xl font-bold text-foreground">{customer.name}</h1>
                  <StatusBadge status={customer.status} />
                  <span className={`text-xs font-semibold px-2 py-0.5 rounded-full capitalize bg-warning/10 text-warning`}>
                    {customer.type}
                  </span>
                </div>
                <p className="text-sm text-muted-foreground mt-0.5 flex items-center gap-1">
                  <Hash size={12} /> {customer.id} · Customer since {new Date(customer.joinDate).toLocaleDateString('en-GB', { month: 'short', year: 'numeric' })}
                </p>
              </div>
              <div className="flex items-center gap-2">
                <button className="btn-outline flex items-center gap-2 text-sm px-3 py-2">
                  <Printer size={15} /> Statement
                </button>
                <button className="btn-primary flex items-center gap-2 text-sm px-3 py-2">
                  <Edit size={15} /> Edit
                </button>
              </div>
            </div>
          </div>
        </div>

        {/* Debt Alert Banner */}
        {customer.overdue > 0 && (
          <div className="flex items-center gap-3 px-4 py-3 rounded-lg bg-danger/5 border border-danger/20">
            <AlertTriangle size={16} className="text-danger shrink-0" />
            <p className="text-sm text-danger font-medium">
              This customer has <MoneyDisplay amount={customer.overdue} className="text-danger" /> overdue debt.
              Oldest overdue invoice is 7 days past due.
            </p>
          </div>
        )}

        {/* Summary Cards */}
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <div className="card p-4">
            <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Total Purchases</p>
            <MoneyDisplay amount={customer.totalPurchases} compact className="text-xl font-bold text-foreground mt-1 block" />
            <p className="text-xs text-muted-foreground mt-1">All time</p>
          </div>
          <div className="card p-4">
            <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Outstanding</p>
            <MoneyDisplay amount={customer.outstanding} compact className="text-xl font-bold text-warning mt-1 block" />
            <p className="text-xs text-muted-foreground mt-1">Current balance</p>
          </div>
          <div className="card p-4 border-danger/20 bg-danger/5">
            <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Overdue</p>
            <MoneyDisplay amount={customer.overdue} compact className="text-xl font-bold text-danger mt-1 block" />
            <p className="text-xs text-muted-foreground mt-1">Past due date</p>
          </div>
          <div className="card p-4">
            <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Credit Limit</p>
            <MoneyDisplay amount={customer.creditLimit} compact className="text-xl font-bold text-foreground mt-1 block" />
            <div className="mt-2">
              <div className="flex justify-between text-xs text-muted-foreground mb-1">
                <span>Used {creditUsedPct}%</span>
                <span><MoneyDisplay amount={customer.creditLimit - customer.creditUsed} compact className="text-xs" /> free</span>
              </div>
              <div className="h-1.5 bg-muted rounded-full overflow-hidden">
                <div
                  className={`h-full rounded-full transition-all ${creditUsedPct >= 90 ? 'bg-danger' : creditUsedPct >= 70 ? 'bg-warning' : 'bg-success'}`}
                  style={{ width: `${creditUsedPct}%` }}
                />
              </div>
            </div>
          </div>
        </div>

        {/* Tabs */}
        <div className="border-b border-border">
          <div className="flex gap-0 overflow-x-auto">
            {tabs.map(tab => (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                className={`flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap ${
                  activeTab === tab.id
                    ? 'border-primary text-primary' :'border-transparent text-muted-foreground hover:text-foreground hover:border-border'
                }`}
              >
                {tab.icon}
                {tab.label}
              </button>
            ))}
          </div>
        </div>

        {/* Tab Content */}
        {activeTab === 'overview' && (
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
            {/* Contact Info */}
            <div className="lg:col-span-2 space-y-4">
              <div className="card p-5">
                <h3 className="text-sm font-semibold text-foreground mb-4 flex items-center gap-2">
                  <User size={15} className="text-primary" /> Contact Information
                </h3>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <p className="text-xs text-muted-foreground uppercase tracking-wide font-semibold mb-1">Contact Person</p>
                    <p className="text-sm text-foreground font-medium">{customer.contactPerson}</p>
                  </div>
                  <div>
                    <p className="text-xs text-muted-foreground uppercase tracking-wide font-semibold mb-1">Primary Phone</p>
                    <p className="text-sm text-foreground font-medium flex items-center gap-1.5">
                      <Phone size={13} className="text-muted-foreground" /> {customer.phone}
                    </p>
                  </div>
                  <div>
                    <p className="text-xs text-muted-foreground uppercase tracking-wide font-semibold mb-1">Alt Phone</p>
                    <p className="text-sm text-foreground font-medium flex items-center gap-1.5">
                      <Phone size={13} className="text-muted-foreground" /> {customer.altPhone}
                    </p>
                  </div>
                  <div>
                    <p className="text-xs text-muted-foreground uppercase tracking-wide font-semibold mb-1">Email</p>
                    <p className="text-sm text-foreground font-medium flex items-center gap-1.5">
                      <Mail size={13} className="text-muted-foreground" /> {customer.email}
                    </p>
                  </div>
                  <div className="sm:col-span-2">
                    <p className="text-xs text-muted-foreground uppercase tracking-wide font-semibold mb-1">Address</p>
                    <p className="text-sm text-foreground font-medium flex items-center gap-1.5">
                      <MapPin size={13} className="text-muted-foreground" /> {customer.address}
                    </p>
                  </div>
                </div>
              </div>

              {/* Notes */}
              <div className="card p-5">
                <h3 className="text-sm font-semibold text-foreground mb-3 flex items-center gap-2">
                  <FileText size={15} className="text-primary" /> Notes
                </h3>
                <p className="text-sm text-muted-foreground">{customer.notes}</p>
              </div>
            </div>

            {/* Credit & Quick Stats */}
            <div className="space-y-4">
              <div className="card p-5">
                <h3 className="text-sm font-semibold text-foreground mb-4 flex items-center gap-2">
                  <CreditCard size={15} className="text-primary" /> Credit Account
                </h3>
                <div className="space-y-3">
                  <div className="flex justify-between items-center">
                    <span className="text-xs text-muted-foreground">Credit Limit</span>
                    <MoneyDisplay amount={customer.creditLimit} compact className="text-sm font-semibold" />
                  </div>
                  <div className="flex justify-between items-center">
                    <span className="text-xs text-muted-foreground">Credit Used</span>
                    <MoneyDisplay amount={customer.creditUsed} compact className="text-sm font-semibold text-warning" />
                  </div>
                  <div className="flex justify-between items-center">
                    <span className="text-xs text-muted-foreground">Available Credit</span>
                    <MoneyDisplay amount={customer.creditLimit - customer.creditUsed} compact className="text-sm font-semibold text-success" />
                  </div>
                  <div className="pt-1">
                    <div className="h-2 bg-muted rounded-full overflow-hidden">
                      <div
                        className={`h-full rounded-full ${creditUsedPct >= 90 ? 'bg-danger' : creditUsedPct >= 70 ? 'bg-warning' : 'bg-success'}`}
                        style={{ width: `${creditUsedPct}%` }}
                      />
                    </div>
                    <p className="text-xs text-muted-foreground mt-1">{creditUsedPct}% of credit limit used</p>
                  </div>
                </div>
              </div>

              <div className="card p-5">
                <h3 className="text-sm font-semibold text-foreground mb-4 flex items-center gap-2">
                  <TrendingUp size={15} className="text-primary" /> Account Summary
                </h3>
                <div className="space-y-3">
                  <div className="flex justify-between items-center">
                    <span className="text-xs text-muted-foreground">Total Purchases</span>
                    <MoneyDisplay amount={customer.totalPurchases} compact className="text-sm font-semibold" />
                  </div>
                  <div className="flex justify-between items-center">
                    <span className="text-xs text-muted-foreground">Total Payments</span>
                    <MoneyDisplay amount={customer.totalPayments} compact className="text-sm font-semibold text-success" />
                  </div>
                  <div className="flex justify-between items-center border-t border-border pt-3">
                    <span className="text-xs font-semibold text-foreground">Net Balance</span>
                    <MoneyDisplay amount={customer.outstanding} compact className="text-sm font-bold text-warning" />
                  </div>
                  <div className="flex justify-between items-center">
                    <span className="text-xs text-muted-foreground">Last Sale</span>
                    <span className="text-sm font-medium text-foreground flex items-center gap-1">
                      <Calendar size={12} className="text-muted-foreground" />
                      {new Date(customer.lastSale).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}

        {activeTab === 'sales' && (
          <div className="card overflow-hidden">
            <div className="px-4 py-3 border-b border-border flex items-center justify-between">
              <h3 className="text-sm font-semibold text-foreground">Sales History</h3>
              <button className="btn-outline text-xs px-2 py-1 flex items-center gap-1.5">
                <Download size={13} /> Export
              </button>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-border bg-muted/30">
                    <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Date</th>
                    <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Invoice</th>
                    <th className="text-center px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide hidden sm:table-cell">Items</th>
                    <th className="text-right px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Total</th>
                    <th className="text-right px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide hidden md:table-cell">Paid</th>
                    <th className="text-right px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Balance</th>
                    <th className="text-center px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {salesHistory.map(sale => {
                    const cfg = saleStatusConfig[sale.status];
                    return (
                      <tr key={sale.id} className="hover:bg-muted/20 transition-colors">
                        <td className="px-4 py-3 text-sm text-muted-foreground whitespace-nowrap">
                          {new Date(sale.date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}
                        </td>
                        <td className="px-4 py-3">
                          <span className="text-sm font-medium text-primary hover:underline cursor-pointer">{sale.invoiceNo}</span>
                        </td>
                        <td className="px-4 py-3 text-center text-sm text-muted-foreground hidden sm:table-cell">{sale.items}</td>
                        <td className="px-4 py-3 text-right">
                          <MoneyDisplay amount={sale.total} compact className="text-sm" />
                        </td>
                        <td className="px-4 py-3 text-right hidden md:table-cell">
                          <MoneyDisplay amount={sale.paid} compact className="text-sm text-success" />
                        </td>
                        <td className="px-4 py-3 text-right">
                          {sale.balance > 0 ? (
                            <MoneyDisplay amount={sale.balance} compact className="text-sm text-danger" />
                          ) : (
                            <span className="text-sm text-muted-foreground">—</span>
                          )}
                        </td>
                        <td className="px-4 py-3 text-center">
                          <span className={cfg.className}>{cfg.label}</span>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          </div>
        )}

        {activeTab === 'payments' && (
          <div className="card overflow-hidden">
            <div className="px-4 py-3 border-b border-border flex items-center justify-between">
              <h3 className="text-sm font-semibold text-foreground">Payment History</h3>
              <button className="btn-outline text-xs px-2 py-1 flex items-center gap-1.5">
                <Download size={13} /> Export
              </button>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-border bg-muted/30">
                    <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Date</th>
                    <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Receipt No</th>
                    <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide hidden md:table-cell">Method</th>
                    <th className="text-right px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Amount</th>
                    <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide hidden lg:table-cell">Reference</th>
                    <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide hidden sm:table-cell">Allocated To</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {paymentHistory.map(payment => (
                    <tr key={payment.id} className="hover:bg-muted/20 transition-colors">
                      <td className="px-4 py-3 text-sm text-muted-foreground whitespace-nowrap">
                        {new Date(payment.date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}
                      </td>
                      <td className="px-4 py-3">
                        <span className="text-sm font-medium text-primary hover:underline cursor-pointer">{payment.receiptNo}</span>
                      </td>
                      <td className="px-4 py-3 hidden md:table-cell">
                        <span className={`text-xs font-semibold px-2 py-0.5 rounded-full capitalize ${methodColors[payment.method]}`}>
                          {payment.method === 'mobile-money' ? 'M-Pesa' : payment.method}
                        </span>
                      </td>
                      <td className="px-4 py-3 text-right">
                        <MoneyDisplay amount={payment.amount} compact className="text-sm text-success" />
                      </td>
                      <td className="px-4 py-3 text-sm text-muted-foreground hidden lg:table-cell">{payment.reference}</td>
                      <td className="px-4 py-3 text-sm text-muted-foreground hidden sm:table-cell">{payment.allocatedTo}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}

        {activeTab === 'statement' && (
          <div className="space-y-4">
            {/* Statement Header */}
            <div className="card p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div>
                <h3 className="text-sm font-semibold text-foreground">Account Statement</h3>
                <p className="text-xs text-muted-foreground mt-0.5">Showing all transactions · Running balance</p>
              </div>
              <div className="flex items-center gap-2">
                <select className="input text-sm">
                  <option>Last 30 Days</option>
                  <option>Last 60 Days</option>
                  <option>Last 90 Days</option>
                  <option>This Year</option>
                  <option>Custom Range</option>
                </select>
                <button className="btn-outline text-sm px-3 py-2 flex items-center gap-1.5">
                  <Printer size={14} /> Print
                </button>
                <button className="btn-outline text-sm px-3 py-2 flex items-center gap-1.5">
                  <Download size={14} /> PDF
                </button>
              </div>
            </div>

            {/* Opening Balance */}
            <div className="card overflow-hidden">
              <div className="px-4 py-3 bg-muted/30 border-b border-border flex items-center justify-between">
                <span className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Opening Balance</span>
                <MoneyDisplay amount={830000} compact className="text-sm font-bold text-warning" />
              </div>
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b border-border bg-muted/20">
                      <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide w-28">Date</th>
                      <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Description</th>
                      <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide hidden sm:table-cell w-32">Reference</th>
                      <th className="text-right px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide w-28">Debit</th>
                      <th className="text-right px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide w-28">Credit</th>
                      <th className="text-right px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide w-32">Balance</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-border">
                    {statementEntries.map(entry => (
                      <tr
                        key={entry.id}
                        className={`hover:bg-muted/20 transition-colors ${entry.type === 'credit' ? 'bg-success/3' : ''}`}
                      >
                        <td className="px-4 py-3 text-xs text-muted-foreground whitespace-nowrap">
                          {new Date(entry.date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}
                        </td>
                        <td className="px-4 py-3">
                          <div className="flex items-center gap-2">
                            {entry.type === 'credit' ? (
                              <CheckCircle2 size={13} className="text-success shrink-0" />
                            ) : (
                              <ShoppingCart size={13} className="text-muted-foreground shrink-0" />
                            )}
                            <span className="text-sm text-foreground">{entry.description}</span>
                          </div>
                        </td>
                        <td className="px-4 py-3 text-xs text-muted-foreground hidden sm:table-cell">{entry.reference}</td>
                        <td className="px-4 py-3 text-right">
                          {entry.debit > 0 ? (
                            <MoneyDisplay amount={entry.debit} compact className="text-sm text-foreground" />
                          ) : (
                            <span className="text-sm text-muted-foreground">—</span>
                          )}
                        </td>
                        <td className="px-4 py-3 text-right">
                          {entry.credit > 0 ? (
                            <MoneyDisplay amount={entry.credit} compact className="text-sm text-success" />
                          ) : (
                            <span className="text-sm text-muted-foreground">—</span>
                          )}
                        </td>
                        <td className="px-4 py-3 text-right">
                          <MoneyDisplay
                            amount={entry.balance}
                            compact
                            className={`text-sm font-semibold ${entry.balance > 0 ? 'text-warning' : 'text-success'}`}
                          />
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              {/* Closing Balance */}
              <div className="px-4 py-3 bg-muted/30 border-t border-border flex items-center justify-between">
                <span className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Closing Balance</span>
                <MoneyDisplay amount={customer.outstanding} compact className="text-base font-bold text-warning" />
              </div>
            </div>

            {/* Statement Summary */}
            <div className="grid grid-cols-3 gap-3">
              <div className="card p-4 text-center">
                <p className="text-xs text-muted-foreground uppercase tracking-wide font-semibold">Total Debits</p>
                <MoneyDisplay
                  amount={statementEntries.reduce((s, e) => s + e.debit, 0)}
                  compact
                  className="text-lg font-bold text-foreground mt-1 block"
                />
              </div>
              <div className="card p-4 text-center">
                <p className="text-xs text-muted-foreground uppercase tracking-wide font-semibold">Total Credits</p>
                <MoneyDisplay
                  amount={statementEntries.reduce((s, e) => s + e.credit, 0)}
                  compact
                  className="text-lg font-bold text-success mt-1 block"
                />
              </div>
              <div className="card p-4 text-center border-warning/20 bg-warning/5">
                <p className="text-xs text-muted-foreground uppercase tracking-wide font-semibold">Net Balance</p>
                <MoneyDisplay amount={customer.outstanding} compact className="text-lg font-bold text-warning mt-1 block" />
              </div>
            </div>
          </div>
        )}
      </div>
    </AppLayout>
  );
}
