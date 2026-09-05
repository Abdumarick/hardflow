'use client';
import React, { useState, useRef } from 'react';
import { useParams, useRouter } from 'next/navigation';
import Link from 'next/link';
import AppLayout, { DashboardLayout } from '@/components/AppLayout';
import StatusBadge from '@/components/ui/StatusBadge';
import MoneyDisplay from '@/components/ui/MoneyDisplay';
import { ArrowLeft, Save, Printer, Download, ArrowRight, Plus, Trash2, User, Phone, MapPin, Calendar, Hash, Check, Edit2,  } from 'lucide-react';

interface LineItem {
  id: string;
  productName: string;
  sku: string;
  unit: string;
  qty: number;
  unitPrice: number;
  discount: number;
}

interface QuotationData {
  id: string;
  quotationNo: string;
  status: 'draft' | 'pending' | 'confirmed' | 'cancelled' | 'closed';
  date: string;
  validUntil: string;
  customer: { name: string; phone: string; email: string; location: string; type: string };
  notes: string;
  items: LineItem[];
  createdBy: string;
  discountGlobal: number;
}

const mockQuotations: Record<string, QuotationData> = {
  'q-001': {
    id: 'q-001', quotationNo: 'QT-2026-0048', status: 'pending',
    date: '2026-09-05', validUntil: '2026-09-19',
    customer: { name: 'Karibu Construction Ltd', phone: '+255 712 345 678', email: 'info@karibuconstruction.co.tz', location: 'Dar es Salaam', type: 'Contractor' },
    notes: 'Prices valid for 14 days. Delivery available within Dar es Salaam.',
    createdBy: 'James Mwangi',
    discountGlobal: 5,
    items: [
      { id: 'li-1', productName: 'Portland Cement 50kg', sku: 'CEM-50KG', unit: 'Bag', qty: 100, unitPrice: 18500, discount: 0 },
      { id: 'li-2', productName: 'Steel Rod 12mm x 12m', sku: 'STL-ROD-12', unit: 'Piece', qty: 50, unitPrice: 32000, discount: 5 },
      { id: 'li-3', productName: 'River Sand (1 Tonne)', sku: 'SAND-1T', unit: 'Tonne', qty: 10, unitPrice: 85000, discount: 0 },
      { id: 'li-4', productName: 'Roofing Sheets 28G x 8ft', sku: 'ROOF-28G-8', unit: 'Sheet', qty: 80, unitPrice: 14500, discount: 0 },
      { id: 'li-5', productName: 'Binding Wire 1kg', sku: 'WIRE-1KG', unit: 'Roll', qty: 20, unitPrice: 4500, discount: 0 },
      { id: 'li-6', productName: 'Nails Assorted 5kg', sku: 'NAIL-5KG', unit: 'Pack', qty: 15, unitPrice: 8500, discount: 0 },
      { id: 'li-7', productName: 'PVC Pipe 4 inch x 6m', sku: 'PVC-4IN-6M', unit: 'Piece', qty: 30, unitPrice: 12000, discount: 0 },
      { id: 'li-8', productName: 'Timber 2x4 x 12ft', sku: 'TIM-2X4-12', unit: 'Piece', qty: 40, unitPrice: 9500, discount: 0 },
    ],
  },
  'q-002': {
    id: 'q-002', quotationNo: 'QT-2026-0047', status: 'confirmed',
    date: '2026-09-04', validUntil: '2026-09-18',
    customer: { name: 'Nyumba Bora Developers', phone: '+255 742 876 543', email: 'info@nyumbabora.co.tz', location: 'Dar es Salaam', type: 'Contractor' },
    notes: 'Bulk discount applied. Payment terms: 50% upfront, 50% on delivery.',
    createdBy: 'Sarah Kimani',
    discountGlobal: 5,
    items: [
      { id: 'li-1', productName: 'Portland Cement 50kg', sku: 'CEM-50KG', unit: 'Bag', qty: 300, unitPrice: 18500, discount: 5 },
      { id: 'li-2', productName: 'Steel Rod 16mm x 12m', sku: 'STL-ROD-16', unit: 'Piece', qty: 120, unitPrice: 48000, discount: 5 },
      { id: 'li-3', productName: 'Hollow Blocks 6 inch', sku: 'BLOCK-6IN', unit: 'Piece', qty: 2000, unitPrice: 1800, discount: 0 },
    ],
  },
};

const defaultQuotation: QuotationData = {
  id: 'new', quotationNo: 'QT-2026-0049', status: 'draft',
  date: '2026-09-05', validUntil: '2026-09-19',
  customer: { name: '', phone: '', email: '', location: '', type: 'Retail' },
  notes: '',
  createdBy: 'James Mwangi',
  discountGlobal: 0,
  items: [{ id: 'li-new-1', productName: '', sku: '', unit: 'Piece', qty: 1, unitPrice: 0, discount: 0 }],
};

function calcLineTotal(item: LineItem): number {
  const gross = item.qty * item.unitPrice;
  return gross - (gross * item.discount / 100);
}

export default function QuotationDetailPage() {
  const params = useParams();
  const router = useRouter();
  const id = params?.id as string;
  const isNew = id === 'new' || id === 'new-draft';
  const printRef = useRef<HTMLDivElement>(null);

  const initial = isNew ? defaultQuotation : (mockQuotations[id] || mockQuotations['q-001']);
  const [quotation, setQuotation] = useState<QuotationData>(initial);
  const [layout] = useState<DashboardLayout>('classic');
  const [saved, setSaved] = useState(false);
  const [showConvertConfirm, setShowConvertConfirm] = useState(false);
  const [editingCustomer, setEditingCustomer] = useState(isNew);

  const subtotal = quotation.items.reduce((s, i) => s + calcLineTotal(i), 0);
  const globalDiscountAmt = subtotal * quotation.discountGlobal / 100;
  const total = subtotal - globalDiscountAmt;
  const tax = 0; // VAT handled separately in backend

  const updateItem = (id: string, field: keyof LineItem, value: string | number) => {
    setQuotation(prev => ({
      ...prev,
      items: prev.items.map(i => i.id === id ? { ...i, [field]: value } : i),
    }));
  };

  const addItem = () => {
    const newItem: LineItem = {
      id: `li-${Date.now()}`, productName: '', sku: '', unit: 'Piece', qty: 1, unitPrice: 0, discount: 0,
    };
    setQuotation(prev => ({ ...prev, items: [...prev.items, newItem] }));
  };

  const removeItem = (id: string) => {
    setQuotation(prev => ({ ...prev, items: prev.items.filter(i => i.id !== id) }));
  };

  const handleSave = () => {
    setSaved(true);
    setTimeout(() => setSaved(false), 2500);
  };

  const handlePrint = () => {
    window.print();
  };

  const handleConvertToSale = () => {
    setShowConvertConfirm(false);
    router.push('/sales-pos');
  };

  const updateCustomer = (field: keyof QuotationData['customer'], value: string) => {
    setQuotation(prev => ({ ...prev, customer: { ...prev.customer, [field]: value } }));
  };

  const canConvert = quotation.status === 'confirmed' || quotation.status === 'pending';

  return (
    <AppLayout layout={layout}>
      {/* Convert Confirm Modal */}
      {showConvertConfirm && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 fade-in">
          <div className="card p-6 w-full max-w-md mx-4 scale-in">
            <div className="flex items-start gap-3 mb-4">
              <div className="w-10 h-10 rounded-full bg-success/10 flex items-center justify-center shrink-0">
                <ArrowRight size={18} className="text-success" />
              </div>
              <div>
                <h3 className="font-bold text-foreground">Convert to Sale?</h3>
                <p className="text-sm text-muted-foreground mt-1">
                  This will create a new sale from quotation <strong>{quotation.quotationNo}</strong> and mark it as closed.
                  The cart will be pre-filled with all line items.
                </p>
              </div>
            </div>
            <div className="bg-muted/50 rounded-lg p-3 mb-4 text-sm">
              <div className="flex justify-between mb-1">
                <span className="text-muted-foreground">Customer</span>
                <span className="font-medium">{quotation.customer.name || '—'}</span>
              </div>
              <div className="flex justify-between mb-1">
                <span className="text-muted-foreground">Items</span>
                <span className="font-medium">{quotation.items.length}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Total</span>
                <MoneyDisplay amount={total} className="text-sm" />
              </div>
            </div>
            <div className="flex gap-2 justify-end">
              <button className="btn-secondary" onClick={() => setShowConvertConfirm(false)}>Cancel</button>
              <button className="btn-primary bg-success hover:bg-success/90" onClick={handleConvertToSale}>
                <Check size={14} /> Confirm & Convert
              </button>
            </div>
          </div>
        </div>
      )}

      <div className="p-6 space-y-5 max-w-5xl mx-auto">
        {/* Header */}
        <div className="flex items-center justify-between gap-4 flex-wrap print:hidden">
          <div className="flex items-center gap-3">
            <Link href="/quotations" className="btn-ghost p-2">
              <ArrowLeft size={16} />
            </Link>
            <div>
              <div className="flex items-center gap-2">
                <h1 className="text-xl font-bold text-foreground">{quotation.quotationNo}</h1>
                <StatusBadge status={quotation.status} />
              </div>
              <p className="text-sm text-muted-foreground">Created by {quotation.createdBy} · {quotation.date}</p>
            </div>
          </div>
          <div className="flex items-center gap-2 flex-wrap">
            <button className="btn-secondary" onClick={handlePrint}>
              <Printer size={15} /> Print
            </button>
            <button className="btn-secondary">
              <Download size={15} /> PDF
            </button>
            <button className="btn-secondary" onClick={handleSave}>
              {saved ? <><Check size={15} className="text-success" /> Saved</> : <><Save size={15} /> Save</>}
            </button>
            {canConvert && (
              <button className="btn-primary" style={{ background: 'var(--success)' }} onClick={() => setShowConvertConfirm(true)}>
                <ArrowRight size={15} /> Convert to Sale
              </button>
            )}
          </div>
        </div>

        {/* Printable Document */}
        <div ref={printRef} className="card overflow-hidden">
          {/* Document Header - Print Header */}
          <div className="bg-primary px-6 py-5 print:bg-primary">
            <div className="flex items-start justify-between gap-4">
              <div>
                <h2 className="text-xl font-bold text-primary-foreground">QUOTATION</h2>
                <p className="text-primary-foreground/70 text-sm mt-0.5">Karibu Hardware Ltd</p>
                <p className="text-primary-foreground/60 text-xs mt-0.5">Dar es Salaam Main Branch · Tel: +255 712 000 000</p>
              </div>
              <div className="text-right">
                <p className="text-primary-foreground font-bold text-lg">{quotation.quotationNo}</p>
                <div className="mt-1 space-y-0.5 text-xs text-primary-foreground/70">
                  <div className="flex items-center justify-end gap-1.5">
                    <Calendar size={11} />
                    <span>Date: {quotation.date}</span>
                  </div>
                  <div className="flex items-center justify-end gap-1.5">
                    <Calendar size={11} />
                    <span>Valid Until: {quotation.validUntil}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Customer Info */}
          <div className="px-6 py-5 border-b border-border">
            <div className="flex items-center justify-between mb-3">
              <h3 className="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Bill To</h3>
              <button
                className="btn-ghost p-1 text-xs gap-1 print:hidden"
                onClick={() => setEditingCustomer(!editingCustomer)}
              >
                <Edit2 size={12} /> {editingCustomer ? 'Done' : 'Edit'}
              </button>
            </div>
            {editingCustomer ? (
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label className="text-xs text-muted-foreground mb-1 block">Customer Name *</label>
                  <input className="input-base" placeholder="Customer name" value={quotation.customer.name} onChange={e => updateCustomer('name', e.target.value)} />
                </div>
                <div>
                  <label className="text-xs text-muted-foreground mb-1 block">Phone Number</label>
                  <input className="input-base" placeholder="+255 7XX XXX XXX" value={quotation.customer.phone} onChange={e => updateCustomer('phone', e.target.value)} />
                </div>
                <div>
                  <label className="text-xs text-muted-foreground mb-1 block">Email</label>
                  <input className="input-base" placeholder="email@example.com" value={quotation.customer.email} onChange={e => updateCustomer('email', e.target.value)} />
                </div>
                <div>
                  <label className="text-xs text-muted-foreground mb-1 block">Location</label>
                  <input className="input-base" placeholder="City / Region" value={quotation.customer.location} onChange={e => updateCustomer('location', e.target.value)} />
                </div>
                <div>
                  <label className="text-xs text-muted-foreground mb-1 block">Customer Type</label>
                  <select className="input-base" value={quotation.customer.type} onChange={e => updateCustomer('type', e.target.value)}>
                    <option>Retail</option>
                    <option>Wholesale</option>
                    <option>Contractor</option>
                  </select>
                </div>
              </div>
            ) : (
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-y-2 gap-x-8">
                <div className="flex items-start gap-2">
                  <User size={14} className="text-muted-foreground mt-0.5 shrink-0" />
                  <div>
                    <p className="font-semibold text-foreground">{quotation.customer.name || <span className="text-muted-foreground italic">No customer selected</span>}</p>
                    <p className="text-xs text-muted-foreground">{quotation.customer.type}</p>
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <Phone size={14} className="text-muted-foreground shrink-0" />
                  <span className="text-sm text-foreground">{quotation.customer.phone || '—'}</span>
                </div>
                <div className="flex items-center gap-2">
                  <MapPin size={14} className="text-muted-foreground shrink-0" />
                  <span className="text-sm text-foreground">{quotation.customer.location || '—'}</span>
                </div>
                <div className="flex items-center gap-2">
                  <Hash size={14} className="text-muted-foreground shrink-0" />
                  <span className="text-sm text-foreground">{quotation.customer.email || '—'}</span>
                </div>
              </div>
            )}
          </div>

          {/* Line Items */}
          <div className="px-6 py-5">
            <div className="flex items-center justify-between mb-3">
              <h3 className="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Line Items</h3>
              <button className="btn-ghost text-xs gap-1 print:hidden" onClick={addItem}>
                <Plus size={13} /> Add Item
              </button>
            </div>
            <div className="overflow-x-auto -mx-2">
              <table className="w-full min-w-[640px]">
                <thead>
                  <tr className="border-b border-border">
                    <th className="table-th pl-2 w-8">#</th>
                    <th className="table-th">Product / Description</th>
                    <th className="table-th">SKU</th>
                    <th className="table-th">Unit</th>
                    <th className="table-th text-right">Qty</th>
                    <th className="table-th text-right">Unit Price</th>
                    <th className="table-th text-right">Disc %</th>
                    <th className="table-th text-right">Total</th>
                    <th className="table-th w-8 print:hidden"></th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border/50">
                  {quotation.items.map((item, idx) => (
                    <tr key={item.id} className="group">
                      <td className="table-td pl-2 text-muted-foreground text-xs">{idx + 1}</td>
                      <td className="table-td">
                        <input
                          className="input-base py-1 text-sm min-w-[160px]"
                          value={item.productName}
                          onChange={e => updateItem(item.id, 'productName', e.target.value)}
                          placeholder="Product name"
                        />
                      </td>
                      <td className="table-td">
                        <input
                          className="input-base py-1 text-sm w-24"
                          value={item.sku}
                          onChange={e => updateItem(item.id, 'sku', e.target.value)}
                          placeholder="SKU"
                        />
                      </td>
                      <td className="table-td">
                        <select
                          className="input-base py-1 text-sm w-24"
                          value={item.unit}
                          onChange={e => updateItem(item.id, 'unit', e.target.value)}
                        >
                          {['Piece', 'Bag', 'Tonne', 'Sheet', 'Roll', 'Pack', 'Box', 'Litre', 'Metre', 'Kg'].map(u => (
                            <option key={u}>{u}</option>
                          ))}
                        </select>
                      </td>
                      <td className="table-td text-right">
                        <input
                          type="number"
                          min="1"
                          className="input-base py-1 text-sm text-right w-20"
                          value={item.qty}
                          onChange={e => updateItem(item.id, 'qty', parseFloat(e.target.value) || 0)}
                        />
                      </td>
                      <td className="table-td text-right">
                        <input
                          type="number"
                          min="0"
                          className="input-base py-1 text-sm text-right w-28"
                          value={item.unitPrice}
                          onChange={e => updateItem(item.id, 'unitPrice', parseFloat(e.target.value) || 0)}
                        />
                      </td>
                      <td className="table-td text-right">
                        <input
                          type="number"
                          min="0"
                          max="100"
                          className="input-base py-1 text-sm text-right w-16"
                          value={item.discount}
                          onChange={e => updateItem(item.id, 'discount', parseFloat(e.target.value) || 0)}
                        />
                      </td>
                      <td className="table-td text-right font-semibold tabular-nums">
                        <MoneyDisplay amount={calcLineTotal(item)} />
                      </td>
                      <td className="table-td print:hidden">
                        <button
                          className="opacity-0 group-hover:opacity-100 btn-ghost p-1 text-danger hover:text-danger transition-opacity"
                          onClick={() => removeItem(item.id)}
                          disabled={quotation.items.length === 1}
                        >
                          <Trash2 size={13} />
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {/* Totals */}
            <div className="mt-5 flex justify-end">
              <div className="w-full max-w-xs space-y-2">
                <div className="flex justify-between text-sm">
                  <span className="text-muted-foreground">Subtotal</span>
                  <MoneyDisplay amount={subtotal} />
                </div>
                <div className="flex justify-between text-sm items-center">
                  <div className="flex items-center gap-2">
                    <span className="text-muted-foreground">Global Discount</span>
                    <input
                      type="number"
                      min="0"
                      max="100"
                      className="input-base py-0.5 text-xs text-right w-14 print:hidden"
                      value={quotation.discountGlobal}
                      onChange={e => setQuotation(prev => ({ ...prev, discountGlobal: parseFloat(e.target.value) || 0 }))}
                    />
                    <span className="hidden print:inline text-xs">({quotation.discountGlobal}%)</span>
                  </div>
                  <span className="text-danger">
                    {quotation.discountGlobal > 0 && '- '}
                    <MoneyDisplay amount={globalDiscountAmt} />
                  </span>
                </div>
                <div className="flex justify-between text-sm text-muted-foreground">
                  <span>VAT (0%)</span>
                  <span>—</span>
                </div>
                <div className="flex justify-between font-bold text-base border-t border-border pt-2 mt-2">
                  <span>Total</span>
                  <MoneyDisplay amount={total} className="text-primary" />
                </div>
              </div>
            </div>
          </div>

          {/* Notes */}
          <div className="px-6 pb-6 border-t border-border pt-4">
            <label className="text-xs font-semibold text-muted-foreground uppercase tracking-wider block mb-2">Notes / Terms</label>
            <textarea
              className="input-base text-sm resize-none"
              rows={3}
              placeholder="Payment terms, delivery notes, validity conditions..."
              value={quotation.notes}
              onChange={e => setQuotation(prev => ({ ...prev, notes: e.target.value }))}
            />
          </div>

          {/* Print Footer */}
          <div className="hidden print:block px-6 pb-6 border-t border-border pt-4">
            <div className="grid grid-cols-3 gap-8 mt-4">
              <div>
                <p className="text-xs text-muted-foreground mb-6">Prepared By</p>
                <div className="border-t border-border pt-1">
                  <p className="text-xs text-muted-foreground">{quotation.createdBy}</p>
                </div>
              </div>
              <div>
                <p className="text-xs text-muted-foreground mb-6">Authorized By</p>
                <div className="border-t border-border pt-1">
                  <p className="text-xs text-muted-foreground">Signature</p>
                </div>
              </div>
              <div>
                <p className="text-xs text-muted-foreground mb-6">Customer Acceptance</p>
                <div className="border-t border-border pt-1">
                  <p className="text-xs text-muted-foreground">Signature & Date</p>
                </div>
              </div>
            </div>
            <p className="text-xs text-muted-foreground text-center mt-6">
              Thank you for your business · Karibu Hardware Ltd · {quotation.quotationNo}
            </p>
          </div>
        </div>

        {/* Bottom Actions */}
        <div className="flex items-center justify-between gap-4 flex-wrap print:hidden">
          <Link href="/quotations" className="btn-ghost">
            <ArrowLeft size={15} /> Back to Quotations
          </Link>
          <div className="flex items-center gap-2">
            <button className="btn-secondary" onClick={handlePrint}>
              <Printer size={15} /> Print
            </button>
            <button className="btn-secondary">
              <Download size={15} /> Download PDF
            </button>
            <button className="btn-secondary" onClick={handleSave}>
              {saved ? <><Check size={15} className="text-success" /> Saved!</> : <><Save size={15} /> Save Draft</>}
            </button>
            {canConvert && (
              <button
                className="btn-primary"
                style={{ background: 'var(--success)' }}
                onClick={() => setShowConvertConfirm(true)}
              >
                <ArrowRight size={15} /> Convert to Sale
              </button>
            )}
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
