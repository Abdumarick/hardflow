'use client';
import React, { useState } from 'react';
import { Menu, Bell, Plus, Search, Sun, Moon, LayoutGrid, ChevronDown, X } from 'lucide-react';
import { DashboardLayout } from './AppLayout';
import GlobalSearch from './GlobalSearch';
import LayoutSwitcher from './LayoutSwitcher';

interface TopbarProps {
  sidebarCollapsed: boolean;
  onSidebarToggle: () => void;
  layout: DashboardLayout;
  onLayoutChange?: (layout: DashboardLayout) => void;
  showSidebarToggle: boolean;
  onMobileMenuToggle: () => void;
}

export default function Topbar({
  sidebarCollapsed,
  onSidebarToggle,
  layout,
  onLayoutChange,
  showSidebarToggle,
  onMobileMenuToggle,
}: TopbarProps) {
  const [searchOpen, setSearchOpen] = useState(false);
  const [layoutSwitcherOpen, setLayoutSwitcherOpen] = useState(false);
  const [darkMode, setDarkMode] = useState(false);
  const [notifOpen, setNotifOpen] = useState(false);
  const [quickCreateOpen, setQuickCreateOpen] = useState(false);

  const toggleDark = () => {
    setDarkMode(!darkMode);
    document.documentElement.classList.toggle('dark');
  };

  return (
    <>
      <header
        className="sticky top-0 z-30 w-full bg-card border-b border-border flex items-center gap-1 sm:gap-2 px-3 sm:px-4 shrink-0"
        style={{ height: 'var(--topbar-height)' }}
      >
        {/* Mobile menu toggle */}
        <button onClick={onMobileMenuToggle} className="btn-ghost lg:hidden p-2 shrink-0">
          <Menu size={20} />
        </button>

        {/* Sidebar toggle (classic only, desktop) */}
        {showSidebarToggle && (
          <button onClick={onSidebarToggle} className="btn-ghost hidden lg:flex p-2 shrink-0">
            <Menu size={20} />
          </button>
        )}

        {/* SmartFlow / Command: show logo */}
        {(layout === 'smartflow' || layout === 'command') && (
          <div className="flex items-center gap-2 mr-2 shrink-0">
            <div className="w-7 h-7 rounded-lg bg-primary flex items-center justify-center">
              <span className="text-primary-foreground text-xs font-bold">HF</span>
            </div>
            <span className="font-bold text-sm text-foreground hidden sm:block">HardFlow</span>
          </div>
        )}

        {/* Search trigger */}
        <button
          onClick={() => setSearchOpen(true)}
          className="flex items-center gap-2 flex-1 min-w-0 max-w-sm px-3 py-2 text-sm text-muted-foreground bg-muted rounded-lg border border-border hover:border-primary/50 transition-colors cursor-text"
        >
          <Search size={14} className="shrink-0" />
          <span className="hidden sm:block truncate">Search products, customers, invoices...</span>
          <span className="sm:hidden truncate">Search...</span>
          <kbd className="ml-auto hidden sm:block text-2xs bg-background border border-border px-1.5 py-0.5 rounded font-mono shrink-0">⌘K</kbd>
        </button>

        <div className="flex items-center gap-0.5 sm:gap-1 ml-auto shrink-0">
          {/* Quick Create — hidden on very small screens */}
          <div className="relative hidden sm:block">
            <button
              onClick={() => setQuickCreateOpen(!quickCreateOpen)}
              className="btn-primary text-xs px-2.5 sm:px-3 py-2 flex items-center gap-1"
            >
              <Plus size={14} />
              <span className="hidden md:inline">Quick Create</span>
              <ChevronDown size={12} />
            </button>
            {quickCreateOpen && (
              <div className="absolute right-0 top-full mt-1 w-52 bg-card border border-border rounded-xl shadow-lg z-50 py-1 scale-in">
                {[
                  { label: 'New Sale', shortcut: 'N' },
                  { label: 'Create Quotation', shortcut: 'Q' },
                  { label: 'New Purchase', shortcut: 'P' },
                  { label: 'Receive Stock', shortcut: 'R' },
                  { label: 'Record Expense', shortcut: 'E' },
                  { label: 'Receive Payment', shortcut: 'M' },
                  { label: 'Add Product', shortcut: 'A' },
                ].map(a => (
                  <button
                    key={`qa-${a.label}`}
                    onClick={() => setQuickCreateOpen(false)}
                    className="w-full flex items-center justify-between px-4 py-2 text-sm text-foreground hover:bg-muted transition-colors"
                  >
                    {a.label}
                    <kbd className="text-2xs bg-muted border border-border px-1.5 py-0.5 rounded font-mono">{a.shortcut}</kbd>
                  </button>
                ))}
              </div>
            )}
          </div>

          {/* Layout switcher */}
          <button
            onClick={() => setLayoutSwitcherOpen(true)}
            className="btn-ghost p-2"
            title="Switch dashboard layout"
          >
            <LayoutGrid size={18} />
          </button>

          {/* Dark mode */}
          <button onClick={toggleDark} className="btn-ghost p-2 hidden sm:flex">
            {darkMode ? <Sun size={18} /> : <Moon size={18} />}
          </button>

          {/* Notifications */}
          <div className="relative">
            <button
              onClick={() => setNotifOpen(!notifOpen)}
              className="btn-ghost p-2 relative"
            >
              <Bell size={18} />
              <span className="absolute top-1.5 right-1.5 w-2 h-2 bg-danger rounded-full" />
            </button>
            {notifOpen && (
              <div className="absolute right-0 top-full mt-1 w-[calc(100vw-2rem)] sm:w-80 max-w-sm bg-card border border-border rounded-xl shadow-lg z-50 scale-in">
                <div className="flex items-center justify-between px-4 py-3 border-b border-border">
                  <span className="font-semibold text-sm">Notifications</span>
                  <button onClick={() => setNotifOpen(false)}><X size={14} className="text-muted-foreground" /></button>
                </div>
                <div className="max-h-72 sm:max-h-80 overflow-y-auto">
                  {[
                    { id: 'n-1', title: 'Low Stock Alert', msg: 'Portland Cement 50kg — 8 bags remaining', time: '5m ago', type: 'warning' },
                    { id: 'n-2', title: 'Approval Required', msg: 'Price override request from Cashier Ali', time: '12m ago', type: 'info' },
                    { id: 'n-3', title: 'Customer Overdue', msg: 'Juma Builders — TZS 2.4M overdue 14 days', time: '1h ago', type: 'danger' },
                    { id: 'n-4', title: 'Stock Received', msg: 'Purchase #PO-2024-089 partially received', time: '2h ago', type: 'success' },
                    { id: 'n-5', title: 'Cash Reconciliation', msg: 'End-of-day cash variance TZS 15,000', time: '3h ago', type: 'warning' },
                  ].map(n => (
                    <div key={n.id} className="px-4 py-3 border-b border-border last:border-0 hover:bg-muted transition-colors cursor-pointer">
                      <div className="flex items-start gap-3">
                        <span className={`w-2 h-2 rounded-full mt-1.5 shrink-0 ${
                          n.type === 'warning' ? 'bg-warning' :
                          n.type === 'danger' ? 'bg-danger' :
                          n.type === 'success' ? 'bg-success' : 'bg-info'
                        }`} />
                        <div className="flex-1 min-w-0">
                          <p className="text-sm font-semibold text-foreground">{n.title}</p>
                          <p className="text-xs text-muted-foreground truncate">{n.msg}</p>
                          <p className="text-2xs text-muted-foreground mt-0.5">{n.time}</p>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
                <div className="px-4 py-2 border-t border-border">
                  <button className="text-xs text-primary font-semibold hover:underline">View all notifications</button>
                </div>
              </div>
            )}
          </div>

          {/* Avatar */}
          <div className="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-primary-foreground text-xs font-bold cursor-pointer ml-0.5 sm:ml-1 shrink-0">
            JM
          </div>
        </div>
      </header>

      {searchOpen && <GlobalSearch onClose={() => setSearchOpen(false)} />}
      {layoutSwitcherOpen && (
        <LayoutSwitcher
          current={layout}
          onSelect={(l) => { onLayoutChange?.(l); setLayoutSwitcherOpen(false); }}
          onClose={() => setLayoutSwitcherOpen(false)}
        />
      )}
    </>
  );
}