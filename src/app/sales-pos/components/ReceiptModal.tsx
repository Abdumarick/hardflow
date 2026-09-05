'use client';
import React, { useState, useEffect } from 'react';
import { X, Printer, Download, RotateCcw } from 'lucide-react';
import { CartItem } from './SaleCart';

interface ReceiptModalProps {
  items: CartItem[];
  total: number;
  customerName: string;
  paymentMethod: string;
  amountPaid: number;
  saleRef: string;
  onClose: () => void;
  onNewSale: () => void;
}

export default function ReceiptModal({
  items, total, customerName, paymentMethod, amountPaid, saleRef, onClose, onNewSale,
}: ReceiptModalProps) {
  const change = amountPaid - total;
  const [dateStr, setDateStr] = useState('');
  const [timeStr, setTimeStr] = useState('');

  useEffect(() => {
    const date = new Date();
    setDateStr(`${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear()}`);
    setTimeStr(`${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`);
  }, []);

  return (
    <div className="fixed inset-0 z-50 bg-foreground/50 flex items-center justify-center px-4" onClick={onClose}>
      <div
        className="w-full max-w-sm bg-card border border-border rounded-2xl shadow-2xl scale-in overflow-hidden"
        onClick={e => e.stopPropagation()}
      >
        {/* Header */}
        <div className="flex items-center justify-between px-4 py-3 border-b border-border bg-success/5">
          <div className="flex items-center gap-2">
            <div className="w-7 h-7 rounded-full bg-success/20 flex items-center justify-center">
              <span className="text-success text-xs">✓</span>
            </div>
            <div>
              <p className="text-sm font-bold text-foreground">Sale Complete</p>
              <p className="text-2xs text-muted-foreground">{saleRef}</p>
            </div>
          </div>
          <button onClick={onClose} className="btn-ghost p-1"><X size={14} /></button>
        </div>

        {/* Receipt body */}
        <div className="p-4 space-y-3 font-mono text-xs">
          {/* Business header */}
          <div className="text-center border-b border-dashed border-border pb-3">
            <p className="font-bold text-sm text-foreground">KARIBU HARDWARE LTD</p>
            <p className="text-muted-foreground">Dar es Salaam Main Branch</p>
            <p className="text-muted-foreground">Tel: +255 22 123 4567</p>
            <p className="text-muted-foreground mt-1">{dateStr} {timeStr} · {saleRef}</p>
          </div>

          {/* Customer */}
          <div className="flex justify-between">
            <span className="text-muted-foreground">Customer:</span>
            <span className="font-semibold text-foreground">{customerName}</span>
          </div>

          {/* Items */}
          <div className="border-t border-dashed border-border pt-2 space-y-1.5">
            {items.map(item => (
              <div key={`rcpt-${item.id}`}>
                <p className="text-foreground font-semibold truncate">{item.product.name}</p>
                <div className="flex justify-between text-muted-foreground">
                  <span>{item.qty} × TZS {item.unitPrice.toLocaleString('en-US')}</span>
                  <span className="text-foreground font-semibold">
                    TZS {(item.unitPrice * item.qty * (1 - item.discount / 100)).toLocaleString('en-US')}
                  </span>
                </div>
              </div>
            ))}
          </div>

          {/* Totals */}
          <div className="border-t border-dashed border-border pt-2 space-y-1">
            <div className="flex justify-between font-bold text-sm text-foreground">
              <span>TOTAL</span>
              <span>TZS {total.toLocaleString('en-US')}</span>
            </div>
            <div className="flex justify-between text-muted-foreground">
              <span>Payment ({paymentMethod})</span>
              <span>TZS {amountPaid.toLocaleString('en-US')}</span>
            </div>
            {change >= 0 && (
              <div className="flex justify-between text-success font-semibold">
                <span>Change</span>
                <span>TZS {change.toLocaleString('en-US')}</span>
              </div>
            )}
          </div>

          <div className="text-center border-t border-dashed border-border pt-2">
            <p className="text-muted-foreground">Thank you for your business!</p>
            <p className="text-muted-foreground text-2xs mt-0.5">Powered by HardFlow</p>
          </div>
        </div>

        {/* Actions */}
        <div className="flex gap-2 px-4 pb-4">
          <button className="btn-secondary flex-1 text-xs gap-1.5">
            <Printer size={13} /> Print
          </button>
          <button className="btn-secondary flex-1 text-xs gap-1.5">
            <Download size={13} /> PDF
          </button>
          <button onClick={onNewSale} className="btn-primary flex-1 text-xs gap-1.5">
            <RotateCcw size={13} /> New Sale
          </button>
        </div>
      </div>
    </div>
  );
}