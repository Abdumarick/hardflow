'use client';
import React, { useState } from 'react';
import AppLayout from '@/components/AppLayout';
import StatusBadge from '@/components/ui/StatusBadge';
import { Search, Plus, Edit2, UserX, UserCheck, Phone, Mail, MapPin, ChevronDown, X, Check, Users, Shield,  } from 'lucide-react';

type UserRole = 'owner' | 'branch_manager' | 'cashier' | 'storekeeper' | 'accountant' | 'viewer';

interface StaffMember {
  id: string;
  name: string;
  initials: string;
  email: string;
  phone: string;
  role: UserRole;
  branch: string;
  active: boolean;
  joinedDate: string;
  lastLogin: string;
}

const ROLE_META: Record<UserRole, { label: string; color: string; bg: string }> = {
  owner: { label: 'Owner', color: 'text-purple-700', bg: 'bg-purple-100' },
  branch_manager: { label: 'Branch Manager', color: 'text-blue-700', bg: 'bg-blue-100' },
  cashier: { label: 'Cashier', color: 'text-green-700', bg: 'bg-green-100' },
  storekeeper: { label: 'Storekeeper', color: 'text-amber-700', bg: 'bg-amber-100' },
  accountant: { label: 'Accountant', color: 'text-rose-700', bg: 'bg-rose-100' },
  viewer: { label: 'Viewer', color: 'text-gray-600', bg: 'bg-gray-100' },
};

const BRANCHES = ['All Branches', 'Dar es Salaam Main', 'Mwanza Branch', 'Arusha Branch', 'Dodoma Branch'];

const INITIAL_STAFF: StaffMember[] = [
  { id: '1', name: 'James Mwangi', initials: 'JM', email: 'owner@hardflow.co', phone: '+255 712 345 678', role: 'owner', branch: 'Dar es Salaam Main', active: true, joinedDate: '2022-01-10', lastLogin: '2026-09-05' },
  { id: '2', name: 'Amina Saleh', initials: 'AS', email: 'manager@hardflow.co', phone: '+255 754 987 321', role: 'branch_manager', branch: 'Mwanza Branch', active: true, joinedDate: '2022-03-15', lastLogin: '2026-09-04' },
  { id: '3', name: 'Peter Odhiambo', initials: 'PO', email: 'cashier@hardflow.co', phone: '+255 765 111 222', role: 'cashier', branch: 'Dar es Salaam Main', active: true, joinedDate: '2023-06-01', lastLogin: '2026-09-05' },
  { id: '4', name: 'Grace Kimani', initials: 'GK', email: 'accountant@hardflow.co', phone: '+255 744 333 444', role: 'accountant', branch: 'Dar es Salaam Main', active: true, joinedDate: '2023-02-20', lastLogin: '2026-09-03' },
  { id: '5', name: 'David Njoroge', initials: 'DN', email: 'david.n@hardflow.co', phone: '+255 788 555 666', role: 'storekeeper', branch: 'Arusha Branch', active: true, joinedDate: '2023-08-12', lastLogin: '2026-09-02' },
  { id: '6', name: 'Fatuma Hassan', initials: 'FH', email: 'fatuma.h@hardflow.co', phone: '+255 711 777 888', role: 'cashier', branch: 'Mwanza Branch', active: false, joinedDate: '2023-11-05', lastLogin: '2026-07-20' },
  { id: '7', name: 'Samuel Otieno', initials: 'SO', email: 'samuel.o@hardflow.co', phone: '+255 769 999 000', role: 'branch_manager', branch: 'Arusha Branch', active: true, joinedDate: '2022-09-18', lastLogin: '2026-09-04' },
  { id: '8', name: 'Lilian Mwamba', initials: 'LM', email: 'lilian.m@hardflow.co', phone: '+255 733 222 111', role: 'viewer', branch: 'Dodoma Branch', active: false, joinedDate: '2024-01-08', lastLogin: '2026-06-15' },
  { id: '9', name: 'Hassan Ally', initials: 'HA', email: 'hassan.a@hardflow.co', phone: '+255 756 444 333', role: 'storekeeper', branch: 'Dar es Salaam Main', active: true, joinedDate: '2024-03-22', lastLogin: '2026-09-05' },
  { id: '10', name: 'Rose Mwangi', initials: 'RM', email: 'rose.m@hardflow.co', phone: '+255 742 666 555', role: 'accountant', branch: 'Mwanza Branch', active: true, joinedDate: '2023-05-14', lastLogin: '2026-09-01' },
];

interface EditModalProps {
  member: StaffMember;
  onClose: () => void;
  onSave: (updated: StaffMember) => void;
}

function EditModal({ member, onClose, onSave }: EditModalProps) {
  const [form, setForm] = useState({ ...member });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSave(form);
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
      <div className="bg-card border border-border rounded-xl shadow-xl w-full max-w-md">
        <div className="flex items-center justify-between px-5 py-4 border-b border-border">
          <h2 className="text-base font-semibold text-foreground">Edit Staff Member</h2>
          <button onClick={onClose} className="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-muted transition-colors text-muted-foreground">
            <X size={16} />
          </button>
        </div>
        <form onSubmit={handleSubmit} className="p-5 space-y-4">
          <div className="grid grid-cols-2 gap-3">
            <div className="col-span-2">
              <label className="block text-xs font-medium text-muted-foreground mb-1">Full Name</label>
              <input
                type="text"
                value={form.name}
                onChange={e => setForm(f => ({ ...f, name: e.target.value }))}
                className="w-full px-3 py-2 text-sm bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground"
                required
              />
            </div>
            <div>
              <label className="block text-xs font-medium text-muted-foreground mb-1">Email</label>
              <input
                type="email"
                value={form.email}
                onChange={e => setForm(f => ({ ...f, email: e.target.value }))}
                className="w-full px-3 py-2 text-sm bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground"
                required
              />
            </div>
            <div>
              <label className="block text-xs font-medium text-muted-foreground mb-1">Phone</label>
              <input
                type="text"
                value={form.phone}
                onChange={e => setForm(f => ({ ...f, phone: e.target.value }))}
                className="w-full px-3 py-2 text-sm bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground"
              />
            </div>
            <div>
              <label className="block text-xs font-medium text-muted-foreground mb-1">Role</label>
              <select
                value={form.role}
                onChange={e => setForm(f => ({ ...f, role: e.target.value as UserRole }))}
                className="w-full px-3 py-2 text-sm bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground"
              >
                {(Object.keys(ROLE_META) as UserRole[]).map(r => (
                  <option key={r} value={r}>{ROLE_META[r].label}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-xs font-medium text-muted-foreground mb-1">Branch</label>
              <select
                value={form.branch}
                onChange={e => setForm(f => ({ ...f, branch: e.target.value }))}
                className="w-full px-3 py-2 text-sm bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground"
              >
                {BRANCHES.filter(b => b !== 'All Branches').map(b => (
                  <option key={b} value={b}>{b}</option>
                ))}
              </select>
            </div>
          </div>
          <div className="flex items-center gap-3 pt-1">
            <button type="button" onClick={onClose} className="flex-1 px-4 py-2 text-sm font-medium border border-border rounded-lg hover:bg-muted transition-colors text-foreground">
              Cancel
            </button>
            <button type="submit" className="flex-1 px-4 py-2 text-sm font-medium bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors flex items-center justify-center gap-2">
              <Check size={14} /> Save Changes
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

export default function StaffPage() {
  const [staff, setStaff] = useState<StaffMember[]>(INITIAL_STAFF);
  const [search, setSearch] = useState('');
  const [roleFilter, setRoleFilter] = useState<string>('All Roles');
  const [branchFilter, setBranchFilter] = useState<string>('All Branches');
  const [statusFilter, setStatusFilter] = useState<string>('All');
  const [editingMember, setEditingMember] = useState<StaffMember | null>(null);

  const filtered = staff.filter(m => {
    const matchSearch = m.name.toLowerCase().includes(search.toLowerCase()) ||
      m.email.toLowerCase().includes(search.toLowerCase()) ||
      m.phone.includes(search);
    const matchRole = roleFilter === 'All Roles' || m.role === roleFilter;
    const matchBranch = branchFilter === 'All Branches' || m.branch === branchFilter;
    const matchStatus = statusFilter === 'All' || (statusFilter === 'Active' ? m.active : !m.active);
    return matchSearch && matchRole && matchBranch && matchStatus;
  });

  const handleToggleActive = (id: string) => {
    setStaff(prev => prev.map(m => m.id === id ? { ...m, active: !m.active } : m));
  };

  const handleSave = (updated: StaffMember) => {
    setStaff(prev => prev.map(m => m.id === updated.id ? updated : m));
    setEditingMember(null);
  };

  const activeCount = staff.filter(m => m.active).length;
  const inactiveCount = staff.filter(m => !m.active).length;

  return (
    <AppLayout>
      <div className="p-4 md:p-6 space-y-5">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-bold text-foreground">Staff Management</h1>
            <p className="text-sm text-muted-foreground mt-0.5">Manage team members, roles, and access across branches</p>
          </div>
          <button className="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground text-sm font-medium rounded-lg hover:bg-primary/90 transition-colors shrink-0">
            <Plus size={15} /> Add Staff Member
          </button>
        </div>

        {/* Summary tiles */}
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
          {[
            { label: 'Total Staff', value: staff.length, icon: <Users size={16} />, color: 'text-primary', bg: 'bg-primary/10' },
            { label: 'Active', value: activeCount, icon: <UserCheck size={16} />, color: 'text-success', bg: 'bg-success/10' },
            { label: 'Inactive', value: inactiveCount, icon: <UserX size={16} />, color: 'text-muted-foreground', bg: 'bg-muted' },
            { label: 'Roles', value: Object.keys(ROLE_META).length, icon: <Shield size={16} />, color: 'text-warning', bg: 'bg-warning/10' },
          ].map(tile => (
            <div key={tile.label} className="bg-card border border-border rounded-xl px-4 py-3 flex items-center gap-3">
              <div className={`w-9 h-9 rounded-lg ${tile.bg} flex items-center justify-center ${tile.color} shrink-0`}>
                {tile.icon}
              </div>
              <div>
                <p className="text-xs text-muted-foreground">{tile.label}</p>
                <p className="text-lg font-bold text-foreground leading-tight">{tile.value}</p>
              </div>
            </div>
          ))}
        </div>

        {/* Filters */}
        <div className="bg-card border border-border rounded-xl p-3 flex flex-col sm:flex-row gap-2">
          <div className="relative flex-1">
            <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
            <input
              type="text"
              placeholder="Search by name, email or phone…"
              value={search}
              onChange={e => setSearch(e.target.value)}
              className="w-full pl-8 pr-3 py-2 text-sm bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground placeholder:text-muted-foreground"
            />
          </div>
          <div className="flex gap-2 flex-wrap">
            {/* Role filter */}
            <div className="relative">
              <select
                value={roleFilter}
                onChange={e => setRoleFilter(e.target.value)}
                className="appearance-none pl-3 pr-7 py-2 text-sm bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground cursor-pointer"
              >
                <option>All Roles</option>
                {(Object.keys(ROLE_META) as UserRole[]).map(r => (
                  <option key={r} value={r}>{ROLE_META[r].label}</option>
                ))}
              </select>
              <ChevronDown size={12} className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none" />
            </div>
            {/* Branch filter */}
            <div className="relative">
              <select
                value={branchFilter}
                onChange={e => setBranchFilter(e.target.value)}
                className="appearance-none pl-3 pr-7 py-2 text-sm bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground cursor-pointer"
              >
                {BRANCHES.map(b => <option key={b}>{b}</option>)}
              </select>
              <ChevronDown size={12} className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none" />
            </div>
            {/* Status filter */}
            <div className="relative">
              <select
                value={statusFilter}
                onChange={e => setStatusFilter(e.target.value)}
                className="appearance-none pl-3 pr-7 py-2 text-sm bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground cursor-pointer"
              >
                <option>All</option>
                <option>Active</option>
                <option>Inactive</option>
              </select>
              <ChevronDown size={12} className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none" />
            </div>
          </div>
        </div>

        {/* Table — desktop */}
        <div className="hidden md:block bg-card border border-border rounded-xl overflow-hidden">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-border bg-muted/40">
                <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Member</th>
                <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Role</th>
                <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Branch</th>
                <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Contact</th>
                <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Last Login</th>
                <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Status</th>
                <th className="text-right px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border">
              {filtered.length === 0 ? (
                <tr>
                  <td colSpan={7} className="text-center py-12 text-muted-foreground text-sm">
                    No staff members match your filters.
                  </td>
                </tr>
              ) : filtered.map(member => {
                const roleMeta = ROLE_META[member.role];
                return (
                  <tr key={member.id} className="hover:bg-muted/30 transition-colors">
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-3">
                        <div className={`w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold shrink-0 ${member.active ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'}`}>
                          {member.initials}
                        </div>
                        <div>
                          <p className="font-medium text-foreground">{member.name}</p>
                          <p className="text-xs text-muted-foreground">{member.email}</p>
                        </div>
                      </div>
                    </td>
                    <td className="px-4 py-3">
                      <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${roleMeta.bg} ${roleMeta.color}`}>
                        {roleMeta.label}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-1.5 text-sm text-foreground">
                        <MapPin size={12} className="text-muted-foreground shrink-0" />
                        {member.branch}
                      </div>
                    </td>
                    <td className="px-4 py-3">
                      <div className="space-y-0.5">
                        <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
                          <Phone size={11} /> {member.phone}
                        </div>
                        <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
                          <Mail size={11} /> {member.email}
                        </div>
                      </div>
                    </td>
                    <td className="px-4 py-3 text-sm text-muted-foreground">{member.lastLogin}</td>
                    <td className="px-4 py-3">
                      <StatusBadge status={member.active ? 'active' : 'inactive'} />
                    </td>
                    <td className="px-4 py-3">
                      <div className="flex items-center justify-end gap-1">
                        <button
                          onClick={() => setEditingMember(member)}
                          className="w-8 h-8 flex items-center justify-center rounded-lg text-muted-foreground hover:text-primary hover:bg-primary/10 transition-colors"
                          title="Edit member"
                        >
                          <Edit2 size={14} />
                        </button>
                        <button
                          onClick={() => handleToggleActive(member.id)}
                          className={`w-8 h-8 flex items-center justify-center rounded-lg transition-colors ${
                            member.active
                              ? 'text-muted-foreground hover:text-danger hover:bg-danger/10'
                              : 'text-muted-foreground hover:text-success hover:bg-success/10'
                          }`}
                          title={member.active ? 'Deactivate member' : 'Activate member'}
                        >
                          {member.active ? <UserX size={14} /> : <UserCheck size={14} />}
                        </button>
                      </div>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
          {filtered.length > 0 && (
            <div className="px-4 py-3 border-t border-border bg-muted/20 text-xs text-muted-foreground">
              Showing {filtered.length} of {staff.length} staff members
            </div>
          )}
        </div>

        {/* Cards — mobile */}
        <div className="md:hidden space-y-3">
          {filtered.length === 0 ? (
            <div className="text-center py-12 text-muted-foreground text-sm bg-card border border-border rounded-xl">
              No staff members match your filters.
            </div>
          ) : filtered.map(member => {
            const roleMeta = ROLE_META[member.role];
            return (
              <div key={member.id} className="bg-card border border-border rounded-xl p-4 space-y-3">
                <div className="flex items-start justify-between gap-2">
                  <div className="flex items-center gap-3">
                    <div className={`w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold shrink-0 ${member.active ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'}`}>
                      {member.initials}
                    </div>
                    <div>
                      <p className="font-semibold text-foreground text-sm">{member.name}</p>
                      <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${roleMeta.bg} ${roleMeta.color}`}>
                        {roleMeta.label}
                      </span>
                    </div>
                  </div>
                  <StatusBadge status={member.active ? 'active' : 'inactive'} />
                </div>
                <div className="grid grid-cols-2 gap-2 text-xs text-muted-foreground">
                  <div className="flex items-center gap-1.5">
                    <MapPin size={11} className="shrink-0" /> {member.branch}
                  </div>
                  <div className="flex items-center gap-1.5">
                    <Phone size={11} className="shrink-0" /> {member.phone}
                  </div>
                  <div className="flex items-center gap-1.5 col-span-2">
                    <Mail size={11} className="shrink-0" /> {member.email}
                  </div>
                </div>
                <div className="flex items-center justify-between pt-1 border-t border-border">
                  <span className="text-xs text-muted-foreground">Last login: {member.lastLogin}</span>
                  <div className="flex items-center gap-1">
                    <button
                      onClick={() => setEditingMember(member)}
                      className="w-8 h-8 flex items-center justify-center rounded-lg text-muted-foreground hover:text-primary hover:bg-primary/10 transition-colors"
                    >
                      <Edit2 size={14} />
                    </button>
                    <button
                      onClick={() => handleToggleActive(member.id)}
                      className={`w-8 h-8 flex items-center justify-center rounded-lg transition-colors ${
                        member.active
                          ? 'text-muted-foreground hover:text-danger hover:bg-danger/10'
                          : 'text-muted-foreground hover:text-success hover:bg-success/10'
                      }`}
                    >
                      {member.active ? <UserX size={14} /> : <UserCheck size={14} />}
                    </button>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {editingMember && (
        <EditModal
          member={editingMember}
          onClose={() => setEditingMember(null)}
          onSave={handleSave}
        />
      )}
    </AppLayout>
  );
}
