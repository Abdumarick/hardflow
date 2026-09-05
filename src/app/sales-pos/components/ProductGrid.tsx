'use client';
import React, { useState } from 'react';
import { Search, Grid, List, Package } from 'lucide-react';
import MoneyDisplay from '@/components/ui/MoneyDisplay';
import StatusBadge from '@/components/ui/StatusBadge';

// Backend integration point: replace with /api/products?branch=1&pos=true&search=...

export interface Product {
  id: string;
  name: string;
  sku: string;
  category: string;
  brand: string;
  retailPrice: number;
  wholesalePrice: number;
  stock: number;
  unit: string;
  altUnits?: { unit: string; qty: number; price: number }[];
}

const categories = ['All', 'Cement', 'Iron Sheets', 'Steel', 'Pipes', 'Paint', 'Timber', 'Electrical', 'Plumbing', 'Tools'];

export const posProducts: Product[] = [
  { id: 'prod-001', name: 'Portland Cement 50kg', sku: 'CEM-50-PORT', category: 'Cement', brand: 'Bamburi', retailPrice: 28500, wholesalePrice: 27000, stock: 248, unit: 'bag', altUnits: [{ unit: 'tonne', qty: 20, price: 540000 }] },
  { id: 'prod-002', name: 'Iron Sheet 26G 8ft', sku: 'IRN-26-8FT', category: 'Iron Sheets', brand: 'Mabati Rolling Mills', retailPrice: 42000, wholesalePrice: 39500, stock: 94, unit: 'sheet' },
  { id: 'prod-003', name: 'Round Bar 12mm x 6m', sku: 'RBR-12-6M', category: 'Steel', brand: 'Steel Structures', retailPrice: 38000, wholesalePrice: 35500, stock: 42, unit: 'piece' },
  { id: 'prod-004', name: 'PVC Pipe 4" x 6m', sku: 'PVC-4-6M', category: 'Pipes', brand: 'Boresha Pipes', retailPrice: 22500, wholesalePrice: 20000, stock: 18, unit: 'piece' },
  { id: 'prod-005', name: 'Binding Wire 2kg', sku: 'BWR-2KG', category: 'Steel', brand: 'Kenya Wire', retailPrice: 8500, wholesalePrice: 7800, stock: 12, unit: 'roll' },
  { id: 'prod-006', name: 'Roofing Nail 4" 5kg', sku: 'NAL-4-5KG', category: 'Tools', brand: 'General', retailPrice: 12000, wholesalePrice: 11000, stock: 67, unit: 'packet' },
  { id: 'prod-007', name: 'Paint Primer 20L', sku: 'PNT-PRM-20', category: 'Paint', brand: 'Crown Paints', retailPrice: 85000, wholesalePrice: 79000, stock: 4, unit: 'tin' },
  { id: 'prod-008', name: 'Timber 2x4 12ft', sku: 'TMB-2X4-12', category: 'Timber', brand: 'Local', retailPrice: 14500, wholesalePrice: 13000, stock: 130, unit: 'piece' },
  { id: 'prod-009', name: 'Electrical Conduit 20mm', sku: 'ELC-20MM', category: 'Electrical', brand: 'Clipsal', retailPrice: 4500, wholesalePrice: 4000, stock: 88, unit: 'piece' },
  { id: 'prod-010', name: 'Ball Valve 1/2"', sku: 'BLV-HALF', category: 'Plumbing', brand: 'Flomatic', retailPrice: 6500, wholesalePrice: 5800, stock: 55, unit: 'piece' },
  { id: 'prod-011', name: 'Iron Sheet 28G 10ft', sku: 'IRN-28-10FT', category: 'Iron Sheets', brand: 'Mabati Rolling Mills', retailPrice: 48000, wholesalePrice: 45000, stock: 72, unit: 'sheet' },
  { id: 'prod-012', name: 'CCA Treated Post 4x4 6ft', sku: 'TMB-4X4-6', category: 'Timber', brand: 'Local', retailPrice: 18000, wholesalePrice: 16500, stock: 45, unit: 'piece' },
  { id: 'prod-013', name: 'Paint Emulsion 20L White', sku: 'PNT-EMU-20W', category: 'Paint', brand: 'Crown Paints', retailPrice: 92000, wholesalePrice: 86000, stock: 22, unit: 'tin' },
  { id: 'prod-014', name: 'Square Bar 10mm x 6m', sku: 'SBR-10-6M', category: 'Steel', brand: 'Steel Structures', retailPrice: 32000, wholesalePrice: 29500, stock: 38, unit: 'piece' },
  { id: 'prod-015', name: 'PPRC Pipe 25mm x 4m', sku: 'PPR-25-4M', category: 'Plumbing', brand: 'Wavin', retailPrice: 15500, wholesalePrice: 14000, stock: 34, unit: 'piece' },
  { id: 'prod-016', name: 'Circuit Breaker 32A', sku: 'CB-32A', category: 'Electrical', brand: 'Schneider', retailPrice: 28000, wholesalePrice: 25000, stock: 19, unit: 'piece' },
];

const products = posProducts;

interface ProductGridProps {
  onAddToCart: (product: Product, qty: number, unit: string, price: number) => void;
}

export default function ProductGrid({ onAddToCart }: ProductGridProps) {
  const [search, setSearch] = useState('');
  const [activeCategory, setActiveCategory] = useState('All');
  const [viewMode, setViewMode] = useState<'grid' | 'list'>('grid');

  const filtered = products.filter(p => {
    const matchCat = activeCategory === 'All' || p.category === activeCategory;
    const matchSearch = !search || p.name.toLowerCase().includes(search.toLowerCase()) || p.sku.toLowerCase().includes(search.toLowerCase());
    return matchCat && matchSearch;
  });

  const stockStatus = (stock: number) => {
    if (stock === 0) return 'out-of-stock' as const;
    if (stock <= 10) return 'low-stock' as const;
    return 'in-stock' as const;
  };

  return (
    <div className="flex flex-col h-full">
      {/* Search */}
      <div className="p-3 border-b border-border space-y-2">
        <div className="flex items-center gap-2">
          <div className="relative flex-1">
            <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
            <input
              value={search}
              onChange={e => setSearch(e.target.value)}
              placeholder="Search by name, SKU or scan barcode..."
              className="input-base pl-9 text-sm"
            />
          </div>
          <div className="flex border border-border rounded-lg overflow-hidden">
            <button
              onClick={() => setViewMode('grid')}
              className={`p-2 transition-colors ${viewMode === 'grid' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted'}`}
            >
              <Grid size={14} />
            </button>
            <button
              onClick={() => setViewMode('list')}
              className={`p-2 transition-colors ${viewMode === 'list' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted'}`}
            >
              <List size={14} />
            </button>
          </div>
        </div>

        {/* Category tabs */}
        <div className="flex gap-1.5 overflow-x-auto pb-1">
          {categories.map(cat => (
            <button
              key={`cat-${cat}`}
              onClick={() => setActiveCategory(cat)}
              className={`text-xs font-medium px-3 py-1.5 rounded-full whitespace-nowrap transition-all duration-150 ${
                activeCategory === cat
                  ? 'bg-primary text-primary-foreground'
                  : 'bg-muted text-muted-foreground hover:text-foreground border border-border'
              }`}
            >
              {cat}
            </button>
          ))}
        </div>
      </div>

      {/* Product grid/list */}
      <div className="flex-1 overflow-y-auto p-3">
        {filtered.length === 0 ? (
          <div className="flex flex-col items-center justify-center py-12 text-center">
            <Package size={32} className="text-muted-foreground mb-2" />
            <p className="text-sm font-semibold text-foreground">No products found</p>
            <p className="text-xs text-muted-foreground mt-1">Try a different search term or category</p>
          </div>
        ) : viewMode === 'grid' ? (
          <div className="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-3 2xl:grid-cols-4 gap-2">
            {filtered.map(product => (
              <button
                key={product.id}
                onClick={() => onAddToCart(product, 1, product.unit, product.retailPrice)}
                disabled={product.stock === 0}
                className={`text-left p-3 rounded-xl border transition-all duration-150 active:scale-95 ${
                  product.stock === 0
                    ? 'border-border bg-muted/50 opacity-60 cursor-not-allowed' :'border-border bg-card hover:border-primary/40 hover:bg-primary/5 hover:shadow-sm'
                }`}
              >
                <div className="w-full aspect-square rounded-lg bg-muted flex items-center justify-center mb-2 overflow-hidden">
                  <Package size={24} className="text-muted-foreground" />
                </div>
                <p className="text-xs font-semibold text-foreground leading-tight line-clamp-2 mb-1">{product.name}</p>
                <p className="text-2xs text-muted-foreground font-mono mb-1.5">{product.sku}</p>
                <div className="flex items-center justify-between gap-1">
                  <MoneyDisplay amount={product.retailPrice} compact className="text-xs text-primary" />
                  <StatusBadge status={stockStatus(product.stock)} customLabel={product.stock === 0 ? 'Out' : product.stock <= 10 ? `${product.stock} left` : `${product.stock}`} />
                </div>
              </button>
            ))}
          </div>
        ) : (
          <div className="space-y-1">
            {filtered.map(product => (
              <button
                key={product.id}
                onClick={() => onAddToCart(product, 1, product.unit, product.retailPrice)}
                disabled={product.stock === 0}
                className={`w-full flex items-center gap-3 p-3 rounded-lg border text-left transition-all duration-150 active:scale-[0.99] ${
                  product.stock === 0
                    ? 'border-border bg-muted/50 opacity-60 cursor-not-allowed' :'border-border bg-card hover:border-primary/40 hover:bg-primary/5'
                }`}
              >
                <div className="w-9 h-9 rounded-lg bg-muted flex items-center justify-center shrink-0">
                  <Package size={16} className="text-muted-foreground" />
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-semibold text-foreground truncate">{product.name}</p>
                  <p className="text-2xs text-muted-foreground font-mono">{product.sku} · {product.brand}</p>
                </div>
                <div className="text-right shrink-0">
                  <MoneyDisplay amount={product.retailPrice} compact className="text-sm text-primary" />
                  <StatusBadge status={stockStatus(product.stock)} customLabel={`${product.stock} ${product.unit}`} />
                </div>
              </button>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}