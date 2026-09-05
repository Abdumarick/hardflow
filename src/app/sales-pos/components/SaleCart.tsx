'use client';
import React, { useState } from 'react';
import { Trash2, Plus, Minus, ChevronDown, User, Tag, AlertCircle } from 'lucide-react';
import MoneyDisplay from '@/components/ui/MoneyDisplay';
import { Product } from './ProductGrid';

export interface CartItem {
  id: string;
  product: Product;
  qty: number;
  unit: string;
  unitPrice: number;
  discount: number;
  priceLevel: 'retail' | 'wholesale' | 'custom';
}

interface SaleCartProps {
  items: CartItem[];
  onUpdateQty: (id: string, qty: number) => void;
  onUpdateDiscount: (id: string, discount: number) => void;
  onUpdatePriceLevel: (id: string, level: 'retail' | 'wholesale' | 'custom', price?: number) => void;
  onRemove: (id: string) => void;
  onCheckout: (customer: Customer | null, paymentMethod: string, amountPaid: number, isCredit: boolean) => void;
  onHold: () => void;
}

interface Customer {
  id: string;
  name: string;
  phone: string;
  debt: number;
  creditLimit: number;
  type: 'retail' | 'wholesale';
}

const customers: Customer[] = [
{ id: 'cust-011', name: 'Juma Builders & Co', phone: '+255 712 345 678', debt: 2450000, creditLimit: 5000000, type: 'wholesale' },
{ id: 'cust-024', name: 'Fatuma Construction Ltd', phone: '+255 754 890 123', debt: 0, creditLimit: 3000000, type: 'wholesale' },
{ id: 'cust-038', name: 'Karibu Contractors', phone: '+255 784 567 890', debt: 1920000, creditLimit: 2000000, type: 'wholesale' },
{ id: 'cust-055', name: 'Ali & Sons Hardware', phone: '+255 762 111 222', debt: 890000, creditLimit: 1500000, type: 'retail' },
{ id: 'cust-067', name: 'Grace Mwangi', phone: '+255 748 333 444', debt: 0, creditLimit: 500000, type: 'retail' }];


const paymentMethods = [
{ id: 'pm-cash', label: 'Cash', icon: '💵' },
{ id: 'pm-mpesa', label: 'M-Pesa', icon: '📱' },
{ id: 'pm-bank', label: 'Bank Transfer', icon: '🏦' },
{ id: 'pm-card', label: 'Card', icon: '💳' }];


export default function SaleCart({
  items, onUpdateQty, onUpdateDiscount, onUpdatePriceLevel, onRemove, onCheckout, onHold
}: SaleCartProps) {
  const [selectedCustomer, setSelectedCustomer] = useState<Customer | null>(null);
  const [customerSearch, setCustomerSearch] = useState('');
  const [customerDropOpen, setCustomerDropOpen] = useState(false);
  const [selectedPayment, setSelectedPayment] = useState('pm-cash');
  const [globalDiscount, setGlobalDiscount] = useState(0);
  const [isCredit, setIsCredit] = useState(false);
  const [amountPaid, setAmountPaid] = useState('');
  const [showPaymentModal, setShowPaymentModal] = useState(false);

  const subtotal = items.reduce((sum, item) => sum + item.unitPrice * item.qty, 0);
  const itemDiscounts = items.reduce((sum, item) => sum + item.unitPrice * item.qty * item.discount / 100, 0);
  const globalDiscountAmt = (subtotal - itemDiscounts) * globalDiscount / 100;
  const total = subtotal - itemDiscounts - globalDiscountAmt;
  const paid = parseFloat(amountPaid) || 0;
  const change = paid - total;

  const filteredCustomers = customers.filter((c) =>
  !customerSearch || c.name.toLowerCase().includes(customerSearch.toLowerCase()) || c.phone.includes(customerSearch)
  );

  return (
    <div className="flex flex-col h-full bg-card border-l border-border">
      {/* Header */}
      <div className="flex items-center justify-between px-4 py-3 border-b border-border">
        <h2 className="text-sm font-bold text-foreground">Current Sale</h2>
        <div className="flex items-center gap-2">
          <span className="badge-muted text-2xs">{items.length} items</span>
          <button onClick={onHold} className="btn-ghost text-xs py-1 px-2">Hold</button>
        </div>
      </div>

      {/* Customer selector */}
      <div className="px-3 py-2 border-b border-border relative">
        <button
          onClick={() => setCustomerDropOpen(!customerDropOpen)}
          className="w-full flex items-center gap-2 px-3 py-2 rounded-lg border border-border bg-background hover:border-primary/50 transition-colors text-sm">

          <User size={14} className="text-muted-foreground shrink-0" />
          <span className={`flex-1 text-left truncate ${selectedCustomer ? 'text-foreground font-medium' : 'text-muted-foreground'}`}>
            {selectedCustomer ? selectedCustomer.name : 'Walk-in Customer'}
          </span>
          {selectedCustomer &&
          <span className="text-2xs text-muted-foreground">{selectedCustomer.type}</span>
          }
          <ChevronDown size={12} className="text-muted-foreground shrink-0" />
        </button>

        {customerDropOpen &&
        <div className="absolute left-3 right-3 top-full mt-1 bg-card border border-border rounded-xl shadow-lg z-20 overflow-hidden scale-in">
            <div className="p-2 border-b border-border">
              <input
              value={customerSearch}
              onChange={(e) => setCustomerSearch(e.target.value)}
              placeholder="Search customer..."
              className="input-base text-xs"
              autoFocus />

            </div>
            <div className="max-h-48 overflow-y-auto">
              <button
              onClick={() => {setSelectedCustomer(null);setCustomerDropOpen(false);setCustomerSearch('');}}
              className="w-full flex items-center gap-2 px-3 py-2 text-sm text-muted-foreground hover:bg-muted transition-colors">

                Walk-in Customer
              </button>
              {filteredCustomers.map((c) =>
            <button
              key={c.id}
              onClick={() => {setSelectedCustomer(c);setCustomerDropOpen(false);setCustomerSearch('');}}
              className="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm hover:bg-muted transition-colors">

                  <div className="text-left min-w-0">
                    <p className="font-medium text-foreground truncate">{c.name}</p>
                    <p className="text-2xs text-muted-foreground">{c.phone}</p>
                  </div>
                  {c.debt > 0 &&
              <span className="text-2xs text-danger font-semibold shrink-0">
                      TZS {(c.debt / 1_000_000).toFixed(1)}M debt
                    </span>
              }
                </button>
            )}
            </div>
          </div>
        }

        {selectedCustomer && selectedCustomer.debt > 0 &&
        <div className="flex items-center gap-1.5 mt-1.5 px-1">
            <AlertCircle size={11} className="text-warning shrink-0" />
            <p className="text-2xs text-warning">
              Outstanding debt: TZS {(selectedCustomer.debt / 1_000_000).toFixed(2)}M
            </p>
          </div>
        }
      </div>

      {/* Cart items */}
      <div className="flex-1 overflow-y-auto">
        {items.length === 0 ?
        <div className="flex flex-col items-center justify-center h-full py-12 text-center px-4">
            <div className="w-12 h-12 rounded-2xl bg-muted flex items-center justify-center mb-3">
              <Tag size={20} className="text-muted-foreground" />
            </div>
            <p className="text-sm font-semibold text-foreground">Cart is empty</p>
            <p className="text-xs text-muted-foreground mt-1">Click a product to add it to the sale</p>
          </div> :

        <div className="divide-y divide-border">
            {items.map((item) =>
          <div key={item.id} className="p-3 hover:bg-muted/30 transition-colors">
                {/* Product header — name prominent, delete on right */}
                <div className="flex items-start justify-between gap-2 mb-2.5">
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-bold text-foreground leading-tight">{item.product.name}</p>
                    <p className="text-2xs text-muted-foreground font-mono mt-0.5">{item.product.sku}</p>
                  </div>
                  <button
                onClick={() => onRemove(item.id)}
                className="text-muted-foreground hover:text-danger transition-colors p-0.5 shrink-0 mt-0.5">

                    <Trash2 size={13} />
                  </button>
                </div>

                {/* Row 1: Qty stepper + unit label */}
                <div className="flex items-center gap-2 mb-2">
                  <div className="flex items-center border border-border rounded-lg overflow-hidden">
                    <button
                  onClick={() => onUpdateQty(item.id, Math.max(1, item.qty - 1))}
                  className="px-2.5 py-1.5 hover:bg-muted transition-colors">

                      <Minus size={11} />
                    </button>
                    <input
                  type="number"
                  value={item.qty}
                  onChange={(e) => onUpdateQty(item.id, Math.max(1, parseInt(e.target.value) || 1))}
                  className="w-12 text-center text-sm font-bold bg-transparent border-x border-border py-1.5 focus:outline-none" />

                    <button
                  onClick={() => onUpdateQty(item.id, item.qty + 1)}
                  className="px-2.5 py-1.5 hover:bg-muted transition-colors">

                      <Plus size={11} />
                    </button>
                  </div>
                  <span className="text-xs font-medium text-muted-foreground bg-muted px-2 py-1 rounded-md">{item.unit}</span>
                  <div className="flex-1" />
                  <MoneyDisplay
                amount={item.unitPrice * item.qty * (1 - item.discount / 100)}
                compact
                className="text-sm font-bold text-foreground" />

                </div>

                {/* Row 2: Price level + discount */}
                <div className="flex items-center gap-2">
                  <select
                value={item.priceLevel}
                onChange={(e) => onUpdatePriceLevel(item.id, e.target.value as 'retail' | 'wholesale' | 'custom')}
                className="flex-1 text-xs border border-border rounded-md px-2 py-1.5 bg-background text-foreground focus:outline-none focus:ring-1 focus:ring-ring">

                    <option value="retail">Retail</option>
                    <option value="wholesale">Wholesale</option>
                  </select>
                  <div className="flex items-center gap-1 shrink-0">
                    <span className="text-2xs text-muted-foreground">Disc</span>
                    <input
                  type="number"
                  value={item.discount}
                  onChange={(e) => onUpdateDiscount(item.id, Math.min(100, Math.max(0, parseFloat(e.target.value) || 0)))}
                  className="w-12 text-xs text-center border border-border rounded-md py-1.5 bg-background focus:outline-none focus:ring-1 focus:ring-ring" />

                    <span className="text-2xs text-muted-foreground">%</span>
                  </div>
                </div>

                {/* Unit price line */}
                <p className="text-2xs text-muted-foreground mt-1.5">
                  TZS {item.unitPrice.toLocaleString('en-US')} × {item.qty}
                  {item.discount > 0 && <span className="text-success ml-1">−{item.discount}%</span>}
                </p>
              </div>
          )}
          </div>
        }
      </div>

      {/* Totals & Checkout */}
      <div className="border-t border-border p-3 space-y-3">
        {/* Global discount */}
        <div className="flex items-center justify-between gap-2">
          <label htmlFor="global-discount" className="text-xs text-muted-foreground font-medium">Global Discount</label>
          <div className="flex items-center gap-1">
            <input
              id="global-discount"
              type="number"
              value={globalDiscount}
              onChange={(e) => setGlobalDiscount(Math.min(100, Math.max(0, parseFloat(e.target.value) || 0)))}
              className="w-14 text-xs text-center border border-border rounded-md py-1 bg-background focus:outline-none focus:ring-1 focus:ring-ring" />

            <span className="text-xs text-muted-foreground">%</span>
          </div>
        </div>

        {/* Summary */}
        <div className="space-y-1 text-sm">
          <div className="flex justify-between">
            <span className="text-muted-foreground">Subtotal</span>
            <MoneyDisplay amount={subtotal} compact className="text-sm" />
          </div>
          {itemDiscounts + globalDiscountAmt > 0 &&
          <div className="flex justify-between text-success">
              <span>Discounts</span>
              <span className="font-semibold tabular-nums">- TZS {((itemDiscounts + globalDiscountAmt) / 1000).toFixed(0)}K</span>
            </div>
          }
          <div className="flex justify-between font-bold text-base border-t border-border pt-2 mt-2">
            <span className="text-foreground">Total</span>
            <MoneyDisplay amount={total} className="text-lg font-bold text-foreground" />
          </div>
        </div>

        {/* Credit toggle */}
        <div className="flex items-center justify-between rounded-br-sm rounded-t-sm rounded-bl-sm">
          <label className="text-xs font-medium text-foreground">Credit Sale</label>
          <button
            onClick={() => {
              setIsCredit((prev) => {
                if (!prev) setAmountPaid('');
                return !prev;
              });
            }}
            className={`relative w-10 h-5 rounded-full transition-colors duration-200 ${isCredit ? 'bg-warning' : 'bg-muted border border-border'}`}>

            <span className={`absolute top-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform duration-200 ${isCredit ? 'translate-x-5' : 'translate-x-0.5'}`} />
          </button>
        </div>

        {/* Payment method */}
        {!isCredit &&
        <div className="grid grid-cols-4 gap-1.5">
            {paymentMethods.map((pm) =>
          <button
            key={pm.id}
            onClick={() => setSelectedPayment(pm.id)}
            className={`flex flex-col items-center gap-1 py-2 rounded-lg border text-xs font-medium transition-all duration-150 ${
            selectedPayment === pm.id ?
            'border-primary bg-primary/10 text-primary' : 'border-border text-muted-foreground hover:border-primary/30'}`
            }>

                <span>{pm.icon}</span>
                <span className="text-2xs">{pm.label}</span>
              </button>
          )}
          </div>
        }

        {/* Amount paid (cash) */}
        {!isCredit && selectedPayment === 'pm-cash' &&
        <div>
            <label className="text-xs text-muted-foreground font-medium mb-1 block">Amount Received (TZS)</label>
            <input
            type="number"
            value={amountPaid}
            onChange={(e) => setAmountPaid(e.target.value)}
            placeholder={total.toString()}
            className="input-base text-sm font-semibold" />

            {paid > 0 && paid >= total &&
          <p className="text-xs text-success font-semibold mt-1">
                Change: TZS {change.toLocaleString('en-US')}
              </p>
          }
            {paid > 0 && paid < total &&
          <p className="text-xs text-danger font-semibold mt-1">
                Short by: TZS {(total - paid).toLocaleString('en-US')}
              </p>
          }
          </div>
        }

        {/* Action buttons */}
        <div className="grid grid-cols-2 gap-2">
          <button
            onClick={onHold}
            className="btn-secondary text-sm py-2.5"
            disabled={items.length === 0}>

            Hold Sale
          </button>
          <button
            onClick={() => {
              if (items.length > 0) {
                onCheckout(selectedCustomer, selectedPayment, paid, isCredit);
              }
            }}
            disabled={items.length === 0}
            className="btn-primary text-sm py-2.5 disabled:opacity-50">

            {isCredit ? 'Post Credit Sale' : 'Complete Sale'}
          </button>
        </div>
      </div>
    </div>);

}