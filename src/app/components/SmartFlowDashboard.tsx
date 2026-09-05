'use client';
import React, { useState } from 'react';
import {
  Truck, Package, Warehouse, ShoppingCart, CreditCard, BarChart2,
  ArrowRight, AlertTriangle, TrendingUp, Users, CheckSquare,
  ChevronRight, Clock,
} from 'lucide-react';

import KpiCard from '@/components/ui/KpiCard';

// Backend integration point: replace with /api/dashboard/flow-summary

interface FlowNode {
  id: string;
  label: string;
  icon: React.ReactNode;
  color: string;
  bgColor: string;
  borderColor: string;
  stats: { label: string; value: string; alert?: boolean }[];
  href: string;
}

const flowNodes: FlowNode[] = [
  {
    id: 'node-supplier',
    label: 'Supplier',
    icon: <Truck size={24} />,
    color: 'text-muted-foreground',
    bgColor: 'bg-muted',
    borderColor: 'border-border',
    stats: [
      { label: 'Active Suppliers', value: '48' },
      { label: 'Pending Orders', value: '7' },
      { label: 'Awaiting Delivery', value: '3', alert: true },
    ],
    href: '/suppliers',
  },
  {
    id: 'node-purchase',
    label: 'Purchase',
    icon: <ShoppingCart size={24} />,
    color: 'text-info',
    bgColor: 'bg-info/10',
    borderColor: 'border-info/30',
    stats: [
      { label: 'Open POs', value: '12' },
      { label: 'This Month', value: 'TZS 48.2M' },
      { label: 'Partially Received', value: '4', alert: true },
    ],
    href: '/purchases',
  },
  {
    id: 'node-receiving',
    label: 'Goods Receiving',
    icon: <Package size={24} />,
    color: 'text-warning',
    bgColor: 'bg-warning/10',
    borderColor: 'border-warning/30',
    stats: [
      { label: 'Pending Receipt', value: '7', alert: true },
      { label: 'Received Today', value: '2' },
      { label: 'Short Deliveries', value: '2', alert: true },
    ],
    href: '/inventory',
  },
  {
    id: 'node-inventory',
    label: 'Inventory',
    icon: <Warehouse size={24} />,
    color: 'text-primary',
    bgColor: 'bg-primary/10',
    borderColor: 'border-primary/30',
    stats: [
      { label: 'Total Products', value: '10,542' },
      { label: 'Low Stock', value: '32', alert: true },
      { label: 'Stock Value', value: 'TZS 428.5M' },
    ],
    href: '/inventory',
  },
  {
    id: 'node-sales',
    label: 'Sales',
    icon: <ShoppingCart size={24} />,
    color: 'text-success',
    bgColor: 'bg-success/10',
    borderColor: 'border-success/30',
    stats: [
      { label: 'Today', value: 'TZS 8.45M' },
      { label: 'Transactions', value: '126' },
      { label: 'Pending Fulfil', value: '3' },
    ],
    href: '/sales-pos',
  },
  {
    id: 'node-customer',
    label: 'Customer',
    icon: <Users size={24} />,
    color: 'text-accent',
    bgColor: 'bg-accent/10',
    borderColor: 'border-accent/30',
    stats: [
      { label: 'Active Customers', value: '284' },
      { label: 'Outstanding Debt', value: 'TZS 14.8M' },
      { label: 'Overdue Accounts', value: '18', alert: true },
    ],
    href: '/customers',
  },
  {
    id: 'node-payment',
    label: 'Payment',
    icon: <CreditCard size={24} />,
    color: 'text-primary',
    bgColor: 'bg-primary/10',
    borderColor: 'border-primary/30',
    stats: [
      { label: 'Cash Balance', value: 'TZS 3.24M' },
      { label: 'Received Today', value: 'TZS 8.45M' },
      { label: 'Pending', value: '5', alert: false },
    ],
    href: '/payments',
  },
  {
    id: 'node-reports',
    label: 'Reports',
    icon: <BarChart2 size={24} />,
    color: 'text-muted-foreground',
    bgColor: 'bg-muted',
    borderColor: 'border-border',
    stats: [
      { label: 'Gross Profit', value: 'TZS 1.62M' },
      { label: 'Net Margin', value: '19.2%' },
      { label: 'Pending Approvals', value: '7', alert: true },
    ],
    href: '/reports',
  },
];

function FlowNodeCard({ node, isActive, onClick }: { node: FlowNode; isActive: boolean; onClick: () => void }) {
  return (
    <button
      onClick={onClick}
      className={`flow-node-hover text-left p-5 rounded-2xl border-2 transition-all duration-200 w-full ${
        isActive
          ? `${node.bgColor} ${node.borderColor} shadow-lg`
          : 'bg-card border-border hover:border-primary/30'
      }`}
    >
      <div className="flex items-start justify-between mb-3">
        <div className={`w-11 h-11 rounded-xl ${node.bgColor} border ${node.borderColor} flex items-center justify-center ${node.color}`}>
          {node.icon}
        </div>
        {node.stats.some(s => s.alert) && (
          <span className="w-2 h-2 rounded-full bg-warning mt-1" />
        )}
      </div>
      <h3 className="text-sm font-bold text-foreground mb-3">{node.label}</h3>
      <div className="space-y-1.5">
        {node.stats.map((stat, i) => (
          <div key={`stat-${node.id}-${i}`} className="flex items-center justify-between">
            <span className="text-2xs text-muted-foreground">{stat.label}</span>
            <span className={`text-xs font-semibold tabular-nums ${stat.alert ? 'text-warning' : 'text-foreground'}`}>
              {stat.value}
            </span>
          </div>
        ))}
      </div>
      <div className="mt-3 flex items-center gap-1 text-2xs text-primary font-semibold">
        Open module <ChevronRight size={10} />
      </div>
    </button>
  );
}

const topAlerts = [
  { id: 'alert-1', icon: <AlertTriangle size={14} className="text-warning" />, msg: 'Portland Cement 50kg — 8 bags remaining (min: 50)', time: '5m ago' },
  { id: 'alert-2', icon: <AlertTriangle size={14} className="text-danger" />, msg: 'Mwangi Properties — TZS 5.8M overdue 45 days', time: '1h ago' },
  { id: 'alert-3', icon: <CheckSquare size={14} className="text-info" />, msg: '7 approvals pending — 4 high priority', time: 'ongoing' },
  { id: 'alert-4', icon: <TrendingUp size={14} className="text-success" />, msg: 'Today\'s sales up 12.4% vs yesterday', time: '2m ago' },
];

export default function SmartFlowDashboard() {
  const [activeNode, setActiveNode] = useState<string | null>(null);

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h1 className="text-2xl font-bold text-foreground">Smart Flow Dashboard</h1>
          <p className="text-sm text-muted-foreground">Karibu Hardware Ltd — business process overview · 05 Sep 2026</p>
        </div>
        <div className="flex items-center gap-1.5 text-xs text-muted-foreground bg-muted px-3 py-2 rounded-lg border border-border">
          <Clock size={12} />
          <span>Live · Updated 2 min ago</span>
        </div>
      </div>

      {/* Top alerts strip */}
      <div className="card p-3 bg-warning/5 border-warning/20">
        <div className="flex items-center gap-2 flex-wrap">
          <span className="text-xs font-semibold text-warning uppercase tracking-wide shrink-0">Alerts</span>
          <div className="flex items-center gap-3 overflow-x-auto flex-1">
            {topAlerts.map(a => (
              <div key={a.id} className="flex items-center gap-1.5 whitespace-nowrap">
                {a.icon}
                <span className="text-xs text-foreground">{a.msg}</span>
                <span className="text-2xs text-muted-foreground">· {a.time}</span>
                <span className="text-muted-foreground mx-1">|</span>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Flow: Supplier → Purchase → Receiving → Inventory → Sales → Customer → Payment → Reports */}
      {/* Row 1: first 4 nodes */}
      <div>
        <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-3">Business Flow</p>
        <div className="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-4 2xl:grid-cols-4 gap-3">
          {flowNodes.slice(0, 4).map((node, i) => (
            <React.Fragment key={node.id}>
              <FlowNodeCard
                node={node}
                isActive={activeNode === node.id}
                onClick={() => setActiveNode(activeNode === node.id ? null : node.id)}
              />
              {i < 3 && (
                <div className="hidden md:flex items-center justify-center absolute" style={{ display: 'none' }} />
              )}
            </React.Fragment>
          ))}
        </div>

        {/* Arrow connector */}
        <div className="flex items-center justify-center my-3 gap-2">
          <div className="flex-1 h-px bg-border" />
          <div className="flex items-center gap-2 text-xs text-muted-foreground font-medium">
            <ArrowRight size={14} className="text-primary" />
            <span>Stock flows to Sales</span>
            <ArrowRight size={14} className="text-primary" />
          </div>
          <div className="flex-1 h-px bg-border" />
        </div>

        {/* Row 2: last 4 nodes */}
        <div className="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-4 2xl:grid-cols-4 gap-3">
          {flowNodes.slice(4, 8).map(node => (
            <FlowNodeCard
              key={node.id}
              node={node}
              isActive={activeNode === node.id}
              onClick={() => setActiveNode(activeNode === node.id ? null : node.id)}
            />
          ))}
        </div>
      </div>

      {/* KPI Summary strip */}
      <div className="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-4 2xl:grid-cols-4 gap-4">
        <KpiCard
          title="Today's Sales"
          value={8450000}
          isMoney
          change={12.4}
          changeLabel="vs yesterday"
          icon={<ShoppingCart size={16} className="text-primary" />}
          iconBg="bg-primary/10"
        />
        <KpiCard
          title="Gross Profit"
          value={1620000}
          isMoney
          change={8.2}
          changeLabel="vs yesterday"
          icon={<TrendingUp size={16} className="text-success" />}
          iconBg="bg-success/10"
          variant="success"
        />
        <KpiCard
          title="Outstanding Debt"
          value={14800000}
          isMoney
          compact
          change={3.2}
          changeLabel="vs last week"
          icon={<CreditCard size={16} className="text-warning" />}
          iconBg="bg-warning/10"
          variant="warning"
        />
        <KpiCard
          title="Pending Approvals"
          value={7}
          change={0}
          changeLabel="unchanged"
          icon={<CheckSquare size={16} className="text-danger" />}
          iconBg="bg-danger/10"
          variant="danger"
        />
      </div>
    </div>
  );
}