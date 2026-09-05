'use client';
import React, { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import Sidebar from './Sidebar';
import Topbar from './Topbar';
import CommandCenterNav from './CommandCenterNav';
import { useAuth } from '@/context/AuthContext';

export type DashboardLayout = 'classic' | 'smartflow' | 'command';

interface AppLayoutProps {
  children: React.ReactNode;
  layout?: DashboardLayout;
  onLayoutChange?: (layout: DashboardLayout) => void;
}

export default function AppLayout({
  children,
  layout = 'classic',
  onLayoutChange,
}: AppLayoutProps) {
  const { user, isLoading } = useAuth();
  const router = useRouter();
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
  const [mobileSidebarOpen, setMobileSidebarOpen] = useState(false);

  useEffect(() => {
    if (!isLoading && !user) {
      router.replace('/login');
    }
  }, [user, isLoading, router]);

  if (isLoading || !user) {
    return (
      <div className="min-h-screen bg-background flex items-center justify-center">
        <div className="w-8 h-8 border-2 border-primary border-t-transparent rounded-full animate-spin" />
      </div>
    );
  }

  if (layout === 'command') {
    return (
      <div className="min-h-screen bg-background flex flex-col">
        <CommandCenterNav
          layout={layout}
          onLayoutChange={onLayoutChange}
          onMobileMenuToggle={() => setMobileSidebarOpen(!mobileSidebarOpen)}
        />
        <main className="flex-1 w-full max-w-screen-2xl mx-auto px-3 sm:px-4 lg:px-8 xl:px-10 2xl:px-16 py-4 sm:py-6">
          {children}
        </main>
      </div>
    );
  }

  if (layout === 'smartflow') {
    return (
      <div className="min-h-screen bg-background flex flex-col">
        <Topbar
          sidebarCollapsed={false}
          onSidebarToggle={() => {}}
          layout={layout}
          onLayoutChange={onLayoutChange}
          showSidebarToggle={false}
          onMobileMenuToggle={() => setMobileSidebarOpen(!mobileSidebarOpen)}
        />
        <main className="flex-1 w-full max-w-screen-2xl mx-auto px-3 sm:px-4 lg:px-8 xl:px-10 2xl:px-16 py-4 sm:py-6">
          {children}
        </main>
      </div>
    );
  }

  // Classic layout — sidebar is fixed/off-canvas on mobile, inline on desktop
  // On mobile: no margin (sidebar slides over content)
  // On desktop: margin equals sidebar width
  const mainMarginStyle = sidebarCollapsed
    ? { '--desktop-margin': 'var(--sidebar-collapsed-width)' } as React.CSSProperties
    : { '--desktop-margin': 'var(--sidebar-width)' } as React.CSSProperties;

  return (
    <div className="min-h-screen bg-background flex">
      {/* Mobile overlay */}
      {mobileSidebarOpen && (
        <div
          className="fixed inset-0 z-40 bg-foreground/30 lg:hidden"
          onClick={() => setMobileSidebarOpen(false)}
        />
      )}

      <Sidebar
        collapsed={sidebarCollapsed}
        mobileOpen={mobileSidebarOpen}
        onMobileClose={() => setMobileSidebarOpen(false)}
      />

      {/* Main content wrapper */}
      <div
        className="flex-1 flex flex-col min-w-0 main-content-area"
        style={mainMarginStyle}
      >
        <Topbar
          sidebarCollapsed={sidebarCollapsed}
          onSidebarToggle={() => setSidebarCollapsed(!sidebarCollapsed)}
          layout={layout}
          onLayoutChange={onLayoutChange}
          showSidebarToggle
          onMobileMenuToggle={() => setMobileSidebarOpen(!mobileSidebarOpen)}
        />
        <main className="flex-1 w-full px-3 sm:px-4 lg:px-6 xl:px-8 py-4 sm:py-6 max-w-screen-2xl overflow-x-hidden">
          {children}
        </main>
      </div>
    </div>
  );
}