'use client';
import React, { useState } from 'react';
import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import AppLogo from './ui/AppLogo';
import { useAuth } from '@/context/AuthContext';
import {
  LayoutDashboard, ShoppingCart, FileText, Users, CreditCard,
  Package, Warehouse, ShoppingBag, Truck, Receipt, ArrowLeftRight,
  CheckSquare, BarChart2, UserCog, ClipboardList, Bell, Settings,
  ChevronDown, ChevronRight, Building2, GitBranch, LogOut, UsersRound,
  Banknote, RotateCcw, Shield,
} from 'lucide-react';

interface NavItem {
  id: string;
  label: string;
  icon: React.ReactNode;
  href?: string;
  badge?: number;
  badgeColor?: string;
  children?: NavItem[];
}

const navGroups: { label: string; items: NavItem[] }[] = [
  {
    label: 'Operations',
    items: [
      { id: 'nav-dashboard', label: 'Dashboard', icon: <LayoutDashboard size={18} />, href: '/' },
      { id: 'nav-pos', label: 'Sales / POS', icon: <ShoppingCart size={18} />, href: '/sales-pos' },
      { id: 'nav-quotations', label: 'Quotations', icon: <FileText size={18} />, href: '/quotations' },
    ],
  },
  {
    label: 'Customers',
    items: [
      { id: 'nav-customers', label: 'Customers', icon: <Users size={18} />, href: '/customers' },
      { id: 'nav-debts', label: 'Customer Debts', icon: <CreditCard size={18} />, badge: 18, badgeColor: 'danger', href: '/customer-debts' },
    ],
  },
  {
    label: 'Inventory',
    items: [
      { id: 'nav-products', label: 'Products', icon: <Package size={18} />, href: '/product-management' },
      { id: 'nav-inventory', label: 'Inventory', icon: <Warehouse size={18} />, href: '/inventory' },
      { id: 'nav-inv-movements', label: 'Movements', icon: <ArrowLeftRight size={18} />, href: '/inventory-movements' },
    ],
  },
  {
    label: 'Procurement',
    items: [
      { id: 'nav-purchases', label: 'Purchases', icon: <ShoppingBag size={18} />, href: '/purchases' },
      { id: 'nav-suppliers', label: 'Suppliers', icon: <Truck size={18} />, href: '/suppliers' },
    ],
  },
  {
    label: 'Finance',
    items: [
      { id: 'nav-expenses', label: 'Expenses', icon: <Receipt size={18} />, href: '/expenses' },
      { id: 'nav-payments', label: 'Cash & Payments', icon: <Banknote size={18} />, href: '/cash-payments' },
      { id: 'nav-returns', label: 'Returns', icon: <RotateCcw size={18} />, href: '/returns' },
    ],
  },
  {
    label: 'Management',
    items: [
      { id: 'nav-approvals', label: 'Approvals', icon: <CheckSquare size={18} />, badge: 7, badgeColor: 'warning', href: '/approvals' },
      { id: 'nav-reports', label: 'Reports', icon: <BarChart2 size={18} />, href: '/reports' },
      { id: 'nav-staff', label: 'Staff', icon: <UsersRound size={18} />, href: '/staff' },
      { id: 'nav-users', label: 'Users & Roles', icon: <UserCog size={18} />, href: '/admin' },
      { id: 'nav-audit', label: 'Audit Logs', icon: <ClipboardList size={18} />, href: '/audit' },
    ],
  },
  {
    label: 'System',
    items: [
      { id: 'nav-notifications', label: 'Notifications', icon: <Bell size={18} />, badge: 5, badgeColor: 'info', href: '/notifications' },
      { id: 'nav-super-admin', label: 'Super Admin', icon: <Shield size={18} />, href: '/super-admin' },
      { id: 'nav-settings', label: 'Settings', icon: <Settings size={18} />, href: '/settings' },
    ],
  },
];

interface SidebarProps {
  collapsed: boolean;
  mobileOpen: boolean;
  onMobileClose: () => void;
}

export default function Sidebar({ collapsed, mobileOpen, onMobileClose }: SidebarProps) {
  const pathname = usePathname();
  const router = useRouter();
  const { user, logout } = useAuth();
  const [expandedGroups, setExpandedGroups] = useState<string[]>(['Operations', 'Customers', 'Inventory']);

  const toggleGroup = (label: string) => {
    setExpandedGroups(prev =>
      prev.includes(label) ? prev.filter(g => g !== label) : [...prev, label]
    );
  };

  const isActive = (href?: string) => {
    if (!href) return false;
    if (href === '/') return pathname === '/';
    return pathname.startsWith(href);
  };

  const handleLogout = () => {
    logout();
    router.replace('/login');
  };

  const roleLabel: Record<string, string> = {
    owner: 'Business Owner',
    branch_manager: 'Branch Manager',
    cashier: 'Cashier',
    storekeeper: 'Storekeeper',
    accountant: 'Accountant',
    viewer: 'Viewer',
  };

  return (
    <aside
      className={[
        'fixed top-0 left-0 h-full z-50 bg-card border-r border-border flex flex-col sidebar-transition',
        'lg:translate-x-0',
        mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
        collapsed ? 'lg:w-[var(--sidebar-collapsed-width)]' : 'w-[var(--sidebar-width)]',
      ].join(' ')}
    >
      {/* Logo */}
      <div className={`flex items-center gap-2 px-4 border-b border-border ${collapsed ? 'justify-center py-4' : 'py-3'}`}
        style={{ height: 'var(--topbar-height)' }}>
        <AppLogo size={32} />
        {!collapsed && (
          <span className="font-bold text-base text-foreground tracking-tight">HardFlow</span>
        )}
      </div>

      {/* Business / Branch selector */}
      {!collapsed && (
        <div className="px-3 py-2 border-b border-border">
          <div className="flex items-center gap-2 px-2 py-2 rounded-lg bg-primary/5 border border-primary/20 cursor-pointer hover:bg-primary/10 transition-colors">
            <Building2 size={14} className="text-primary shrink-0" />
            <div className="flex-1 min-w-0">
              <p className="text-xs font-semibold text-foreground truncate">Karibu Hardware Ltd</p>
              <p className="text-2xs text-muted-foreground flex items-center gap-1">
                <GitBranch size={10} /> {user?.branch ?? 'Main Branch'}
              </p>
            </div>
            <ChevronDown size={12} className="text-muted-foreground shrink-0" />
          </div>
        </div>
      )}

      {/* Nav items */}
      <nav className="flex-1 overflow-y-auto py-2 px-2 space-y-0.5">
        {navGroups.map(group => (
          <div key={`group-${group.label}`} className="mb-1">
            {!collapsed && (
              <button
                onClick={() => toggleGroup(group.label)}
                className="w-full flex items-center justify-between px-2 py-1.5 text-2xs font-semibold text-muted-foreground uppercase tracking-widest hover:text-foreground transition-colors"
              >
                {group.label}
                {expandedGroups.includes(group.label) ? <ChevronDown size={10} /> : <ChevronRight size={10} />}
              </button>
            )}
            {(collapsed || expandedGroups.includes(group.label)) && (
              <div className="space-y-0.5">
                {group.items.map(item => (
                  <Link
                    key={item.id}
                    href={item.href || '#'}
                    onClick={onMobileClose}
                    title={collapsed ? item.label : undefined}
                    className={[
                      'nav-item group relative',
                      isActive(item.href) ? 'nav-item-active' : '',
                      collapsed ? 'justify-center px-0' : '',
                    ].join(' ')}
                  >
                    <span className={`shrink-0 ${isActive(item.href) ? 'text-primary' : 'text-muted-foreground group-hover:text-foreground'}`}>
                      {item.icon}
                    </span>
                    {!collapsed && (
                      <span className="flex-1 truncate">{item.label}</span>
                    )}
                    {!collapsed && item.badge && (
                      <span className={`text-2xs font-bold px-1.5 py-0.5 rounded-full ${
                        item.badgeColor === 'danger' ? 'bg-danger/10 text-danger' :
                        item.badgeColor === 'warning'? 'bg-warning/10 text-warning' : 'bg-info/10 text-info'
                      }`}>
                        {item.badge}
                      </span>
                    )}
                    {collapsed && item.badge && (
                      <span className="absolute top-1 right-1 w-2 h-2 rounded-full bg-danger" />
                    )}
                    {collapsed && (
                      <div className="absolute left-full ml-2 px-2 py-1 bg-foreground text-background text-xs rounded-md whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity z-50">
                        {item.label}
                      </div>
                    )}
                  </Link>
                ))}
              </div>
            )}
          </div>
        ))}
      </nav>

      {/* User profile */}
      <div className={`border-t border-border px-3 py-3 ${collapsed ? 'flex flex-col items-center gap-2' : ''}`}>
        {collapsed ? (
          <>
            <div className="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-primary-foreground text-xs font-bold">
              {user?.initials ?? 'U'}
            </div>
            <button
              onClick={handleLogout}
              title="Sign out"
              className="w-8 h-8 flex items-center justify-center rounded-lg text-muted-foreground hover:text-danger hover:bg-danger/10 transition-colors"
            >
              <LogOut size={14} />
            </button>
          </>
        ) : (
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-primary-foreground text-xs font-bold shrink-0">
              {user?.initials ?? 'U'}
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-semibold text-foreground truncate">{user?.name ?? 'User'}</p>
              <p className="text-2xs text-muted-foreground">{roleLabel[user?.role ?? ''] ?? user?.role}</p>
            </div>
            <button
              onClick={handleLogout}
              title="Sign out"
              className="w-7 h-7 flex items-center justify-center rounded-lg text-muted-foreground hover:text-danger hover:bg-danger/10 transition-colors shrink-0"
            >
              <LogOut size={14} />
            </button>
          </div>
        )}
      </div>
    </aside>
  );
}