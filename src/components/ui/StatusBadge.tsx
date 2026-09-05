import React from 'react';

type Status =
  | 'active' | 'inactive' | 'draft' | 'confirmed' | 'fulfilled' | 'paid' | 'closed' |'cancelled'| 'pending' | 'approved' | 'rejected' | 'overdue' | 'partial' |'ordered'| 'received' | 'posted' | 'reversed' | 'low-stock' | 'out-of-stock' |'in-stock' | 'credit' | 'cash' | 'mobile-money' | 'card';

const statusMap: Record<Status, { label: string; className: string }> = {
  active: { label: 'Active', className: 'badge-success' },
  inactive: { label: 'Inactive', className: 'badge-muted' },
  draft: { label: 'Draft', className: 'badge-muted' },
  confirmed: { label: 'Confirmed', className: 'badge-info' },
  fulfilled: { label: 'Fulfilled', className: 'badge-success' },
  paid: { label: 'Paid', className: 'badge-success' },
  closed: { label: 'Closed', className: 'badge-muted' },
  cancelled: { label: 'Cancelled', className: 'badge-danger' },
  pending: { label: 'Pending', className: 'badge-warning' },
  approved: { label: 'Approved', className: 'badge-success' },
  rejected: { label: 'Rejected', className: 'badge-danger' },
  overdue: { label: 'Overdue', className: 'badge-danger' },
  partial: { label: 'Partial', className: 'badge-warning' },
  ordered: { label: 'Ordered', className: 'badge-info' },
  received: { label: 'Received', className: 'badge-success' },
  posted: { label: 'Posted', className: 'badge-success' },
  reversed: { label: 'Reversed', className: 'badge-danger' },
  'low-stock': { label: 'Low Stock', className: 'badge-warning' },
  'out-of-stock': { label: 'Out of Stock', className: 'badge-danger' },
  'in-stock': { label: 'In Stock', className: 'badge-success' },
  credit: { label: 'Credit', className: 'badge-warning' },
  cash: { label: 'Cash', className: 'badge-success' },
  'mobile-money': { label: 'M-Pesa', className: 'badge-info' },
  card: { label: 'Card', className: 'badge-muted' },
};

interface StatusBadgeProps {
  status: Status;
  customLabel?: string;
}

export default function StatusBadge({ status, customLabel }: StatusBadgeProps) {
  const config = statusMap[status] || { label: status, className: 'badge-muted' };
  return (
    <span className={config.className}>
      {customLabel || config.label}
    </span>
  );
}