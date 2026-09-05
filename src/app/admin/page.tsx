'use client';
import React, { useState } from 'react';
import AppLayout, { DashboardLayout } from '@/components/AppLayout';
import KpiCard from '@/components/ui/KpiCard';
import { Shield, Users, GitBranch, Lock, Plus, Search, Edit2, Trash2, ChevronDown, ChevronRight, Check, X, UserCog, Building2, Key, AlertTriangle, CheckCircle2, ToggleLeft, ToggleRight,  } from 'lucide-react';

// ─── Types ────────────────────────────────────────────────────────────────────

interface Permission {
  id: string;
  module: string;
  action: string;
  description: string;
}

interface Role {
  id: string;
  name: string;
  description: string;
  color: string;
  permissions: string[];
  userCount: number;
  isSystem: boolean;
  createdAt: string;
}

interface Branch {
  id: string;
  name: string;
  location: string;
  manager: string;
  status: 'active' | 'inactive';
  userCount: number;
}

interface User {
  id: string;
  name: string;
  email: string;
  role: string;
  branches: string[];
  status: 'active' | 'inactive';
  lastLogin: string;
  avatar: string;
}

// ─── Mock Data ─────────────────────────────────────────────────────────────────

const allPermissions: Permission[] = [
  // Sales
  { id: 'sales.view', module: 'Sales / POS', action: 'View', description: 'Access POS and view sales' },
  { id: 'sales.create', module: 'Sales / POS', action: 'Create', description: 'Process new sales transactions' },
  { id: 'sales.void', module: 'Sales / POS', action: 'Void', description: 'Void or cancel sales' },
  { id: 'sales.discount', module: 'Sales / POS', action: 'Apply Discount', description: 'Apply discounts on sales' },
  // Inventory
  { id: 'inventory.view', module: 'Inventory', action: 'View', description: 'View stock levels and movements' },
  { id: 'inventory.adjust', module: 'Inventory', action: 'Adjust', description: 'Make stock adjustments' },
  { id: 'inventory.transfer', module: 'Inventory', action: 'Transfer', description: 'Transfer stock between branches' },
  // Products
  { id: 'products.view', module: 'Products', action: 'View', description: 'Browse product catalog' },
  { id: 'products.create', module: 'Products', action: 'Create', description: 'Add new products' },
  { id: 'products.edit', module: 'Products', action: 'Edit', description: 'Edit product details and pricing' },
  { id: 'products.delete', module: 'Products', action: 'Delete', description: 'Remove products from catalog' },
  // Purchases
  { id: 'purchases.view', module: 'Purchases', action: 'View', description: 'View purchase orders' },
  { id: 'purchases.create', module: 'Purchases', action: 'Create', description: 'Create purchase orders' },
  { id: 'purchases.receive', module: 'Purchases', action: 'Receive', description: 'Receive goods against POs' },
  // Expenses
  { id: 'expenses.view', module: 'Expenses', action: 'View', description: 'View expense records' },
  { id: 'expenses.create', module: 'Expenses', action: 'Create', description: 'Submit new expenses' },
  { id: 'expenses.approve', module: 'Expenses', action: 'Approve', description: 'Approve or reject expenses' },
  // Customers
  { id: 'customers.view', module: 'Customers', action: 'View', description: 'View customer profiles' },
  { id: 'customers.edit', module: 'Customers', action: 'Edit', description: 'Edit customer information' },
  { id: 'customers.delete', module: 'Customers', action: 'Delete', description: 'Delete customer records' },
  // Reports
  { id: 'reports.view', module: 'Reports', action: 'View', description: 'Access business reports' },
  { id: 'reports.export', module: 'Reports', action: 'Export', description: 'Export report data' },
  // Admin
  { id: 'admin.users', module: 'Admin', action: 'Manage Users', description: 'Create and manage user accounts' },
  { id: 'admin.roles', module: 'Admin', action: 'Manage Roles', description: 'Define roles and permissions' },
  { id: 'admin.branches', module: 'Admin', action: 'Manage Branches', description: 'Configure branch settings' },
  { id: 'admin.settings', module: 'Admin', action: 'System Settings', description: 'Access system configuration' },
];

const initialRoles: Role[] = [
  {
    id: 'role-owner', name: 'Business Owner', description: 'Full system access with all permissions',
    color: 'bg-purple-500', permissions: allPermissions.map(p => p.id), userCount: 1, isSystem: true, createdAt: '2026-01-01',
  },
  {
    id: 'role-manager', name: 'Branch Manager', description: 'Manage branch operations, approve expenses, view reports',
    color: 'bg-blue-500',
    permissions: ['sales.view','sales.create','sales.void','sales.discount','inventory.view','inventory.adjust','inventory.transfer','products.view','products.edit','purchases.view','purchases.create','purchases.receive','expenses.view','expenses.create','expenses.approve','customers.view','customers.edit','reports.view','reports.export'],
    userCount: 2, isSystem: false, createdAt: '2026-01-15',
  },
  {
    id: 'role-cashier', name: 'Cashier', description: 'Process sales, apply discounts, view products',
    color: 'bg-green-500',
    permissions: ['sales.view','sales.create','sales.discount','products.view','customers.view','inventory.view'],
    userCount: 4, isSystem: false, createdAt: '2026-02-01',
  },
  {
    id: 'role-storekeeper', name: 'Storekeeper', description: 'Manage inventory, receive goods, track stock',
    color: 'bg-orange-500',
    permissions: ['inventory.view','inventory.adjust','inventory.transfer','products.view','purchases.view','purchases.receive'],
    userCount: 3, isSystem: false, createdAt: '2026-02-10',
  },
  {
    id: 'role-accountant', name: 'Accountant', description: 'Manage expenses, view reports, handle finances',
    color: 'bg-teal-500',
    permissions: ['expenses.view','expenses.create','expenses.approve','reports.view','reports.export','customers.view','purchases.view'],
    userCount: 1, isSystem: false, createdAt: '2026-03-01',
  },
  {
    id: 'role-viewer', name: 'Read-Only Viewer', description: 'View-only access across all modules',
    color: 'bg-gray-500',
    permissions: ['sales.view','inventory.view','products.view','purchases.view','expenses.view','customers.view','reports.view'],
    userCount: 2, isSystem: false, createdAt: '2026-04-01',
  },
];

const branches: Branch[] = [
  { id: 'br-main', name: 'Dar es Salaam Main', location: 'Kariakoo, Dar es Salaam', manager: 'James Mwangi', status: 'active', userCount: 8 },
  { id: 'br-warehouse', name: 'Warehouse', location: 'Ubungo Industrial Area', manager: 'Peter Odhiambo', status: 'active', userCount: 5 },
  { id: 'br-mwanza', name: 'Mwanza Branch', location: 'Mwanza City Centre', manager: 'Grace Nyambura', status: 'active', userCount: 3 },
  { id: 'br-arusha', name: 'Arusha Branch', location: 'Arusha CBD', manager: 'David Kimani', status: 'inactive', userCount: 0 },
];

const initialUsers: User[] = [
  { id: 'u1', name: 'James Mwangi', email: 'james@karibu.co.tz', role: 'role-owner', branches: ['br-main','br-warehouse','br-mwanza','br-arusha'], status: 'active', lastLogin: '2026-09-05 18:42', avatar: 'JM' },
  { id: 'u2', name: 'Sarah Kimani', email: 'sarah@karibu.co.tz', role: 'role-manager', branches: ['br-main'], status: 'active', lastLogin: '2026-09-05 17:15', avatar: 'SK' },
  { id: 'u3', name: 'Peter Odhiambo', email: 'peter@karibu.co.tz', role: 'role-storekeeper', branches: ['br-warehouse'], status: 'active', lastLogin: '2026-09-05 16:30', avatar: 'PO' },
  { id: 'u4', name: 'Grace Nyambura', email: 'grace@karibu.co.tz', role: 'role-manager', branches: ['br-mwanza'], status: 'active', lastLogin: '2026-09-04 14:20', avatar: 'GN' },
  { id: 'u5', name: 'Ali Hassan', email: 'ali@karibu.co.tz', role: 'role-cashier', branches: ['br-main'], status: 'active', lastLogin: '2026-09-05 19:00', avatar: 'AH' },
  { id: 'u6', name: 'Fatuma Said', email: 'fatuma@karibu.co.tz', role: 'role-cashier', branches: ['br-main'], status: 'active', lastLogin: '2026-09-05 18:55', avatar: 'FS' },
  { id: 'u7', name: 'John Mwenda', email: 'john@karibu.co.tz', role: 'role-cashier', branches: ['br-mwanza'], status: 'active', lastLogin: '2026-09-03 11:00', avatar: 'JW' },
  { id: 'u8', name: 'Mary Achieng', email: 'mary@karibu.co.tz', role: 'role-accountant', branches: ['br-main','br-warehouse'], status: 'active', lastLogin: '2026-09-05 15:45', avatar: 'MA' },
  { id: 'u9', name: 'David Kimani', email: 'david@karibu.co.tz', role: 'role-viewer', branches: ['br-arusha'], status: 'inactive', lastLogin: '2026-08-20 09:30', avatar: 'DK' },
  { id: 'u10', name: 'Rose Wanjiku', email: 'rose@karibu.co.tz', role: 'role-cashier', branches: ['br-main'], status: 'active', lastLogin: '2026-09-05 20:10', avatar: 'RW' },
];

// ─── Helpers ───────────────────────────────────────────────────────────────────

const moduleGroups = Array.from(new Set(allPermissions.map(p => p.module)));

const roleColors: Record<string, string> = {
  'role-owner': 'bg-purple-500',
  'role-manager': 'bg-blue-500',
  'role-cashier': 'bg-green-500',
  'role-storekeeper': 'bg-orange-500',
  'role-accountant': 'bg-teal-500',
  'role-viewer': 'bg-gray-500',
};

// ─── Sub-components ────────────────────────────────────────────────────────────

function PermissionToggle({ checked, onChange, disabled }: { checked: boolean; onChange: () => void; disabled?: boolean }) {
  return (
    <button
      onClick={onChange}
      disabled={disabled}
      className={`w-8 h-4.5 rounded-full transition-colors relative flex items-center ${checked ? 'bg-primary' : 'bg-border'} ${disabled ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer'}`}
      style={{ height: '18px', width: '32px' }}
    >
      <span className={`absolute w-3 h-3 rounded-full bg-white shadow transition-transform ${checked ? 'translate-x-4' : 'translate-x-0.5'}`} />
    </button>
  );
}

function RoleModal({
  role, onClose, onSave,
}: {
  role: Role | null;
  onClose: () => void;
  onSave: (r: Role) => void;
}) {
  const isNew = !role;
  const [name, setName] = useState(role?.name ?? '');
  const [description, setDescription] = useState(role?.description ?? '');
  const [selectedPerms, setSelectedPerms] = useState<string[]>(role?.permissions ?? []);
  const [expandedModules, setExpandedModules] = useState<string[]>(moduleGroups);

  const togglePerm = (id: string) => {
    setSelectedPerms(prev => prev.includes(id) ? prev.filter(p => p !== id) : [...prev, id]);
  };

  const toggleModule = (module: string) => {
    const modulePerms = allPermissions.filter(p => p.module === module).map(p => p.id);
    const allChecked = modulePerms.every(id => selectedPerms.includes(id));
    if (allChecked) {
      setSelectedPerms(prev => prev.filter(id => !modulePerms.includes(id)));
    } else {
      setSelectedPerms(prev => Array.from(new Set([...prev, ...modulePerms])));
    }
  };

  const handleSave = () => {
    if (!name.trim()) return;
    onSave({
      id: role?.id ?? `role-${Date.now()}`,
      name: name.trim(),
      description: description.trim(),
      color: role?.color ?? 'bg-blue-500',
      permissions: selectedPerms,
      userCount: role?.userCount ?? 0,
      isSystem: role?.isSystem ?? false,
      createdAt: role?.createdAt ?? new Date().toISOString().split('T')[0],
    });
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-foreground/30 p-4">
      <div className="bg-card border border-border rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col">
        <div className="flex items-center justify-between px-5 py-4 border-b border-border">
          <h2 className="text-base font-semibold text-foreground">{isNew ? 'Create New Role' : `Edit Role: ${role?.name}`}</h2>
          <button onClick={onClose} className="p-1.5 rounded-lg hover:bg-muted transition-colors"><X size={16} /></button>
        </div>
        <div className="flex-1 overflow-y-auto p-5 space-y-5">
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-muted-foreground mb-1.5">Role Name *</label>
              <input
                value={name}
                onChange={e => setName(e.target.value)}
                placeholder="e.g. Senior Cashier"
                className="w-full px-3 py-2 text-sm bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
              />
            </div>
            <div>
              <label className="block text-xs font-semibold text-muted-foreground mb-1.5">Description</label>
              <input
                value={description}
                onChange={e => setDescription(e.target.value)}
                placeholder="Brief role description"
                className="w-full px-3 py-2 text-sm bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
              />
            </div>
          </div>

          <div>
            <div className="flex items-center justify-between mb-3">
              <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Permissions</p>
              <span className="text-xs text-muted-foreground">{selectedPerms.length} / {allPermissions.length} selected</span>
            </div>
            <div className="space-y-2">
              {moduleGroups.map(module => {
                const modulePerms = allPermissions.filter(p => p.module === module);
                const checkedCount = modulePerms.filter(p => selectedPerms.includes(p.id)).length;
                const allChecked = checkedCount === modulePerms.length;
                const expanded = expandedModules.includes(module);
                return (
                  <div key={module} className="border border-border rounded-lg overflow-hidden">
                    <div
                      className="flex items-center justify-between px-3 py-2.5 bg-muted/30 cursor-pointer hover:bg-muted/50 transition-colors"
                      onClick={() => setExpandedModules(prev => prev.includes(module) ? prev.filter(m => m !== module) : [...prev, module])}
                    >
                      <div className="flex items-center gap-2">
                        <button
                          onClick={e => { e.stopPropagation(); toggleModule(module); }}
                          className={`w-4 h-4 rounded border flex items-center justify-center transition-colors ${allChecked ? 'bg-primary border-primary' : checkedCount > 0 ? 'bg-primary/30 border-primary/50' : 'border-border bg-background'}`}
                        >
                          {allChecked && <Check size={10} className="text-white" />}
                          {!allChecked && checkedCount > 0 && <span className="w-2 h-0.5 bg-primary rounded" />}
                        </button>
                        <span className="text-sm font-medium text-foreground">{module}</span>
                        <span className="text-xs text-muted-foreground">({checkedCount}/{modulePerms.length})</span>
                      </div>
                      {expanded ? <ChevronDown size={14} className="text-muted-foreground" /> : <ChevronRight size={14} className="text-muted-foreground" />}
                    </div>
                    {expanded && (
                      <div className="divide-y divide-border">
                        {modulePerms.map(perm => (
                          <div key={perm.id} className="flex items-center justify-between px-4 py-2 hover:bg-muted/20 transition-colors">
                            <div>
                              <p className="text-sm text-foreground">{perm.action}</p>
                              <p className="text-xs text-muted-foreground">{perm.description}</p>
                            </div>
                            <PermissionToggle
                              checked={selectedPerms.includes(perm.id)}
                              onChange={() => togglePerm(perm.id)}
                            />
                          </div>
                        ))}
                      </div>
                    )}
                  </div>
                );
              })}
            </div>
          </div>
        </div>
        <div className="flex items-center justify-end gap-3 px-5 py-4 border-t border-border">
          <button onClick={onClose} className="btn-outline text-sm px-4 py-2">Cancel</button>
          <button onClick={handleSave} disabled={!name.trim()} className="btn-primary text-sm px-4 py-2 disabled:opacity-50">
            {isNew ? 'Create Role' : 'Save Changes'}
          </button>
        </div>
      </div>
    </div>
  );
}

function UserModal({
  user, roles, onClose, onSave,
}: {
  user: User | null;
  roles: Role[];
  onClose: () => void;
  onSave: (u: User) => void;
}) {
  const isNew = !user;
  const [name, setName] = useState(user?.name ?? '');
  const [email, setEmail] = useState(user?.email ?? '');
  const [roleId, setRoleId] = useState(user?.role ?? roles[0]?.id ?? '');
  const [selectedBranches, setSelectedBranches] = useState<string[]>(user?.branches ?? []);

  const toggleBranch = (id: string) => {
    setSelectedBranches(prev => prev.includes(id) ? prev.filter(b => b !== id) : [...prev, id]);
  };

  const handleSave = () => {
    if (!name.trim() || !email.trim()) return;
    onSave({
      id: user?.id ?? `u${Date.now()}`,
      name: name.trim(),
      email: email.trim(),
      role: roleId,
      branches: selectedBranches,
      status: user?.status ?? 'active',
      lastLogin: user?.lastLogin ?? '—',
      avatar: name.trim().split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2),
    });
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-foreground/30 p-4">
      <div className="bg-card border border-border rounded-xl shadow-xl w-full max-w-lg">
        <div className="flex items-center justify-between px-5 py-4 border-b border-border">
          <h2 className="text-base font-semibold text-foreground">{isNew ? 'Add New User' : `Edit User: ${user?.name}`}</h2>
          <button onClick={onClose} className="p-1.5 rounded-lg hover:bg-muted transition-colors"><X size={16} /></button>
        </div>
        <div className="p-5 space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-muted-foreground mb-1.5">Full Name *</label>
              <input value={name} onChange={e => setName(e.target.value)} placeholder="John Doe" className="w-full px-3 py-2 text-sm bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary" />
            </div>
            <div>
              <label className="block text-xs font-semibold text-muted-foreground mb-1.5">Email *</label>
              <input value={email} onChange={e => setEmail(e.target.value)} placeholder="user@company.co.tz" className="w-full px-3 py-2 text-sm bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary" />
            </div>
          </div>
          <div>
            <label className="block text-xs font-semibold text-muted-foreground mb-1.5">Assigned Role</label>
            <select value={roleId} onChange={e => setRoleId(e.target.value)} className="w-full px-3 py-2 text-sm bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
              {roles.map(r => <option key={r.id} value={r.id}>{r.name}</option>)}
            </select>
          </div>
          <div>
            <label className="block text-xs font-semibold text-muted-foreground mb-2">Branch Access</label>
            <div className="grid grid-cols-2 gap-2">
              {branches.map(branch => (
                <label key={branch.id} className={`flex items-center gap-2 px-3 py-2 rounded-lg border cursor-pointer transition-colors ${selectedBranches.includes(branch.id) ? 'border-primary bg-primary/5' : 'border-border hover:bg-muted/30'}`}>
                  <input type="checkbox" checked={selectedBranches.includes(branch.id)} onChange={() => toggleBranch(branch.id)} className="sr-only" />
                  <div className={`w-4 h-4 rounded border flex items-center justify-center ${selectedBranches.includes(branch.id) ? 'bg-primary border-primary' : 'border-border'}`}>
                    {selectedBranches.includes(branch.id) && <Check size={10} className="text-white" />}
                  </div>
                  <div className="min-w-0">
                    <p className="text-xs font-medium text-foreground truncate">{branch.name}</p>
                    <p className="text-2xs text-muted-foreground truncate">{branch.location}</p>
                  </div>
                </label>
              ))}
            </div>
          </div>
        </div>
        <div className="flex items-center justify-end gap-3 px-5 py-4 border-t border-border">
          <button onClick={onClose} className="btn-outline text-sm px-4 py-2">Cancel</button>
          <button onClick={handleSave} disabled={!name.trim() || !email.trim()} className="btn-primary text-sm px-4 py-2 disabled:opacity-50">
            {isNew ? 'Add User' : 'Save Changes'}
          </button>
        </div>
      </div>
    </div>
  );
}

// ─── Main Page ─────────────────────────────────────────────────────────────────

export default function AdminPage() {
  const [layout] = useState<DashboardLayout>('classic');
  const [activeTab, setActiveTab] = useState<'roles' | 'permissions' | 'users' | 'branches'>('roles');
  const [roles, setRoles] = useState<Role[]>(initialRoles);
  const [users, setUsers] = useState<User[]>(initialUsers);
  const [search, setSearch] = useState('');
  const [roleModal, setRoleModal] = useState<{ open: boolean; role: Role | null }>({ open: false, role: null });
  const [userModal, setUserModal] = useState<{ open: boolean; user: User | null }>({ open: false, user: null });
  const [selectedRole, setSelectedRole] = useState<string | null>(null);
  const [deleteConfirm, setDeleteConfirm] = useState<string | null>(null);

  // KPI counts
  const activeUsers = users.filter(u => u.status === 'active').length;
  const activeBranches = branches.filter(b => b.status === 'active').length;
  const totalPerms = allPermissions.length;

  const tabs = [
    { id: 'roles', label: 'Roles', icon: <Shield size={15} />, count: roles.length },
    { id: 'permissions', label: 'Permissions Matrix', icon: <Key size={15} />, count: totalPerms },
    { id: 'users', label: 'Users', icon: <Users size={15} />, count: users.length },
    { id: 'branches', label: 'Branch Assignments', icon: <GitBranch size={15} />, count: branches.length },
  ] as const;

  // ── Roles Tab ──────────────────────────────────────────────────────────────

  const filteredRoles = roles.filter(r =>
    r.name.toLowerCase().includes(search.toLowerCase()) ||
    r.description.toLowerCase().includes(search.toLowerCase())
  );

  const handleSaveRole = (r: Role) => {
    setRoles(prev => prev.some(x => x.id === r.id) ? prev.map(x => x.id === r.id ? r : x) : [...prev, r]);
    setRoleModal({ open: false, role: null });
  };

  const handleDeleteRole = (id: string) => {
    setRoles(prev => prev.filter(r => r.id !== id));
    setDeleteConfirm(null);
  };

  // ── Users Tab ──────────────────────────────────────────────────────────────

  const filteredUsers = users.filter(u =>
    u.name.toLowerCase().includes(search.toLowerCase()) ||
    u.email.toLowerCase().includes(search.toLowerCase())
  );

  const handleSaveUser = (u: User) => {
    setUsers(prev => prev.some(x => x.id === u.id) ? prev.map(x => x.id === u.id ? u : x) : [...prev, u]);
    setUserModal({ open: false, user: null });
  };

  const toggleUserStatus = (id: string) => {
    setUsers(prev => prev.map(u => u.id === id ? { ...u, status: u.status === 'active' ? 'inactive' : 'active' } : u));
  };

  const getRoleName = (id: string) => roles.find(r => r.id === id)?.name ?? id;
  const getRoleColor = (id: string) => roleColors[id] ?? 'bg-gray-500';
  const getBranchName = (id: string) => branches.find(b => b.id === id)?.name ?? id;

  return (
    <AppLayout layout={layout}>
      <div className="space-y-5">
        {/* Header */}
        <div className="flex items-start justify-between gap-4">
          <div>
            <h1 className="text-xl font-bold text-foreground flex items-center gap-2">
              <Shield size={20} className="text-primary" /> Admin Panel
            </h1>
            <p className="text-sm text-muted-foreground mt-0.5">Manage roles, permissions, users, and branch access</p>
          </div>
        </div>

        {/* KPI Cards */}
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <KpiCard title="Total Roles" value={roles.length} icon={<Shield size={18} className="text-primary" />} iconBg="bg-primary/10" />
          <KpiCard title="Active Users" value={activeUsers} icon={<Users size={18} className="text-success" />} iconBg="bg-success/10" variant="success" />
          <KpiCard title="Active Branches" value={activeBranches} icon={<Building2 size={18} className="text-info" />} iconBg="bg-info/10" />
          <KpiCard title="Permissions" value={totalPerms} icon={<Key size={18} className="text-warning" />} iconBg="bg-warning/10" />
        </div>

        {/* Tabs */}
        <div className="card p-0 overflow-hidden">
          <div className="flex items-center gap-0 border-b border-border px-4 overflow-x-auto">
            {tabs.map(tab => (
              <button
                key={tab.id}
                onClick={() => { setActiveTab(tab.id); setSearch(''); setSelectedRole(null); }}
                className={`flex items-center gap-1.5 px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap ${activeTab === tab.id ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'}`}
              >
                {tab.icon} {tab.label}
                <span className={`text-xs px-1.5 py-0.5 rounded-full font-semibold ${activeTab === tab.id ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'}`}>{tab.count}</span>
              </button>
            ))}
          </div>

          {/* ── Roles Tab ── */}
          {activeTab === 'roles' && (
            <div className="p-5">
              <div className="flex items-center justify-between gap-3 mb-4">
                <div className="relative flex-1 max-w-xs">
                  <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
                  <input value={search} onChange={e => setSearch(e.target.value)} placeholder="Search roles…" className="w-full pl-8 pr-3 py-2 text-sm bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary" />
                </div>
                <button onClick={() => setRoleModal({ open: true, role: null })} className="btn-primary text-sm px-3 py-2 flex items-center gap-1.5">
                  <Plus size={14} /> New Role
                </button>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                {filteredRoles.map(role => (
                  <div key={role.id} className="border border-border rounded-xl p-4 hover:border-primary/30 hover:shadow-sm transition-all group">
                    <div className="flex items-start justify-between gap-2 mb-3">
                      <div className="flex items-center gap-2.5">
                        <div className={`w-9 h-9 rounded-lg ${role.color} flex items-center justify-center shrink-0`}>
                          <Shield size={16} className="text-white" />
                        </div>
                        <div>
                          <p className="text-sm font-semibold text-foreground">{role.name}</p>
                          {role.isSystem && <span className="text-2xs bg-muted text-muted-foreground px-1.5 py-0.5 rounded font-medium">System</span>}
                        </div>
                      </div>
                      <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button onClick={() => setRoleModal({ open: true, role })} className="p-1.5 rounded-lg hover:bg-muted transition-colors" title="Edit role">
                          <Edit2 size={13} className="text-muted-foreground" />
                        </button>
                        {!role.isSystem && (
                          <button onClick={() => setDeleteConfirm(role.id)} className="p-1.5 rounded-lg hover:bg-danger/10 transition-colors" title="Delete role">
                            <Trash2 size={13} className="text-danger" />
                          </button>
                        )}
                      </div>
                    </div>
                    <p className="text-xs text-muted-foreground mb-3 line-clamp-2">{role.description}</p>
                    <div className="flex items-center justify-between text-xs">
                      <span className="text-muted-foreground flex items-center gap-1">
                        <Lock size={11} /> {role.permissions.length} permissions
                      </span>
                      <span className="text-muted-foreground flex items-center gap-1">
                        <Users size={11} /> {role.userCount} users
                      </span>
                    </div>
                    <div className="mt-3 pt-3 border-t border-border">
                      <div className="flex flex-wrap gap-1">
                        {Array.from(new Set(role.permissions.map(pid => allPermissions.find(p => p.id === pid)?.module).filter(Boolean))).slice(0, 4).map(mod => (
                          <span key={mod} className="text-2xs bg-muted text-muted-foreground px-1.5 py-0.5 rounded">{mod}</span>
                        ))}
                        {Array.from(new Set(role.permissions.map(pid => allPermissions.find(p => p.id === pid)?.module).filter(Boolean))).length > 4 && (
                          <span className="text-2xs bg-muted text-muted-foreground px-1.5 py-0.5 rounded">+{Array.from(new Set(role.permissions.map(pid => allPermissions.find(p => p.id === pid)?.module).filter(Boolean))).length - 4} more</span>
                        )}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* ── Permissions Matrix Tab ── */}
          {activeTab === 'permissions' && (
            <div className="p-5">
              <div className="flex items-center gap-3 mb-4">
                <div className="relative flex-1 max-w-xs">
                  <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
                  <input value={search} onChange={e => setSearch(e.target.value)} placeholder="Filter by role…" className="w-full pl-8 pr-3 py-2 text-sm bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary" />
                </div>
                <p className="text-xs text-muted-foreground">Click a role header to highlight its column</p>
              </div>
              <div className="overflow-x-auto rounded-xl border border-border">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="bg-muted/40 border-b border-border">
                      <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide w-48 sticky left-0 bg-muted/40">Module / Action</th>
                      {roles.filter(r => !search || r.name.toLowerCase().includes(search.toLowerCase())).map(role => (
                        <th
                          key={role.id}
                          onClick={() => setSelectedRole(selectedRole === role.id ? null : role.id)}
                          className={`px-3 py-3 text-center cursor-pointer transition-colors ${selectedRole === role.id ? 'bg-primary/10' : 'hover:bg-muted/60'}`}
                        >
                          <div className="flex flex-col items-center gap-1">
                            <div className={`w-6 h-6 rounded-md ${role.color} flex items-center justify-center`}>
                              <Shield size={12} className="text-white" />
                            </div>
                            <span className="text-2xs font-semibold text-foreground whitespace-nowrap">{role.name}</span>
                          </div>
                        </th>
                      ))}
                    </tr>
                  </thead>
                  <tbody>
                    {moduleGroups.map(module => {
                      const modulePerms = allPermissions.filter(p => p.module === module);
                      return (
                        <React.Fragment key={module}>
                          <tr className="bg-muted/20 border-b border-border">
                            <td colSpan={roles.length + 1} className="px-4 py-2">
                              <span className="text-xs font-bold text-foreground uppercase tracking-wider">{module}</span>
                            </td>
                          </tr>
                          {modulePerms.map((perm, idx) => (
                            <tr key={perm.id} className={`border-b border-border/50 hover:bg-muted/20 transition-colors ${idx === modulePerms.length - 1 ? 'border-b-2 border-border' : ''}`}>
                              <td className="px-4 py-2.5 sticky left-0 bg-card">
                                <p className="text-sm text-foreground">{perm.action}</p>
                                <p className="text-2xs text-muted-foreground">{perm.description}</p>
                              </td>
                              {roles.filter(r => !search || r.name.toLowerCase().includes(search.toLowerCase())).map(role => (
                                <td key={role.id} className={`px-3 py-2.5 text-center transition-colors ${selectedRole === role.id ? 'bg-primary/5' : ''}`}>
                                  {role.permissions.includes(perm.id) ? (
                                    <CheckCircle2 size={16} className="text-success mx-auto" />
                                  ) : (
                                    <X size={14} className="text-border mx-auto" />
                                  )}
                                </td>
                              ))}
                            </tr>
                          ))}
                        </React.Fragment>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* ── Users Tab ── */}
          {activeTab === 'users' && (
            <div className="p-5">
              <div className="flex items-center justify-between gap-3 mb-4">
                <div className="relative flex-1 max-w-xs">
                  <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
                  <input value={search} onChange={e => setSearch(e.target.value)} placeholder="Search users…" className="w-full pl-8 pr-3 py-2 text-sm bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary" />
                </div>
                <button onClick={() => setUserModal({ open: true, user: null })} className="btn-primary text-sm px-3 py-2 flex items-center gap-1.5">
                  <Plus size={14} /> Add User
                </button>
              </div>
              <div className="overflow-x-auto rounded-xl border border-border">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="bg-muted/40 border-b border-border">
                      <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">User</th>
                      <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Role</th>
                      <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Branch Access</th>
                      <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Last Login</th>
                      <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Status</th>
                      <th className="px-4 py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide text-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-border/50">
                    {filteredUsers.map(user => (
                      <tr key={user.id} className="hover:bg-muted/20 transition-colors">
                        <td className="px-4 py-3">
                          <div className="flex items-center gap-2.5">
                            <div className={`w-8 h-8 rounded-full ${getRoleColor(user.role)} flex items-center justify-center text-white text-xs font-bold shrink-0`}>
                              {user.avatar}
                            </div>
                            <div>
                              <p className="text-sm font-medium text-foreground">{user.name}</p>
                              <p className="text-xs text-muted-foreground">{user.email}</p>
                            </div>
                          </div>
                        </td>
                        <td className="px-4 py-3">
                          <span className={`inline-flex items-center gap-1 text-xs font-medium px-2 py-1 rounded-full text-white ${getRoleColor(user.role)}`}>
                            <Shield size={10} /> {getRoleName(user.role)}
                          </span>
                        </td>
                        <td className="px-4 py-3">
                          <div className="flex flex-wrap gap-1">
                            {user.branches.slice(0, 2).map(bid => (
                              <span key={bid} className="text-2xs bg-muted text-muted-foreground px-1.5 py-0.5 rounded">{getBranchName(bid)}</span>
                            ))}
                            {user.branches.length > 2 && (
                              <span className="text-2xs bg-muted text-muted-foreground px-1.5 py-0.5 rounded">+{user.branches.length - 2}</span>
                            )}
                          </div>
                        </td>
                        <td className="px-4 py-3 text-xs text-muted-foreground">{user.lastLogin}</td>
                        <td className="px-4 py-3">
                          <button onClick={() => toggleUserStatus(user.id)} className="flex items-center gap-1.5 group">
                            {user.status === 'active' ? (
                              <><ToggleRight size={18} className="text-success" /><span className="text-xs text-success font-medium">Active</span></>
                            ) : (
                              <><ToggleLeft size={18} className="text-muted-foreground" /><span className="text-xs text-muted-foreground">Inactive</span></>
                            )}
                          </button>
                        </td>
                        <td className="px-4 py-3 text-right">
                          <div className="flex items-center justify-end gap-1">
                            <button onClick={() => setUserModal({ open: true, user })} className="p-1.5 rounded-lg hover:bg-muted transition-colors" title="Edit user">
                              <Edit2 size={13} className="text-muted-foreground" />
                            </button>
                            <button onClick={() => setDeleteConfirm(user.id)} className="p-1.5 rounded-lg hover:bg-danger/10 transition-colors" title="Remove user">
                              <Trash2 size={13} className="text-danger" />
                            </button>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* ── Branch Assignments Tab ── */}
          {activeTab === 'branches' && (
            <div className="p-5 space-y-4">
              <p className="text-sm text-muted-foreground">Overview of users and roles assigned to each branch.</p>
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                {branches.map(branch => {
                  const branchUsers = users.filter(u => u.branches.includes(branch.id));
                  const roleBreakdown = roles.map(role => ({
                    role,
                    count: branchUsers.filter(u => u.role === role.id).length,
                  })).filter(r => r.count > 0);

                  return (
                    <div key={branch.id} className={`border rounded-xl p-4 ${branch.status === 'inactive' ? 'border-border opacity-60' : 'border-border hover:border-primary/30 hover:shadow-sm'} transition-all`}>
                      <div className="flex items-start justify-between gap-2 mb-3">
                        <div className="flex items-center gap-2.5">
                          <div className={`w-9 h-9 rounded-lg ${branch.status === 'active' ? 'bg-primary/10' : 'bg-muted'} flex items-center justify-center`}>
                            <Building2 size={16} className={branch.status === 'active' ? 'text-primary' : 'text-muted-foreground'} />
                          </div>
                          <div>
                            <p className="text-sm font-semibold text-foreground">{branch.name}</p>
                            <p className="text-xs text-muted-foreground">{branch.location}</p>
                          </div>
                        </div>
                        <span className={`text-2xs font-semibold px-2 py-1 rounded-full ${branch.status === 'active' ? 'bg-success/10 text-success' : 'bg-muted text-muted-foreground'}`}>
                          {branch.status === 'active' ? 'Active' : 'Inactive'}
                        </span>
                      </div>

                      <div className="flex items-center gap-4 text-xs text-muted-foreground mb-3 pb-3 border-b border-border">
                        <span className="flex items-center gap-1"><UserCog size={12} /> Manager: <strong className="text-foreground">{branch.manager}</strong></span>
                        <span className="flex items-center gap-1"><Users size={12} /> {branchUsers.length} users</span>
                      </div>

                      {roleBreakdown.length > 0 ? (
                        <div className="space-y-1.5">
                          {roleBreakdown.map(({ role, count }) => (
                            <div key={role.id} className="flex items-center justify-between">
                              <div className="flex items-center gap-2">
                                <div className={`w-2 h-2 rounded-full ${role.color}`} />
                                <span className="text-xs text-foreground">{role.name}</span>
                              </div>
                              <div className="flex items-center gap-2">
                                <div className="flex -space-x-1">
                                  {branchUsers.filter(u => u.role === role.id).slice(0, 3).map(u => (
                                    <div key={u.id} className={`w-5 h-5 rounded-full ${role.color} border border-card flex items-center justify-center text-white text-2xs font-bold`} title={u.name}>
                                      {u.avatar[0]}
                                    </div>
                                  ))}
                                </div>
                                <span className="text-xs text-muted-foreground">{count}</span>
                              </div>
                            </div>
                          ))}
                        </div>
                      ) : (
                        <p className="text-xs text-muted-foreground italic">No users assigned</p>
                      )}
                    </div>
                  );
                })}
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Delete Confirm */}
      {deleteConfirm && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-foreground/30 p-4">
          <div className="bg-card border border-border rounded-xl shadow-xl w-full max-w-sm p-5">
            <div className="flex items-center gap-3 mb-3">
              <div className="w-10 h-10 rounded-full bg-danger/10 flex items-center justify-center">
                <AlertTriangle size={18} className="text-danger" />
              </div>
              <div>
                <p className="text-sm font-semibold text-foreground">Confirm Delete</p>
                <p className="text-xs text-muted-foreground">This action cannot be undone.</p>
              </div>
            </div>
            <div className="flex gap-3 mt-4">
              <button onClick={() => setDeleteConfirm(null)} className="flex-1 btn-outline text-sm py-2">Cancel</button>
              <button
                onClick={() => {
                  if (roles.some(r => r.id === deleteConfirm)) handleDeleteRole(deleteConfirm);
                  else { setUsers(prev => prev.filter(u => u.id !== deleteConfirm)); setDeleteConfirm(null); }
                }}
                className="flex-1 bg-danger text-white text-sm py-2 rounded-lg hover:bg-danger/90 transition-colors font-medium"
              >
                Delete
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Role Modal */}
      {roleModal.open && (
        <RoleModal role={roleModal.role} onClose={() => setRoleModal({ open: false, role: null })} onSave={handleSaveRole} />
      )}

      {/* User Modal */}
      {userModal.open && (
        <UserModal user={userModal.user} roles={roles} onClose={() => setUserModal({ open: false, user: null })} onSave={handleSaveUser} />
      )}
    </AppLayout>
  );
}
