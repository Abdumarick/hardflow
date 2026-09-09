'use client';
import React, { useState } from 'react';
import AppLayout, { DashboardLayout } from '@/components/AppLayout';
import KpiCard from '@/components/ui/KpiCard';
import MoneyDisplay from '@/components/ui/MoneyDisplay';
import { Banknote, Plus, Search, ArrowLeftRight, CreditCard, Building2, Smartphone, Clock, CheckCircle2, ChevronRight, TrendingUp, TrendingDown, Eye, X, Wallet, Lock, Unlock, BarChart3,  } from 'lucide-react';

type SessionStatus = 'open' | 'closed' | 'reconciled';
type AccountType = 'cash' | 'bank' | 'mobile-money';

interface CashSession {
  id: string;
  ref: string;
  cashier: string;
  branch: string;
  openedAt: string;
  closedAt: string | null;
  status: SessionStatus;
  openingBalance: number;
  expectedClosing: number;
  actualClosing: number | null;
  variance: number | null;
  currency: string;
}

interface PaymentAccount {
  id: string;
  name: string;
  type: AccountType;
  balance: number;
  currency: string;
  lastActivity: string;
  accountNo?: string;
  provider?: string;
}

interface Transfer {
  id: string;
  ref: string;
  fromAccount: string;
  toAccount: string;
  amount: number;
  currency: string;
  date: string;
  initiatedBy: string;
  status: 'completed' | 'pending' | 'failed';
  notes?: string;
}

const cashSessions: CashSession[] = [
  { id: 'cs001', ref: 'SES-2026-0041', cashier: 'Sarah Kimani', branch: 'Main Branch', openedAt: '2026-09-09 08:00', closedAt: null, status: 'open', openingBalance: 500000, expectedClosing: 2340000, actualClosing: null, variance: null, currency: 'TZS' },
  { id: 'cs002', ref: 'SES-2026-0040', cashier: 'David Osei', branch: 'Main Branch', openedAt: '2026-09-08 08:00', closedAt: '2026-09-08 18:30', status: 'reconciled', openingBalance: 500000, expectedClosing: 3120000, actualClosing: 3115000, variance: -5000, currency: 'TZS' },
  { id: 'cs003', ref: 'SES-2026-0039', cashier: 'Mary Achieng', branch: 'Kariakoo Branch', openedAt: '2026-09-08 08:00', closedAt: '2026-09-08 17:00', status: 'closed', openingBalance: 300000, expectedClosing: 1850000, actualClosing: 1850000, variance: 0, currency: 'TZS' },
  { id: 'cs004', ref: 'SES-2026-0038', cashier: 'Sarah Kimani', branch: 'Main Branch', openedAt: '2026-09-07 08:00', closedAt: '2026-09-07 18:00', status: 'reconciled', openingBalance: 500000, expectedClosing: 2780000, actualClosing: 2790000, variance: 10000, currency: 'TZS' },
  { id: 'cs005', ref: 'SES-2026-0037', cashier: 'Tom Njoroge', branch: 'Warehouse', openedAt: '2026-09-07 07:30', closedAt: '2026-09-07 16:30', status: 'reconciled', openingBalance: 200000, expectedClosing: 980000, actualClosing: 975000, variance: -5000, currency: 'TZS' },
];

const paymentAccounts: PaymentAccount[] = [
  { id: 'pa001', name: 'Main Cash Register', type: 'cash', balance: 2340000, currency: 'TZS', lastActivity: '2026-09-09', accountNo: 'CASH-001' },
  { id: 'pa002', name: 'CRDB Business Account', type: 'bank', balance: 48750000, currency: 'TZS', lastActivity: '2026-09-09', accountNo: '0150-1234-5678', provider: 'CRDB Bank' },
  { id: 'pa003', name: 'NMB Operations Account', type: 'bank', balance: 22100000, currency: 'TZS', lastActivity: '2026-09-08', accountNo: '4001-9876-5432', provider: 'NMB Bank' },
  { id: 'pa004', name: 'M-Pesa Business', type: 'mobile-money', balance: 3850000, currency: 'TZS', lastActivity: '2026-09-09', accountNo: '+255 712 345 678', provider: 'Vodacom M-Pesa' },
  { id: 'pa005', name: 'Tigo Pesa Business', type: 'mobile-money', balance: 1240000, currency: 'TZS', lastActivity: '2026-09-07', accountNo: '+255 765 432 109', provider: 'Tigo Pesa' },
  { id: 'pa006', name: 'Petty Cash – Main', type: 'cash', balance: 185000, currency: 'TZS', lastActivity: '2026-09-08', accountNo: 'PETTY-001' },
];

const transfers: Transfer[] = [
  { id: 't001', ref: 'TRF-2026-0021', fromAccount: 'Main Cash Register', toAccount: 'CRDB Business Account', amount: 5000000, currency: 'TZS', date: '2026-09-09', initiatedBy: 'James Mwangi', status: 'completed', notes: 'Daily cash deposit' },
  { id: 't002', ref: 'TRF-2026-0020', fromAccount: 'CRDB Business Account', toAccount: 'NMB Operations Account', amount: 10000000, currency: 'TZS', date: '2026-09-08', initiatedBy: 'James Mwangi', status: 'completed' },
  { id: 't003', ref: 'TRF-2026-0019', fromAccount: 'M-Pesa Business', toAccount: 'CRDB Business Account', amount: 2500000, currency: 'TZS', date: '2026-09-08', initiatedBy: 'Sarah Kimani', status: 'pending', notes: 'Mobile money sweep' },
  { id: 't004', ref: 'TRF-2026-0018', fromAccount: 'CRDB Business Account', toAccount: 'Petty Cash – Main', amount: 300000, currency: 'TZS', date: '2026-09-07', initiatedBy: 'James Mwangi', status: 'completed', notes: 'Petty cash replenishment' },
  { id: 't005', ref: 'TRF-2026-0017', fromAccount: 'NMB Operations Account', toAccount: 'Tigo Pesa Business', amount: 1000000, currency: 'TZS', date: '2026-09-06', initiatedBy: 'James Mwangi', status: 'failed', notes: 'Failed – insufficient funds' },
];

const sessionStatusConfig: Record<SessionStatus, { label: string; color: string; icon: React.ReactNode }> = {
  open:        { label: 'Open',        color: 'bg-success/10 text-success border-success/20',   icon: <Unlock size={13} /> },
  closed:      { label: 'Closed',      color: 'bg-warning/10 text-warning border-warning/20',   icon: <Lock size={13} /> },
  reconciled:  { label: 'Reconciled',  color: 'bg-info/10 text-info border-info/20',             icon: <CheckCircle2 size={13} /> },
};

const accountTypeConfig: Record<AccountType, { icon: React.ReactNode; color: string }> = {
  cash:          { icon: <Banknote size={16} />,    color: 'bg-success/10 text-success' },
  bank:          { icon: <Building2 size={16} />,   color: 'bg-info/10 text-info' },
  'mobile-money':{ icon: <Smartphone size={16} />,  color: 'bg-purple-500/10 text-purple-600' },
};

const transferStatusConfig = {
  completed: 'bg-success/10 text-success border-success/20',
  pending:   'bg-warning/10 text-warning border-warning/20',
  failed:    'bg-danger/10 text-danger border-danger/20',
};

type Tab = 'sessions' | 'accounts' | 'transfers';

export default function CashPaymentsPage() {
  const [layout, setLayout] = useState<DashboardLayout>('classic');
  const [activeTab, setActiveTab] = useState<Tab>('sessions');
  const [search, setSearch] = useState('');
  const [showNewTransfer, setShowNewTransfer] = useState(false);

  const totalBalance = paymentAccounts.reduce((s, a) => s + a.balance, 0);
  const openSessions = cashSessions.filter(s => s.status === 'open').length;
  const pendingTransfers = transfers.filter(t => t.status === 'pending').length;
  const todayTransfers = transfers.filter(t => t.date === '2026-09-09').reduce((s, t) => s + t.amount, 0);

  const tabs: { key: Tab; label: string; icon: React.ReactNode }[] = [
    { key: 'sessions',  label: 'Cash Sessions',    icon: <Wallet size={15} /> },
    { key: 'accounts',  label: 'Payment Accounts', icon: <CreditCard size={15} /> },
    { key: 'transfers', label: 'Account Transfers', icon: <ArrowLeftRight size={15} /> },
  ];

  return (
    <AppLayout layout={layout} onLayoutChange={setLayout}>
      <div className="p-4 md:p-6 space-y-5">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-bold text-foreground flex items-center gap-2">
              <Banknote size={22} className="text-primary" />
              Cash & Payments
            </h1>
            <p className="text-sm text-muted-foreground mt-0.5">Manage cash sessions, reconciliation, payment accounts, and transfers</p>
          </div>
          <div className="flex items-center gap-2">
            <button
              onClick={() => setShowNewTransfer(true)}
              className="flex items-center gap-1.5 px-4 py-2 bg-primary text-primary-foreground text-sm font-medium rounded-lg hover:bg-primary/90 transition-colors"
            >
              <Plus size={15} /> New Transfer
            </button>
          </div>
        </div>

        {/* KPIs */}
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <KpiCard title="Total Balance" value={totalBalance} isMoney currency="TZS" icon={<Wallet size={18} />} variant="success" />
          <KpiCard title="Open Sessions" value={String(openSessions)} icon={<Unlock size={18} />} variant={openSessions > 0 ? 'default' : 'success'} />
          <KpiCard title="Pending Transfers" value={String(pendingTransfers)} icon={<Clock size={18} />} variant={pendingTransfers > 0 ? 'warning' : 'default'} />
          <KpiCard title="Today's Transfers" value={todayTransfers} isMoney currency="TZS" icon={<ArrowLeftRight size={18} />} />
        </div>

        {/* Tabs */}
        <div className="flex gap-1 p-1 bg-muted/50 rounded-xl border border-border">
          {tabs.map(tab => (
            <button
              key={tab.key}
              onClick={() => setActiveTab(tab.key)}
              className={`flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-lg transition-all ${
                activeTab === tab.key
                  ? 'bg-card text-foreground shadow-sm border border-border'
                  : 'text-muted-foreground hover:text-foreground'
              }`}
            >
              {tab.icon} {tab.label}
            </button>
          ))}
        </div>

        {/* Search */}
        <div className="relative">
          <Search size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
          <input
            type="text"
            placeholder={`Search ${activeTab}...`}
            value={search}
            onChange={e => setSearch(e.target.value)}
            className="w-full pl-9 pr-4 py-2 text-sm bg-card border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground placeholder:text-muted-foreground"
          />
        </div>

        {/* Cash Sessions Tab */}
        {activeTab === 'sessions' && (
          <div className="space-y-3">
            <div className="flex items-center justify-between">
              <h2 className="text-sm font-semibold text-foreground">Cash Sessions</h2>
              <button className="flex items-center gap-1.5 px-3 py-1.5 text-xs bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
                <Plus size={13} /> Open Session
              </button>
            </div>
            {cashSessions.map(session => {
              const cfg = sessionStatusConfig[session.status];
              const hasVariance = session.variance !== null && session.variance !== 0;
              return (
                <div key={session.id} className="bg-card border border-border rounded-xl p-4">
                  <div className="flex flex-col sm:flex-row sm:items-start gap-3">
                    <div className="flex-1 min-w-0">
                      <div className="flex flex-wrap items-center gap-2 mb-1.5">
                        <span className={`flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full border ${cfg.color}`}>
                          {cfg.icon} {cfg.label}
                        </span>
                        <span className="text-xs font-mono text-muted-foreground">{session.ref}</span>
                      </div>
                      <div className="flex flex-wrap gap-4 text-sm">
                        <div>
                          <p className="text-xs text-muted-foreground">Cashier</p>
                          <p className="font-semibold text-foreground">{session.cashier}</p>
                        </div>
                        <div>
                          <p className="text-xs text-muted-foreground">Branch</p>
                          <p className="font-semibold text-foreground">{session.branch}</p>
                        </div>
                        <div>
                          <p className="text-xs text-muted-foreground">Opened</p>
                          <p className="font-semibold text-foreground">{session.openedAt}</p>
                        </div>
                        {session.closedAt && (
                          <div>
                            <p className="text-xs text-muted-foreground">Closed</p>
                            <p className="font-semibold text-foreground">{session.closedAt}</p>
                          </div>
                        )}
                      </div>
                    </div>
                    <div className="flex gap-4 shrink-0">
                      <div className="text-right">
                        <p className="text-xs text-muted-foreground">Opening</p>
                        <p className="font-semibold text-foreground text-sm"><MoneyDisplay amount={session.openingBalance} currency={session.currency} /></p>
                      </div>
                      <div className="text-right">
                        <p className="text-xs text-muted-foreground">Expected</p>
                        <p className="font-semibold text-foreground text-sm"><MoneyDisplay amount={session.expectedClosing} currency={session.currency} /></p>
                      </div>
                      {session.actualClosing !== null && (
                        <div className="text-right">
                          <p className="text-xs text-muted-foreground">Actual</p>
                          <p className={`font-semibold text-sm ${hasVariance ? 'text-danger' : 'text-success'}`}>
                            <MoneyDisplay amount={session.actualClosing} currency={session.currency} />
                          </p>
                        </div>
                      )}
                      {session.variance !== null && (
                        <div className="text-right">
                          <p className="text-xs text-muted-foreground">Variance</p>
                          <p className={`font-bold text-sm flex items-center gap-1 ${session.variance < 0 ? 'text-danger' : session.variance > 0 ? 'text-success' : 'text-muted-foreground'}`}>
                            {session.variance < 0 ? <TrendingDown size={13} /> : session.variance > 0 ? <TrendingUp size={13} /> : null}
                            <MoneyDisplay amount={Math.abs(session.variance)} currency={session.currency} />
                          </p>
                        </div>
                      )}
                    </div>
                  </div>
                  {session.status === 'open' && (
                    <div className="mt-3 pt-3 border-t border-border flex gap-2">
                      <button className="flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg bg-warning/10 text-warning border border-warning/20 hover:bg-warning/20 transition-colors">
                        <Lock size={12} /> Close Session
                      </button>
                      <button className="flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg border border-border bg-muted/50 hover:bg-muted text-foreground transition-colors">
                        <BarChart3 size={12} /> View Transactions
                      </button>
                    </div>
                  )}
                  {session.status === 'closed' && (
                    <div className="mt-3 pt-3 border-t border-border flex gap-2">
                      <button className="flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg bg-info/10 text-info border border-info/20 hover:bg-info/20 transition-colors">
                        <CheckCircle2 size={12} /> Reconcile
                      </button>
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        )}

        {/* Payment Accounts Tab */}
        {activeTab === 'accounts' && (
          <div className="space-y-3">
            <div className="flex items-center justify-between">
              <h2 className="text-sm font-semibold text-foreground">Payment Accounts</h2>
              <button className="flex items-center gap-1.5 px-3 py-1.5 text-xs bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
                <Plus size={13} /> Add Account
              </button>
            </div>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
              {paymentAccounts.map(account => {
                const typeCfg = accountTypeConfig[account.type];
                return (
                  <div key={account.id} className="bg-card border border-border rounded-xl p-4 hover:shadow-sm transition-all">
                    <div className="flex items-start justify-between mb-3">
                      <div className={`w-10 h-10 rounded-xl flex items-center justify-center ${typeCfg.color}`}>
                        {typeCfg.icon}
                      </div>
                      <span className="text-xs text-muted-foreground capitalize">{account.type.replace('-', ' ')}</span>
                    </div>
                    <h3 className="font-semibold text-foreground text-sm mb-0.5">{account.name}</h3>
                    {account.provider && <p className="text-xs text-muted-foreground mb-2">{account.provider}</p>}
                    {account.accountNo && <p className="text-xs font-mono text-muted-foreground mb-3">{account.accountNo}</p>}
                    <div className="border-t border-border pt-3">
                      <p className="text-xs text-muted-foreground mb-1">Current Balance</p>
                      <p className="text-xl font-bold text-foreground">
                        <MoneyDisplay amount={account.balance} currency={account.currency} />
                      </p>
                      <p className="text-xs text-muted-foreground mt-1">Last activity: {account.lastActivity}</p>
                    </div>
                    <div className="flex gap-2 mt-3">
                      <button className="flex-1 flex items-center justify-center gap-1 py-1.5 text-xs rounded-lg border border-border bg-muted/50 hover:bg-muted text-foreground transition-colors">
                        <Eye size={12} /> Transactions
                      </button>
                      <button className="flex-1 flex items-center justify-center gap-1 py-1.5 text-xs rounded-lg bg-primary/10 text-primary border border-primary/20 hover:bg-primary/20 transition-colors">
                        <ArrowLeftRight size={12} /> Transfer
                      </button>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        )}

        {/* Transfers Tab */}
        {activeTab === 'transfers' && (
          <div className="space-y-3">
            <div className="flex items-center justify-between">
              <h2 className="text-sm font-semibold text-foreground">Account Transfers</h2>
              <button
                onClick={() => setShowNewTransfer(true)}
                className="flex items-center gap-1.5 px-3 py-1.5 text-xs bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors"
              >
                <Plus size={13} /> New Transfer
              </button>
            </div>
            <div className="bg-card border border-border rounded-xl overflow-hidden">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-border bg-muted/30">
                    <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground">Ref</th>
                    <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground">From</th>
                    <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground">To</th>
                    <th className="text-right px-4 py-3 text-xs font-semibold text-muted-foreground">Amount</th>
                    <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground">Date</th>
                    <th className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {transfers.map(t => (
                    <tr key={t.id} className="hover:bg-muted/30 transition-colors">
                      <td className="px-4 py-3 font-mono text-xs text-muted-foreground">{t.ref}</td>
                      <td className="px-4 py-3">
                        <p className="font-medium text-foreground text-xs">{t.fromAccount}</p>
                      </td>
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-1.5">
                          <ChevronRight size={12} className="text-muted-foreground" />
                          <p className="font-medium text-foreground text-xs">{t.toAccount}</p>
                        </div>
                      </td>
                      <td className="px-4 py-3 text-right font-semibold text-foreground">
                        <MoneyDisplay amount={t.amount} currency={t.currency} />
                      </td>
                      <td className="px-4 py-3 text-xs text-muted-foreground">{t.date}</td>
                      <td className="px-4 py-3">
                        <span className={`text-xs font-medium px-2 py-0.5 rounded-full border capitalize ${transferStatusConfig[t.status]}`}>
                          {t.status}
                        </span>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}

        {/* New Transfer Modal */}
        {showNewTransfer && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-foreground/30 p-4">
            <div className="bg-card border border-border rounded-2xl w-full max-w-md shadow-xl">
              <div className="flex items-center justify-between p-5 border-b border-border">
                <h2 className="font-bold text-foreground flex items-center gap-2">
                  <ArrowLeftRight size={18} className="text-primary" /> New Transfer
                </h2>
                <button onClick={() => setShowNewTransfer(false)} className="text-muted-foreground hover:text-foreground">
                  <X size={18} />
                </button>
              </div>
              <div className="p-5 space-y-4">
                <div>
                  <label className="text-xs font-medium text-muted-foreground mb-1.5 block">From Account</label>
                  <select className="w-full px-3 py-2 text-sm bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground">
                    {paymentAccounts.map(a => <option key={a.id} value={a.id}>{a.name}</option>)}
                  </select>
                </div>
                <div>
                  <label className="text-xs font-medium text-muted-foreground mb-1.5 block">To Account</label>
                  <select className="w-full px-3 py-2 text-sm bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground">
                    {paymentAccounts.map(a => <option key={a.id} value={a.id}>{a.name}</option>)}
                  </select>
                </div>
                <div>
                  <label className="text-xs font-medium text-muted-foreground mb-1.5 block">Amount (TZS)</label>
                  <input type="number" placeholder="0" className="w-full px-3 py-2 text-sm bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground" />
                </div>
                <div>
                  <label className="text-xs font-medium text-muted-foreground mb-1.5 block">Notes (optional)</label>
                  <input type="text" placeholder="Transfer reason..." className="w-full px-3 py-2 text-sm bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 text-foreground" />
                </div>
                <div className="flex gap-2 pt-2">
                  <button onClick={() => setShowNewTransfer(false)} className="flex-1 py-2 text-sm rounded-lg border border-border bg-muted/50 hover:bg-muted text-foreground transition-colors">
                    Cancel
                  </button>
                  <button className="flex-1 py-2 text-sm rounded-lg bg-primary text-primary-foreground font-medium hover:bg-primary/90 transition-colors">
                    Confirm Transfer
                  </button>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </AppLayout>
  );
}
