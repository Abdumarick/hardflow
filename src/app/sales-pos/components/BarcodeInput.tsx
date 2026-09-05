'use client';
import React, { useRef, useEffect, useState, useCallback } from 'react';
import { Scan, X } from 'lucide-react';
import { Product } from './ProductGrid';

interface BarcodeInputProps {
  products: Product[];
  onProductFound: (product: Product) => void;
  onNotFound: (barcode: string) => void;
}

// Barcode scanners typically send characters very fast (< 50ms between chars) then Enter
const SCAN_SPEED_THRESHOLD = 50; // ms between keystrokes to detect scanner vs manual typing
const SCAN_MIN_LENGTH = 3; // minimum barcode length

export default function BarcodeInput({ products, onProductFound, onNotFound }: BarcodeInputProps) {
  const inputRef = useRef<HTMLInputElement>(null);
  const lastKeyTime = useRef<number>(0);
  const [value, setValue] = useState('');
  const [status, setStatus] = useState<'idle' | 'found' | 'notfound'>('idle');
  const [isFocused, setIsFocused] = useState(false);
  const statusTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  // Auto-focus on mount
  useEffect(() => {
    inputRef.current?.focus();
  }, []);

  const clearStatus = useCallback(() => {
    if (statusTimer.current) clearTimeout(statusTimer.current);
    statusTimer.current = setTimeout(() => {
      setStatus('idle');
    }, 1500);
  }, []);

  const processBarcode = useCallback((barcode: string) => {
    const trimmed = barcode.trim();
    if (trimmed.length < SCAN_MIN_LENGTH) return;

    // Match against SKU (case-insensitive) or a barcode field if present
    const found = products.find(
      p => p.sku.toLowerCase() === trimmed.toLowerCase()
    );

    if (found) {
      onProductFound(found);
      setStatus('found');
    } else {
      onNotFound(trimmed);
      setStatus('notfound');
    }
    setValue('');
    clearStatus();
  }, [products, onProductFound, onNotFound, clearStatus]);

  const handleKeyDown = useCallback((e: React.KeyboardEvent<HTMLInputElement>) => {
    const now = Date.now();
    const timeSinceLast = now - lastKeyTime.current;
    lastKeyTime.current = now;

    if (e.key === 'Enter') {
      e.preventDefault();
      processBarcode(value);
      return;
    }

    // If typing is very fast (scanner-like) and we have a reasonable length, 
    // we can also process after a short pause — handled via onBlur/timeout approach
    // For now, Enter key is the primary trigger (standard for barcode scanners)
    void timeSinceLast; // suppress unused warning
  }, [value, processBarcode]);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setValue(e.target.value);
    setStatus('idle');
  };

  const handleClear = () => {
    setValue('');
    setStatus('idle');
    inputRef.current?.focus();
  };

  const borderColor =
    status === 'found' ? 'border-success ring-1 ring-success/30' :
    status === 'notfound'? 'border-danger ring-1 ring-danger/30' : isFocused ?'border-primary ring-1 ring-primary/20': 'border-border';

  return (
    <div className="flex items-center gap-2 px-3 py-2 border-b border-border bg-muted/30">
      <div className={`relative flex-1 flex items-center gap-2 rounded-lg border ${borderColor} bg-background transition-all duration-150 px-3 py-2`}>
        <Scan
          size={15}
          className={`shrink-0 transition-colors ${
            status === 'found' ? 'text-success' :
            status === 'notfound'? 'text-danger' : 'text-primary'
          }`}
        />
        <input
          ref={inputRef}
          type="text"
          value={value}
          onChange={handleChange}
          onKeyDown={handleKeyDown}
          onFocus={() => setIsFocused(true)}
          onBlur={() => setIsFocused(false)}
          placeholder="Scan barcode or type SKU + Enter…"
          className="flex-1 bg-transparent text-sm text-foreground placeholder:text-muted-foreground focus:outline-none font-mono"
          autoComplete="off"
          autoCorrect="off"
          autoCapitalize="off"
          spellCheck={false}
        />
        {value && (
          <button
            onClick={handleClear}
            className="text-muted-foreground hover:text-foreground transition-colors"
            tabIndex={-1}
          >
            <X size={13} />
          </button>
        )}
      </div>

      {/* Status indicator */}
      <div className="shrink-0 w-28 text-right">
        {status === 'found' && (
          <span className="text-xs font-semibold text-success">✓ Added to cart</span>
        )}
        {status === 'notfound' && (
          <span className="text-xs font-semibold text-danger">✗ Not found</span>
        )}
        {status === 'idle' && (
          <span className="text-2xs text-muted-foreground">
            {isFocused ? 'Ready to scan' : 'Click to focus'}
          </span>
        )}
      </div>
    </div>
  );
}
