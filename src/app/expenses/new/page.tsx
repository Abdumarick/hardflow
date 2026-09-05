'use client';
import React, { useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import AppLayout, { DashboardLayout } from '@/components/AppLayout';
import MoneyDisplay from '@/components/ui/MoneyDisplay';
import {
  Receipt, ArrowLeft, Save, Send, CheckCircle2, FileText, Clock,
  Banknote, Building2, Smartphone, CreditCard, Upload, Trash2,
  AlertCircle, ChevronRight, User, Calendar, Tag, DollarSign,
} from 'lucide-react';

// ─── Types ────────────────────────────────────────────────────────────────────

type ExpenseStatus = 'draft' | 'pending' | 'approved' | 'posted';
type PaymentMethod = 'cash' | 'bank-transfer' | 'mobile-money' | 'card' | 'petty-cash';

interface ExpenseForm {
  title: string;
  category: string;
  description: string;
  amount: string;
  paymentMethod: PaymentMethod;
  vendor: string;
  branch: string;
  date: string;
  receiptRef: string;
  notes: string;
  approver: string;
}

// ─── Config ────────────────────────────────────────────────────────────────────

const categories = [
  'Office Supplies', 'Transport', 'Utilities', 'Meals & Entertainment',
  'Equipment Maintenance', 'Security', 'Marketing', 'Training & Development',
  'Facilities', 'Software & IT', 'Rent & Lease', 'Other',
];

const paymentMethods: { value: PaymentMethod; label: string; icon: React.ReactNode; desc: string }[] = [
  { value: 'cash',          label: 'Cash',          icon: <Banknote size={18} />,   desc: 'Physical cash payment' },
  { value: 'bank-transfer', label: 'Bank Transfer',  icon: <Building2 size={18} />,  desc: 'Direct bank transfer' },
  { value: 'mobile-money',  label: 'Mobile Money',   icon: <Smartphone size={18} />, desc: 'M-Pesa / Tigo Pesa' },
  { value: 'card',          label: 'Card',           icon: <CreditCard size={18} />, desc: 'Debit or credit card' },
  { value: 'petty-cash',    label: 'Petty Cash',     icon: <Banknote size={18} />,   desc: 'From petty cash float' },
];

const approvers = ['James Mwangi', 'Sarah Kimani', 'Peter Odhiambo'];
const branches = ['Main Branch', 'Warehouse', 'Kariakoo Outlet'];

const statusSteps: { status: ExpenseStatus; label: string; icon: React.ReactNode; desc: string }[] = [
  { status: 'draft',    label: 'Draft',    icon: <FileText size={14} />,     desc: 'Saved but not submitted' },
  { status: 'pending',  label: 'Pending',  icon: <Clock size={14} />,        desc: 'Submitted for approval' },
  { status: 'approved', label: 'Approved', icon: <CheckCircle2 size={14} />, desc: 'Approved by manager' },
  { status: 'posted',   label: 'Posted',   icon: <Send size={14} />,         desc: 'Posted to accounts' },
];

// ─── Workflow Progress ─────────────────────────────────────────────────────────

function WorkflowProgress({ currentStatus }: { currentStatus: ExpenseStatus }) {
  const stepIndex = statusSteps.findIndex(s => s.status === currentStatus);
  return (
    <div className="card p-4">
      <h3 className="text-sm font-semibold text-foreground mb-4 flex items-center gap-2">
        <Receipt size={15} className="text-primary" /> Approval Workflow
      </h3>
      <div className="space-y-3">
        {statusSteps.map((step, i) => {
          const isPast = i < stepIndex;
          const isCurrent = i === stepIndex;
          const isFuture = i > stepIndex;
          return (
            <div key={step.status} className="flex items-start gap-3">
              <div className="flex flex-col items-center">
                <div className={`w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 ${
                  isPast ? 'bg-success text-success-foreground' :
                  isCurrent ? 'bg-primary text-primary-foreground': 'bg-muted text-muted-foreground'
                }`}>
                  {isPast ? <CheckCircle2 size={14} /> : step.icon}
                </div>
                {i < statusSteps.length - 1 && (
                  <div className={`w-px h-6 mt-1 ${isPast ? 'bg-success' : 'bg-border'}`} />
                )}
              </div>
              <div className="pt-0.5">
                <p className={`text-xs font-semibold ${isCurrent ? 'text-primary' : isPast ? 'text-success' : 'text-muted-foreground'}`}>
                  {step.label}
                </p>
                <p className="text-2xs text-muted-foreground">{step.desc}</p>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}

// ─── Main Page ─────────────────────────────────────────────────────────────────

export default function NewExpensePage() {
  const router = useRouter();
  const [layout] = useState<DashboardLayout>('classic');
  const [status, setStatus] = useState<ExpenseStatus>('draft');
  const [saving, setSaving] = useState(false);
  const [errors, setErrors] = useState<Partial<Record<keyof ExpenseForm, string>>>({});

  const today = new Date().toISOString().split('T')[0];

  const [form, setForm] = useState<ExpenseForm>({
    title: '',
    category: '',
    description: '',
    amount: '',
    paymentMethod: 'cash',
    vendor: '',
    branch: 'Main Branch',
    date: today,
    receiptRef: '',
    notes: '',
    approver: approvers[0],
  });

  const set = (field: keyof ExpenseForm, value: string) => {
    setForm(prev => ({ ...prev, [field]: value }));
    if (errors[field]) setErrors(prev => ({ ...prev, [field]: undefined }));
  };

  const validate = (): boolean => {
    const newErrors: Partial<Record<keyof ExpenseForm, string>> = {};
    if (!form.title.trim()) newErrors.title = 'Title is required';
    if (!form.category) newErrors.category = 'Category is required';
    if (!form.amount || isNaN(Number(form.amount)) || Number(form.amount) <= 0) newErrors.amount = 'Valid amount is required';
    if (!form.vendor.trim()) newErrors.vendor = 'Vendor / payee is required';
    if (!form.date) newErrors.date = 'Date is required';
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSaveDraft = () => {
    if (!validate()) return;
    setSaving(true);
    setTimeout(() => {
      setSaving(false);
      setStatus('draft');
      router.push('/expenses');
    }, 800);
  };

  const handleSubmitForApproval = () => {
    if (!validate()) return;
    setSaving(true);
    setTimeout(() => {
      setSaving(false);
      setStatus('pending');
      router.push('/expenses');
    }, 800);
  };

  const amountNum = Number(form.amount) || 0;

  return (
    <AppLayout layout={layout} onLayoutChange={() => {}}>
      <div className="p-4 md:p-6 space-y-5 max-w-5xl mx-auto">
        {/* Header */}
        <div className="flex items-center gap-3">
          <Link
            href="/expenses"
            className="p-2 rounded-lg border border-border bg-card hover:bg-muted text-muted-foreground hover:text-foreground transition-colors"
          >
            <ArrowLeft size={16} />
          </Link>
          <div>
            <h1 className="text-xl font-bold text-foreground flex items-center gap-2">
              <Receipt size={22} className="text-primary" />
              New Expense
            </h1>
            <p className="text-sm text-muted-foreground mt-0.5">Create and submit a new expense for approval</p>
          </div>
        </div>

        {/* Breadcrumb */}
        <div className="flex items-center gap-1 text-xs text-muted-foreground">
          <Link href="/expenses" className="hover:text-foreground transition-colors">Expenses</Link>
          <ChevronRight size={12} />
          <span className="text-foreground font-medium">New Expense</span>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
          {/* Main Form */}
          <div className="lg:col-span-2 space-y-4">
            {/* Basic Info */}
            <div className="card p-5 space-y-4">
              <h2 className="text-sm font-semibold text-foreground flex items-center gap-2 pb-2 border-b border-border">
                <FileText size={15} className="text-primary" /> Expense Details
              </h2>

              {/* Title */}
              <div className="space-y-1">
                <label className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">
                  Expense Title <span className="text-danger">*</span>
                </label>
                <input
                  type="text"
                  value={form.title}
                  onChange={e => set('title', e.target.value)}
                  placeholder="e.g. Office Stationery – September 2026"
                  className={`w-full px-3 py-2.5 text-sm bg-card border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground placeholder:text-muted-foreground ${errors.title ? 'border-danger' : 'border-border'}`}
                />
                {errors.title && <p className="text-xs text-danger flex items-center gap-1"><AlertCircle size={11} />{errors.title}</p>}
              </div>

              {/* Category & Date */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div className="space-y-1">
                  <label className="text-xs font-semibold text-muted-foreground uppercase tracking-wide flex items-center gap-1">
                    <Tag size={11} /> Category <span className="text-danger">*</span>
                  </label>
                  <select
                    value={form.category}
                    onChange={e => set('category', e.target.value)}
                    className={`w-full px-3 py-2.5 text-sm bg-card border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground ${errors.category ? 'border-danger' : 'border-border'}`}
                  >
                    <option value="">Select category...</option>
                    {categories.map(c => <option key={c} value={c}>{c}</option>)}
                  </select>
                  {errors.category && <p className="text-xs text-danger flex items-center gap-1"><AlertCircle size={11} />{errors.category}</p>}
                </div>
                <div className="space-y-1">
                  <label className="text-xs font-semibold text-muted-foreground uppercase tracking-wide flex items-center gap-1">
                    <Calendar size={11} /> Expense Date <span className="text-danger">*</span>
                  </label>
                  <input
                    type="date"
                    value={form.date}
                    onChange={e => set('date', e.target.value)}
                    className={`w-full px-3 py-2.5 text-sm bg-card border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground ${errors.date ? 'border-danger' : 'border-border'}`}
                  />
                  {errors.date && <p className="text-xs text-danger flex items-center gap-1"><AlertCircle size={11} />{errors.date}</p>}
                </div>
              </div>

              {/* Vendor & Branch */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div className="space-y-1">
                  <label className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">
                    Vendor / Payee <span className="text-danger">*</span>
                  </label>
                  <input
                    type="text"
                    value={form.vendor}
                    onChange={e => set('vendor', e.target.value)}
                    placeholder="e.g. Nakumatt Supermarket"
                    className={`w-full px-3 py-2.5 text-sm bg-card border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground placeholder:text-muted-foreground ${errors.vendor ? 'border-danger' : 'border-border'}`}
                  />
                  {errors.vendor && <p className="text-xs text-danger flex items-center gap-1"><AlertCircle size={11} />{errors.vendor}</p>}
                </div>
                <div className="space-y-1">
                  <label className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Branch</label>
                  <select
                    value={form.branch}
                    onChange={e => set('branch', e.target.value)}
                    className="w-full px-3 py-2.5 text-sm bg-card border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground"
                  >
                    {branches.map(b => <option key={b} value={b}>{b}</option>)}
                  </select>
                </div>
              </div>

              {/* Description */}
              <div className="space-y-1">
                <label className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Description</label>
                <textarea
                  value={form.description}
                  onChange={e => set('description', e.target.value)}
                  placeholder="Brief description of the expense..."
                  rows={3}
                  className="w-full px-3 py-2.5 text-sm bg-card border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground placeholder:text-muted-foreground resize-none"
                />
              </div>
            </div>

            {/* Amount & Payment */}
            <div className="card p-5 space-y-4">
              <h2 className="text-sm font-semibold text-foreground flex items-center gap-2 pb-2 border-b border-border">
                <DollarSign size={15} className="text-primary" /> Amount & Payment
              </h2>

              {/* Amount */}
              <div className="space-y-1">
                <label className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">
                  Amount (TZS) <span className="text-danger">*</span>
                </label>
                <div className="relative">
                  <span className="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-muted-foreground font-semibold">TZS</span>
                  <input
                    type="number"
                    value={form.amount}
                    onChange={e => set('amount', e.target.value)}
                    placeholder="0"
                    min="0"
                    className={`w-full pl-12 pr-4 py-2.5 text-sm bg-card border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground placeholder:text-muted-foreground tabular-nums ${errors.amount ? 'border-danger' : 'border-border'}`}
                  />
                </div>
                {errors.amount && <p className="text-xs text-danger flex items-center gap-1"><AlertCircle size={11} />{errors.amount}</p>}
                {amountNum > 0 && (
                  <p className="text-xs text-muted-foreground">
                    = <MoneyDisplay amount={amountNum} currency="TZS" compact={false} className="text-xs" />
                  </p>
                )}
              </div>

              {/* Payment Method */}
              <div className="space-y-2">
                <label className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Payment Method</label>
                <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
                  {paymentMethods.map(pm => (
                    <button
                      key={pm.value}
                      type="button"
                      onClick={() => set('paymentMethod', pm.value)}
                      className={`flex items-center gap-2 p-3 rounded-xl border text-left transition-all ${
                        form.paymentMethod === pm.value
                          ? 'border-primary bg-primary/5 ring-2 ring-primary/20' :'border-border bg-card hover:bg-muted/50'
                      }`}
                    >
                      <span className={form.paymentMethod === pm.value ? 'text-primary' : 'text-muted-foreground'}>
                        {pm.icon}
                      </span>
                      <div>
                        <p className={`text-xs font-semibold ${form.paymentMethod === pm.value ? 'text-primary' : 'text-foreground'}`}>{pm.label}</p>
                        <p className="text-2xs text-muted-foreground">{pm.desc}</p>
                      </div>
                    </button>
                  ))}
                </div>
              </div>

              {/* Receipt Ref */}
              <div className="space-y-1">
                <label className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Receipt / Reference No.</label>
                <input
                  type="text"
                  value={form.receiptRef}
                  onChange={e => set('receiptRef', e.target.value)}
                  placeholder="e.g. RCP-0091 or invoice number"
                  className="w-full px-3 py-2.5 text-sm bg-card border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground placeholder:text-muted-foreground"
                />
              </div>

              {/* Receipt Upload placeholder */}
              <div className="border-2 border-dashed border-border rounded-xl p-4 flex flex-col items-center gap-2 text-center hover:border-primary/40 transition-colors cursor-pointer">
                <Upload size={20} className="text-muted-foreground" />
                <p className="text-xs font-medium text-foreground">Attach Receipt</p>
                <p className="text-2xs text-muted-foreground">PNG, JPG, or PDF up to 5MB</p>
              </div>
            </div>

            {/* Approval & Notes */}
            <div className="card p-5 space-y-4">
              <h2 className="text-sm font-semibold text-foreground flex items-center gap-2 pb-2 border-b border-border">
                <User size={15} className="text-primary" /> Approval & Notes
              </h2>

              <div className="space-y-1">
                <label className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Approver</label>
                <select
                  value={form.approver}
                  onChange={e => set('approver', e.target.value)}
                  className="w-full px-3 py-2.5 text-sm bg-card border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground"
                >
                  {approvers.map(a => <option key={a} value={a}>{a}</option>)}
                </select>
                <p className="text-2xs text-muted-foreground">This person will be notified when you submit for approval.</p>
              </div>

              <div className="space-y-1">
                <label className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Internal Notes</label>
                <textarea
                  value={form.notes}
                  onChange={e => set('notes', e.target.value)}
                  placeholder="Any additional notes for the approver..."
                  rows={3}
                  className="w-full px-3 py-2.5 text-sm bg-card border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground placeholder:text-muted-foreground resize-none"
                />
              </div>
            </div>
          </div>

          {/* Right Sidebar */}
          <div className="space-y-4">
            {/* Summary */}
            <div className="card p-4 space-y-3">
              <h3 className="text-sm font-semibold text-foreground flex items-center gap-2">
                <Receipt size={15} className="text-primary" /> Summary
              </h3>
              <div className="space-y-2 text-sm">
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Category</span>
                  <span className="font-medium text-foreground text-right max-w-[140px] truncate">{form.category || '—'}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Vendor</span>
                  <span className="font-medium text-foreground text-right max-w-[140px] truncate">{form.vendor || '—'}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Date</span>
                  <span className="font-medium text-foreground">{form.date || '—'}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Payment</span>
                  <span className="font-medium text-foreground">
                    {paymentMethods.find(p => p.value === form.paymentMethod)?.label || '—'}
                  </span>
                </div>
                <div className="border-t border-border pt-2 flex justify-between items-center">
                  <span className="text-muted-foreground font-semibold">Total</span>
                  <MoneyDisplay amount={amountNum} currency="TZS" className="text-base font-bold text-foreground" />
                </div>
              </div>
            </div>

            {/* Workflow */}
            <WorkflowProgress currentStatus={status} />

            {/* Actions */}
            <div className="card p-4 space-y-2">
              <h3 className="text-sm font-semibold text-foreground mb-3">Actions</h3>
              <button
                onClick={handleSubmitForApproval}
                disabled={saving}
                className="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-primary text-primary-foreground text-sm font-semibold rounded-lg hover:bg-primary/90 transition-colors disabled:opacity-60"
              >
                <Send size={15} />
                {saving ? 'Submitting...' : 'Submit for Approval'}
              </button>
              <button
                onClick={handleSaveDraft}
                disabled={saving}
                className="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-card border border-border text-foreground text-sm font-medium rounded-lg hover:bg-muted transition-colors disabled:opacity-60"
              >
                <Save size={15} />
                Save as Draft
              </button>
              <Link
                href="/expenses"
                className="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-muted-foreground text-sm rounded-lg hover:bg-muted transition-colors"
              >
                <Trash2 size={15} />
                Discard
              </Link>
            </div>

            {/* Status info */}
            <div className="rounded-xl border border-info/20 bg-info/5 p-3">
              <p className="text-xs font-semibold text-info mb-1 flex items-center gap-1">
                <AlertCircle size={12} /> Workflow Guide
              </p>
              <ul className="text-2xs text-muted-foreground space-y-1">
                <li>• <strong>Draft</strong> — saved, not yet submitted</li>
                <li>• <strong>Pending</strong> — sent to approver</li>
                <li>• <strong>Approved</strong> — manager signed off</li>
                <li>• <strong>Posted</strong> — recorded in accounts</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
