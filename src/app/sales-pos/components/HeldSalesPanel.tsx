'use client';
import React from 'react';
import { Clock, ArrowRight, Trash2 } from 'lucide-react';
import { CartItem } from './SaleCart';
import MoneyDisplay from '@/components/ui/MoneyDisplay';

export interface HeldSale {
  id: string;
  items: CartItem[];
  customerName: string;
  total: number;
  heldAt: string;
}

interface HeldSalesPanelProps {
  heldSales: HeldSale[];
  onResume: (id: string) => void;
  onDelete: (id: string) => void;
  onClose: () => void;
}

export default function HeldSalesPanel({ heldSales, onResume, onDelete, onClose }: HeldSalesPanelProps) {
  return (
    <div className="fixed inset-0 z-50 bg-foreground/40 flex items-end sm:items-center justify-center px-4 pb-4 sm:pb-0" onClick={onClose}>
      <div
        className="w-full max-w-md bg-card border border-border rounded-2xl shadow-2xl scale-in overflow-hidden"
        onClick={e => e.stopPropagation()}
      >
        <div className="flex items-center justify-between px-4 py-3 border-b border-border">
          <div className="flex items-center gap-2">
            <Clock size={16} className="text-warning" />
            <h3 className="text-sm font-bold text-foreground">Held Sales</h3>
            <span className="badge-warning">{heldSales.length}</span>
          </div>
          <button onClick={onClose} className="btn-ghost text-xs">Close</button>
        </div>

        {heldSales.length === 0 ? (
          <div className="py-12 text-center">
            <p className="text-sm text-muted-foreground">No held sales</p>
          </div>
        ) : (
          <div className="divide-y divide-border max-h-80 overflow-y-auto">
            {heldSales.map(sale => (
              <div key={sale.id} className="flex items-center justify-between px-4 py-3 hover:bg-muted/50 transition-colors">
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-semibold text-foreground">{sale.customerName}</p>
                  <p className="text-xs text-muted-foreground">{sale.items.length} items · Held at {sale.heldAt}</p>
                  <MoneyDisplay amount={sale.total} compact className="text-sm text-primary mt-0.5" />
                </div>
                <div className="flex items-center gap-2 ml-3">
                  <button
                    onClick={() => onDelete(sale.id)}
                    className="p-1.5 text-muted-foreground hover:text-danger transition-colors"
                  >
                    <Trash2 size={14} />
                  </button>
                  <button
                    onClick={() => onResume(sale.id)}
                    className="btn-primary text-xs px-3 py-1.5 gap-1"
                  >
                    Resume <ArrowRight size={12} />
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}