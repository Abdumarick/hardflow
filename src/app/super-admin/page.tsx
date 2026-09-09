'use client';
import React, { useState } from 'react';
import AppLayout, { DashboardLayout } from '@/components/AppLayout';

import { Shield, Building2, Users, TrendingUp, Search, Plus, Eye, Settings, CheckCircle2, AlertCircle, Clock, XCircle, BarChart3, Globe, Star, AlertTriangle, Zap,  } from 'lucide-react';

type BusinessStatus = 'active' | 'trial' | 'suspended' | 'inactive';
type PlanType = 'starter' | 'professional' | 'enterprise';

interface Business {
  id: string;
  name: string;
  owner: string;
  email: string;
  phone: string;
  country: string;
  city: string;
  status: BusinessStatus;
  plan: PlanType;
  branches: number;
  users: number;
  joinedDate: string;
  lastActive: string;
  monthlyRevenue: number;
  currency: string;
  storageUsed: number;
  storageLimit: number;
}

const businesses: Business[] = [
  { id: 'b001', name: 'Karibu Hardware Ltd', owner: 'James Mwangi', email: 'james@karibuhardware.co.tz', phone: '+255 712 345 678', country: 'Tanzania', city: 'Dar es Salaam', status: 'active', plan: 'enterprise', branches: 3, users: 12, joinedDate: '2024-03-15', lastActive: '2026-09-09', monthlyRevenue: 48500000, currency: 'TZS', storageUsed: 4.2, storageLimit: 20 },
  { id: 'b002', name: 'Simba Building Supplies', owner: 'Grace Otieno', email: 'grace@simbabuilding.co.ke', phone: '+254 722 987 654', country: 'Kenya', city: 'Nairobi', status: 'active', plan: 'professional', branches: 2, users: 8, joinedDate: '2024-06-20', lastActive: '2026-09-09', monthlyRevenue: 32100000, currency: 'KES', storageUsed: 2.8, storageLimit: 10 },
  { id: 'b003', name: 'Kampala Iron Works', owner: 'Robert Ssekandi', email: 'robert@kampalaironworks.ug', phone: '+256 701 234 567', country: 'Uganda', city: 'Kampala', status: 'active', plan: 'professional', branches: 1, users: 5, joinedDate: '2025-01-10', lastActive: '2026-09-08', monthlyRevenue: 18750000, currency: 'UGX', storageUsed: 1.5, storageLimit: 10 },
  { id: 'b004', name: 'Mombasa Steel & Cement', owner: 'Fatuma Hassan', email: 'fatuma@mombasasteel.co.ke', phone: '+254 733 456 789', country: 'Kenya', city: 'Mombasa', status: 'trial', plan: 'starter', branches: 1, users: 3, joinedDate: '2026-08-25', lastActive: '2026-09-09', monthlyRevenue: 5200000, currency: 'KES', storageUsed: 0.3, storageLimit: 5 },
  { id: 'b005', name: 'Arusha Hardware Hub', owner: 'Emmanuel Kimaro', email: 'ekimaro@arushahardware.co.tz', phone: '+255 754 321 098', country: 'Tanzania', city: 'Arusha', status: 'active', plan: 'starter', branches: 1, users: 4, joinedDate: '2025-07-14', lastActive: '2026-09-07', monthlyRevenue: 8900000, currency: 'TZS', storageUsed: 0.9, storageLimit: 5 },
  { id: 'b006', name: 'Kigali Construction Depot', owner: 'Diane Uwimana', email: 'diane@kigalidepot.rw', phone: '+250 788 123 456', country: 'Rwanda', city: 'Kigali', status: 'suspended', plan: 'professional', branches: 2, users: 6, joinedDate: '2024-11-05', lastActive: '2026-08-15', monthlyRevenue: 0, currency: 'RWF', storageUsed: 3.1, storageLimit: 10 },
  { id: 'b007', name: 'Dodoma Builders Mart', owner: 'Salim Juma', email: 'salim@dodomabuilders.co.tz', phone: '+255 767 890 123', country: 'Tanzania', city: 'Dodoma', status: 'trial', plan: 'starter', branches: 1, users: 2, joinedDate: '2026-09-01', lastActive: '2026-09-09', monthlyRevenue: 1200000, currency: 'TZS', storageUsed: 0.1, storageLimit: 5 },
  { id: 'b008', name: 'Kisumu Hardware Palace', owner: 'Amos Ochieng', email: 'amos@kisumuhardware.co.ke', phone: '+254 710 567 890', country: 'Kenya', city: 'Kisumu', status: 'inactive', plan: 'starter', branches: 1, users: 2, joinedDate: '2025-03-20', lastActive: '2026-06-30', monthlyRevenue: 0, currency: 'KES', storageUsed: 0.4, storageLimit: 5 },
];

const statusConfig: Record<BusinessStatus, { label: string; color: string; icon: React.ReactNode }> = {
  active:    { label: 'Active',    color: 'bg-success/10 text-success border-success/20',   icon: <CheckCircle2 size={13} /> },
  trial:     { label: 'Trial',     color: 'bg-info/10 text-info border-info/20',             icon: <Clock size={13} /> },
  suspended: { label: 'Suspended', color: 'bg-danger/10 text-danger border-danger/20',       icon: <XCircle size={13} /> },
  inactive:  { label: 'Inactive',  color: 'bg-muted text-muted-foreground border-border',    icon: <AlertCircle size={13} /> },
};

const planConfig: Record<PlanType, { label: string; color: string; icon: React.ReactNode }> = {
  starter:      { label: 'Starter',      color: 'bg-muted text-muted-foreground border-border',          icon: <Zap size={12} /> },
  professional: { label: 'Professional', color: 'bg-blue-500/10 text-blue-600 border-blue-200',          icon: <Star size={12} /> },
  enterprise:   { label: 'Enterprise',   color: 'bg-purple-500/10 text-purple-600 border-purple-200',    icon: <Shield size={12} /> },
};

interface PlatformMetric {
  label: string;
  value: string | number;
  change: string;
  positive: boolean;
  icon: React.ReactNode;
  color: string;
}

const platformMetrics: PlatformMetric[] = [
  { label: 'Total Businesses', value: 8, change: '+2 this month', positive: true, icon: <Building2 size={18} />, color: 'text-primary' },
  { label: 'Active Businesses', value: 5, change: '62.5% active rate', positive: true, icon: <CheckCircle2 size={18} />, color: 'text-success' },
  { label: 'Total Users', value: 42, change: '+8 this month', positive: true, icon: <Users size={18} />, color: 'text-info' },
  { label: 'On Trial', value: 2, change: 'Convert before expiry', positive: false, icon: <Clock size={18} />, color: 'text-warning' },
];

export default function SuperAdminPage() {
  const [layout, setLayout] = useState<DashboardLayout>('classic');
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState<BusinessStatus | 'all'>('all');
  const [planFilter, setPlanFilter] = useState<PlanType | 'all'>('all');
  const [selectedBusiness, setSelectedBusiness] = useState<Business | null>(null);

  const filtered = businesses.filter(b => {
    const matchStatus = statusFilter === 'all' || b.status === statusFilter;
    const matchPlan = planFilter === 'all' || b.plan === planFilter;
    const matchSearch = !search || b.name.toLowerCase().includes(search.toLowerCase()) || b.owner.toLowerCase().includes(search.toLowerCase()) || b.city.toLowerCase().includes(search.toLowerCase());
    return matchStatus && matchPlan && matchSearch;
  });

  const totalUsers = businesses.reduce((s, b) => s + b.users, 0);
  const totalBranches = businesses.reduce((s, b) => s + b.branches, 0);

  return (
    <AppLayout layout={layout} onLayoutChange={setLayout}>
      <div className="p-4 md:p-6 space-y-5">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <div className="flex items-center gap-2 mb-1">
              <div className="w-8 h-8 rounded-lg bg-purple-500/10 flex items-center justify-center">
                <Shield size={18} className="text-purple-600" />
              </div>
              <h1 className="text-xl font-bold text-foreground">Super Admin</h1>
            </div>
            <p className="text-sm text-muted-foreground">Platform-level dashboard — manage all businesses on HardFlow</p>
          </div>
          <div className="flex items-center gap-2">
            <button className="flex items-center gap-1.5 px-3 py-2 text-sm rounded-lg border border-border bg-card hover:bg-muted text-foreground transition-colors">
              <BarChart3 size={15} /> Platform Report
            </button>
            <button className="flex items-center gap-1.5 px-4 py-2 bg-primary text-primary-foreground text-sm font-medium rounded-lg hover:bg-primary/90 transition-colors">
              <Plus size={15} /> Add Business
            </button>
          </div>
        </div>

        {/* Platform Metrics */}
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
          {platformMetrics.map((m, i) => (
            <div key={i} className="bg-card border border-border rounded-xl p-4">
              <div className="flex items-start justify-between mb-3">
                <div className={`w-9 h-9 rounded-xl bg-muted/50 flex items-center justify-center ${m.color}`}>
                  {m.icon}
                </div>
                <span className={`text-xs font-medium flex items-center gap-0.5 ${m.positive ? 'text-success' : 'text-warning'}`}>
                  {m.positive ? <TrendingUp size={11} /> : <AlertTriangle size={11} />}
                </span>
              </div>
              <p className="text-2xl font-bold text-foreground">{m.value}</p>
              <p className="text-xs text-muted-foreground mt-0.5">{m.label}</p>
              <p className="text-xs text-muted-foreground mt-1 opacity-70">{m.change}</p>
            </div>
          ))}
        </div>

        {/* Plan Distribution */}
        <div className="grid grid-cols-3 gap-3">
          {(['starter', 'professional', 'enterprise'] as PlanType[]).map(plan => {
            const count = businesses.filter(b => b.plan === plan).length;
            const cfg = planConfig[plan];
            return (
              <button
                key={plan}
                onClick={() => setPlanFilter(planFilter === plan ? 'all' : plan)}
                className={`flex items-center gap-3 p-3 rounded-xl border transition-all text-left ${
                  planFilter === plan ? 'ring-2 ring-primary border-primary/30 bg-primary/5' : 'border-border bg-card hover:bg-muted/50'
                }`}
              >
                <div className={`w-9 h-9 rounded-lg flex items-center justify-center ${cfg.color}`}>
                  {cfg.icon}
                </div>
                <div>
                  <p className="text-xs text-muted-foreground">{cfg.label}</p>
                  <p className="text-xl font-bold text-foreground">{count}</p>
                </div>
              </button>
            );
          })}
        </div>

        {/* Search & Filters */}
        <div className="flex flex-col sm:flex-row gap-2">
          <div className="relative flex-1">
            <Search size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
            <input
              type="text"
              placeholder="Search businesses, owners, or cities..."
              value={search}
              onChange={e => setSearch(e.target.value)}
              className="w-full pl-9 pr-4 py-2 text-sm bg-card border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground placeholder:text-muted-foreground"
            />
          </div>
          <div className="flex gap-1 p-1 bg-muted/50 rounded-lg border border-border">
            {(['all', 'active', 'trial', 'suspended', 'inactive'] as const).map(s => (
              <button
                key={s}
                onClick={() => setStatusFilter(s)}
                className={`px-3 py-1.5 text-xs font-medium rounded-md capitalize transition-all ${
                  statusFilter === s ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'
                }`}
              >
                {s === 'all' ? 'All' : s.charAt(0).toUpperCase() + s.slice(1)}
              </button>
            ))}
          </div>
        </div>

        {/* Businesses Table */}
        <div className="bg-card border border-border rounded-xl overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border bg-muted/30">
                  <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground">Business</th>
                  <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground">Location</th>
                  <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground">Plan</th>
                  <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground">Status</th>
                  <th className="text-center px-4 py-3 text-xs font-semibold text-muted-foreground">Branches</th>
                  <th className="text-center px-4 py-3 text-xs font-semibold text-muted-foreground">Users</th>
                  <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground">Last Active</th>
                  <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground">Storage</th>
                  <th className="text-right px-4 py-3 text-xs font-semibold text-muted-foreground">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {filtered.map(biz => {
                  const statusCfg = statusConfig[biz.status];
                  const planCfg = planConfig[biz.plan];
                  const storagePercent = Math.round((biz.storageUsed / biz.storageLimit) * 100);
                  return (
                    <tr key={biz.id} className="hover:bg-muted/30 transition-colors">
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-2.5">
                          <div className="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                            <Building2 size={14} className="text-primary" />
                          </div>
                          <div>
                            <p className="font-semibold text-foreground text-xs">{biz.name}</p>
                            <p className="text-xs text-muted-foreground">{biz.owner}</p>
                          </div>
                        </div>
                      </td>
                      <td className="px-4 py-3">
                        <p className="text-xs text-foreground flex items-center gap-1">
                          <Globe size={11} className="text-muted-foreground" /> {biz.city}, {biz.country}
                        </p>
                      </td>
                      <td className="px-4 py-3">
                        <span className={`flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full border w-fit ${planCfg.color}`}>
                          {planCfg.icon} {planCfg.label}
                        </span>
                      </td>
                      <td className="px-4 py-3">
                        <span className={`flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full border w-fit ${statusCfg.color}`}>
                          {statusCfg.icon} {statusCfg.label}
                        </span>
                      </td>
                      <td className="px-4 py-3 text-center">
                        <span className="font-semibold text-foreground text-sm">{biz.branches}</span>
                      </td>
                      <td className="px-4 py-3 text-center">
                        <span className="font-semibold text-foreground text-sm">{biz.users}</span>
                      </td>
                      <td className="px-4 py-3 text-xs text-muted-foreground">{biz.lastActive}</td>
                      <td className="px-4 py-3">
                        <div className="w-20">
                          <div className="flex justify-between text-xs text-muted-foreground mb-1">
                            <span>{biz.storageUsed}GB</span>
                            <span>{storagePercent}%</span>
                          </div>
                          <div className="h-1.5 bg-muted rounded-full overflow-hidden">
                            <div
                              className={`h-full rounded-full ${storagePercent > 80 ? 'bg-danger' : storagePercent > 60 ? 'bg-warning' : 'bg-success'}`}
                              style={{ width: `${storagePercent}%` }}
                            />
                          </div>
                        </div>
                      </td>
                      <td className="px-4 py-3">
                        <div className="flex items-center justify-end gap-1">
                          <button
                            onClick={() => setSelectedBusiness(selectedBusiness?.id === biz.id ? null : biz)}
                            className="w-7 h-7 flex items-center justify-center rounded-lg border border-border bg-muted/50 hover:bg-muted text-muted-foreground hover:text-foreground transition-colors"
                          >
                            <Eye size={13} />
                          </button>
                          <button className="w-7 h-7 flex items-center justify-center rounded-lg border border-border bg-muted/50 hover:bg-muted text-muted-foreground hover:text-foreground transition-colors">
                            <Settings size={13} />
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>

        {/* Business Detail Panel */}
        {selectedBusiness && (
          <div className="bg-card border border-border rounded-2xl p-5">
            <div className="flex items-start justify-between mb-4">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                  <Building2 size={22} className="text-primary" />
                </div>
                <div>
                  <h2 className="font-bold text-foreground">{selectedBusiness.name}</h2>
                  <p className="text-sm text-muted-foreground">{selectedBusiness.owner} · {selectedBusiness.email}</p>
                </div>
              </div>
              <button onClick={() => setSelectedBusiness(null)} className="text-muted-foreground hover:text-foreground">
                <XCircle size={18} />
              </button>
            </div>
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
              <div className="bg-muted/40 rounded-xl p-3">
                <p className="text-xs text-muted-foreground mb-1">Plan</p>
                <p className="font-semibold text-foreground capitalize">{selectedBusiness.plan}</p>
              </div>
              <div className="bg-muted/40 rounded-xl p-3">
                <p className="text-xs text-muted-foreground mb-1">Branches</p>
                <p className="font-semibold text-foreground">{selectedBusiness.branches}</p>
              </div>
              <div className="bg-muted/40 rounded-xl p-3">
                <p className="text-xs text-muted-foreground mb-1">Users</p>
                <p className="font-semibold text-foreground">{selectedBusiness.users}</p>
              </div>
              <div className="bg-muted/40 rounded-xl p-3">
                <p className="text-xs text-muted-foreground mb-1">Joined</p>
                <p className="font-semibold text-foreground">{selectedBusiness.joinedDate}</p>
              </div>
            </div>
            <div className="flex flex-wrap gap-2">
              <button className="flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg bg-primary/10 text-primary border border-primary/20 hover:bg-primary/20 transition-colors">
                <Eye size={12} /> View Dashboard
              </button>
              <button className="flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg border border-border bg-muted/50 hover:bg-muted text-foreground transition-colors">
                <Settings size={12} /> Manage Settings
              </button>
              {selectedBusiness.status === 'active' && (
                <button className="flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg bg-danger/10 text-danger border border-danger/20 hover:bg-danger/20 transition-colors">
                  <XCircle size={12} /> Suspend
                </button>
              )}
              {selectedBusiness.status === 'suspended' && (
                <button className="flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg bg-success/10 text-success border border-success/20 hover:bg-success/20 transition-colors">
                  <CheckCircle2 size={12} /> Reactivate
                </button>
              )}
              {selectedBusiness.status === 'trial' && (
                <button className="flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg bg-info/10 text-info border border-info/20 hover:bg-info/20 transition-colors">
                  <Star size={12} /> Upgrade Plan
                </button>
              )}
            </div>
          </div>
        )}
      </div>
    </AppLayout>
  );
}
