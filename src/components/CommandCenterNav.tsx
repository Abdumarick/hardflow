'use client';
import React, { useState } from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { LayoutDashboard, ShoppingCart, FileText, Users, Package, Warehouse, ShoppingBag, Receipt, BarChart2, Bell, Search, Plus, Sun, Moon, ChevronDown, Menu, X, LayoutGrid, CheckSquare, CreditCard,  } from 'lucide-react';
import { DashboardLayout } from './AppLayout';
import GlobalSearch from './GlobalSearch';
import LayoutSwitcher from './LayoutSwitcher';

const megaMenuItems = [
  {
    label: 'Operations',
    items: [
      { label: 'Dashboard', href: '/', icon: <LayoutDashboard size={16} /> },
      { label: 'Sales / POS', href: '/sales-pos', icon: <ShoppingCart size={16} /> },
      { label: 'Quotations', href: '/quotations', icon: <FileText size={16} /> },
    ],
  },
  {
    label: 'Customers',
    items: [
      { label: 'Customers', href: '/customers', icon: <Users size={16} /> },
      { label: 'Customer Debts', href: '/customer-debts', icon: <CreditCard size={16} /> },
    ],
  },
  {
    label: 'Inventory',
    items: [
      { label: 'Products', href: '/product-management', icon: <Package size={16} /> },
      { label: 'Inventory', href: '/inventory', icon: <Warehouse size={16} /> },
    ],
  },
  {
    label: 'Procurement',
    items: [
      { label: 'Purchases', href: '/purchases', icon: <ShoppingBag size={16} /> },
      { label: 'Suppliers', href: '/suppliers', icon: <ShoppingBag size={16} /> },
    ],
  },
  {
    label: 'Finance',
    items: [
      { label: 'Expenses', href: '/expenses', icon: <Receipt size={16} /> },
      { label: 'Payments', href: '/payments', icon: <Receipt size={16} /> },
    ],
  },
  {
    label: 'Reports',
    items: [
      { label: 'Reports Center', href: '/reports', icon: <BarChart2 size={16} /> },
      { label: 'Approvals', href: '/approvals', icon: <CheckSquare size={16} /> },
    ],
  },
];

interface CommandCenterNavProps {
  layout: DashboardLayout;
  onLayoutChange?: (layout: DashboardLayout) => void;
  onMobileMenuToggle: () => void;
}

export default function CommandCenterNav({ layout, onLayoutChange, onMobileMenuToggle }: CommandCenterNavProps) {
  const pathname = usePathname();
  const [openMenu, setOpenMenu] = useState<string | null>(null);
  const [searchOpen, setSearchOpen] = useState(false);
  const [layoutSwitcherOpen, setLayoutSwitcherOpen] = useState(false);
  const [darkMode, setDarkMode] = useState(false);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  const toggleDark = () => {
    setDarkMode(!darkMode);
    document.documentElement.classList.toggle('dark');
  };

  return (
    <>
      <header className="sticky top-0 z-30 w-full bg-card border-b border-border" style={{ height: 'var(--topbar-height)' }}>
        <div className="flex items-center gap-2 px-4 lg:px-8 h-full max-w-screen-2xl mx-auto">
          {/* Logo */}
          <div className="flex items-center gap-2 mr-4">
            <div className="w-7 h-7 rounded-lg bg-primary flex items-center justify-center">
              <span className="text-primary-foreground text-xs font-bold">HF</span>
            </div>
            <span className="font-bold text-sm text-foreground hidden sm:block">HardFlow</span>
          </div>

          {/* Mega menu items */}
          <nav className="hidden lg:flex items-center gap-1">
            {megaMenuItems.map(group => (
              <div key={`cc-${group.label}`} className="relative">
                <button
                  onMouseEnter={() => setOpenMenu(group.label)}
                  onMouseLeave={() => setOpenMenu(null)}
                  className={`flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
                    openMenu === group.label ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:text-foreground hover:bg-muted'
                  }`}
                >
                  {group.label}
                  <ChevronDown size={12} />
                </button>
                {openMenu === group.label && (
                  <div
                    className="absolute top-full left-0 mt-1 w-52 bg-card border border-border rounded-xl shadow-lg z-50 py-1 scale-in"
                    onMouseEnter={() => setOpenMenu(group.label)}
                    onMouseLeave={() => setOpenMenu(null)}
                  >
                    {group.items.map(item => (
                      <Link
                        key={`ccm-${item.href}`}
                        href={item.href}
                        className={`flex items-center gap-2 px-4 py-2 text-sm transition-colors ${
                          pathname === item.href ? 'text-primary bg-primary/5' : 'text-foreground hover:bg-muted'
                        }`}
                      >
                        <span className="text-muted-foreground">{item.icon}</span>
                        {item.label}
                      </Link>
                    ))}
                  </div>
                )}
              </div>
            ))}
          </nav>

          {/* Right side */}
          <div className="flex items-center gap-1 ml-auto">
            <button
              onClick={() => setSearchOpen(true)}
              className="flex items-center gap-2 px-3 py-2 text-sm text-muted-foreground bg-muted rounded-lg border border-border hover:border-primary/50 transition-colors cursor-text max-w-xs hidden md:flex"
            >
              <Search size={14} />
              <span>Search...</span>
              <kbd className="ml-2 text-2xs bg-background border border-border px-1.5 py-0.5 rounded font-mono">⌘K</kbd>
            </button>

            <button className="btn-primary text-xs px-3 py-2 hidden sm:flex">
              <Plus size={14} />
              New Sale
            </button>

            <button onClick={() => setLayoutSwitcherOpen(true)} className="btn-ghost p-2">
              <LayoutGrid size={18} />
            </button>

            <button onClick={toggleDark} className="btn-ghost p-2">
              {darkMode ? <Sun size={18} /> : <Moon size={18} />}
            </button>

            <button className="btn-ghost p-2 relative">
              <Bell size={18} />
              <span className="absolute top-1.5 right-1.5 w-2 h-2 bg-danger rounded-full" />
            </button>

            <div className="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-primary-foreground text-xs font-bold cursor-pointer ml-1">
              JM
            </div>

            <button onClick={() => setMobileMenuOpen(!mobileMenuOpen)} className="btn-ghost p-2 lg:hidden">
              {mobileMenuOpen ? <X size={18} /> : <Menu size={18} />}
            </button>
          </div>
        </div>

        {/* Mobile menu */}
        {mobileMenuOpen && (
          <div className="lg:hidden bg-card border-t border-border px-4 py-3 space-y-1 fade-in">
            {megaMenuItems.flatMap(g => g.items).map(item => (
              <Link
                key={`mob-${item.href}`}
                href={item.href}
                onClick={() => setMobileMenuOpen(false)}
                className={`flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
                  pathname === item.href ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                }`}
              >
                {item.icon}
                {item.label}
              </Link>
            ))}
          </div>
        )}
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