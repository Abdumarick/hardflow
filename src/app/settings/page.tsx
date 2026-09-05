'use client';
import React, { useState } from 'react';
import AppLayout, { DashboardLayout } from '@/components/AppLayout';
import { Building2, DollarSign, CreditCard, Package, Bell, GitBranch, Save, ChevronDown, Check, Plus, Edit2, ToggleLeft, ToggleRight, AlertTriangle, Globe, Phone, Mail, MapPin, Hash, Percent, Banknote, Smartphone, Landmark, Wallet, Upload, ShieldCheck, Info,  } from 'lucide-react';

// ─── Types ────────────────────────────────────────────────────────────────────

interface CompanyInfo {
  name: string;
  legalName: string;
  tin: string;
  vrn: string;
  phone: string;
  email: string;
  website: string;
  address: string;
  city: string;
  country: string;
  postalCode: string;
  logo: string;
  receiptFooter: string;
  fiscalYearStart: string;
}

interface CurrencyTax {
  baseCurrency: string;
  currencySymbol: string;
  decimalPlaces: number;
  thousandSeparator: string;
  decimalSeparator: string;
  vatEnabled: boolean;
  vatRate: number;
  vatNumber: string;
  withholdingTaxEnabled: boolean;
  withholdingTaxRate: number;
  pricesIncludeTax: boolean;
  taxOnReceipt: boolean;
}

interface PaymentMethod {
  id: string;
  name: string;
  type: 'cash' | 'bank' | 'mobile' | 'card' | 'credit';
  enabled: boolean;
  isDefault: boolean;
  accountRef: string;
  requiresRef: boolean;
  icon: React.ReactNode;
}

interface StockThreshold {
  id: string;
  name: string;
  category: string;
  lowStockQty: number;
  criticalStockQty: number;
  reorderQty: number;
  autoReorder: boolean;
}

interface NotificationPref {
  id: string;
  label: string;
  description: string;
  email: boolean;
  sms: boolean;
  inApp: boolean;
  group: string;
}

interface Branch {
  id: string;
  name: string;
  location: string;
  manager: string;
  currency: string;
  vatRate: number;
  defaultPayment: string;
  lowStockAlert: number;
  active: boolean;
  overrideGlobal: boolean;
}

// ─── Initial State ─────────────────────────────────────────────────────────────

const initCompany: CompanyInfo = {
  name: 'HardFlow',
  legalName: 'Karibu Hardware Ltd',
  tin: '123-456-789',
  vrn: 'VRN-40-12345',
  phone: '+255 712 345 678',
  email: 'info@karibuhardware.co.tz',
  website: 'www.karibuhardware.co.tz',
  address: '45 Kariakoo Street',
  city: 'Dar es Salaam',
  country: 'Tanzania',
  postalCode: 'P.O. Box 1234',
  logo: '',
  receiptFooter: 'Thank you for shopping at Karibu Hardware. Returns accepted within 7 days with receipt.',
  fiscalYearStart: 'January',
};

const initCurrencyTax: CurrencyTax = {
  baseCurrency: 'TZS',
  currencySymbol: 'TZS',
  decimalPlaces: 0,
  thousandSeparator: ',',
  decimalSeparator: '.',
  vatEnabled: true,
  vatRate: 18,
  vatNumber: 'VRN-40-12345',
  withholdingTaxEnabled: false,
  withholdingTaxRate: 5,
  pricesIncludeTax: false,
  taxOnReceipt: true,
};

const initPaymentMethods: PaymentMethod[] = [
  { id: 'pm-cash', name: 'Cash', type: 'cash', enabled: true, isDefault: true, accountRef: 'CASH-001', requiresRef: false, icon: <Banknote size={16} /> },
  { id: 'pm-mpesa', name: 'M-Pesa', type: 'mobile', enabled: true, isDefault: false, accountRef: '0712345678', requiresRef: true, icon: <Smartphone size={16} /> },
  { id: 'pm-tigopesa', name: 'Tigo Pesa', type: 'mobile', enabled: true, isDefault: false, accountRef: '0655123456', requiresRef: true, icon: <Smartphone size={16} /> },
  { id: 'pm-bank', name: 'Bank Transfer', type: 'bank', enabled: true, isDefault: false, accountRef: 'CRDB-001-2345', requiresRef: true, icon: <Landmark size={16} /> },
  { id: 'pm-card', name: 'Debit / Credit Card', type: 'card', enabled: false, isDefault: false, accountRef: '', requiresRef: true, icon: <CreditCard size={16} /> },
  { id: 'pm-credit', name: 'Customer Credit', type: 'credit', enabled: true, isDefault: false, accountRef: '', requiresRef: false, icon: <Wallet size={16} /> },
];

const initStockThresholds: StockThreshold[] = [
  { id: 'st-1', name: 'Default (All Products)', category: 'All', lowStockQty: 10, criticalStockQty: 3, reorderQty: 50, autoReorder: false },
  { id: 'st-2', name: 'Cement & Aggregates', category: 'Cement', lowStockQty: 20, criticalStockQty: 5, reorderQty: 100, autoReorder: true },
  { id: 'st-3', name: 'Electrical Fittings', category: 'Electrical', lowStockQty: 15, criticalStockQty: 5, reorderQty: 60, autoReorder: false },
  { id: 'st-4', name: 'Plumbing Supplies', category: 'Plumbing', lowStockQty: 12, criticalStockQty: 4, reorderQty: 40, autoReorder: false },
  { id: 'st-5', name: 'Power Tools', category: 'Tools', lowStockQty: 5, criticalStockQty: 2, reorderQty: 15, autoReorder: false },
];

const initNotifications: NotificationPref[] = [
  // Stock
  { id: 'n-low-stock', label: 'Low Stock Alert', description: 'Notify when product falls below low stock threshold', email: true, sms: false, inApp: true, group: 'Inventory' },
  { id: 'n-critical-stock', label: 'Critical Stock Alert', description: 'Urgent alert when stock hits critical level', email: true, sms: true, inApp: true, group: 'Inventory' },
  { id: 'n-out-stock', label: 'Out of Stock', description: 'Alert when a product is completely out of stock', email: true, sms: true, inApp: true, group: 'Inventory' },
  { id: 'n-expiry', label: 'Expiry Warning', description: 'Notify 30 days before product expiry date', email: true, sms: false, inApp: true, group: 'Inventory' },
  // Sales
  { id: 'n-large-sale', label: 'Large Sale Alert', description: 'Notify when a single sale exceeds TZS 1,000,000', email: false, sms: false, inApp: true, group: 'Sales' },
  { id: 'n-daily-summary', label: 'Daily Sales Summary', description: 'End-of-day sales report sent automatically', email: true, sms: false, inApp: false, group: 'Sales' },
  { id: 'n-void-sale', label: 'Sale Voided', description: 'Alert when a sale is voided or reversed', email: true, sms: false, inApp: true, group: 'Sales' },
  // Finance
  { id: 'n-expense-approval', label: 'Expense Approval Request', description: 'Notify approver when expense is submitted', email: true, sms: false, inApp: true, group: 'Finance' },
  { id: 'n-overdue-debt', label: 'Overdue Customer Debt', description: 'Alert when customer debt is overdue by 7+ days', email: true, sms: true, inApp: true, group: 'Finance' },
  { id: 'n-purchase-received', label: 'Purchase Order Received', description: 'Confirm when goods are received against a PO', email: true, sms: false, inApp: true, group: 'Finance' },
  // System
  { id: 'n-user-login', label: 'New User Login', description: 'Alert on login from a new device or location', email: false, sms: false, inApp: true, group: 'System' },
  { id: 'n-backup', label: 'Backup Completed', description: 'Confirm when daily data backup succeeds', email: true, sms: false, inApp: false, group: 'System' },
];

const initBranches: Branch[] = [
  { id: 'br-1', name: 'Main Branch – Kariakoo', location: 'Dar es Salaam', manager: 'James Mwangi', currency: 'TZS', vatRate: 18, defaultPayment: 'Cash', lowStockAlert: 10, active: true, overrideGlobal: false },
  { id: 'br-2', name: 'Warehouse – Ubungo', location: 'Dar es Salaam', manager: 'Peter Odhiambo', currency: 'TZS', vatRate: 18, defaultPayment: 'Bank Transfer', lowStockAlert: 20, active: true, overrideGlobal: true },
  { id: 'br-3', name: 'Mwanza Branch', location: 'Mwanza', manager: 'Grace Nyamwezi', currency: 'TZS', vatRate: 18, defaultPayment: 'M-Pesa', lowStockAlert: 8, active: true, overrideGlobal: true },
  { id: 'br-4', name: 'Arusha Outlet', location: 'Arusha', manager: 'David Kimaro', currency: 'TZS', vatRate: 18, defaultPayment: 'Cash', lowStockAlert: 10, active: false, overrideGlobal: false },
];

// ─── Tab Config ────────────────────────────────────────────────────────────────

const TABS = [
  { id: 'company', label: 'Company Info', icon: <Building2 size={15} /> },
  { id: 'currency', label: 'Currency & Tax', icon: <DollarSign size={15} /> },
  { id: 'payments', label: 'Payment Methods', icon: <CreditCard size={15} /> },
  { id: 'stock', label: 'Stock Thresholds', icon: <Package size={15} /> },
  { id: 'notifications', label: 'Notifications', icon: <Bell size={15} /> },
  { id: 'branches', label: 'Branch Overrides', icon: <GitBranch size={15} /> },
];

const CURRENCIES = ['TZS', 'USD', 'EUR', 'GBP', 'KES', 'UGX', 'RWF', 'ZAR'];
const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

// ─── Reusable Field Components ─────────────────────────────────────────────────

function FieldGroup({ label, children, hint }: { label: string; children: React.ReactNode; hint?: string }) {
  return (
    <div className="space-y-1">
      <label className="block text-xs font-semibold text-foreground">{label}</label>
      {children}
      {hint && <p className="text-2xs text-muted-foreground">{hint}</p>}
    </div>
  );
}

function TextInput({ value, onChange, placeholder, prefix, type = 'text' }: {
  value: string | number; onChange: (v: string) => void; placeholder?: string; prefix?: string; type?: string;
}) {
  return (
    <div className="flex items-center border border-border rounded-lg overflow-hidden bg-background focus-within:ring-2 focus-within:ring-primary/30 focus-within:border-primary transition-all">
      {prefix && <span className="px-3 py-2 text-xs text-muted-foreground bg-muted border-r border-border shrink-0">{prefix}</span>}
      <input
        type={type}
        value={value}
        onChange={e => onChange(e.target.value)}
        placeholder={placeholder}
        className="flex-1 px-3 py-2 text-sm bg-transparent outline-none text-foreground placeholder:text-muted-foreground"
      />
    </div>
  );
}

function Toggle({ checked, onChange, label }: { checked: boolean; onChange: (v: boolean) => void; label?: string }) {
  return (
    <button
      onClick={() => onChange(!checked)}
      className={`flex items-center gap-2 text-sm font-medium transition-colors ${checked ? 'text-primary' : 'text-muted-foreground'}`}
    >
      {checked ? <ToggleRight size={22} className="text-primary" /> : <ToggleLeft size={22} />}
      {label && <span>{label}</span>}
    </button>
  );
}

function SectionCard({ title, description, children }: { title: string; description?: string; children: React.ReactNode }) {
  return (
    <div className="bg-card border border-border rounded-xl p-5 space-y-4">
      <div>
        <h3 className="text-sm font-semibold text-foreground">{title}</h3>
        {description && <p className="text-xs text-muted-foreground mt-0.5">{description}</p>}
      </div>
      {children}
    </div>
  );
}

// ─── Tab: Company Info ─────────────────────────────────────────────────────────

function CompanyTab({ data, onChange }: { data: CompanyInfo; onChange: (d: CompanyInfo) => void }) {
  const set = (key: keyof CompanyInfo) => (v: string) => onChange({ ...data, [key]: v });
  return (
    <div className="space-y-5">
      <SectionCard title="Business Identity" description="Legal name, registration numbers, and contact details">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <FieldGroup label="Display Name">
            <TextInput value={data.name} onChange={set('name')} placeholder="HardFlow" />
          </FieldGroup>
          <FieldGroup label="Legal / Registered Name">
            <TextInput value={data.legalName} onChange={set('legalName')} placeholder="Karibu Hardware Ltd" />
          </FieldGroup>
          <FieldGroup label="TIN (Tax Identification Number)">
            <TextInput value={data.tin} onChange={set('tin')} placeholder="123-456-789" prefix={<Hash size={12} />} />
          </FieldGroup>
          <FieldGroup label="VAT Registration Number (VRN)">
            <TextInput value={data.vrn} onChange={set('vrn')} placeholder="VRN-40-XXXXX" prefix={<ShieldCheck size={12} />} />
          </FieldGroup>
          <FieldGroup label="Fiscal Year Start">
            <div className="relative">
              <select
                value={data.fiscalYearStart}
                onChange={e => set('fiscalYearStart')(e.target.value)}
                className="w-full px-3 py-2 text-sm border border-border rounded-lg bg-background text-foreground appearance-none outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
              >
                {MONTHS.map(m => <option key={m} value={m}>{m}</option>)}
              </select>
              <ChevronDown size={14} className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none" />
            </div>
          </FieldGroup>
        </div>
      </SectionCard>

      <SectionCard title="Contact Information" description="Phone, email, website, and physical address">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <FieldGroup label="Phone Number">
            <TextInput value={data.phone} onChange={set('phone')} placeholder="+255 7XX XXX XXX" prefix={<Phone size={12} />} />
          </FieldGroup>
          <FieldGroup label="Email Address">
            <TextInput value={data.email} onChange={set('email')} placeholder="info@company.co.tz" prefix={<Mail size={12} />} />
          </FieldGroup>
          <FieldGroup label="Website">
            <TextInput value={data.website} onChange={set('website')} placeholder="www.company.co.tz" prefix={<Globe size={12} />} />
          </FieldGroup>
          <FieldGroup label="City">
            <TextInput value={data.city} onChange={set('city')} placeholder="Dar es Salaam" />
          </FieldGroup>
          <FieldGroup label="Country">
            <TextInput value={data.country} onChange={set('country')} placeholder="Tanzania" />
          </FieldGroup>
          <FieldGroup label="Postal Code / P.O. Box">
            <TextInput value={data.postalCode} onChange={set('postalCode')} placeholder="P.O. Box 1234" prefix={<MapPin size={12} />} />
          </FieldGroup>
          <div className="md:col-span-2">
            <FieldGroup label="Street Address">
              <TextInput value={data.address} onChange={set('address')} placeholder="45 Kariakoo Street" />
            </FieldGroup>
          </div>
        </div>
      </SectionCard>

      <SectionCard title="Receipt & Branding" description="Logo and receipt footer message">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <FieldGroup label="Company Logo">
            <div className="flex items-center gap-3">
              <div className="w-16 h-16 rounded-lg border-2 border-dashed border-border flex items-center justify-center bg-muted text-muted-foreground">
                <Building2 size={24} />
              </div>
              <button className="flex items-center gap-2 px-3 py-2 text-xs font-medium border border-border rounded-lg hover:bg-muted transition-colors text-foreground">
                <Upload size={13} /> Upload Logo
              </button>
            </div>
          </FieldGroup>
          <div className="md:col-span-2">
            <FieldGroup label="Receipt Footer Message" hint="Printed at the bottom of every receipt">
              <textarea
                value={data.receiptFooter}
                onChange={e => set('receiptFooter')(e.target.value)}
                rows={3}
                className="w-full px-3 py-2 text-sm border border-border rounded-lg bg-background text-foreground outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary resize-none"
                placeholder="Thank you for your purchase..."
              />
            </FieldGroup>
          </div>
        </div>
      </SectionCard>
    </div>
  );
}

// ─── Tab: Currency & Tax ───────────────────────────────────────────────────────

function CurrencyTab({ data, onChange }: { data: CurrencyTax; onChange: (d: CurrencyTax) => void }) {
  const set = (key: keyof CurrencyTax) => (v: string | number | boolean) => onChange({ ...data, [key]: v });
  return (
    <div className="space-y-5">
      <SectionCard title="Currency Settings" description="Base currency and number formatting">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <FieldGroup label="Base Currency">
            <div className="relative">
              <select
                value={data.baseCurrency}
                onChange={e => set('baseCurrency')(e.target.value)}
                className="w-full px-3 py-2 text-sm border border-border rounded-lg bg-background text-foreground appearance-none outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
              >
                {CURRENCIES.map(c => <option key={c} value={c}>{c}</option>)}
              </select>
              <ChevronDown size={14} className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none" />
            </div>
          </FieldGroup>
          <FieldGroup label="Currency Symbol">
            <TextInput value={data.currencySymbol} onChange={v => set('currencySymbol')(v)} placeholder="TZS" />
          </FieldGroup>
          <FieldGroup label="Decimal Places">
            <div className="relative">
              <select
                value={data.decimalPlaces}
                onChange={e => set('decimalPlaces')(Number(e.target.value))}
                className="w-full px-3 py-2 text-sm border border-border rounded-lg bg-background text-foreground appearance-none outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
              >
                {[0, 1, 2, 3].map(n => <option key={n} value={n}>{n}</option>)}
              </select>
              <ChevronDown size={14} className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none" />
            </div>
          </FieldGroup>
          <FieldGroup label="Thousands Separator">
            <div className="relative">
              <select
                value={data.thousandSeparator}
                onChange={e => set('thousandSeparator')(e.target.value)}
                className="w-full px-3 py-2 text-sm border border-border rounded-lg bg-background text-foreground appearance-none outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
              >
                <option value=",">, (comma)</option>
                <option value=".">. (period)</option>
                <option value=" ">  (space)</option>
              </select>
              <ChevronDown size={14} className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none" />
            </div>
          </FieldGroup>
          <FieldGroup label="Decimal Separator">
            <div className="relative">
              <select
                value={data.decimalSeparator}
                onChange={e => set('decimalSeparator')(e.target.value)}
                className="w-full px-3 py-2 text-sm border border-border rounded-lg bg-background text-foreground appearance-none outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
              >
                <option value=".">. (period)</option>
                <option value=",">, (comma)</option>
              </select>
              <ChevronDown size={14} className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none" />
            </div>
          </FieldGroup>
          <FieldGroup label="Preview">
            <div className="px-3 py-2 text-sm border border-border rounded-lg bg-muted text-foreground font-mono">
              {data.currencySymbol} 1{data.thousandSeparator}234{data.thousandSeparator}567{data.decimalPlaces > 0 ? data.decimalSeparator + '00' : ''}
            </div>
          </FieldGroup>
        </div>
      </SectionCard>

      <SectionCard title="VAT / Tax Configuration" description="Value Added Tax rates and display settings">
        <div className="space-y-4">
          <div className="flex items-center justify-between p-3 rounded-lg bg-muted/50 border border-border">
            <div>
              <p className="text-sm font-medium text-foreground">Enable VAT</p>
              <p className="text-xs text-muted-foreground">Apply VAT to all taxable transactions</p>
            </div>
            <Toggle checked={data.vatEnabled} onChange={v => set('vatEnabled')(v)} />
          </div>
          {data.vatEnabled && (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <FieldGroup label="VAT Rate (%)" hint="Standard rate applied to taxable items">
                <TextInput value={data.vatRate} onChange={v => set('vatRate')(Number(v))} type="number" prefix={<Percent size={12} />} />
              </FieldGroup>
              <FieldGroup label="VAT Registration Number">
                <TextInput value={data.vatNumber} onChange={v => set('vatNumber')(v)} placeholder="VRN-40-XXXXX" />
              </FieldGroup>
            </div>
          )}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div className="flex items-center justify-between p-3 rounded-lg border border-border">
              <div>
                <p className="text-sm font-medium text-foreground">Prices Include Tax</p>
                <p className="text-xs text-muted-foreground">Product prices are tax-inclusive</p>
              </div>
              <Toggle checked={data.pricesIncludeTax} onChange={v => set('pricesIncludeTax')(v)} />
            </div>
            <div className="flex items-center justify-between p-3 rounded-lg border border-border">
              <div>
                <p className="text-sm font-medium text-foreground">Show Tax on Receipt</p>
                <p className="text-xs text-muted-foreground">Print VAT breakdown on receipts</p>
              </div>
              <Toggle checked={data.taxOnReceipt} onChange={v => set('taxOnReceipt')(v)} />
            </div>
          </div>
        </div>
      </SectionCard>

      <SectionCard title="Withholding Tax" description="WHT settings for applicable transactions">
        <div className="space-y-4">
          <div className="flex items-center justify-between p-3 rounded-lg bg-muted/50 border border-border">
            <div>
              <p className="text-sm font-medium text-foreground">Enable Withholding Tax</p>
              <p className="text-xs text-muted-foreground">Deduct WHT from supplier payments</p>
            </div>
            <Toggle checked={data.withholdingTaxEnabled} onChange={v => set('withholdingTaxEnabled')(v)} />
          </div>
          {data.withholdingTaxEnabled && (
            <FieldGroup label="Withholding Tax Rate (%)" hint="Applied to qualifying supplier payments">
              <TextInput value={data.withholdingTaxRate} onChange={v => set('withholdingTaxRate')(Number(v))} type="number" prefix={<Percent size={12} />} />
            </FieldGroup>
          )}
        </div>
      </SectionCard>
    </div>
  );
}

// ─── Tab: Payment Methods ──────────────────────────────────────────────────────

function PaymentsTab({ methods, onChange }: { methods: PaymentMethod[]; onChange: (m: PaymentMethod[]) => void }) {
  const update = (id: string, patch: Partial<PaymentMethod>) => {
    onChange(methods.map(m => m.id === id ? { ...m, ...patch } : m));
  };
  const setDefault = (id: string) => {
    onChange(methods.map(m => ({ ...m, isDefault: m.id === id })));
  };

  const typeColors: Record<string, string> = {
    cash: 'bg-success/10 text-success',
    mobile: 'bg-info/10 text-info',
    bank: 'bg-primary/10 text-primary',
    card: 'bg-warning/10 text-warning',
    credit: 'bg-danger/10 text-danger',
  };

  return (
    <div className="space-y-5">
      <SectionCard title="Payment Methods" description="Enable, disable, and configure accepted payment methods">
        <div className="space-y-3">
          {methods.map(method => (
            <div key={method.id} className={`p-4 rounded-xl border transition-all ${method.enabled ? 'border-border bg-card' : 'border-border/50 bg-muted/30 opacity-60'}`}>
              <div className="flex items-start gap-3">
                <div className={`w-8 h-8 rounded-lg flex items-center justify-center shrink-0 ${typeColors[method.type]}`}>
                  {method.icon}
                </div>
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2 flex-wrap">
                    <span className="text-sm font-semibold text-foreground">{method.name}</span>
                    <span className={`text-2xs font-medium px-2 py-0.5 rounded-full capitalize ${typeColors[method.type]}`}>{method.type}</span>
                    {method.isDefault && (
                      <span className="text-2xs font-medium px-2 py-0.5 rounded-full bg-primary/10 text-primary">Default</span>
                    )}
                  </div>
                  {method.enabled && (
                    <div className="mt-2 flex items-center gap-2">
                      <input
                        type="text"
                        value={method.accountRef}
                        onChange={e => update(method.id, { accountRef: e.target.value })}
                        placeholder="Account / reference number"
                        className="flex-1 px-2.5 py-1.5 text-xs border border-border rounded-lg bg-background text-foreground outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
                      />
                    </div>
                  )}
                </div>
                <div className="flex items-center gap-2 shrink-0">
                  {method.enabled && !method.isDefault && (
                    <button
                      onClick={() => setDefault(method.id)}
                      className="text-2xs px-2 py-1 rounded-md border border-border hover:bg-muted text-muted-foreground hover:text-foreground transition-colors"
                    >
                      Set Default
                    </button>
                  )}
                  <Toggle checked={method.enabled} onChange={v => update(method.id, { enabled: v })} />
                </div>
              </div>
              {method.enabled && (
                <div className="mt-3 flex items-center gap-4 pt-3 border-t border-border/50">
                  <label className="flex items-center gap-2 text-xs text-muted-foreground cursor-pointer">
                    <input
                      type="checkbox"
                      checked={method.requiresRef}
                      onChange={e => update(method.id, { requiresRef: e.target.checked })}
                      className="rounded border-border"
                    />
                    Require transaction reference
                  </label>
                </div>
              )}
            </div>
          ))}
        </div>
      </SectionCard>
    </div>
  );
}

// ─── Tab: Stock Thresholds ─────────────────────────────────────────────────────

function StockTab({ thresholds, onChange }: { thresholds: StockThreshold[]; onChange: (t: StockThreshold[]) => void }) {
  const [editing, setEditing] = useState<string | null>(null);
  const update = (id: string, patch: Partial<StockThreshold>) => {
    onChange(thresholds.map(t => t.id === id ? { ...t, ...patch } : t));
  };

  return (
    <div className="space-y-5">
      <div className="flex items-start gap-3 p-4 rounded-xl bg-warning/5 border border-warning/20">
        <AlertTriangle size={16} className="text-warning shrink-0 mt-0.5" />
        <div>
          <p className="text-sm font-medium text-foreground">Threshold Rules</p>
          <p className="text-xs text-muted-foreground mt-0.5">The Default rule applies to all products without a category-specific override. Category rules take precedence over the default.</p>
        </div>
      </div>

      <SectionCard title="Stock Alert Thresholds" description="Define low stock, critical, and reorder quantities per category">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-border">
                <th className="text-left py-2 px-3 text-xs font-semibold text-muted-foreground">Rule Name</th>
                <th className="text-center py-2 px-3 text-xs font-semibold text-muted-foreground">Low Stock</th>
                <th className="text-center py-2 px-3 text-xs font-semibold text-muted-foreground">Critical</th>
                <th className="text-center py-2 px-3 text-xs font-semibold text-muted-foreground">Reorder Qty</th>
                <th className="text-center py-2 px-3 text-xs font-semibold text-muted-foreground">Auto Reorder</th>
                <th className="py-2 px-3" />
              </tr>
            </thead>
            <tbody className="divide-y divide-border">
              {thresholds.map(t => (
                <tr key={t.id} className="hover:bg-muted/30 transition-colors">
                  <td className="py-3 px-3">
                    <div>
                      <p className="text-sm font-medium text-foreground">{t.name}</p>
                      <p className="text-2xs text-muted-foreground">{t.category}</p>
                    </div>
                  </td>
                  <td className="py-3 px-3 text-center">
                    {editing === t.id ? (
                      <input
                        type="number"
                        value={t.lowStockQty}
                        onChange={e => update(t.id, { lowStockQty: Number(e.target.value) })}
                        className="w-16 text-center px-2 py-1 text-xs border border-border rounded-lg bg-background outline-none focus:ring-2 focus:ring-primary/30"
                      />
                    ) : (
                      <span className="px-2 py-1 rounded-md bg-warning/10 text-warning text-xs font-semibold">{t.lowStockQty}</span>
                    )}
                  </td>
                  <td className="py-3 px-3 text-center">
                    {editing === t.id ? (
                      <input
                        type="number"
                        value={t.criticalStockQty}
                        onChange={e => update(t.id, { criticalStockQty: Number(e.target.value) })}
                        className="w-16 text-center px-2 py-1 text-xs border border-border rounded-lg bg-background outline-none focus:ring-2 focus:ring-primary/30"
                      />
                    ) : (
                      <span className="px-2 py-1 rounded-md bg-danger/10 text-danger text-xs font-semibold">{t.criticalStockQty}</span>
                    )}
                  </td>
                  <td className="py-3 px-3 text-center">
                    {editing === t.id ? (
                      <input
                        type="number"
                        value={t.reorderQty}
                        onChange={e => update(t.id, { reorderQty: Number(e.target.value) })}
                        className="w-16 text-center px-2 py-1 text-xs border border-border rounded-lg bg-background outline-none focus:ring-2 focus:ring-primary/30"
                      />
                    ) : (
                      <span className="text-sm text-foreground">{t.reorderQty}</span>
                    )}
                  </td>
                  <td className="py-3 px-3 text-center">
                    <Toggle checked={t.autoReorder} onChange={v => update(t.id, { autoReorder: v })} />
                  </td>
                  <td className="py-3 px-3 text-right">
                    <button
                      onClick={() => setEditing(editing === t.id ? null : t.id)}
                      className="p-1.5 rounded-lg hover:bg-muted transition-colors text-muted-foreground hover:text-foreground"
                    >
                      {editing === t.id ? <Check size={14} className="text-primary" /> : <Edit2 size={14} />}
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <button className="flex items-center gap-2 text-xs font-medium text-primary hover:text-primary/80 transition-colors mt-2">
          <Plus size={14} /> Add Category Rule
        </button>
      </SectionCard>
    </div>
  );
}

// ─── Tab: Notifications ────────────────────────────────────────────────────────

function NotificationsTab({ prefs, onChange }: { prefs: NotificationPref[]; onChange: (p: NotificationPref[]) => void }) {
  const update = (id: string, patch: Partial<NotificationPref>) => {
    onChange(prefs.map(p => p.id === id ? { ...p, ...patch } : p));
  };

  const groups = Array.from(new Set(prefs.map(p => p.group)));

  return (
    <div className="space-y-5">
      <div className="flex items-start gap-3 p-4 rounded-xl bg-info/5 border border-info/20">
        <Info size={16} className="text-info shrink-0 mt-0.5" />
        <p className="text-xs text-muted-foreground">Configure how and where each alert is delivered. In-App notifications appear in the notification bell. SMS requires a configured SMS gateway.</p>
      </div>

      {groups.map(group => (
        <SectionCard key={group} title={group} description={`${group} event notifications`}>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border">
                  <th className="text-left py-2 text-xs font-semibold text-muted-foreground">Event</th>
                  <th className="text-center py-2 px-4 text-xs font-semibold text-muted-foreground">
                    <div className="flex items-center justify-center gap-1"><Mail size={12} /> Email</div>
                  </th>
                  <th className="text-center py-2 px-4 text-xs font-semibold text-muted-foreground">
                    <div className="flex items-center justify-center gap-1"><Smartphone size={12} /> SMS</div>
                  </th>
                  <th className="text-center py-2 px-4 text-xs font-semibold text-muted-foreground">
                    <div className="flex items-center justify-center gap-1"><Bell size={12} /> In-App</div>
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {prefs.filter(p => p.group === group).map(pref => (
                  <tr key={pref.id} className="hover:bg-muted/30 transition-colors">
                    <td className="py-3 pr-4">
                      <p className="text-sm font-medium text-foreground">{pref.label}</p>
                      <p className="text-2xs text-muted-foreground">{pref.description}</p>
                    </td>
                    <td className="py-3 px-4 text-center">
                      <input
                        type="checkbox"
                        checked={pref.email}
                        onChange={e => update(pref.id, { email: e.target.checked })}
                        className="w-4 h-4 rounded border-border accent-primary cursor-pointer"
                      />
                    </td>
                    <td className="py-3 px-4 text-center">
                      <input
                        type="checkbox"
                        checked={pref.sms}
                        onChange={e => update(pref.id, { sms: e.target.checked })}
                        className="w-4 h-4 rounded border-border accent-primary cursor-pointer"
                      />
                    </td>
                    <td className="py-3 px-4 text-center">
                      <input
                        type="checkbox"
                        checked={pref.inApp}
                        onChange={e => update(pref.id, { inApp: e.target.checked })}
                        className="w-4 h-4 rounded border-border accent-primary cursor-pointer"
                      />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </SectionCard>
      ))}
    </div>
  );
}

// ─── Tab: Branch Overrides ─────────────────────────────────────────────────────

function BranchesTab({ branches, onChange }: { branches: Branch[]; onChange: (b: Branch[]) => void }) {
  const [expanded, setExpanded] = useState<string | null>(null);
  const update = (id: string, patch: Partial<Branch>) => {
    onChange(branches.map(b => b.id === id ? { ...b, ...patch } : b));
  };

  return (
    <div className="space-y-5">
      <div className="flex items-start gap-3 p-4 rounded-xl bg-primary/5 border border-primary/20">
        <Info size={16} className="text-primary shrink-0 mt-0.5" />
        <p className="text-xs text-muted-foreground">Branch overrides let individual branches use different currency, VAT rate, default payment method, or stock thresholds. When "Override Global" is off, the branch inherits all global settings.</p>
      </div>

      <div className="space-y-3">
        {branches.map(branch => (
          <div key={branch.id} className="bg-card border border-border rounded-xl overflow-hidden">
            {/* Branch Header */}
            <div
              className="flex items-center gap-3 p-4 cursor-pointer hover:bg-muted/30 transition-colors"
              onClick={() => setExpanded(expanded === branch.id ? null : branch.id)}
            >
              <div className={`w-9 h-9 rounded-lg flex items-center justify-center shrink-0 ${branch.active ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'}`}>
                <GitBranch size={16} />
              </div>
              <div className="flex-1 min-w-0">
                <div className="flex items-center gap-2 flex-wrap">
                  <span className="text-sm font-semibold text-foreground">{branch.name}</span>
                  <span className={`text-2xs px-2 py-0.5 rounded-full font-medium ${branch.active ? 'bg-success/10 text-success' : 'bg-muted text-muted-foreground'}`}>
                    {branch.active ? 'Active' : 'Inactive'}
                  </span>
                  {branch.overrideGlobal && (
                    <span className="text-2xs px-2 py-0.5 rounded-full font-medium bg-warning/10 text-warning">Custom Overrides</span>
                  )}
                </div>
                <p className="text-xs text-muted-foreground mt-0.5">{branch.location} · Manager: {branch.manager}</p>
              </div>
              <div className="flex items-center gap-2 shrink-0">
                <Toggle checked={branch.active} onChange={v => update(branch.id, { active: v })} />
                <ChevronDown size={16} className={`text-muted-foreground transition-transform ${expanded === branch.id ? 'rotate-180' : ''}`} />
              </div>
            </div>

            {/* Branch Override Settings */}
            {expanded === branch.id && (
              <div className="border-t border-border p-4 bg-muted/20 space-y-4">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm font-medium text-foreground">Override Global Settings</p>
                    <p className="text-xs text-muted-foreground">Enable to apply branch-specific configuration</p>
                  </div>
                  <Toggle checked={branch.overrideGlobal} onChange={v => update(branch.id, { overrideGlobal: v })} />
                </div>

                {branch.overrideGlobal && (
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-border/50">
                    <FieldGroup label="Currency">
                      <div className="relative">
                        <select
                          value={branch.currency}
                          onChange={e => update(branch.id, { currency: e.target.value })}
                          className="w-full px-3 py-2 text-sm border border-border rounded-lg bg-background text-foreground appearance-none outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
                        >
                          {CURRENCIES.map(c => <option key={c} value={c}>{c}</option>)}
                        </select>
                        <ChevronDown size={14} className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none" />
                      </div>
                    </FieldGroup>
                    <FieldGroup label="VAT Rate (%)">
                      <TextInput value={branch.vatRate} onChange={v => update(branch.id, { vatRate: Number(v) })} type="number" prefix={<Percent size={12} />} />
                    </FieldGroup>
                    <FieldGroup label="Default Payment Method">
                      <div className="relative">
                        <select
                          value={branch.defaultPayment}
                          onChange={e => update(branch.id, { defaultPayment: e.target.value })}
                          className="w-full px-3 py-2 text-sm border border-border rounded-lg bg-background text-foreground appearance-none outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
                        >
                          {['Cash', 'M-Pesa', 'Tigo Pesa', 'Bank Transfer', 'Card'].map(p => <option key={p} value={p}>{p}</option>)}
                        </select>
                        <ChevronDown size={14} className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none" />
                      </div>
                    </FieldGroup>
                    <FieldGroup label="Low Stock Alert Qty" hint="Override global low stock threshold">
                      <TextInput value={branch.lowStockAlert} onChange={v => update(branch.id, { lowStockAlert: Number(v) })} type="number" />
                    </FieldGroup>
                  </div>
                )}
              </div>
            )}
          </div>
        ))}
      </div>
    </div>
  );
}

// ─── Main Page ─────────────────────────────────────────────────────────────────

export default function SettingsPage() {
  const [activeTab, setActiveTab] = useState('company');
  const [saved, setSaved] = useState(false);

  const [company, setCompany] = useState<CompanyInfo>(initCompany);
  const [currencyTax, setCurrencyTax] = useState<CurrencyTax>(initCurrencyTax);
  const [paymentMethods, setPaymentMethods] = useState<PaymentMethod[]>(initPaymentMethods);
  const [stockThresholds, setStockThresholds] = useState<StockThreshold[]>(initStockThresholds);
  const [notifications, setNotifications] = useState<NotificationPref[]>(initNotifications);
  const [branches, setBranches] = useState<Branch[]>(initBranches);

  const handleSave = () => {
    setSaved(true);
    setTimeout(() => setSaved(false), 2500);
  };

  return (
    <AppLayout>
      {(layout: DashboardLayout) => (
        <div className="flex flex-col h-full">
          {/* Page Header */}
          <div className="flex flex-col sm:flex-row sm:items-center justify-between px-4 sm:px-6 py-3 sm:py-4 gap-3 border-b border-border bg-card shrink-0">
            <div>
              <h1 className="text-base sm:text-lg font-bold text-foreground">System Configuration</h1>
              <p className="text-xs text-muted-foreground mt-0.5">Manage company settings, taxes, payments, stock rules, and branch overrides</p>
            </div>
            <button
              onClick={handleSave}
              className={`flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-all self-start sm:self-auto ${
                saved
                  ? 'bg-success/10 text-success border border-success/30' :'bg-primary text-primary-foreground hover:bg-primary/90'
              }`}
            >
              {saved ? <><Check size={15} /> Saved</> : <><Save size={15} /> Save Changes</>}
            </button>
          </div>

          {/* Tab Bar */}
          <div className="flex items-center gap-1 px-3 sm:px-6 py-2 border-b border-border bg-card shrink-0 overflow-x-auto">
            {TABS.map(tab => (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                className={`flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold whitespace-nowrap transition-all ${
                  activeTab === tab.id
                    ? 'bg-primary/10 text-primary' :'text-muted-foreground hover:text-foreground hover:bg-muted'
                }`}
              >
                {tab.icon}
                {tab.label}
              </button>
            ))}
          </div>

          {/* Tab Content */}
          <div className="flex-1 overflow-y-auto p-3 sm:p-6">
            {activeTab === 'company' && <CompanyTab data={company} onChange={setCompany} />}
            {activeTab === 'currency' && <CurrencyTab data={currencyTax} onChange={setCurrencyTax} />}
            {activeTab === 'payments' && <PaymentsTab methods={paymentMethods} onChange={setPaymentMethods} />}
            {activeTab === 'stock' && <StockTab thresholds={stockThresholds} onChange={setStockThresholds} />}
            {activeTab === 'notifications' && <NotificationsTab prefs={notifications} onChange={setNotifications} />}
            {activeTab === 'branches' && <BranchesTab branches={branches} onChange={setBranches} />}
          </div>
        </div>
      )}
    </AppLayout>
  );
}
