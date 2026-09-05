'use client';
import React, { useState } from 'react';
import AppLayout, { DashboardLayout } from '@/components/AppLayout';
import ClassicDashboard from './components/ClassicDashboard';
import SmartFlowDashboard from './components/SmartFlowDashboard';
import CommandCenterDashboard from './components/CommandCenterDashboard';

export default function DashboardPage() {
  const [layout, setLayout] = useState<DashboardLayout>('classic');

  return (
    <AppLayout layout={layout} onLayoutChange={setLayout}>
      {layout === 'classic' && <ClassicDashboard />}
      {layout === 'smartflow' && <SmartFlowDashboard />}
      {layout === 'command' && <CommandCenterDashboard />}
    </AppLayout>
  );
}