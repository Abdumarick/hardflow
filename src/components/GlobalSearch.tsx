'use client';
import React, { useState, useEffect, useRef } from 'react';
import { Search, X, Package, Users, ShoppingCart, FileText, Truck, ArrowRight } from 'lucide-react';

interface SearchResult {
  id: string;
  type: 'product' | 'customer' | 'sale' | 'quotation' | 'supplier';
  title: string;
  subtitle: string;
  meta?: string;
}

const allResults: SearchResult[] = [
  { id: 'sr-1', type: 'product', title: 'Portland Cement 50kg', subtitle: 'SKU: CEM-50-PORT | Bamburi Brand', meta: 'Stock: 248 bags' },
  { id: 'sr-2', type: 'product', title: 'Iron Sheet 26G 8ft', subtitle: 'SKU: IRN-26-8FT | Mabati Rolling Mills', meta: 'Stock: 94 sheets' },
  { id: 'sr-3', type: 'product', title: 'Round Bar 12mm x 6m', subtitle: 'SKU: RBR-12-6M | Steel Structures Ltd', meta: 'Stock: 42 pieces' },
  { id: 'sr-4', type: 'customer', title: 'Juma Builders & Co', subtitle: '+255 712 345 678 | Kariakoo, Dar es Salaam', meta: 'Debt: TZS 2,450,000' },
  { id: 'sr-5', type: 'customer', title: 'Fatuma Construction Ltd', subtitle: '+255 754 890 123 | Mikocheni, Dar es Salaam', meta: 'Debt: TZS 0' },
  { id: 'sr-6', type: 'sale', title: 'Sale #INV-2024-1847', subtitle: 'Juma Builders — TZS 1,250,000', meta: 'Paid · 04/09/2024' },
  { id: 'sr-7', type: 'sale', title: 'Sale #INV-2024-1846', subtitle: 'Walk-in Customer — TZS 87,500', meta: 'Paid · 04/09/2024' },
  { id: 'sr-8', type: 'quotation', title: 'QUO-2024-0312', subtitle: 'Mwangi Properties — TZS 4,800,000', meta: 'Valid until 15/09/2024' },
  { id: 'sr-9', type: 'supplier', title: 'Bamburi Cement Ltd', subtitle: 'Supplier · Mombasa Road', meta: '14 active orders' },
  { id: 'sr-10', type: 'product', title: 'PVC Pipe 4" x 6m', subtitle: 'SKU: PVC-4-6M | Boresha Pipes', meta: 'Stock: 18 pieces ⚠ Low' },
];

const typeIcon = (type: SearchResult['type']) => {
  switch (type) {
    case 'product': return <Package size={16} className="text-primary" />;
    case 'customer': return <Users size={16} className="text-success" />;
    case 'sale': return <ShoppingCart size={16} className="text-accent" />;
    case 'quotation': return <FileText size={16} className="text-info" />;
    case 'supplier': return <Truck size={16} className="text-muted-foreground" />;
  }
};

const typeLabel = (type: SearchResult['type']) => {
  const map: Record<string, string> = { product: 'Product', customer: 'Customer', sale: 'Sale', quotation: 'Quotation', supplier: 'Supplier' };
  return map[type] || type;
};

interface GlobalSearchProps {
  onClose: () => void;
}

export default function GlobalSearch({ onClose }: GlobalSearchProps) {
  const [query, setQuery] = useState('');
  const [focused, setFocused] = useState(0);
  const inputRef = useRef<HTMLInputElement>(null);

  const filtered = query.length >= 1
    ? allResults.filter(r =>
        r.title.toLowerCase().includes(query.toLowerCase()) ||
        r.subtitle.toLowerCase().includes(query.toLowerCase())
      )
    : allResults.slice(0, 6);

  useEffect(() => {
    inputRef.current?.focus();
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') onClose();
      if (e.key === 'ArrowDown') setFocused(f => Math.min(f + 1, filtered.length - 1));
      if (e.key === 'ArrowUp') setFocused(f => Math.max(f - 1, 0));
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [filtered.length, onClose]);

  return (
    <div className="fixed inset-0 z-50 bg-foreground/40 flex items-start justify-center pt-16 px-4" onClick={onClose}>
      <div
        className="w-full max-w-2xl bg-card border border-border rounded-2xl shadow-2xl scale-in overflow-hidden"
        onClick={e => e.stopPropagation()}
      >
        {/* Search input */}
        <div className="flex items-center gap-3 px-4 py-3 border-b border-border">
          <Search size={18} className="text-muted-foreground shrink-0" />
          <input
            ref={inputRef}
            value={query}
            onChange={e => { setQuery(e.target.value); setFocused(0); }}
            placeholder="Search products, customers, invoices, suppliers..."
            className="flex-1 bg-transparent text-sm text-foreground placeholder:text-muted-foreground outline-none"
          />
          <button onClick={onClose} className="btn-ghost p-1">
            <X size={16} />
          </button>
        </div>

        {/* Type filter pills */}
        <div className="flex items-center gap-2 px-4 py-2 border-b border-border overflow-x-auto">
          {['All', 'Products', 'Customers', 'Sales', 'Quotations', 'Suppliers'].map(f => (
            <button
              key={`sf-${f}`}
              className="text-xs font-medium px-3 py-1 rounded-full border border-border bg-muted hover:bg-primary/10 hover:text-primary hover:border-primary/30 transition-colors whitespace-nowrap"
            >
              {f}
            </button>
          ))}
        </div>

        {/* Results */}
        <div className="max-h-96 overflow-y-auto">
          {filtered.length === 0 ? (
            <div className="px-4 py-8 text-center text-sm text-muted-foreground">
              No results for &ldquo;{query}&rdquo;
            </div>
          ) : (
            <div className="py-1">
              {filtered.map((result, i) => (
                <button
                  key={result.id}
                  className={`w-full flex items-center gap-3 px-4 py-3 text-left transition-colors ${
                    i === focused ? 'bg-primary/5' : 'hover:bg-muted'
                  }`}
                  onMouseEnter={() => setFocused(i)}
                >
                  <div className="w-8 h-8 rounded-lg bg-muted flex items-center justify-center shrink-0">
                    {typeIcon(result.type)}
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-semibold text-foreground truncate">{result.title}</p>
                    <p className="text-xs text-muted-foreground truncate">{result.subtitle}</p>
                  </div>
                  <div className="flex items-center gap-2 shrink-0">
                    {result.meta && <span className="text-xs text-muted-foreground hidden sm:block">{result.meta}</span>}
                    <span className="text-2xs bg-muted px-2 py-0.5 rounded-full text-muted-foreground">{typeLabel(result.type)}</span>
                    <ArrowRight size={12} className="text-muted-foreground" />
                  </div>
                </button>
              ))}
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="flex items-center justify-between px-4 py-2 border-t border-border bg-muted/50">
          <div className="flex items-center gap-3 text-2xs text-muted-foreground">
            <span><kbd className="font-mono bg-background border border-border px-1 rounded">↑↓</kbd> navigate</span>
            <span><kbd className="font-mono bg-background border border-border px-1 rounded">↵</kbd> select</span>
            <span><kbd className="font-mono bg-background border border-border px-1 rounded">Esc</kbd> close</span>
          </div>
          <span className="text-2xs text-muted-foreground">{filtered.length} results</span>
        </div>
      </div>
    </div>
  );
}