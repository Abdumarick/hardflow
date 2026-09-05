'use client';
import React, { useState } from 'react';
import { Search, Filter, X, ChevronDown, SlidersHorizontal } from 'lucide-react';

export interface ProductFilters {
  search: string;
  category: string;
  brand: string;
  stockStatus: string;
  priceLevel: string;
  branch: string;
}

interface ProductFilterBarProps {
  filters: ProductFilters;
  onFiltersChange: (filters: ProductFilters) => void;
  totalCount: number;
  filteredCount: number;
}

const categories = ['All Categories', 'Cement', 'Iron Sheets', 'Steel', 'Pipes', 'Paint', 'Timber', 'Electrical', 'Plumbing', 'Tools', 'Fasteners', 'Roofing'];
const brands = ['All Brands', 'Bamburi', 'Mabati Rolling Mills', 'Steel Structures', 'Boresha Pipes', 'Crown Paints', 'Clipsal', 'Schneider', 'Wavin', 'Kenya Wire'];
const stockStatuses = ['All Status', 'In Stock', 'Low Stock', 'Out of Stock'];
const priceLevels = ['All Price Levels', 'Retail', 'Wholesale'];
const branches = ['All Branches', 'Dar es Salaam Main', 'Kariakoo Branch', 'Mikocheni Branch'];

export default function ProductFilterBar({ filters, onFiltersChange, totalCount, filteredCount }: ProductFilterBarProps) {
  const [expanded, setExpanded] = useState(false);

  const update = (key: keyof ProductFilters, value: string) => {
    onFiltersChange({ ...filters, [key]: value });
  };

  const clearAll = () => {
    onFiltersChange({ search: '', category: '', brand: '', stockStatus: '', priceLevel: '', branch: '' });
  };

  const activeFiltersCount = [filters.category, filters.brand, filters.stockStatus, filters.priceLevel, filters.branch]
    .filter(v => v && v !== '').length;

  return (
    <div className="card p-3 space-y-3">
      {/* Search + toggle */}
      <div className="flex items-center gap-2">
        <div className="relative flex-1">
          <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
          <input
            value={filters.search}
            onChange={e => update('search', e.target.value)}
            placeholder="Search by name, SKU, barcode..."
            className="input-base pl-9 text-sm"
          />
          {filters.search && (
            <button
              onClick={() => update('search', '')}
              className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
            >
              <X size={12} />
            </button>
          )}
        </div>
        <button
          onClick={() => setExpanded(!expanded)}
          className={`flex items-center gap-1.5 px-3 py-2 rounded-lg border text-sm font-medium transition-colors ${
            expanded || activeFiltersCount > 0
              ? 'border-primary/30 bg-primary/10 text-primary' :'border-border text-muted-foreground hover:text-foreground hover:bg-muted'
          }`}
        >
          <SlidersHorizontal size={14} />
          Filters
          {activeFiltersCount > 0 && (
            <span className="w-4 h-4 rounded-full bg-primary text-primary-foreground text-2xs flex items-center justify-center font-bold">
              {activeFiltersCount}
            </span>
          )}
          <ChevronDown size={12} className={`transition-transform duration-200 ${expanded ? 'rotate-180' : ''}`} />
        </button>
        {activeFiltersCount > 0 && (
          <button onClick={clearAll} className="btn-ghost text-xs px-2 py-2 text-danger">
            <X size={12} /> Clear
          </button>
        )}
      </div>

      {/* Expanded filters */}
      {expanded && (
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 xl:grid-cols-5 2xl:grid-cols-5 gap-2 fade-in">
          {[
            { label: 'Category', key: 'category' as const, options: categories },
            { label: 'Brand', key: 'brand' as const, options: brands },
            { label: 'Stock Status', key: 'stockStatus' as const, options: stockStatuses },
            { label: 'Price Level', key: 'priceLevel' as const, options: priceLevels },
            { label: 'Branch', key: 'branch' as const, options: branches },
          ].map(f => (
            <div key={`flt-${f.key}`}>
              <label className="text-2xs font-semibold text-muted-foreground uppercase tracking-wide mb-1 block">{f.label}</label>
              <select
                value={filters[f.key]}
                onChange={e => update(f.key, e.target.value === f.options[0] ? '' : e.target.value)}
                className="w-full text-xs border border-input rounded-lg px-2 py-2 bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
              >
                {f.options.map(opt => (
                  <option key={`opt-${f.key}-${opt}`} value={opt === f.options[0] ? '' : opt}>{opt}</option>
                ))}
              </select>
            </div>
          ))}
        </div>
      )}

      {/* Results count */}
      <div className="flex items-center gap-2 text-xs text-muted-foreground">
        <Filter size={11} />
        <span>
          Showing <span className="font-semibold text-foreground">{filteredCount.toLocaleString()}</span> of{' '}
          <span className="font-semibold text-foreground">{totalCount.toLocaleString()}</span> products
        </span>
      </div>
    </div>
  );
}