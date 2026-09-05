'use client';
import React, { useState } from 'react';
import { X, Package, Tag, Layers, ArrowUpDown, TrendingDown, TrendingUp, Edit2, Printer, AlertTriangle,  } from 'lucide-react';
import { ProductRow } from './ProductTable';
import StatusBadge from '@/components/ui/StatusBadge';
import MoneyDisplay from '@/components/ui/MoneyDisplay';

// Backend integration point: GET /api/products/:id for full details including movement history

const mockMovements = [
  { id: 'mv-001', date: '05/09/2026', type: 'Sale', ref: 'INV-2024-1847', qtyOut: 10, qtyIn: 0, balance: 248, user: 'Ali Hassan' },
  { id: 'mv-002', date: '04/09/2026', type: 'Purchase Receipt', ref: 'PO-2024-089', qtyOut: 0, qtyIn: 100, balance: 258, user: 'Amina Juma' },
  { id: 'mv-003', date: '04/09/2026', type: 'Sale', ref: 'INV-2024-1839', qtyOut: 20, qtyIn: 0, balance: 158, user: 'Grace Mwangi' },
  { id: 'mv-004', date: '03/09/2026', type: 'Stock Adjustment', ref: 'ADJ-042', qtyOut: 5, qtyIn: 0, balance: 178, user: 'Amina Juma' },
  { id: 'mv-005', date: '02/09/2026', type: 'Sale', ref: 'INV-2024-1821', qtyOut: 15, qtyIn: 0, balance: 183, user: 'Ali Hassan' },
  { id: 'mv-006', date: '01/09/2026', type: 'Opening Stock', ref: 'OPEN-001', qtyOut: 0, qtyIn: 198, balance: 198, user: 'System' },
];

const priceHistory = [
  { id: 'ph-1', date: '01/09/2026', retail: 28500, wholesale: 27000, cost: 24000, changedBy: 'Manager Baraka' },
  { id: 'ph-2', date: '01/08/2026', retail: 27500, wholesale: 26000, cost: 23500, changedBy: 'Business Owner' },
  { id: 'ph-3', date: '01/07/2026', retail: 26000, wholesale: 24500, cost: 22000, changedBy: 'Business Owner' },
];

const unitConversions = [
  { id: 'uc-1', unit: 'Bag (50kg)', qty: 1, price: 28500 },
  { id: 'uc-2', unit: 'Tonne', qty: 20, price: 540000 },
  { id: 'uc-3', unit: 'Pallet (40 bags)', qty: 40, price: 1080000 },
];

interface ProductDetailDrawerProps {
  product: ProductRow | null;
  onClose: () => void;
  onEdit: (id: string) => void;
}

export default function ProductDetailDrawer({ product, onClose, onEdit }: ProductDetailDrawerProps) {
  const [activeTab, setActiveTab] = useState<'overview' | 'movements' | 'pricing' | 'units'>('overview');

  if (!product) return null;

  const stockStatusLabel = product.stock === 0 ? 'out-of-stock' as const :
    product.stock <= product.minStock ? 'low-stock' as const : 'in-stock' as const;

  return (
    <div
      className="fixed inset-0 z-50 flex"
      onClick={onClose}
    >
      {/* Backdrop */}
      <div className="flex-1 bg-foreground/30" />

      {/* Drawer */}
      <div
        className="w-full max-w-lg bg-card border-l border-border flex flex-col h-full shadow-2xl fade-in overflow-hidden"
        onClick={e => e.stopPropagation()}
      >
        {/* Header */}
        <div className="flex items-start justify-between px-5 py-4 border-b border-border">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
              <Package size={18} className="text-primary" />
            </div>
            <div>
              <h2 className="text-base font-bold text-foreground leading-tight">{product.name}</h2>
              <p className="text-xs font-mono text-muted-foreground">{product.sku}</p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <button onClick={() => onEdit(product.id)} className="btn-secondary text-xs py-1.5 gap-1">
              <Edit2 size={12} /> Edit
            </button>
            <button className="btn-secondary text-xs py-1.5 gap-1">
              <Printer size={12} /> Print
            </button>
            <button onClick={onClose} className="btn-ghost p-1.5">
              <X size={16} />
            </button>
          </div>
        </div>

        {/* Status row */}
        <div className="flex items-center gap-3 px-5 py-3 bg-muted/30 border-b border-border flex-wrap">
          <StatusBadge status={product.status} />
          <StatusBadge status={stockStatusLabel} />
          {product.stock <= product.minStock && product.stock > 0 && (
            <div className="flex items-center gap-1 text-2xs text-warning font-semibold">
              <AlertTriangle size={11} />
              Below minimum stock ({product.minStock} {product.unit})
            </div>
          )}
        </div>

        {/* Tabs */}
        <div className="flex border-b border-border px-5 gap-1">
          {[
            { id: 'overview' as const, label: 'Overview', icon: <Package size={13} /> },
            { id: 'movements' as const, label: 'Movements', icon: <ArrowUpDown size={13} /> },
            { id: 'pricing' as const, label: 'Price History', icon: <TrendingUp size={13} /> },
            { id: 'units' as const, label: 'Units', icon: <Layers size={13} /> },
          ].map(tab => (
            <button
              key={`dt-${tab.id}`}
              onClick={() => setActiveTab(tab.id)}
              className={`flex items-center gap-1.5 px-3 py-3 text-xs font-semibold border-b-2 transition-colors ${
                activeTab === tab.id
                  ? 'border-primary text-primary' :'border-transparent text-muted-foreground hover:text-foreground'
              }`}
            >
              {tab.icon}
              {tab.label}
            </button>
          ))}
        </div>

        {/* Tab content */}
        <div className="flex-1 overflow-y-auto p-5">
          {activeTab === 'overview' && (
            <div className="space-y-5">
              {/* Key metrics */}
              <div className="grid grid-cols-2 gap-3">
                <div className="card p-3">
                  <p className="text-2xs text-muted-foreground uppercase tracking-wide font-semibold mb-1">Current Stock</p>
                  <p className={`text-2xl font-bold tabular-nums ${product.stock <= product.minStock ? 'text-warning' : 'text-foreground'}`}>
                    {product.stock.toLocaleString()}
                  </p>
                  <p className="text-xs text-muted-foreground">{product.unit}</p>
                </div>
                <div className="card p-3">
                  <p className="text-2xs text-muted-foreground uppercase tracking-wide font-semibold mb-1">Stock Value</p>
                  <MoneyDisplay amount={product.stock * product.costPrice} compact className="text-xl font-bold" />
                  <p className="text-xs text-muted-foreground">at cost price</p>
                </div>
                <div className="card p-3">
                  <p className="text-2xs text-muted-foreground uppercase tracking-wide font-semibold mb-1">Retail Price</p>
                  <MoneyDisplay amount={product.retailPrice} className="text-xl font-bold text-primary" />
                </div>
                <div className="card p-3">
                  <p className="text-2xs text-muted-foreground uppercase tracking-wide font-semibold mb-1">Wholesale Price</p>
                  <MoneyDisplay amount={product.wholesalePrice} className="text-xl font-bold text-foreground" />
                </div>
              </div>

              {/* Product details */}
              <div className="space-y-2">
                <h3 className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Product Details</h3>
                <div className="card divide-y divide-border">
                  {[
                    { label: 'Category', value: product.category },
                    { label: 'Brand', value: product.brand },
                    { label: 'Base Unit', value: product.unit },
                    { label: 'Barcode', value: product.barcode, mono: true },
                    { label: 'SKU', value: product.sku, mono: true },
                    { label: 'Min Stock Level', value: `${product.minStock} ${product.unit}` },
                    { label: 'Cost Price', value: `TZS ${product.costPrice.toLocaleString()}` },
                    { label: 'Gross Margin', value: `${(((product.retailPrice - product.costPrice) / product.retailPrice) * 100).toFixed(1)}%` },
                  ].map(row => (
                    <div key={`dr-${row.label}`} className="flex items-center justify-between px-3 py-2.5">
                      <span className="text-xs text-muted-foreground">{row.label}</span>
                      <span className={`text-sm font-semibold text-foreground ${row.mono ? 'font-mono' : ''}`}>{row.value}</span>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}

          {activeTab === 'movements' && (
            <div className="space-y-3">
              <h3 className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Recent Stock Movements</h3>
              <div className="card overflow-hidden">
                <div className="overflow-x-auto">
                  <table className="w-full text-xs">
                    <thead className="bg-muted/50 border-b border-border">
                      <tr>
                        <th className="table-th text-2xs">Date</th>
                        <th className="table-th text-2xs">Type</th>
                        <th className="table-th text-2xs">Ref</th>
                        <th className="table-th text-2xs text-right">In</th>
                        <th className="table-th text-2xs text-right">Out</th>
                        <th className="table-th text-2xs text-right">Balance</th>
                        <th className="table-th text-2xs">User</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-border">
                      {mockMovements.map(mv => (
                        <tr key={mv.id} className="table-row-hover">
                          <td className="table-td text-xs text-muted-foreground">{mv.date}</td>
                          <td className="table-td">
                            <span className={`text-2xs font-semibold ${
                              mv.type === 'Sale' ? 'text-danger' : mv.type.includes('Purchase') ? 'text-success' :
                              'text-muted-foreground'
                            }`}>
                              {mv.type}
                            </span>
                          </td>
                          <td className="table-td font-mono text-2xs text-primary">{mv.ref}</td>
                          <td className="table-td text-right">
                            {mv.qtyIn > 0 ? (
                              <span className="text-success font-semibold">+{mv.qtyIn}</span>
                            ) : <span className="text-muted-foreground">—</span>}
                          </td>
                          <td className="table-td text-right">
                            {mv.qtyOut > 0 ? (
                              <span className="text-danger font-semibold">-{mv.qtyOut}</span>
                            ) : <span className="text-muted-foreground">—</span>}
                          </td>
                          <td className="table-td text-right font-bold tabular-nums">{mv.balance}</td>
                          <td className="table-td text-muted-foreground text-2xs">{mv.user}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          )}

          {activeTab === 'pricing' && (
            <div className="space-y-3">
              <h3 className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Price History</h3>
              <div className="card overflow-hidden">
                <div className="overflow-x-auto">
                  <table className="w-full text-xs">
                    <thead className="bg-muted/50 border-b border-border">
                      <tr>
                        <th className="table-th text-2xs">Effective Date</th>
                        <th className="table-th text-2xs text-right">Retail</th>
                        <th className="table-th text-2xs text-right">Wholesale</th>
                        <th className="table-th text-2xs text-right">Cost</th>
                        <th className="table-th text-2xs">Changed By</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-border">
                      {priceHistory.map((ph, idx) => (
                        <tr key={ph.id} className={`table-row-hover ${idx === 0 ? 'bg-success/5' : ''}`}>
                          <td className="table-td text-muted-foreground">
                            {ph.date}
                            {idx === 0 && <span className="ml-1 text-2xs text-success font-semibold">(current)</span>}
                          </td>
                          <td className="table-td text-right font-semibold tabular-nums">
                            TZS {ph.retail.toLocaleString()}
                            {idx > 0 && (
                              <span className={`ml-1 text-2xs ${ph.retail < priceHistory[idx - 1].retail ? 'text-danger' : 'text-success'}`}>
                                {ph.retail < priceHistory[idx - 1].retail ? <TrendingDown size={9} className="inline" /> : <TrendingUp size={9} className="inline" />}
                              </span>
                            )}
                          </td>
                          <td className="table-td text-right tabular-nums">TZS {ph.wholesale.toLocaleString()}</td>
                          <td className="table-td text-right tabular-nums text-muted-foreground">TZS {ph.cost.toLocaleString()}</td>
                          <td className="table-td text-muted-foreground text-2xs">{ph.changedBy}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          )}

          {activeTab === 'units' && (
            <div className="space-y-3">
              <h3 className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Unit Conversions</h3>
              <div className="card divide-y divide-border">
                {unitConversions.map(uc => (
                  <div key={uc.id} className="flex items-center justify-between px-4 py-3">
                    <div className="flex items-center gap-3">
                      <div className="w-8 h-8 rounded-lg bg-muted flex items-center justify-center">
                        <Layers size={14} className="text-muted-foreground" />
                      </div>
                      <div>
                        <p className="text-sm font-semibold text-foreground">{uc.unit}</p>
                        <p className="text-2xs text-muted-foreground">{uc.qty} × base unit ({product.unit})</p>
                      </div>
                    </div>
                    <MoneyDisplay amount={uc.price} className="text-sm font-bold text-primary" />
                  </div>
                ))}
              </div>
              <div className="card p-4 bg-muted/50">
                <div className="flex items-center gap-2 mb-2">
                  <Tag size={13} className="text-muted-foreground" />
                  <span className="text-xs font-semibold text-muted-foreground">Barcode</span>
                </div>
                <p className="font-mono text-lg font-bold text-foreground tracking-widest">{product.barcode}</p>
                <p className="text-2xs text-muted-foreground mt-1">Scan to add to sale or locate in inventory</p>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}