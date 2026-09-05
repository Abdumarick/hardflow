'use client';
import React, { useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import Link from 'next/link';
import AppLayout, { DashboardLayout } from '@/components/AppLayout';
import MoneyDisplay from '@/components/ui/MoneyDisplay';
import { PackageCheck, ArrowLeft, Save, AlertTriangle, CheckCircle2, Truck, FileText, ChevronRight, Info, Package,  } from 'lucide-react';

// ─── Types ────────────────────────────────────────────────────────────────────

interface ReceivingLineItem {
  id: string;
  product: string;
  sku: string;
  unit: string;
  orderedQty: number;
  previouslyReceived: number;
  outstandingQty: number;
  receivedNow: number;
  damaged: number;
  accepted: number;
  unitCost: number;
  differenceReason: string;
}

// ─── Mock PO Data ──────────────────────────────────────────────────────────────

const mockPO = {
  id: 'po001',
  poNumber: 'PO-2026-0091',
  supplier: 'Bamburi Cement Ltd',
  supplierPhone: '+255 22 211 0000',
  branch: 'Main Branch',
  createdDate: '2026-09-01',
  expectedDate: '2026-09-08',
  status: 'partially-received' as const,
  createdBy: 'James Mwangi',
  notes: 'Urgent order for ongoing project',
};

const initialLineItems: ReceivingLineItem[] = [
  { id: 'li001', product: 'Portland Cement 50kg', sku: 'CEM-50-PORT', unit: 'bag', orderedQty: 200, previouslyReceived: 120, outstandingQty: 80, receivedNow: 0, damaged: 0, accepted: 0, unitCost: 24000, differenceReason: '' },
  { id: 'li002', product: 'Masonry Cement 50kg', sku: 'CEM-50-MAS', unit: 'bag', orderedQty: 100, previouslyReceived: 60, outstandingQty: 40, receivedNow: 0, damaged: 0, accepted: 0, unitCost: 22500, differenceReason: '' },
  { id: 'li003', product: 'White Cement 25kg', sku: 'CEM-25-WHT', unit: 'bag', orderedQty: 50, previouslyReceived: 50, outstandingQty: 0, receivedNow: 0, damaged: 0, accepted: 0, unitCost: 18000, differenceReason: '' },
  { id: 'li004', product: 'Rapid Set Cement 25kg', sku: 'CEM-25-RAP', unit: 'bag', orderedQty: 30, previouslyReceived: 0, outstandingQty: 30, receivedNow: 0, damaged: 0, accepted: 0, unitCost: 26000, differenceReason: '' },
  { id: 'li005', product: 'Cement Bags (Empty)', sku: 'BAG-EMPTY', unit: 'bundle', orderedQty: 10, previouslyReceived: 0, outstandingQty: 10, receivedNow: 0, damaged: 0, accepted: 0, unitCost: 5000, differenceReason: '' },
];

const differenceReasons = [
  '',
  'Supplier short-delivered',
  'Damaged in transit',
  'Wrong product delivered',
  'Quality rejected',
  'Delivery vehicle capacity',
  'Partial delivery by agreement',
  'Other',
];

// ─── Numeric Input ─────────────────────────────────────────────────────────────

function NumInput({
  value, onChange, max, disabled, className,
}: {
  value: number; onChange: (v: number) => void; max?: number; disabled?: boolean; className?: string;
}) {
  return (
    <input
      type="number"
      min={0}
      max={max}
      value={value === 0 ? '' : value}
      placeholder="0"
      disabled={disabled}
      onChange={e => {
        const v = parseInt(e.target.value) || 0;
        onChange(max !== undefined ? Math.min(v, max) : v);
      }}
      className={`w-20 text-center text-sm px-2 py-1.5 bg-card border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground disabled:opacity-40 disabled:cursor-not-allowed ${className || 'border-border'}`}
    />
  );
}

// ─── Main Page ─────────────────────────────────────────────────────────────────

export default function GoodsReceivingPage() {
  const params = useParams();
  const router = useRouter();
  const [layout, setLayout] = useState<DashboardLayout>('classic');
  const [lineItems, setLineItems] = useState<ReceivingLineItem[]>(initialLineItems);
  const [deliveryNote, setDeliveryNote] = useState('');
  const [receivedBy, setReceivedBy] = useState('James Mwangi');
  const [receivingDate, setReceivingDate] = useState('2026-09-05');
  const [vehiclePlate, setVehiclePlate] = useState('');
  const [driverName, setDriverName] = useState('');
  const [generalNotes, setGeneralNotes] = useState('');
  const [submitted, setSubmitted] = useState(false);

  const updateLine = (id: string, field: keyof ReceivingLineItem, value: number | string) => {
    setLineItems(prev => prev.map(item => {
      if (item.id !== id) return item;
      const updated = { ...item, [field]: value };
      // Auto-calculate accepted = receivedNow - damaged
      if (field === 'receivedNow' || field === 'damaged') {
        const rn = field === 'receivedNow' ? (value as number) : updated.receivedNow;
        const dmg = field === 'damaged' ? (value as number) : updated.damaged;
        updated.accepted = Math.max(0, rn - dmg);
      }
      return updated;
    }));
  };

  const totalOrdered = lineItems.reduce((s, i) => s + i.orderedQty, 0);
  const totalPrevReceived = lineItems.reduce((s, i) => s + i.previouslyReceived, 0);
  const totalOutstanding = lineItems.reduce((s, i) => s + i.outstandingQty, 0);
  const totalReceivedNow = lineItems.reduce((s, i) => s + i.receivedNow, 0);
  const totalDamaged = lineItems.reduce((s, i) => s + i.damaged, 0);
  const totalAccepted = lineItems.reduce((s, i) => s + i.accepted, 0);
  const totalAcceptedValue = lineItems.reduce((s, i) => s + i.accepted * i.unitCost, 0);
  const totalDamagedValue = lineItems.reduce((s, i) => s + i.damaged * i.unitCost, 0);

  const hasShortDelivery = lineItems.some(i => i.outstandingQty > 0 && i.receivedNow < i.outstandingQty);
  const hasDamage = totalDamaged > 0;
  const hasMissingReasons = lineItems.some(i => {
    const diff = i.outstandingQty - i.receivedNow;
    return diff > 0 && i.receivedNow >= 0 && !i.differenceReason;
  });

  const handleSubmit = () => {
    setSubmitted(true);
  };

  if (submitted) {
    return (
      <AppLayout layout={layout} onLayoutChange={setLayout}>
        <div className="p-4 md:p-6 flex items-center justify-center min-h-[60vh]">
          <div className="text-center space-y-4 max-w-md">
            <div className="w-16 h-16 rounded-full bg-success/10 flex items-center justify-center mx-auto">
              <CheckCircle2 size={32} className="text-success" />
            </div>
            <h2 className="text-xl font-bold text-foreground">Goods Received Successfully</h2>
            <p className="text-muted-foreground text-sm">
              Receiving record for <span className="font-semibold text-foreground">{mockPO.poNumber}</span> has been saved.
              Stock levels have been updated accordingly.
            </p>
            <div className="bg-card border border-border rounded-xl p-4 text-left space-y-2">
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Units Accepted</span>
                <span className="font-semibold text-success">{totalAccepted}</span>
              </div>
              {hasDamage && (
                <div className="flex justify-between text-sm">
                  <span className="text-muted-foreground">Units Damaged</span>
                  <span className="font-semibold text-danger">{totalDamaged}</span>
                </div>
              )}
              <div className="flex justify-between text-sm border-t border-border pt-2">
                <span className="text-muted-foreground">Accepted Value</span>
                <MoneyDisplay amount={totalAcceptedValue} currency="TZS" className="font-bold text-foreground" />
              </div>
            </div>
            <div className="flex gap-2 justify-center">
              <Link
                href="/purchases"
                className="px-4 py-2 text-sm bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors"
              >
                Back to Purchases
              </Link>
              <button
                onClick={() => setSubmitted(false)}
                className="px-4 py-2 text-sm border border-border rounded-lg hover:bg-muted transition-colors text-foreground"
              >
                View Record
              </button>
            </div>
          </div>
        </div>
      </AppLayout>
    );
  }

  return (
    <AppLayout layout={layout} onLayoutChange={setLayout}>
      <div className="p-4 md:p-6 space-y-5">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <div className="flex items-center gap-2 text-sm text-muted-foreground mb-1">
              <Link href="/purchases" className="hover:text-foreground transition-colors">Purchases</Link>
              <ChevronRight size={14} />
              <span className="text-foreground font-medium">{mockPO.poNumber}</span>
              <ChevronRight size={14} />
              <span className="text-primary font-medium">Receive Goods</span>
            </div>
            <h1 className="text-xl font-bold text-foreground flex items-center gap-2">
              <PackageCheck size={22} className="text-primary" />
              Goods Receiving
            </h1>
          </div>
          <Link
            href="/purchases"
            className="flex items-center gap-1.5 px-3 py-2 text-sm border border-border rounded-lg hover:bg-muted transition-colors text-foreground"
          >
            <ArrowLeft size={15} /> Back
          </Link>
        </div>

        {/* PO Summary Card */}
        <div className="bg-card border border-border rounded-xl p-4">
          <div className="flex flex-wrap gap-6">
            <div>
              <p className="text-xs text-muted-foreground">Purchase Order</p>
              <p className="font-mono font-bold text-primary">{mockPO.poNumber}</p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground">Supplier</p>
              <p className="font-semibold text-foreground">{mockPO.supplier}</p>
              <p className="text-xs text-muted-foreground">{mockPO.supplierPhone}</p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground">Branch</p>
              <p className="font-medium text-foreground">{mockPO.branch}</p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground">Expected Date</p>
              <p className="font-medium text-foreground">{mockPO.expectedDate}</p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground">Status</p>
              <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-warning/10 text-warning border border-warning/20">
                <PackageCheck size={11} /> Partially Received
              </span>
            </div>
          </div>
        </div>

        {/* Receiving Details */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
          <div className="flex flex-col gap-1">
            <label className="text-xs font-medium text-muted-foreground">Receiving Date <span className="text-danger">*</span></label>
            <input
              type="date"
              value={receivingDate}
              onChange={e => setReceivingDate(e.target.value)}
              className="text-sm bg-card border border-border rounded-lg px-3 py-2 text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30"
            />
          </div>
          <div className="flex flex-col gap-1">
            <label className="text-xs font-medium text-muted-foreground">Received By <span className="text-danger">*</span></label>
            <input
              type="text"
              value={receivedBy}
              onChange={e => setReceivedBy(e.target.value)}
              className="text-sm bg-card border border-border rounded-lg px-3 py-2 text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30"
            />
          </div>
          <div className="flex flex-col gap-1">
            <label className="text-xs font-medium text-muted-foreground">Delivery Note / Waybill</label>
            <input
              type="text"
              value={deliveryNote}
              onChange={e => setDeliveryNote(e.target.value)}
              placeholder="e.g. DN-2026-0045"
              className="text-sm bg-card border border-border rounded-lg px-3 py-2 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30"
            />
          </div>
          <div className="flex flex-col gap-1">
            <label className="text-xs font-medium text-muted-foreground">Vehicle Plate</label>
            <input
              type="text"
              value={vehiclePlate}
              onChange={e => setVehiclePlate(e.target.value)}
              placeholder="e.g. T 123 ABC"
              className="text-sm bg-card border border-border rounded-lg px-3 py-2 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30"
            />
          </div>
        </div>

        {/* Alerts */}
        {(hasShortDelivery || hasDamage) && (
          <div className="flex flex-col gap-2">
            {hasShortDelivery && (
              <div className="flex items-start gap-2 p-3 bg-warning/5 border border-warning/20 rounded-xl text-sm text-warning">
                <AlertTriangle size={16} className="shrink-0 mt-0.5" />
                <span>Some items have short delivery. Please select a difference reason for each affected line.</span>
              </div>
            )}
            {hasDamage && (
              <div className="flex items-start gap-2 p-3 bg-danger/5 border border-danger/20 rounded-xl text-sm text-danger">
                <AlertTriangle size={16} className="shrink-0 mt-0.5" />
                <span>Damaged goods detected. These will be logged separately and will not update available stock.</span>
              </div>
            )}
          </div>
        )}

        {/* Line Items Table */}
        <div className="bg-card border border-border rounded-xl overflow-hidden">
          <div className="flex items-center justify-between px-4 py-3 border-b border-border">
            <h2 className="font-semibold text-foreground flex items-center gap-2">
              <Package size={16} className="text-primary" />
              Line Items
            </h2>
            <div className="flex items-center gap-1 text-xs text-muted-foreground">
              <Info size={12} />
              Enter quantities received for each item
            </div>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border bg-muted/30">
                  <th className="px-4 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide">Product</th>
                  <th className="px-3 py-2.5 text-center text-xs font-semibold text-muted-foreground uppercase tracking-wide">Ordered</th>
                  <th className="px-3 py-2.5 text-center text-xs font-semibold text-muted-foreground uppercase tracking-wide">Prev. Received</th>
                  <th className="px-3 py-2.5 text-center text-xs font-semibold text-muted-foreground uppercase tracking-wide">Outstanding</th>
                  <th className="px-3 py-2.5 text-center text-xs font-semibold text-primary uppercase tracking-wide bg-primary/5">Received Now</th>
                  <th className="px-3 py-2.5 text-center text-xs font-semibold text-danger uppercase tracking-wide bg-danger/5">Damaged</th>
                  <th className="px-3 py-2.5 text-center text-xs font-semibold text-success uppercase tracking-wide bg-success/5">Accepted</th>
                  <th className="px-4 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide">Difference Reason</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {lineItems.map(item => {
                  const isFullyReceived = item.outstandingQty === 0;
                  const diff = item.outstandingQty - item.receivedNow;
                  const needsReason = diff > 0 && item.receivedNow >= 0 && item.outstandingQty > 0;
                  return (
                    <tr
                      key={item.id}
                      className={`transition-colors ${isFullyReceived ? 'opacity-50 bg-muted/20' : 'hover:bg-muted/20'}`}
                    >
                      <td className="px-4 py-3">
                        <p className="font-medium text-foreground">{item.product}</p>
                        <p className="text-xs text-muted-foreground font-mono">{item.sku} · {item.unit}</p>
                        <p className="text-xs text-muted-foreground">
                          <MoneyDisplay amount={item.unitCost} currency="TZS" /> / {item.unit}
                        </p>
                      </td>
                      <td className="px-3 py-3 text-center">
                        <span className="font-semibold text-foreground">{item.orderedQty}</span>
                        <p className="text-2xs text-muted-foreground">{item.unit}</p>
                      </td>
                      <td className="px-3 py-3 text-center">
                        <span className={`font-medium ${item.previouslyReceived > 0 ? 'text-info' : 'text-muted-foreground'}`}>
                          {item.previouslyReceived}
                        </span>
                        <p className="text-2xs text-muted-foreground">{item.unit}</p>
                      </td>
                      <td className="px-3 py-3 text-center">
                        <span className={`font-semibold ${item.outstandingQty > 0 ? 'text-warning' : 'text-success'}`}>
                          {item.outstandingQty}
                        </span>
                        <p className="text-2xs text-muted-foreground">{item.unit}</p>
                      </td>
                      <td className="px-3 py-3 text-center bg-primary/3">
                        <NumInput
                          value={item.receivedNow}
                          onChange={v => updateLine(item.id, 'receivedNow', v)}
                          max={item.outstandingQty}
                          disabled={isFullyReceived}
                          className={isFullyReceived ? 'border-border' : 'border-primary/40 focus:ring-primary/30'}
                        />
                        {isFullyReceived && (
                          <p className="text-2xs text-success mt-0.5 flex items-center justify-center gap-0.5">
                            <CheckCircle2 size={10} /> Done
                          </p>
                        )}
                      </td>
                      <td className="px-3 py-3 text-center bg-danger/3">
                        <NumInput
                          value={item.damaged}
                          onChange={v => updateLine(item.id, 'damaged', Math.min(v, item.receivedNow))}
                          max={item.receivedNow}
                          disabled={isFullyReceived || item.receivedNow === 0}
                          className="border-danger/30 focus:ring-danger/20"
                        />
                        {item.damaged > 0 && (
                          <p className="text-2xs text-danger mt-0.5">
                            <MoneyDisplay amount={item.damaged * item.unitCost} currency="TZS" />
                          </p>
                        )}
                      </td>
                      <td className="px-3 py-3 text-center bg-success/3">
                        <div className="w-20 mx-auto text-center py-1.5 rounded-lg bg-success/10 border border-success/20">
                          <span className="font-bold text-success">{item.accepted}</span>
                        </div>
                        {item.accepted > 0 && (
                          <p className="text-2xs text-success mt-0.5">
                            <MoneyDisplay amount={item.accepted * item.unitCost} currency="TZS" />
                          </p>
                        )}
                      </td>
                      <td className="px-4 py-3">
                        {needsReason ? (
                          <select
                            value={item.differenceReason}
                            onChange={e => updateLine(item.id, 'differenceReason', e.target.value)}
                            className={`w-full text-xs bg-card border rounded-lg px-2 py-1.5 text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 ${
                              !item.differenceReason ? 'border-warning/50 bg-warning/5' : 'border-border'
                            }`}
                          >
                            {differenceReasons.map(r => (
                              <option key={r} value={r}>{r || '— Select reason —'}</option>
                            ))}
                          </select>
                        ) : (
                          <span className="text-xs text-muted-foreground">—</span>
                        )}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
              {/* Totals Row */}
              <tfoot>
                <tr className="border-t-2 border-border bg-muted/40">
                  <td className="px-4 py-3 font-semibold text-foreground text-sm">Totals</td>
                  <td className="px-3 py-3 text-center font-bold text-foreground">{totalOrdered}</td>
                  <td className="px-3 py-3 text-center font-bold text-info">{totalPrevReceived}</td>
                  <td className="px-3 py-3 text-center font-bold text-warning">{totalOutstanding}</td>
                  <td className="px-3 py-3 text-center font-bold text-primary bg-primary/5">{totalReceivedNow}</td>
                  <td className="px-3 py-3 text-center font-bold text-danger bg-danger/5">{totalDamaged}</td>
                  <td className="px-3 py-3 text-center font-bold text-success bg-success/5">{totalAccepted}</td>
                  <td className="px-4 py-3" />
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        {/* Summary + Notes */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
          {/* Receiving Summary */}
          <div className="bg-card border border-border rounded-xl p-4 space-y-3">
            <h3 className="font-semibold text-foreground flex items-center gap-2">
              <FileText size={15} className="text-primary" />
              Receiving Summary
            </h3>
            <div className="space-y-2 text-sm">
              <div className="flex justify-between">
                <span className="text-muted-foreground">Total Units Ordered</span>
                <span className="font-medium text-foreground">{totalOrdered}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Previously Received</span>
                <span className="font-medium text-info">{totalPrevReceived}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Outstanding Before This</span>
                <span className="font-medium text-warning">{totalOutstanding}</span>
              </div>
              <div className="border-t border-border pt-2 flex justify-between">
                <span className="text-muted-foreground">Received Now</span>
                <span className="font-semibold text-primary">{totalReceivedNow}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Damaged</span>
                <span className="font-semibold text-danger">{totalDamaged}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Accepted into Stock</span>
                <span className="font-bold text-success">{totalAccepted}</span>
              </div>
              <div className="border-t border-border pt-2 flex justify-between">
                <span className="text-muted-foreground">Accepted Value</span>
                <MoneyDisplay amount={totalAcceptedValue} currency="TZS" className="font-bold text-foreground" />
              </div>
              {totalDamaged > 0 && (
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Damaged Value</span>
                  <MoneyDisplay amount={totalDamagedValue} currency="TZS" className="font-semibold text-danger" />
                </div>
              )}
            </div>
          </div>

          {/* Notes */}
          <div className="bg-card border border-border rounded-xl p-4 space-y-3">
            <h3 className="font-semibold text-foreground flex items-center gap-2">
              <Truck size={15} className="text-primary" />
              Delivery Information
            </h3>
            <div className="flex flex-col gap-1">
              <label className="text-xs font-medium text-muted-foreground">Driver Name</label>
              <input
                type="text"
                value={driverName}
                onChange={e => setDriverName(e.target.value)}
                placeholder="e.g. Ali Hassan"
                className="text-sm bg-card border border-border rounded-lg px-3 py-2 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30"
              />
            </div>
            <div className="flex flex-col gap-1">
              <label className="text-xs font-medium text-muted-foreground">Receiving Notes</label>
              <textarea
                value={generalNotes}
                onChange={e => setGeneralNotes(e.target.value)}
                rows={4}
                placeholder="Any additional notes about this delivery..."
                className="text-sm bg-card border border-border rounded-lg px-3 py-2 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"
              />
            </div>
          </div>
        </div>

        {/* Action Bar */}
        <div className="flex flex-col sm:flex-row items-center justify-between gap-3 p-4 bg-card border border-border rounded-xl">
          <div className="text-sm text-muted-foreground">
            {totalReceivedNow === 0 ? (
              <span className="flex items-center gap-1.5 text-warning">
                <AlertTriangle size={14} /> Enter received quantities to proceed
              </span>
            ) : hasMissingReasons ? (
              <span className="flex items-center gap-1.5 text-warning">
                <AlertTriangle size={14} /> Please provide difference reasons for short deliveries
              </span>
            ) : (
              <span className="flex items-center gap-1.5 text-success">
                <CheckCircle2 size={14} /> Ready to save — {totalAccepted} units accepted
              </span>
            )}
          </div>
          <div className="flex items-center gap-2">
            <Link
              href="/purchases"
              className="px-4 py-2 text-sm border border-border rounded-lg hover:bg-muted transition-colors text-foreground"
            >
              Cancel
            </Link>
            <button
              onClick={handleSubmit}
              disabled={totalReceivedNow === 0}
              className="flex items-center gap-1.5 px-5 py-2 text-sm font-medium bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
            >
              <Save size={15} /> Save Receiving Record
            </button>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
