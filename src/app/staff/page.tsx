'use client';
import React, { useState } from 'react';
import AppLayout from '@/components/AppLayout';
import StatusBadge from '@/components/ui/StatusBadge';
import {
  Search, Plus, Edit2, UserX, UserCheck, Phone, Mail, MapPin,
  ChevronDown, X, Check, Users, Shield, Eye, Lock, Unlock,
  Key, ChevronRight, ArrowLeft, Save,
} from 'lucide-react';

// ─── Types ────────────────────────────────────────────────────────────────────

type UserRole = 'owner' | 'manager' | 'cashier' | 'storekeeper' | 'accountant' | 'salesperson';

type PermissionLevel = 'full' | 'view' | 'none';

interface ModulePermission {
  module: string;
  label: string;
  level: PermissionLevel;
}

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
  permissions: ModulePermission[];
}

// ─── Constants ────────────────────────────────────────────────────────────────

const ROLE_META: Record<UserRole, { label: string; color: string; bg: string; description: string }> = {
  owner: { label: 'Owner', color: 'text-purple-700', bg: 'bg-purple-100', description: 'Full access to all modules and settings' },
  manager: { label: 'Manager', color: 'text-blue-700', bg: 'bg-blue-100', description: 'Manages operations, approvals, and staff' },
  cashier: { label: 'Cashier', color: 'text-green-700', bg: 'bg-green-100', description: 'Handles POS sales and customer transactions' },
  storekeeper: { label: 'Storekeeper', color: 'text-amber-700', bg: 'bg-amber-100', description: 'Manages inventory and stock movements' },
  accountant: { label: 'Accountant', color: 'text-rose-700', bg: 'bg-rose-100', description: 'Handles finances, expenses, and reports' },
  salesperson: { label: 'Salesperson', color: 'text-teal-700', bg: 'bg-teal-100', description: 'Creates quotations and manages customers' },
};

const BRANCHES = ['Dar es Salaam Main', 'Mwanza Branch', 'Arusha Branch', 'Dodoma Branch'];

const ALL_MODULES = [
  { module: 'dashboard', label: 'Dashboard' },
  { module: 'sales_pos', label: 'Sales / POS' },
  { module: 'quotations', label: 'Quotations' },
  { module: 'customers', label: 'Customers' },
  { module: 'customer_debts', label: 'Customer Debts' },
  { module: 'products', label: 'Products' },
  { module: 'inventory', label: 'Inventory' },
  { module: 'inventory_movements', label: 'Inventory Movements' },
  { module: 'purchases', label: 'Purchases' },
  { module: 'suppliers', label: 'Suppliers' },
  { module: 'expenses', label: 'Expenses' },
  { module: 'cash_payments', label: 'Cash & Payments' },
  { module: 'returns', label: 'Returns' },
  { module: 'approvals', label: 'Approvals' },
  { module: 'reports', label: 'Reports' },
  { module: 'staff', label: 'Staff Management' },
  { module: 'settings', label: 'Settings' },
];

const DEFAULT_PERMISSIONS: Record<UserRole, PermissionLevel[]> = {
  owner:       ['full','full','full','full','full','full','full','full','full','full','full','full','full','full','full','full','full'],
  manager:     ['full','full','full','full','full','full','full','full','full','full','full','full','full','full','full','full','view'],
  cashier:     ['view','full','view','full','view','view','view','none','none','none','none','none','none','none','none','none','none'],
  storekeeper: ['view','none','none','view','none','full','full','full','full','full','none','none','view','none','view','none','none'],
  accountant:  ['view','view','view','view','full','view','view','view','view','view','full','full','full','view','full','none','none'],
  salesperson: ['view','full','full','full','view','view','view','none','none','none','none','none','none','none','view','none','none'],
};

function buildDefaultPermissions(role: UserRole): ModulePermission[] {
  return ALL_MODULES.map((m, i) => ({
    module: m.module,
    label: m.label,
    level: DEFAULT_PERMISSIONS[role][i],
  }));
}

const INITIAL_STAFF: StaffMember[] = [
  { id: '1', name: 'James Mwangi', initials: 'JM', email: 'owner@hardflow.co', phone: '+255 712 345 678', role: 'owner', branch: 'Dar es Salaam Main', active: true, joinedDate: '2022-01-10', lastLogin: '2026-09-05', permissions: buildDefaultPermissions('owner') },
  { id: '2', name: 'Amina Saleh', initials: 'AS', email: 'manager@hardflow.co', phone: '+255 754 987 321', role: 'manager', branch: 'Mwanza Branch', active: true, joinedDate: '2022-03-15', lastLogin: '2026-09-04', permissions: buildDefaultPermissions('manager') },
  { id: '3', name: 'Peter Odhiambo', initials: 'PO', email: 'cashier@hardflow.co', phone: '+255 765 111 222', role: 'cashier', branch: 'Dar es Salaam Main', active: true, joinedDate: '2023-06-01', lastLogin: '2026-09-05', permissions: buildDefaultPermissions('cashier') },
  { id: '4', name: 'Grace Kimani', initials: 'GK', email: 'accountant@hardflow.co', phone: '+255 744 333 444', role: 'accountant', branch: 'Dar es Salaam Main', active: true, joinedDate: '2023-02-20', lastLogin: '2026-09-03', permissions: buildDefaultPermissions('accountant') },
  { id: '5', name: 'David Njoroge', initials: 'DN', email: 'david.n@hardflow.co', phone: '+255 788 555 666', role: 'storekeeper', branch: 'Arusha Branch', active: true, joinedDate: '2023-08-12', lastLogin: '2026-09-02', permissions: buildDefaultPermissions('storekeeper') },
  { id: '6', name: 'Fatuma Hassan', initials: 'FH', email: 'fatuma.h@hardflow.co', phone: '+255 711 777 888', role: 'cashier', branch: 'Mwanza Branch', active: false, joinedDate: '2023-11-05', lastLogin: '2026-07-20', permissions: buildDefaultPermissions('cashier') },
  { id: '7', name: 'Samuel Otieno', initials: 'SO', email: 'samuel.o@hardflow.co', phone: '+255 769 999 000', role: 'salesperson', branch: 'Arusha Branch', active: true, joinedDate: '2022-09-18', lastLogin: '2026-09-04', permissions: buildDefaultPermissions('salesperson') },
  { id: '8', name: 'Lilian Mwamba', initials: 'LM', email: 'lilian.m@hardflow.co', phone: '+255 733 222 111', role: 'salesperson', branch: 'Dodoma Branch', active: false, joinedDate: '2024-01-08', lastLogin: '2026-06-15', permissions: buildDefaultPermissions('salesperson') },
  { id: '9', name: 'Hassan Ally', initials: 'HA', email: 'hassan.a@hardflow.co', phone: '+255 756 444 333', role: 'storekeeper', branch: 'Dar es Salaam Main', active: true, joinedDate: '2024-03-22', lastLogin: '2026-09-05', permissions: buildDefaultPermissions('storekeeper') },
  { id: '10', name: 'Rose Mwangi', initials: 'RM', email: 'rose.m@hardflow.co', phone: '+255 742 666 555', role: 'accountant', branch: 'Mwanza Branch', active: true, joinedDate: '2023-05-14', lastLogin: '2026-09-01', permissions: buildDefaultPermissions('accountant') },
];

// ─── Permission Badge ─────────────────────────────────────────────────────────

function PermBadge({ level }: { level: PermissionLevel }) {
  if (level === 'full') return (
    <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-success/10 text-success">
      <Unlock size={10} /> Full
    </span>
  );
  if (level === 'view') return (
    <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-info/10 text-info">
      <Eye size={10} /> View
    </span>
  );
  return (
    <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-muted text-muted-foreground">
      <Lock size={10} /> None
    </span>
  );
}

// ─── Add / Edit Staff Modal ───────────────────────────────────────────────────

interface StaffModalProps {
  member?: StaffMember | null;
  onClose: () => void;
  onSave: (data: StaffMember) => void;
}

function StaffModal({ member, onClose, onSave }: StaffModalProps) {
  const isEdit = !!member;
  const [form, setForm] = useState<StaffMember>(
    member ?? {
      id: String(Date.now()),
      name: '',
      initials: '',
      email: '',
      phone: '',
      role: 'cashier',
      branch: BRANCHES[0],
      active: true,
      joinedDate: new Date().toISOString().split('T')[0],
      lastLogin: '—',
      permissions: buildDefaultPermissions('cashier'),
    }
  );

  const handleRoleChange = (role: UserRole) => {
    setForm(f => ({ ...f, role, permissions: buildDefaultPermissions(role) }));
  };

  const handleNameChange = (name: string) => {
    const parts = name.trim().split(' ');
    const initials = parts.length >= 2
      ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
      : name.slice(0, 2).toUpperCase();
    setForm(f => ({ ...f, name, initials }));
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSave(form);
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
      <div className="bg-card border border-border rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] flex flex-col">
        <div className="flex items-center justify-between px-5 py-4 border-b border-border shrink-0">
          <h2 className="text-base font-semibold text-foreground">
            {isEdit ? 'Edit Employee Profile' : 'Add New Employee'}
          </h2>
          <button onClick={onClose} className="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-muted transition-colors text-muted-foreground">
            <X size={16} />
          </button>
        </div>
        <form onSubmit={handleSubmit} className="flex flex-col flex-1 overflow-hidden">
          <div className="p-5 space-y-4 overflow-y-auto flex-1">
            {/* Name */}
            <div>
              <label className="block text-xs font-medium text-muted-foreground mb-1">Full Name *</label>
              <input
                type="text"
                value={form.name}
                onChange={e => handleNameChange(e.target.value)}
                className="w-full px-3 py-2 text-sm bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground"
                required
                placeholder="e.g. John Doe"
              />
            </div>
            <div className="grid grid-cols-2 gap-3">
              {/* Email */}
              <div>
                <label className="block text-xs font-medium text-muted-foreground mb-1">Email *</label>
                <input
                  type="email"
                  value={form.email}
                  onChange={e => setForm(f => ({ ...f, email: e.target.value }))}
                  className="w-full px-3 py-2 text-sm bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground"
                  required
                  placeholder="email@company.co"
                />
              </div>
              {/* Phone */}
              <div>
                <label className="block text-xs font-medium text-muted-foreground mb-1">Phone</label>
                <input
                  type="text"
                  value={form.phone}
                  onChange={e => setForm(f => ({ ...f, phone: e.target.value }))}
                  className="w-full px-3 py-2 text-sm bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground"
                  placeholder="+255 7xx xxx xxx"
                />
              </div>
              {/* Role */}
              <div>
                <label className="block text-xs font-medium text-muted-foreground mb-1">Role *</label>
                <div className="relative">
                  <select
                    value={form.role}
                    onChange={e => handleRoleChange(e.target.value as UserRole)}
                    className="w-full appearance-none pl-3 pr-7 py-2 text-sm bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground"
                  >
                    {(Object.keys(ROLE_META) as UserRole[]).map(r => (
                      <option key={r} value={r}>{ROLE_META[r].label}</option>
                    ))}
                  </select>
                  <ChevronDown size={12} className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none" />
                </div>
              </div>
              {/* Branch */}
              <div>
                <label className="block text-xs font-medium text-muted-foreground mb-1">Branch *</label>
                <div className="relative">
                  <select
                    value={form.branch}
                    onChange={e => setForm(f => ({ ...f, branch: e.target.value }))}
                    className="w-full appearance-none pl-3 pr-7 py-2 text-sm bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground"
                  >
                    {BRANCHES.map(b => <option key={b}>{b}</option>)}
                  </select>
                  <ChevronDown size={12} className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none" />
                </div>
              </div>
            </div>
            {/* Role description */}
            <div className="px-3 py-2 rounded-lg bg-muted/50 border border-border text-xs text-muted-foreground flex items-start gap-2">
              <Shield size={13} className="shrink-0 mt-0.5 text-primary" />
              <span><strong className="text-foreground">{ROLE_META[form.role].label}:</strong> {ROLE_META[form.role].description}</span>
            </div>
          </div>
          <div className="flex items-center gap-3 px-5 py-4 border-t border-border shrink-0">
            <button type="button" onClick={onClose} className="flex-1 px-4 py-2 text-sm font-medium border border-border rounded-lg hover:bg-muted transition-colors text-foreground">
              Cancel
            </button>
            <button type="submit" className="flex-1 px-4 py-2 text-sm font-medium bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors flex items-center justify-center gap-2">
              <Check size={14} /> {isEdit ? 'Save Changes' : 'Add Employee'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

// ─── Employee Profile / Permissions Panel ─────────────────────────────────────

interface ProfilePanelProps {
  member: StaffMember;
  onBack: () => void;
  onSavePermissions: (id: string, permissions: ModulePermission[]) => void;
  onEdit: (member: StaffMember) => void;
  onToggleActive: (id: string) => void;
}

function ProfilePanel({ member, onBack, onSavePermissions, onEdit, onToggleActive }: ProfilePanelProps) {
  const [perms, setPerms] = useState<ModulePermission[]>(member.permissions);
  const [saved, setSaved] = useState(false);
  const roleMeta = ROLE_META[member.role];

  const cycleLevel = (module: string) => {
    setPerms(prev => prev.map(p => {
      if (p.module !== module) return p;
      const next: PermissionLevel = p.level === 'full' ? 'view' : p.level === 'view' ? 'none' : 'full';
      return { ...p, level: next };
    }));
    setSaved(false);
  };

  const handleSave = () => {
    onSavePermissions(member.id, perms);
    setSaved(true);
    setTimeout(() => setSaved(false), 2000);
  };

  const handleResetToRole = () => {
    setPerms(buildDefaultPermissions(member.role));
    setSaved(false);
  };

  const fullCount = perms.filter(p => p.level === 'full').length;
  const viewCount = perms.filter(p => p.level === 'view').length;
  const noneCount = perms.filter(p => p.level === 'none').length;

  return (
    <div className="space-y-5">
      {/* Back + header */}
      <div className="flex items-center gap-3">
        <button
          onClick={onBack}
          className="w-8 h-8 flex items-center justify-center rounded-lg border border-border hover:bg-muted transition-colors text-muted-foreground"
        >
          <ArrowLeft size={15} />
        </button>
        <div>
          <h1 className="text-xl font-bold text-foreground">Employee Profile</h1>
          <p className="text-sm text-muted-foreground mt-0.5">View and manage profile, role, and module permissions</p>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
        {/* Left — profile card */}
        <div className="lg:col-span-1 space-y-4">
          <div className="bg-card border border-border rounded-xl p-5">
            {/* Avatar + name */}
            <div className="flex flex-col items-center text-center gap-2 pb-4 border-b border-border">
              <div className={`w-16 h-16 rounded-full flex items-center justify-center text-xl font-bold ${member.active ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'}`}>
                {member.initials}
              </div>
              <div>
                <p className="text-base font-bold text-foreground">{member.name}</p>
                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${roleMeta.bg} ${roleMeta.color}`}>
                  {roleMeta.label}
                </span>
              </div>
              <StatusBadge status={member.active ? 'active' : 'inactive'} />
            </div>

            {/* Details */}
            <div className="pt-4 space-y-3 text-sm">
              <div className="flex items-center gap-2.5 text-muted-foreground">
                <Mail size={14} className="shrink-0" />
                <span className="truncate">{member.email}</span>
              </div>
              <div className="flex items-center gap-2.5 text-muted-foreground">
                <Phone size={14} className="shrink-0" />
                <span>{member.phone}</span>
              </div>
              <div className="flex items-center gap-2.5 text-muted-foreground">
                <MapPin size={14} className="shrink-0" />
                <span>{member.branch}</span>
              </div>
              <div className="flex items-center gap-2.5 text-muted-foreground">
                <Key size={14} className="shrink-0" />
                <span>Joined {member.joinedDate}</span>
              </div>
            </div>

            {/* Actions */}
            <div className="pt-4 mt-4 border-t border-border space-y-2">
              <button
                onClick={() => onEdit(member)}
                className="w-full flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium border border-border rounded-lg hover:bg-muted transition-colors text-foreground"
              >
                <Edit2 size={14} /> Edit Profile
              </button>
              <button
                onClick={() => onToggleActive(member.id)}
                className={`w-full flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium rounded-lg transition-colors ${
                  member.active
                    ? 'border border-danger/30 text-danger hover:bg-danger/10' :'border border-success/30 text-success hover:bg-success/10'
                }`}
              >
                {member.active ? <><UserX size={14} /> Deactivate</> : <><UserCheck size={14} /> Activate</>}
              </button>
            </div>
          </div>

          {/* Permission summary */}
          <div className="bg-card border border-border rounded-xl p-4">
            <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-3">Permission Summary</p>
            <div className="space-y-2">
              {[
                { label: 'Full Access', count: fullCount, color: 'text-success', bg: 'bg-success' },
                { label: 'View Only', count: viewCount, color: 'text-info', bg: 'bg-info' },
                { label: 'No Access', count: noneCount, color: 'text-muted-foreground', bg: 'bg-muted-foreground' },
              ].map(s => (
                <div key={s.label} className="flex items-center gap-2">
                  <div className="flex-1 flex items-center gap-2">
                    <span className={`text-xs font-medium ${s.color}`}>{s.label}</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <div className="w-24 h-1.5 bg-muted rounded-full overflow-hidden">
                      <div
                        className={`h-full ${s.bg} rounded-full transition-all`}
                        style={{ width: `${(s.count / ALL_MODULES.length) * 100}%` }}
                      />
                    </div>
                    <span className="text-xs text-muted-foreground w-4 text-right">{s.count}</span>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* Right — permissions table */}
        <div className="lg:col-span-2">
          <div className="bg-card border border-border rounded-xl overflow-hidden">
            <div className="flex items-center justify-between px-5 py-4 border-b border-border">
              <div>
                <p className="text-sm font-semibold text-foreground">Module Permissions</p>
                <p className="text-xs text-muted-foreground mt-0.5">Click a permission badge to cycle: Full → View → None</p>
              </div>
              <div className="flex items-center gap-2">
                <button
                  onClick={handleResetToRole}
                  className="px-3 py-1.5 text-xs font-medium border border-border rounded-lg hover:bg-muted transition-colors text-muted-foreground"
                >
                  Reset to Role
                </button>
                <button
                  onClick={handleSave}
                  className={`px-3 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5 ${
                    saved
                      ? 'bg-success/10 text-success border border-success/30' :'bg-primary text-primary-foreground hover:bg-primary/90'
                  }`}
                >
                  {saved ? <><Check size={12} /> Saved</> : <><Save size={12} /> Save</>}
                </button>
              </div>
            </div>
            <div className="divide-y divide-border">
              {perms.map(perm => (
                <div key={perm.module} className="flex items-center justify-between px-5 py-3 hover:bg-muted/20 transition-colors">
                  <span className="text-sm text-foreground">{perm.label}</span>
                  <button
                    onClick={() => cycleLevel(perm.module)}
                    className="transition-transform hover:scale-105 active:scale-95"
                    title="Click to change permission level"
                  >
                    <PermBadge level={perm.level} />
                  </button>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

// ─── Main Page ────────────────────────────────────────────────────────────────

export default function StaffPage() {
  const [staff, setStaff] = useState<StaffMember[]>(INITIAL_STAFF);
  const [search, setSearch] = useState('');
  const [roleFilter, setRoleFilter] = useState<string>('All Roles');
  const [branchFilter, setBranchFilter] = useState<string>('All Branches');
  const [statusFilter, setStatusFilter] = useState<string>('All');
  const [modalMember, setModalMember] = useState<StaffMember | null | undefined>(undefined); // undefined = closed
  const [profileMember, setProfileMember] = useState<StaffMember | null>(null);

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
    if (profileMember?.id === id) {
      setProfileMember(prev => prev ? { ...prev, active: !prev.active } : prev);
    }
  };

  const handleSaveStaff = (data: StaffMember) => {
    setStaff(prev => {
      const exists = prev.find(m => m.id === data.id);
      if (exists) return prev.map(m => m.id === data.id ? data : m);
      return [...prev, data];
    });
    if (profileMember?.id === data.id) setProfileMember(data);
    setModalMember(undefined);
  };

  const handleSavePermissions = (id: string, permissions: ModulePermission[]) => {
    setStaff(prev => prev.map(m => m.id === id ? { ...m, permissions } : m));
    if (profileMember?.id === id) setProfileMember(prev => prev ? { ...prev, permissions } : prev);
  };

  const activeCount = staff.filter(m => m.active).length;
  const inactiveCount = staff.filter(m => !m.active).length;

  // ── Profile view ──
  if (profileMember) {
    const live = staff.find(m => m.id === profileMember.id) ?? profileMember;
    return (
      <AppLayout>
        <div className="p-4 md:p-6">
          <ProfilePanel
            member={live}
            onBack={() => setProfileMember(null)}
            onSavePermissions={handleSavePermissions}
            onEdit={m => setModalMember(m)}
            onToggleActive={handleToggleActive}
          />
        </div>
        {modalMember !== undefined && (
          <StaffModal
            member={modalMember}
            onClose={() => setModalMember(undefined)}
            onSave={handleSaveStaff}
          />
        )}
      </AppLayout>
    );
  }

  // ── List view ──
  return (
    <AppLayout>
      <div className="p-4 md:p-6 space-y-5">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-bold text-foreground">Staff Management</h1>
            <p className="text-sm text-muted-foreground mt-0.5">Manage employee profiles, roles, and module-level permissions</p>
          </div>
          <button
            onClick={() => setModalMember(null)}
            className="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground text-sm font-medium rounded-lg hover:bg-primary/90 transition-colors shrink-0"
          >
            <Plus size={15} /> Add Employee
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

        {/* Role legend */}
        <div className="bg-card border border-border rounded-xl p-4">
          <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-3">Roles Overview</p>
          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
            {(Object.keys(ROLE_META) as UserRole[]).map(role => {
              const meta = ROLE_META[role];
              const count = staff.filter(m => m.role === role).length;
              return (
                <div key={role} className={`px-3 py-2 rounded-lg ${meta.bg} flex items-center justify-between gap-2`}>
                  <span className={`text-xs font-semibold ${meta.color}`}>{meta.label}</span>
                  <span className={`text-xs font-bold ${meta.color}`}>{count}</span>
                </div>
              );
            })}
          </div>
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
            <div className="relative">
              <select value={roleFilter} onChange={e => setRoleFilter(e.target.value)}
                className="appearance-none pl-3 pr-7 py-2 text-sm bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground cursor-pointer">
                <option>All Roles</option>
                {(Object.keys(ROLE_META) as UserRole[]).map(r => (
                  <option key={r} value={r}>{ROLE_META[r].label}</option>
                ))}
              </select>
              <ChevronDown size={12} className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none" />
            </div>
            <div className="relative">
              <select value={branchFilter} onChange={e => setBranchFilter(e.target.value)}
                className="appearance-none pl-3 pr-7 py-2 text-sm bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground cursor-pointer">
                <option>All Branches</option>
                {BRANCHES.map(b => <option key={b}>{b}</option>)}
              </select>
              <ChevronDown size={12} className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none" />
            </div>
            <div className="relative">
              <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)}
                className="appearance-none pl-3 pr-7 py-2 text-sm bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground cursor-pointer">
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
                <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Employee</th>
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
                    No employees match your filters.
                  </td>
                </tr>
              ) : filtered.map(member => {
                const roleMeta = ROLE_META[member.role];
                return (
                  <tr key={member.id} className="hover:bg-muted/30 transition-colors">
                    <td className="px-4 py-3">
                      <button
                        onClick={() => setProfileMember(member)}
                        className="flex items-center gap-3 text-left hover:opacity-80 transition-opacity"
                      >
                        <div className={`w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold shrink-0 ${member.active ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'}`}>
                          {member.initials}
                        </div>
                        <div>
                          <p className="font-medium text-foreground hover:text-primary transition-colors">{member.name}</p>
                          <p className="text-xs text-muted-foreground">{member.email}</p>
                        </div>
                      </button>
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
                          onClick={() => setProfileMember(member)}
                          className="w-8 h-8 flex items-center justify-center rounded-lg text-muted-foreground hover:text-info hover:bg-info/10 transition-colors"
                          title="View profile & permissions"
                        >
                          <Key size={14} />
                        </button>
                        <button
                          onClick={() => setModalMember(member)}
                          className="w-8 h-8 flex items-center justify-center rounded-lg text-muted-foreground hover:text-primary hover:bg-primary/10 transition-colors"
                          title="Edit employee"
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
                          title={member.active ? 'Deactivate' : 'Activate'}
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
              Showing {filtered.length} of {staff.length} employees
            </div>
          )}
        </div>

        {/* Cards — mobile */}
        <div className="md:hidden space-y-3">
          {filtered.length === 0 ? (
            <div className="text-center py-12 text-muted-foreground text-sm bg-card border border-border rounded-xl">
              No employees match your filters.
            </div>
          ) : filtered.map(member => {
            const roleMeta = ROLE_META[member.role];
            return (
              <div key={member.id} className="bg-card border border-border rounded-xl p-4 space-y-3">
                <div className="flex items-start justify-between gap-2">
                  <button onClick={() => setProfileMember(member)} className="flex items-center gap-3 text-left">
                    <div className={`w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold shrink-0 ${member.active ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'}`}>
                      {member.initials}
                    </div>
                    <div>
                      <p className="font-semibold text-foreground text-sm">{member.name}</p>
                      <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${roleMeta.bg} ${roleMeta.color}`}>
                        {roleMeta.label}
                      </span>
                    </div>
                  </button>
                  <StatusBadge status={member.active ? 'active' : 'inactive'} />
                </div>
                <div className="grid grid-cols-2 gap-2 text-xs text-muted-foreground">
                  <div className="flex items-center gap-1.5"><MapPin size={11} className="shrink-0" /> {member.branch}</div>
                  <div className="flex items-center gap-1.5"><Phone size={11} className="shrink-0" /> {member.phone}</div>
                  <div className="flex items-center gap-1.5 col-span-2"><Mail size={11} className="shrink-0" /> {member.email}</div>
                </div>
                <div className="flex items-center justify-between pt-1 border-t border-border">
                  <span className="text-xs text-muted-foreground">Last login: {member.lastLogin}</span>
                  <div className="flex items-center gap-1">
                    <button onClick={() => setProfileMember(member)} className="w-8 h-8 flex items-center justify-center rounded-lg text-muted-foreground hover:text-info hover:bg-info/10 transition-colors" title="Permissions">
                      <Key size={14} />
                    </button>
                    <button onClick={() => setModalMember(member)} className="w-8 h-8 flex items-center justify-center rounded-lg text-muted-foreground hover:text-primary hover:bg-primary/10 transition-colors">
                      <Edit2 size={14} />
                    </button>
                    <button
                      onClick={() => handleToggleActive(member.id)}
                      className={`w-8 h-8 flex items-center justify-center rounded-lg transition-colors ${member.active ? 'text-muted-foreground hover:text-danger hover:bg-danger/10' : 'text-muted-foreground hover:text-success hover:bg-success/10'}`}
                    >
                      {member.active ? <UserX size={14} /> : <UserCheck size={14} />}
                    </button>
                    <button onClick={() => setProfileMember(member)} className="w-8 h-8 flex items-center justify-center rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted transition-colors">
                      <ChevronRight size={14} />
                    </button>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* Add / Edit modal */}
      {modalMember !== undefined && (
        <StaffModal
          member={modalMember}
          onClose={() => setModalMember(undefined)}
          onSave={handleSaveStaff}
        />
      )}
    </AppLayout>
  );
}
