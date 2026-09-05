'use client';
import React, { useState } from 'react';
import {
  ChevronUp, ChevronDown, Eye, Edit2, Trash2, MoreHorizontal,
  CheckSquare, Square, Minus, Package,
} from 'lucide-react';
import StatusBadge from '@/components/ui/StatusBadge';
import MoneyDisplay from '@/components/ui/MoneyDisplay';

export interface ProductRow {
  id: string;
  name: string;
  sku: string;
  barcode: string;
  category: string;
  brand: string;
  unit: string;
  retailPrice: number;
  wholesalePrice: number;
  costPrice: number;
  stock: number;
  minStock: number;
  status: 'active' | 'inactive';
}

interface ProductTableProps {
  products: ProductRow[];
  onView: (id: string) => void;
  onEdit: (id: string) => void;
  onDelete: (id: string) => void;
}

type SortKey = keyof ProductRow;

export default function ProductTable({ products, onView, onEdit, onDelete }: ProductTableProps) {
  const [sortKey, setSortKey] = useState<SortKey>('name');
  const [sortDir, setSortDir] = useState<'asc' | 'desc'>('asc');
  const [selected, setSelected] = useState<string[]>([]);
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(15);
  const [actionMenuOpen, setActionMenuOpen] = useState<string | null>(null);

  const handleSort = (key: SortKey) => {
    if (sortKey === key) {
      setSortDir(d => d === 'asc' ? 'desc' : 'asc');
    } else {
      setSortKey(key);
      setSortDir('asc');
    }
  };

  const sorted = [...products].sort((a, b) => {
    const av = a[sortKey];
    const bv = b[sortKey];
    if (typeof av === 'string' && typeof bv === 'string') {
      return sortDir === 'asc' ? av.localeCompare(bv) : bv.localeCompare(av);
    }
    if (typeof av === 'number' && typeof bv === 'number') {
      return sortDir === 'asc' ? av - bv : bv - av;
    }
    return 0;
  });

  const totalPages = Math.ceil(sorted.length / perPage);
  const paged = sorted.slice((page - 1) * perPage, page * perPage);

  const allSelected = paged.length > 0 && paged.every(p => selected.includes(p.id));
  const someSelected = paged.some(p => selected.includes(p.id)) && !allSelected;

  const toggleAll = () => {
    if (allSelected) {
      setSelected(prev => prev.filter(id => !paged.map(p => p.id).includes(id)));
    } else {
      setSelected(prev => [...new Set([...prev, ...paged.map(p => p.id)])]);
    }
  };

  const toggleOne = (id: string) => {
    setSelected(prev => prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id]);
  };

  const stockStatus = (stock: number, min: number) => {
    if (stock === 0) return 'out-of-stock' as const;
    if (stock <= min) return 'low-stock' as const;
    return 'in-stock' as const;
  };

  const SortIcon = ({ col }: { col: SortKey }) => (
    <span className="inline-flex flex-col ml-1">
      <ChevronUp size={9} className={sortKey === col && sortDir === 'asc' ? 'text-primary' : 'text-muted-foreground/40'} />
      <ChevronDown size={9} className={sortKey === col && sortDir === 'desc' ? 'text-primary' : 'text-muted-foreground/40'} style={{ marginTop: '-2px' }} />
    </span>
  );

  return (
    <div className="card overflow-hidden">
      {/* Bulk action bar */}
      {selected.length > 0 && (
        <div className="flex items-center gap-3 px-4 py-2.5 bg-primary/10 border-b border-primary/20 slide-up">
          <span className="text-sm font-semibold text-primary">{selected.length} selected</span>
          <div className="flex items-center gap-2 ml-2">
            <button className="btn-secondary text-xs py-1">Export Selected</button>
            <button className="btn-secondary text-xs py-1">Update Price</button>
            <button className="btn-secondary text-xs py-1">Change Category</button>
            <button className="btn-danger text-xs py-1">Delete Selected</button>
          </div>
          <button
            onClick={() => setSelected([])}
            className="ml-auto btn-ghost text-xs py-1"
          >
            Clear selection
          </button>
        </div>
      )}

      {/* Table */}
      <div className="overflow-x-auto">
        <table className="w-full min-w-[1000px]">
          <thead className="bg-muted/50 border-b border-border">
            <tr>
              <th className="table-th w-10">
                <button onClick={toggleAll} className="text-muted-foreground hover:text-foreground">
                  {allSelected ? <CheckSquare size={15} className="text-primary" /> :
                    someSelected ? <Minus size={15} className="text-primary" /> :
                    <Square size={15} />}
                </button>
              </th>
              {[
                { key: 'name' as SortKey, label: 'Product Name' },
                { key: 'sku' as SortKey, label: 'SKU' },
                { key: 'category' as SortKey, label: 'Category' },
                { key: 'brand' as SortKey, label: 'Brand' },
                { key: 'unit' as SortKey, label: 'Unit' },
                { key: 'retailPrice' as SortKey, label: 'Retail Price' },
                { key: 'wholesalePrice' as SortKey, label: 'Wholesale' },
                { key: 'costPrice' as SortKey, label: 'Cost Price' },
                { key: 'stock' as SortKey, label: 'Stock' },
                { key: 'status' as SortKey, label: 'Status' },
              ].map(col => (
                <th
                  key={`th-${col.key}`}
                  className="table-th cursor-pointer hover:text-foreground select-none"
                  onClick={() => handleSort(col.key)}
                >
                  <span className="flex items-center">
                    {col.label}
                    <SortIcon col={col.key} />
                  </span>
                </th>
              ))}
              <th className="table-th w-16 text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-border">
            {paged.length === 0 ? (
              <tr>
                <td colSpan={12} className="py-16 text-center">
                  <div className="flex flex-col items-center gap-2 text-muted-foreground">
                    <Package size={28} />
                    <p className="text-sm font-semibold text-foreground">No products found</p>
                    <p className="text-xs">Try adjusting your search or filters</p>
                  </div>
                </td>
              </tr>
            ) : paged.map(product => (
              <tr
                key={product.id}
                className={`table-row-hover ${selected.includes(product.id) ? 'bg-primary/5' : ''}`}
              >
                <td className="table-td w-10">
                  <button onClick={() => toggleOne(product.id)} className="text-muted-foreground hover:text-primary">
                    {selected.includes(product.id)
                      ? <CheckSquare size={15} className="text-primary" />
                      : <Square size={15} />}
                  </button>
                </td>
                <td className="table-td">
                  <div className="flex items-center gap-2">
                    <div className="w-7 h-7 rounded-lg bg-muted flex items-center justify-center shrink-0">
                      <Package size={13} className="text-muted-foreground" />
                    </div>
                    <span className="font-semibold text-foreground max-w-[180px] truncate">{product.name}</span>
                  </div>
                </td>
                <td className="table-td">
                  <span className="font-mono text-xs text-muted-foreground">{product.sku}</span>
                </td>
                <td className="table-td text-muted-foreground">{product.category}</td>
                <td className="table-td text-muted-foreground">{product.brand}</td>
                <td className="table-td text-muted-foreground">{product.unit}</td>
                <td className="table-td">
                  <MoneyDisplay amount={product.retailPrice} compact className="text-sm text-foreground" />
                </td>
                <td className="table-td">
                  <MoneyDisplay amount={product.wholesalePrice} compact className="text-sm text-muted-foreground" />
                </td>
                <td className="table-td">
                  <MoneyDisplay amount={product.costPrice} compact className="text-sm text-muted-foreground" />
                </td>
                <td className="table-td">
                  <div>
                    <span className={`font-semibold tabular-nums ${
                      product.stock === 0 ? 'text-danger' :
                      product.stock <= product.minStock ? 'text-warning' : 'text-foreground'
                    }`}>
                      {product.stock.toLocaleString()}
                    </span>
                    <span className="text-2xs text-muted-foreground ml-1">{product.unit}</span>
                    <div>
                      <StatusBadge status={stockStatus(product.stock, product.minStock)} />
                    </div>
                  </div>
                </td>
                <td className="table-td">
                  <StatusBadge status={product.status} />
                </td>
                <td className="table-td text-right relative">
                  <div className="flex items-center justify-end gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button
                      onClick={() => onView(product.id)}
                      title="View product details"
                      className="p-1.5 text-muted-foreground hover:text-primary hover:bg-primary/10 rounded-md transition-colors"
                    >
                      <Eye size={13} />
                    </button>
                    <button
                      onClick={() => onEdit(product.id)}
                      title="Edit product"
                      className="p-1.5 text-muted-foreground hover:text-accent hover:bg-accent/10 rounded-md transition-colors"
                    >
                      <Edit2 size={13} />
                    </button>
                    <div className="relative">
                      <button
                        onClick={() => setActionMenuOpen(actionMenuOpen === product.id ? null : product.id)}
                        className="p-1.5 text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors"
                      >
                        <MoreHorizontal size={13} />
                      </button>
                      {actionMenuOpen === product.id && (
                        <div className="absolute right-0 top-full mt-1 w-40 bg-card border border-border rounded-xl shadow-lg z-20 py-1 scale-in">
                          <button
                            onClick={() => { onView(product.id); setActionMenuOpen(null); }}
                            className="w-full flex items-center gap-2 px-3 py-2 text-sm text-foreground hover:bg-muted transition-colors"
                          >
                            <Eye size={13} /> View Details
                          </button>
                          <button
                            onClick={() => { onEdit(product.id); setActionMenuOpen(null); }}
                            className="w-full flex items-center gap-2 px-3 py-2 text-sm text-foreground hover:bg-muted transition-colors"
                          >
                            <Edit2 size={13} /> Edit Product
                          </button>
                          <hr className="my-1 border-border" />
                          <button
                            onClick={() => { onDelete(product.id); setActionMenuOpen(null); }}
                            className="w-full flex items-center gap-2 px-3 py-2 text-sm text-danger hover:bg-danger/10 transition-colors"
                          >
                            <Trash2 size={13} /> Delete Product
                          </button>
                        </div>
                      )}
                    </div>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* Pagination */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 px-4 py-3 border-t border-border bg-muted/30">
        <div className="flex items-center gap-2 text-sm text-muted-foreground">
          <span>Show</span>
          <select
            value={perPage}
            onChange={e => { setPerPage(Number(e.target.value)); setPage(1); }}
            className="text-xs border border-border rounded-md px-2 py-1 bg-background text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
          >
            {[10, 15, 25, 50, 100].map(n => (
              <option key={`pp-${n}`} value={n}>{n}</option>
            ))}
          </select>
          <span>per page · {products.length.toLocaleString()} total</span>
        </div>

        <div className="flex items-center gap-1">
          <button
            onClick={() => setPage(1)}
            disabled={page === 1}
            className="btn-ghost text-xs px-2 py-1.5 disabled:opacity-40"
          >
            «
          </button>
          <button
            onClick={() => setPage(p => Math.max(1, p - 1))}
            disabled={page === 1}
            className="btn-ghost text-xs px-2 py-1.5 disabled:opacity-40"
          >
            ‹
          </button>

          {Array.from({ length: Math.min(5, totalPages) }, (_, i) => {
            const pageNum = Math.max(1, Math.min(page - 2, totalPages - 4)) + i;
            return (
              <button
                key={`page-${pageNum}`}
                onClick={() => setPage(pageNum)}
                className={`text-xs px-3 py-1.5 rounded-lg font-medium transition-colors ${
                  page === pageNum
                    ? 'bg-primary text-primary-foreground'
                    : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                }`}
              >
                {pageNum}
              </button>
            );
          })}

          <button
            onClick={() => setPage(p => Math.min(totalPages, p + 1))}
            disabled={page === totalPages}
            className="btn-ghost text-xs px-2 py-1.5 disabled:opacity-40"
          >
            ›
          </button>
          <button
            onClick={() => setPage(totalPages)}
            disabled={page === totalPages}
            className="btn-ghost text-xs px-2 py-1.5 disabled:opacity-40"
          >
            »
          </button>
        </div>
      </div>
    </div>
  );
}