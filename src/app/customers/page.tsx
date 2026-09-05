'use client';
import React, { useState, useMemo } from 'react';
import Link from 'next/link';
import AppLayout, { DashboardLayout } from '@/components/AppLayout';
import StatusBadge from '@/components/ui/StatusBadge';
import MoneyDisplay from '@/components/ui/MoneyDisplay';
import KpiCard from '@/components/ui/KpiCard';
import { Users, Search, Plus, Download, Phone, MapPin, CreditCard, AlertTriangle, ChevronRight, UserCheck,  } from 'lucide-react';

interface Customer {
  id: string;
  name: string;
  type: 'retail' | 'wholesale' | 'contractor';
  phone: string;
  email: string;
  location: string;
  creditLimit: number;
  outstanding: number;
  overdue: number;
  lastSale: string;
  status: 'active' | 'inactive';
  totalPurchases: number;
}

const customers: Customer[] = [
  { id: 'cust-001', name: 'Karibu Construction Ltd', type: 'contractor', phone: '+255 712 345 678', email: 'info@karibuconstruction.co.tz', location: 'Dar es Salaam', creditLimit: 5000000, outstanding: 2850000, overdue: 850000, lastSale: '2026-09-04', status: 'active', totalPurchases: 48500000 },
  { id: 'cust-002', name: 'Juma Builders & Co', type: 'wholesale', phone: '+255 754 987 654', email: 'juma@jumabuilders.co.tz', location: 'Arusha', creditLimit: 3000000, outstanding: 1200000, overdue: 0, lastSale: '2026-09-03', status: 'active', totalPurchases: 22800000 },
  { id: 'cust-003', name: 'Fatuma Mwangi', type: 'retail', phone: '+255 765 432 109', email: 'fatuma.m@gmail.com', location: 'Mwanza', creditLimit: 500000, outstanding: 320000, overdue: 320000, lastSale: '2026-08-28', status: 'active', totalPurchases: 3400000 },
  { id: 'cust-004', name: 'Simba Hardware Supplies', type: 'wholesale', phone: '+255 713 654 321', email: 'simba@simbahardware.co.tz', location: 'Dodoma', creditLimit: 8000000, outstanding: 4500000, overdue: 1200000, lastSale: '2026-09-02', status: 'active', totalPurchases: 95000000 },
  { id: 'cust-005', name: 'Peter Omondi', type: 'retail', phone: '+255 789 123 456', email: 'peter.o@yahoo.com', location: 'Dar es Salaam', creditLimit: 200000, outstanding: 0, overdue: 0, lastSale: '2026-09-01', status: 'active', totalPurchases: 1850000 },
  { id: 'cust-006', name: 'Nyumba Bora Developers', type: 'contractor', phone: '+255 742 876 543', email: 'info@nyumbabora.co.tz', location: 'Dar es Salaam', creditLimit: 12000000, outstanding: 7800000, overdue: 3200000, lastSale: '2026-08-30', status: 'active', totalPurchases: 185000000 },
  { id: 'cust-007', name: 'Hassan Ally', type: 'retail', phone: '+255 756 234 567', email: 'hassan.ally@gmail.com', location: 'Zanzibar', creditLimit: 300000, outstanding: 150000, overdue: 0, lastSale: '2026-09-04', status: 'active', totalPurchases: 2100000 },
  { id: 'cust-008', name: 'Mwamba Trading Co', type: 'wholesale', phone: '+255 723 456 789', email: 'mwamba@mwambatrading.co.tz', location: 'Mbeya', creditLimit: 6000000, outstanding: 0, overdue: 0, lastSale: '2026-08-25', status: 'inactive', totalPurchases: 42000000 },
  { id: 'cust-009', name: 'Grace Kimani', type: 'retail', phone: '+255 768 901 234', email: 'grace.k@gmail.com', location: 'Arusha', creditLimit: 400000, outstanding: 400000, overdue: 400000, lastSale: '2026-07-15', status: 'active', totalPurchases: 980000 },
  { id: 'cust-010', name: 'Coastal Builders Ltd', type: 'contractor', phone: '+255 714 567 890', email: 'info@coastalbuilders.co.tz', location: 'Tanga', creditLimit: 4000000, outstanding: 1850000, overdue: 0, lastSale: '2026-09-03', status: 'active', totalPurchases: 67500000 },
  { id: 'cust-011', name: 'Amina Stores', type: 'retail', phone: '+255 777 345 678', email: 'amina.stores@gmail.com', location: 'Morogoro', creditLimit: 250000, outstanding: 80000, overdue: 0, lastSale: '2026-09-02', status: 'active', totalPurchases: 1200000 },
  { id: 'cust-012', name: 'Kilimanjaro Hardware', type: 'wholesale', phone: '+255 755 678 901', email: 'kili@kilihardware.co.tz', location: 'Moshi', creditLimit: 5000000, outstanding: 2200000, overdue: 500000, lastSale: '2026-08-29', status: 'active', totalPurchases: 38000000 },
];

const typeColors: Record<string, string> = {
  retail: 'bg-info/10 text-info',
  wholesale: 'bg-primary/10 text-primary',
  contractor: 'bg-warning/10 text-warning',
};

export default function CustomersPage() {
  const [layout] = useState<DashboardLayout>('classic');
  const [search, setSearch] = useState('');
  const [typeFilter, setTypeFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [debtFilter, setDebtFilter] = useState('');

  const filtered = useMemo(() => {
    return customers.filter(c => {
      if (search) {
        const q = search.toLowerCase();
        if (!c.name.toLowerCase().includes(q) && !c.phone.includes(q) && !c.location.toLowerCase().includes(q)) return false;
      }
      if (typeFilter && c.type !== typeFilter) return false;
      if (statusFilter && c.status !== statusFilter) return false;
      if (debtFilter === 'overdue' && c.overdue === 0) return false;
      if (debtFilter === 'outstanding' && c.outstanding === 0) return false;
      if (debtFilter === 'clear' && c.outstanding > 0) return false;
      return true;
    });
  }, [search, typeFilter, statusFilter, debtFilter]);

  const totalOutstanding = customers.reduce((s, c) => s + c.outstanding, 0);
  const totalOverdue = customers.reduce((s, c) => s + c.overdue, 0);
  const activeCount = customers.filter(c => c.status === 'active').length;
  const overdueCount = customers.filter(c => c.overdue > 0).length;

  return (
    <AppLayout layout={layout}>
      <div className="space-y-5">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-bold text-foreground">Customers</h1>
            <p className="text-sm text-muted-foreground mt-0.5">{customers.length} total customers · {activeCount} active</p>
          </div>
          <div className="flex items-center gap-2">
            <button className="btn-outline flex items-center gap-2 text-sm px-3 py-2">
              <Download size={15} /> Export
            </button>
            <button className="btn-primary flex items-center gap-2 text-sm px-3 py-2">
              <Plus size={15} /> New Customer
            </button>
          </div>
        </div>

        {/* KPI Row */}
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <KpiCard
            title="Total Customers"
            value={customers.length}
            icon={<Users size={18} className="text-primary" />}
            iconBg="bg-primary/10"
          />
          <KpiCard
            title="Active Customers"
            value={activeCount}
            icon={<UserCheck size={18} className="text-success" />}
            iconBg="bg-success/10"
            variant="success"
          />
          <KpiCard
            title="Outstanding Debt"
            value={totalOutstanding}
            isMoney
            icon={<CreditCard size={18} className="text-warning" />}
            iconBg="bg-warning/10"
            variant="warning"
          />
          <KpiCard
            title="Overdue Debt"
            value={totalOverdue}
            isMoney
            icon={<AlertTriangle size={18} className="text-danger" />}
            iconBg="bg-danger/10"
            variant="danger"
            subtitle={`${overdueCount} customers overdue`}
          />
        </div>

        {/* Filters */}
        <div className="card p-3">
          <div className="flex flex-col sm:flex-row gap-2">
            <div className="relative flex-1">
              <Search size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
              <input
                type="text"
                placeholder="Search by name, phone, location..."
                value={search}
                onChange={e => setSearch(e.target.value)}
                className="input pl-9 w-full text-sm"
              />
            </div>
            <div className="flex gap-2 flex-wrap">
              <select value={typeFilter} onChange={e => setTypeFilter(e.target.value)} className="input text-sm min-w-[130px]">
                <option value="">All Types</option>
                <option value="retail">Retail</option>
                <option value="wholesale">Wholesale</option>
                <option value="contractor">Contractor</option>
              </select>
              <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)} className="input text-sm min-w-[120px]">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
              <select value={debtFilter} onChange={e => setDebtFilter(e.target.value)} className="input text-sm min-w-[140px]">
                <option value="">All Debt Status</option>
                <option value="overdue">Has Overdue</option>
                <option value="outstanding">Has Outstanding</option>
                <option value="clear">Clear Balance</option>
              </select>
            </div>
          </div>
        </div>

        {/* Table */}
        <div className="card overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border bg-muted/30">
                  <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Customer</th>
                  <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide hidden md:table-cell">Type</th>
                  <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide hidden lg:table-cell">Contact</th>
                  <th className="text-right px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Credit Limit</th>
                  <th className="text-right px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Outstanding</th>
                  <th className="text-right px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide hidden sm:table-cell">Overdue</th>
                  <th className="text-center px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide hidden md:table-cell">Status</th>
                  <th className="px-4 py-3 w-10"></th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {filtered.map(customer => (
                  <tr key={customer.id} className="hover:bg-muted/20 transition-colors group">
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-3">
                        <div className="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                          <span className="text-xs font-bold text-primary">
                            {customer.name.split(' ').map(w => w[0]).slice(0, 2).join('')}
                          </span>
                        </div>
                        <div>
                          <Link href={`/customers/${customer.id}`} className="font-semibold text-foreground hover:text-primary transition-colors">
                            {customer.name}
                          </Link>
                          <p className="text-xs text-muted-foreground flex items-center gap-1 mt-0.5">
                            <MapPin size={10} /> {customer.location}
                          </p>
                        </div>
                      </div>
                    </td>
                    <td className="px-4 py-3 hidden md:table-cell">
                      <span className={`text-xs font-semibold px-2 py-0.5 rounded-full capitalize ${typeColors[customer.type]}`}>
                        {customer.type}
                      </span>
                    </td>
                    <td className="px-4 py-3 hidden lg:table-cell">
                      <p className="text-sm text-foreground flex items-center gap-1">
                        <Phone size={11} className="text-muted-foreground" /> {customer.phone}
                      </p>
                    </td>
                    <td className="px-4 py-3 text-right">
                      <MoneyDisplay amount={customer.creditLimit} compact className="text-sm" />
                    </td>
                    <td className="px-4 py-3 text-right">
                      {customer.outstanding > 0 ? (
                        <MoneyDisplay amount={customer.outstanding} compact className="text-sm text-warning" />
                      ) : (
                        <span className="text-sm text-muted-foreground">—</span>
                      )}
                    </td>
                    <td className="px-4 py-3 text-right hidden sm:table-cell">
                      {customer.overdue > 0 ? (
                        <MoneyDisplay amount={customer.overdue} compact className="text-sm text-danger" />
                      ) : (
                        <span className="text-sm text-muted-foreground">—</span>
                      )}
                    </td>
                    <td className="px-4 py-3 text-center hidden md:table-cell">
                      <StatusBadge status={customer.status} />
                    </td>
                    <td className="px-4 py-3">
                      <Link href={`/customers/${customer.id}`} className="p-1.5 rounded-md hover:bg-muted transition-colors flex items-center justify-center text-muted-foreground hover:text-foreground">
                        <ChevronRight size={16} />
                      </Link>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
            {filtered.length === 0 && (
              <div className="text-center py-12 text-muted-foreground">
                <Users size={32} className="mx-auto mb-2 opacity-30" />
                <p className="text-sm">No customers found matching your filters.</p>
              </div>
            )}
          </div>
          <div className="px-4 py-3 border-t border-border bg-muted/20 flex items-center justify-between">
            <p className="text-xs text-muted-foreground">Showing {filtered.length} of {customers.length} customers</p>
            <div className="flex items-center gap-1">
              <button className="btn-outline text-xs px-2 py-1" disabled>Previous</button>
              <span className="text-xs text-muted-foreground px-2">Page 1 of 1</span>
              <button className="btn-outline text-xs px-2 py-1" disabled>Next</button>
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
