'use client';
import React, { useState, useMemo } from 'react';
import Link from 'next/link';
import AppLayout, { DashboardLayout } from '@/components/AppLayout';
import StatusBadge from '@/components/ui/StatusBadge';
import MoneyDisplay from '@/components/ui/MoneyDisplay';
import { Plus, Search, Download, Eye, Copy, ArrowRight } from 'lucide-react';

interface Quotation {
  id: string;
  quotationNo: string;
  customer: string;
  phone: string;
  location: string;
  date: string;
  validUntil: string;
  items: number;
  subtotal: number;
  discount: number;
  total: number;
  status: 'draft' | 'pending' | 'confirmed' | 'cancelled' | 'closed';
  createdBy: string;
}

const quotations: Quotation[] = [
  { id: 'q-001', quotationNo: 'QT-2026-0048', customer: 'Karibu Construction Ltd', phone: '+255 712 345 678', location: 'Dar es Salaam', date: '2026-09-05', validUntil: '2026-09-19', items: 8, subtotal: 4850000, discount: 242500, total: 4607500, status: 'pending', createdBy: 'James Mwangi' },
  { id: 'q-002', quotationNo: 'QT-2026-0047', customer: 'Nyumba Bora Developers', phone: '+255 742 876 543', location: 'Dar es Salaam', date: '2026-09-04', validUntil: '2026-09-18', items: 15, subtotal: 12400000, discount: 620000, total: 11780000, status: 'confirmed', createdBy: 'Sarah Kimani' },
  { id: 'q-003', quotationNo: 'QT-2026-0046', customer: 'Juma Builders & Co', phone: '+255 754 987 654', location: 'Arusha', date: '2026-09-03', validUntil: '2026-09-17', items: 6, subtotal: 2200000, discount: 0, total: 2200000, status: 'draft', createdBy: 'James Mwangi' },
  { id: 'q-004', quotationNo: 'QT-2026-0045', customer: 'Simba Hardware Supplies', phone: '+255 713 654 321', location: 'Dodoma', date: '2026-09-02', validUntil: '2026-09-16', items: 22, subtotal: 8750000, discount: 875000, total: 7875000, status: 'closed', createdBy: 'Peter Omondi' },
  { id: 'q-005', quotationNo: 'QT-2026-0044', customer: 'Coastal Builders Ltd', phone: '+255 714 567 890', location: 'Tanga', date: '2026-09-01', validUntil: '2026-09-15', items: 11, subtotal: 5600000, discount: 280000, total: 5320000, status: 'pending', createdBy: 'Sarah Kimani' },
  { id: 'q-006', quotationNo: 'QT-2026-0043', customer: 'Hassan Ally', phone: '+255 756 234 567', location: 'Zanzibar', date: '2026-08-30', validUntil: '2026-09-13', items: 3, subtotal: 480000, discount: 0, total: 480000, status: 'cancelled', createdBy: 'James Mwangi' },
  { id: 'q-007', quotationNo: 'QT-2026-0042', customer: 'Kilimanjaro Hardware', phone: '+255 755 678 901', location: 'Moshi', date: '2026-08-29', validUntil: '2026-09-12', items: 18, subtotal: 9200000, discount: 460000, total: 8740000, status: 'confirmed', createdBy: 'Peter Omondi' },
  { id: 'q-008', quotationNo: 'QT-2026-0041', customer: 'Grace Kimani', phone: '+255 768 901 234', location: 'Arusha', date: '2026-08-28', validUntil: '2026-09-11', items: 4, subtotal: 720000, discount: 36000, total: 684000, status: 'draft', createdBy: 'James Mwangi' },
];

const statusOptions = ['', 'draft', 'pending', 'confirmed', 'cancelled', 'closed'];

export default function QuotationsPage() {
  const [layout] = useState<DashboardLayout>('classic');
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');

  const filtered = useMemo(() => {
    return quotations.filter(q => {
      if (search) {
        const s = search.toLowerCase();
        if (!q.customer.toLowerCase().includes(s) && !q.quotationNo.toLowerCase().includes(s) && !q.phone.includes(s)) return false;
      }
      if (statusFilter && q.status !== statusFilter) return false;
      return true;
    });
  }, [search, statusFilter]);

  const totalValue = filtered.reduce((s, q) => s + q.total, 0);
  const pendingCount = quotations.filter(q => q.status === 'pending').length;
  const confirmedCount = quotations.filter(q => q.status === 'confirmed').length;

  const isExpired = (validUntil: string) => new Date(validUntil) < new Date('2026-09-05');

  return (
    <AppLayout layout={layout}>
      <div className="p-6 space-y-6">
        {/* Header */}
        <div className="flex items-center justify-between gap-4 flex-wrap">
          <div>
            <h1 className="text-xl font-bold text-foreground">Quotations</h1>
            <p className="text-sm text-muted-foreground mt-0.5">Manage customer quotations and convert to sales</p>
          </div>
          <div className="flex items-center gap-2">
            <button className="btn-secondary">
              <Download size={15} />
              Export
            </button>
            <Link href="/quotations/new" className="btn-primary">
              <Plus size={15} />
              New Quotation
            </Link>
          </div>
        </div>

        {/* KPI Row */}
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <div className="card p-4">
            <p className="text-xs text-muted-foreground font-medium uppercase tracking-wide">Total Quotations</p>
            <p className="text-2xl font-bold text-foreground mt-1">{quotations.length}</p>
            <p className="text-xs text-muted-foreground mt-1">This month</p>
          </div>
          <div className="card p-4">
            <p className="text-xs text-muted-foreground font-medium uppercase tracking-wide">Pending</p>
            <p className="text-2xl font-bold text-warning mt-1">{pendingCount}</p>
            <p className="text-xs text-muted-foreground mt-1">Awaiting response</p>
          </div>
          <div className="card p-4">
            <p className="text-xs text-muted-foreground font-medium uppercase tracking-wide">Confirmed</p>
            <p className="text-2xl font-bold text-success mt-1">{confirmedCount}</p>
            <p className="text-xs text-muted-foreground mt-1">Ready to convert</p>
          </div>
          <div className="card p-4">
            <p className="text-xs text-muted-foreground font-medium uppercase tracking-wide">Pipeline Value</p>
            <MoneyDisplay amount={totalValue} compact className="text-lg mt-1 block" />
            <p className="text-xs text-muted-foreground mt-1">Filtered total</p>
          </div>
        </div>

        {/* Filters */}
        <div className="card p-4 flex flex-wrap gap-3 items-center">
          <div className="relative flex-1 min-w-[200px]">
            <Search size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
            <input
              className="input-base pl-9"
              placeholder="Search by customer, quotation no, phone..."
              value={search}
              onChange={e => setSearch(e.target.value)}
            />
          </div>
          <select
            className="input-base w-auto min-w-[140px]"
            value={statusFilter}
            onChange={e => setStatusFilter(e.target.value)}
          >
            <option value="">All Statuses</option>
            {statusOptions.filter(Boolean).map(s => (
              <option key={s} value={s}>{s.charAt(0).toUpperCase() + s.slice(1)}</option>
            ))}
          </select>
        </div>

        {/* Table */}
        <div className="card overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-muted/50 border-b border-border">
                <tr>
                  <th className="table-th">Quotation #</th>
                  <th className="table-th">Customer</th>
                  <th className="table-th">Date</th>
                  <th className="table-th">Valid Until</th>
                  <th className="table-th text-center">Items</th>
                  <th className="table-th text-right">Total</th>
                  <th className="table-th">Status</th>
                  <th className="table-th">Created By</th>
                  <th className="table-th text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {filtered.map(q => (
                  <tr key={q.id} className="table-row-hover hover:bg-muted/30">
                    <td className="table-td">
                      <Link href={`/quotations/${q.id}`} className="font-semibold text-primary hover:underline">
                        {q.quotationNo}
                      </Link>
                    </td>
                    <td className="table-td">
                      <div>
                        <p className="font-medium text-foreground">{q.customer}</p>
                        <p className="text-xs text-muted-foreground">{q.phone}</p>
                      </div>
                    </td>
                    <td className="table-td text-muted-foreground">{q.date}</td>
                    <td className="table-td">
                      <span className={isExpired(q.validUntil) && q.status === 'pending' ? 'text-danger font-medium' : 'text-muted-foreground'}>
                        {q.validUntil}
                        {isExpired(q.validUntil) && q.status === 'pending' && <span className="ml-1 text-xs">(Expired)</span>}
                      </span>
                    </td>
                    <td className="table-td text-center">{q.items}</td>
                    <td className="table-td text-right">
                      <MoneyDisplay amount={q.total} />
                    </td>
                    <td className="table-td">
                      <StatusBadge status={q.status} />
                    </td>
                    <td className="table-td text-muted-foreground">{q.createdBy}</td>
                    <td className="table-td">
                      <div className="flex items-center justify-end gap-1">
                        <Link href={`/quotations/${q.id}`} className="btn-ghost p-1.5" title="View">
                          <Eye size={14} />
                        </Link>
                        <button className="btn-ghost p-1.5" title="Duplicate">
                          <Copy size={14} />
                        </button>
                        {(q.status === 'confirmed' || q.status === 'pending') && (
                          <button className="btn-ghost p-1.5 text-success hover:text-success" title="Convert to Sale">
                            <ArrowRight size={14} />
                          </button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
                {filtered.length === 0 && (
                  <tr>
                    <td colSpan={9} className="table-td text-center py-12 text-muted-foreground">
                      No quotations found matching your filters.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
          <div className="px-4 py-3 border-t border-border flex items-center justify-between text-sm text-muted-foreground">
            <span>Showing {filtered.length} of {quotations.length} quotations</span>
            <span className="font-medium text-foreground">
              Total: <MoneyDisplay amount={totalValue} />
            </span>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
