'use client';
import React from 'react';
import { X, LayoutDashboard, GitBranch, Monitor, Check } from 'lucide-react';
import { DashboardLayout } from './AppLayout';

interface LayoutSwitcherProps {
  current: DashboardLayout;
  onSelect: (layout: DashboardLayout) => void;
  onClose: () => void;
}

const layouts: { id: DashboardLayout; label: string; desc: string; icon: React.ReactNode; preview: React.ReactNode }[] = [
  {
    id: 'classic',
    label: 'Classic Sidebar',
    desc: 'Traditional sidebar navigation with full KPI dashboard',
    icon: <LayoutDashboard size={20} />,
    preview: (
      <div className="w-full h-20 bg-muted rounded-lg overflow-hidden flex">
        <div className="w-8 bg-card border-r border-border flex flex-col gap-1 p-1">
          {[...Array(5)].map((_, i) => (
            <div key={`prev-sb-${i}`} className="h-1.5 bg-muted-foreground/30 rounded" />
          ))}
        </div>
        <div className="flex-1 p-2 space-y-1.5">
          <div className="grid grid-cols-3 gap-1">
            {[...Array(3)].map((_, i) => (
              <div key={`prev-card-${i}`} className="h-5 bg-primary/20 rounded" />
            ))}
          </div>
          <div className="h-8 bg-muted rounded" />
          <div className="grid grid-cols-2 gap-1">
            <div className="h-5 bg-muted rounded" />
            <div className="h-5 bg-muted rounded" />
          </div>
        </div>
      </div>
    ),
  },
  {
    id: 'smartflow',
    label: 'Smart Flow',
    desc: 'Business process flow with interactive operation nodes',
    icon: <GitBranch size={20} />,
    preview: (
      <div className="w-full h-20 bg-muted rounded-lg overflow-hidden p-2 flex items-center justify-center gap-1">
        {['P', '→', 'I', '→', 'S', '→', '$'].map((n, i) => (
          <div
            key={`prev-flow-${i}`}
            className={n === '→' ? 'text-muted-foreground text-xs' : 'w-7 h-7 rounded-lg bg-primary/20 border border-primary/30 flex items-center justify-center text-2xs font-bold text-primary'}
          >
            {n}
          </div>
        ))}
      </div>
    ),
  },
  {
    id: 'command',
    label: 'Command Center',
    desc: 'Top navigation with operational command center layout',
    icon: <Monitor size={20} />,
    preview: (
      <div className="w-full h-20 bg-muted rounded-lg overflow-hidden">
        <div className="h-5 bg-card border-b border-border flex items-center gap-1 px-2">
          {[...Array(5)].map((_, i) => (
            <div key={`prev-nav-${i}`} className="h-2 w-6 bg-muted-foreground/30 rounded" />
          ))}
        </div>
        <div className="p-2 space-y-1.5">
          <div className="grid grid-cols-4 gap-1">
            {[...Array(4)].map((_, i) => (
              <div key={`prev-kpi-${i}`} className="h-4 bg-primary/20 rounded" />
            ))}
          </div>
          <div className="grid grid-cols-3 gap-1">
            {[...Array(3)].map((_, i) => (
              <div key={`prev-op-${i}`} className="h-5 bg-muted rounded" />
            ))}
          </div>
        </div>
      </div>
    ),
  },
];

export default function LayoutSwitcher({ current, onSelect, onClose }: LayoutSwitcherProps) {
  return (
    <div className="fixed inset-0 z-50 bg-foreground/40 flex items-center justify-center px-4" onClick={onClose}>
      <div
        className="w-full max-w-2xl bg-card border border-border rounded-2xl shadow-2xl scale-in overflow-hidden"
        onClick={e => e.stopPropagation()}
      >
        <div className="flex items-center justify-between px-6 py-4 border-b border-border">
          <div>
            <h2 className="text-base font-bold text-foreground">Dashboard Appearance</h2>
            <p className="text-xs text-muted-foreground mt-0.5">Choose how your dashboard is organized</p>
          </div>
          <button onClick={onClose} className="btn-ghost p-1"><X size={16} /></button>
        </div>

        <div className="p-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
          {layouts.map(l => (
            <button
              key={`ls-${l.id}`}
              onClick={() => onSelect(l.id)}
              className={`text-left p-4 rounded-xl border-2 transition-all duration-150 hover:border-primary/50 ${
                current === l.id
                  ? 'border-primary bg-primary/5' :'border-border bg-background'
              }`}
            >
              {l.preview}
              <div className="mt-3 flex items-start justify-between gap-2">
                <div>
                  <p className="text-sm font-semibold text-foreground">{l.label}</p>
                  <p className="text-xs text-muted-foreground mt-0.5 leading-relaxed">{l.desc}</p>
                </div>
                {current === l.id && (
                  <div className="w-5 h-5 rounded-full bg-primary flex items-center justify-center shrink-0 mt-0.5">
                    <Check size={12} className="text-primary-foreground" />
                  </div>
                )}
              </div>
            </button>
          ))}
        </div>

        <div className="px-6 pb-4 flex items-center justify-between">
          <p className="text-xs text-muted-foreground">Layout preference is saved per user account</p>
          <button onClick={onClose} className="btn-secondary text-xs">Close</button>
        </div>
      </div>
    </div>
  );
}