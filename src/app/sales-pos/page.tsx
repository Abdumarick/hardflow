'use client';
import React, { useState } from 'react';
import AppLayout from '@/components/AppLayout';
import { DashboardLayout } from '@/components/AppLayout';
import ProductGrid, { Product, posProducts } from './components/ProductGrid';
import SaleCart, { CartItem } from './components/SaleCart';
import ReceiptModal from './components/ReceiptModal';
import HeldSalesPanel, { HeldSale } from './components/HeldSalesPanel';
import BarcodeInput from './components/BarcodeInput';
import { Clock, ChevronRight, ShoppingCart } from 'lucide-react';
import { toast } from 'sonner';

// Backend integration point: POST /api/sales for checkout, GET /api/held-sales

export default function SalesPOSPage() {
  const [layout] = useState<DashboardLayout>('classic');
  const [cartItems, setCartItems] = useState<CartItem[]>([]);
  const [heldSales, setHeldSales] = useState<HeldSale[]>([]);
  const [showReceipt, setShowReceipt] = useState(false);
  const [showHeld, setShowHeld] = useState(false);
  const [lastSale, setLastSale] = useState<{
    items: CartItem[]; total: number; customerName: string;
    paymentMethod: string; amountPaid: number; saleRef: string;
  } | null>(null);
  const [saleCounter, setSaleCounter] = useState(1848);

  const addToCart = (product: Product, qty: number, unit: string, price: number) => {
    const existing = cartItems.find(i => i.product.id === product.id && i.unit === unit);
    if (existing) {
      setCartItems(prev => prev.map(i =>
        i.id === existing.id ? { ...i, qty: i.qty + qty } : i
      ));
    } else {
      const newItem: CartItem = {
        id: `ci-${product.id}-${Date.now()}`,
        product,
        qty,
        unit,
        unitPrice: price,
        discount: 0,
        priceLevel: 'retail',
      };
      setCartItems(prev => [...prev, newItem]);
    }
    toast.success(`${product.name} added to cart`);
  };

  const handleBarcodeFound = (product: Product) => {
    addToCart(product, 1, product.unit, product.retailPrice);
  };

  const handleBarcodeNotFound = (barcode: string) => {
    toast.error(`No product found for barcode: ${barcode}`);
  };

  const updateQty = (id: string, qty: number) => {
    setCartItems(prev => prev.map(i => i.id === id ? { ...i, qty } : i));
  };

  const updateDiscount = (id: string, discount: number) => {
    setCartItems(prev => prev.map(i => i.id === id ? { ...i, discount } : i));
  };

  const updatePriceLevel = (id: string, level: 'retail' | 'wholesale' | 'custom', price?: number) => {
    setCartItems(prev => prev.map(i => {
      if (i.id !== id) return i;
      const newPrice = level === 'retail' ? i.product.retailPrice :
        level === 'wholesale' ? i.product.wholesalePrice :
        price || i.unitPrice;
      return { ...i, priceLevel: level, unitPrice: newPrice };
    }));
  };

  const removeItem = (id: string) => {
    setCartItems(prev => prev.filter(i => i.id !== id));
  };

  const holdSale = () => {
    if (cartItems.length === 0) return;
    const heldAt = `${new Date().getHours()}:${String(new Date().getMinutes()).padStart(2, '0')}`;
    const total = cartItems.reduce((s, i) => s + i.unitPrice * i.qty * (1 - i.discount / 100), 0);
    const newHeld: HeldSale = {
      id: `held-${Date.now()}`,
      items: [...cartItems],
      customerName: 'Walk-in Customer',
      total,
      heldAt,
    };
    setHeldSales(prev => [...prev, newHeld]);
    setCartItems([]);
    toast.success('Sale held successfully');
  };

  const resumeHeld = (id: string) => {
    const held = heldSales.find(h => h.id === id);
    if (held) {
      setCartItems(held.items);
      setHeldSales(prev => prev.filter(h => h.id !== id));
      setShowHeld(false);
    }
  };

  const deleteHeld = (id: string) => {
    setHeldSales(prev => prev.filter(h => h.id !== id));
    toast.success('Held sale deleted');
  };

  const checkout = (customer: { name: string } | null, paymentMethod: string, amountPaid: number, isCredit: boolean) => {
    const total = cartItems.reduce((s, i) => s + i.unitPrice * i.qty * (1 - i.discount / 100), 0);
    const ref = `INV-2024-${saleCounter}`;
    setSaleCounter(c => c + 1);
    setLastSale({
      items: [...cartItems],
      total,
      customerName: customer?.name || 'Walk-in Customer',
      paymentMethod: isCredit ? 'Credit' : paymentMethod,
      amountPaid: isCredit ? 0 : amountPaid,
      saleRef: ref,
    });
    setShowReceipt(true);
    // Backend integration point: POST /api/sales with cartItems, customer, paymentMethod, total
    toast.success(`Sale ${ref} completed successfully`);
  };

  const startNewSale = () => {
    setCartItems([]);
    setShowReceipt(false);
    setLastSale(null);
  };

  return (
    <AppLayout layout={layout}>
      <div className="flex flex-col h-[calc(100vh-var(--topbar-height)-48px)] min-h-[600px]">
        {/* POS header bar */}
        <div className="flex items-center justify-between mb-3 flex-wrap gap-2">
          <div className="flex items-center gap-2 text-sm text-muted-foreground">
            <span className="font-semibold text-foreground">Sales / POS</span>
            <ChevronRight size={14} />
            <span>New Sale</span>
          </div>
          <div className="flex items-center gap-2 flex-wrap">
            {heldSales.length > 0 && (
              <button
                onClick={() => setShowHeld(true)}
                className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-warning/30 bg-warning/10 text-warning text-xs font-semibold hover:bg-warning/20 transition-colors"
              >
                <Clock size={13} />
                {heldSales.length} Held
              </button>
            )}
            <div className="flex items-center gap-1.5 text-xs text-muted-foreground bg-muted px-3 py-1.5 rounded-lg border border-border">
              <ShoppingCart size={13} />
              <span>Session: 08:00 — open</span>
            </div>
          </div>
        </div>

        {/* Barcode scanner input — spans full width above the POS grid */}
        <div className="mb-3 rounded-xl border border-border overflow-hidden bg-card">
          <BarcodeInput
            products={posProducts}
            onProductFound={handleBarcodeFound}
            onNotFound={handleBarcodeNotFound}
          />
        </div>

        {/* Main POS layout — stacked on mobile, side-by-side on desktop */}
        <div className="flex-1 flex flex-col lg:grid lg:grid-cols-5 xl:grid-cols-5 2xl:grid-cols-5 gap-0 border border-border rounded-xl overflow-hidden min-h-0">
          {/* Product grid */}
          <div className="lg:col-span-3 border-b lg:border-b-0 lg:border-r border-border overflow-hidden flex flex-col min-h-0 h-[50%] lg:h-auto">
            <ProductGrid onAddToCart={addToCart} />
          </div>

          {/* Cart */}
          <div className="lg:col-span-2 overflow-hidden flex flex-col min-h-0 h-[50%] lg:h-auto">
            <SaleCart
              items={cartItems}
              onUpdateQty={updateQty}
              onUpdateDiscount={updateDiscount}
              onUpdatePriceLevel={updatePriceLevel}
              onRemove={removeItem}
              onCheckout={checkout}
              onHold={holdSale}
            />
          </div>
        </div>
      </div>

      {showReceipt && lastSale && (
        <ReceiptModal
          items={lastSale.items}
          total={lastSale.total}
          customerName={lastSale.customerName}
          paymentMethod={lastSale.paymentMethod}
          amountPaid={lastSale.amountPaid}
          saleRef={lastSale.saleRef}
          onClose={() => setShowReceipt(false)}
          onNewSale={startNewSale}
        />
      )}

      {showHeld && (
        <HeldSalesPanel
          heldSales={heldSales}
          onResume={resumeHeld}
          onDelete={deleteHeld}
          onClose={() => setShowHeld(false)}
        />
      )}
    </AppLayout>
  );
}