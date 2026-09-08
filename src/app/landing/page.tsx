'use client';
import React, { useState, useEffect, useRef } from 'react';
import Link from 'next/link';
import AppLogo from '@/components/ui/AppLogo';

// ─── Navbar ────────────────────────────────────────────────────────────────────
function Navbar() {
  const [scrolled, setScrolled] = useState(false);
  const [menuOpen, setMenuOpen] = useState(false);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 20);
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  const links = ['Home', 'Features', 'Solutions', 'How It Works', 'Pricing', 'About', 'Contact'];

  return (
    <nav
      className={`fixed top-0 left-0 right-0 z-50 transition-all duration-300 ${
        scrolled ? 'bg-white/95 backdrop-blur-md shadow-sm border-b border-slate-100' : 'bg-transparent'
      }`}
    >
      <div className="max-w-7xl mx-auto px-5 sm:px-8 h-16 flex items-center justify-between">
        {/* Logo */}
        <Link href="/landing" className="flex items-center gap-2.5 shrink-0">
          <AppLogo size={32} />
          <span className="text-lg font-bold text-slate-900 tracking-tight">HardFlow</span>
        </Link>

        {/* Desktop nav */}
        <div className="hidden lg:flex items-center gap-1">
          {links.map(l => (
            <a
              key={l}
              href={`#${l.toLowerCase().replace(/\s+/g, '-')}`}
              className="px-3 py-1.5 text-sm font-medium text-slate-600 hover:text-slate-900 rounded-md hover:bg-slate-100 transition-colors"
            >
              {l}
            </a>
          ))}
        </div>

        {/* CTA */}
        <div className="hidden lg:flex items-center gap-3">
          <Link
            href="/login"
            className="px-4 py-2 text-sm font-semibold text-slate-700 hover:text-slate-900 transition-colors"
          >
            Sign In
          </Link>
          <Link
            href="/login"
            className="px-4 py-2 text-sm font-semibold bg-[#1e40af] text-white rounded-lg hover:bg-[#1d3a9e] transition-colors shadow-sm"
          >
            Get Started
          </Link>
        </div>

        {/* Mobile toggle */}
        <button
          className="lg:hidden p-2 rounded-md text-slate-600 hover:bg-slate-100 transition-colors"
          onClick={() => setMenuOpen(v => !v)}
          aria-label="Toggle menu"
        >
          <div className="w-5 h-4 flex flex-col justify-between">
            <span className={`block h-0.5 bg-current transition-all ${menuOpen ? 'rotate-45 translate-y-1.5' : ''}`} />
            <span className={`block h-0.5 bg-current transition-all ${menuOpen ? 'opacity-0' : ''}`} />
            <span className={`block h-0.5 bg-current transition-all ${menuOpen ? '-rotate-45 -translate-y-1.5' : ''}`} />
          </div>
        </button>
      </div>

      {/* Mobile menu */}
      {menuOpen && (
        <div className="lg:hidden bg-white border-b border-slate-100 px-5 pb-4 space-y-1">
          {links.map(l => (
            <a
              key={l}
              href={`#${l.toLowerCase().replace(/\s+/g, '-')}`}
              onClick={() => setMenuOpen(false)}
              className="block px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 rounded-md transition-colors"
            >
              {l}
            </a>
          ))}
          <div className="pt-2 flex flex-col gap-2">
            <Link href="/login" className="block px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 rounded-md transition-colors">
              Sign In
            </Link>
            <Link href="/login" className="block px-3 py-2.5 text-sm font-semibold bg-[#1e40af] text-white rounded-lg text-center hover:bg-[#1d3a9e] transition-colors">
              Get Started
            </Link>
          </div>
        </div>
      )}
    </nav>
  );
}

// ─── Hero ───────────────────────────────────────────────────────────────────────
function Hero() {
  return (
    <section
      id="home"
      className="relative min-h-screen flex items-center overflow-hidden"
      style={{
        background: 'linear-gradient(135deg, #0f172a 0%, #1e3a8a 45%, #1e40af 70%, #1d4ed8 100%)',
      }}
    >
      {/* Animated background blobs */}
      <div className="absolute inset-0 overflow-hidden pointer-events-none">
        <div
          className="absolute w-[600px] h-[600px] rounded-full opacity-20"
          style={{
            background: 'radial-gradient(circle, #60a5fa 0%, transparent 70%)',
            top: '-10%',
            right: '-5%',
            animation: 'float 8s ease-in-out infinite',
          }}
        />
        <div
          className="absolute w-[400px] h-[400px] rounded-full opacity-10"
          style={{
            background: 'radial-gradient(circle, #f59e0b 0%, transparent 70%)',
            bottom: '10%',
            left: '-5%',
            animation: 'float 10s ease-in-out infinite reverse',
          }}
        />
        {/* Grid pattern */}
        <div
          className="absolute inset-0 opacity-5"
          style={{
            backgroundImage: `linear-gradient(rgba(255,255,255,0.3) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.3) 1px, transparent 1px)`,
            backgroundSize: '60px 60px',
          }}
        />
      </div>

      <div className="relative max-w-7xl mx-auto px-5 sm:px-8 pt-24 pb-20 w-full">
        <div className="max-w-4xl">
          {/* Badge */}
          <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 border border-white/20 text-white/80 text-xs font-medium mb-8 backdrop-blur-sm">
            <span className="w-1.5 h-1.5 rounded-full bg-[#f59e0b] animate-pulse" />
            Built for Wholesale & Retail Hardware Businesses
          </div>

          <h1 className="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-bold text-white leading-[1.05] tracking-tight mb-6">
            Run Your Hardware<br />
            <span className="text-transparent bg-clip-text" style={{ backgroundImage: 'linear-gradient(90deg, #93c5fd, #f59e0b)' }}>
              Business Smarter
            </span><br />
            with HardFlow
          </h1>

          <p className="text-lg sm:text-xl text-white/70 leading-relaxed max-w-2xl mb-10">
            Manage sales, stock, purchases, customer debts, expenses, payments, and reports from one powerful system built for wholesale and retail hardware businesses.
          </p>

          <div className="flex flex-col sm:flex-row gap-4">
            <Link
              href="/login"
              className="inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-xl bg-[#f59e0b] text-slate-900 font-bold text-base hover:bg-[#f59e0b]/90 transition-all duration-200 shadow-lg shadow-amber-500/25 hover:shadow-amber-500/40 hover:-translate-y-0.5"
            >
              Get Started
              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
              </svg>
            </Link>
            <a
              href="#features"
              className="inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-xl bg-white/10 text-white font-semibold text-base border border-white/20 hover:bg-white/20 transition-all duration-200 backdrop-blur-sm"
            >
              Explore Features
            </a>
          </div>

          {/* Quick stats */}
          <div className="mt-16 grid grid-cols-3 sm:grid-cols-3 gap-4 max-w-lg">
            {[
              { value: '12+', label: 'Business Modules' },
              { value: '6', label: 'User Roles' },
              { value: '1', label: 'Unified System' },
            ].map(s => (
              <div key={s.label} className="text-center">
                <p className="text-2xl sm:text-3xl font-bold text-white">{s.value}</p>
                <p className="text-xs text-white/50 mt-0.5">{s.label}</p>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Bottom fade */}
      <div className="absolute bottom-0 left-0 right-0 h-24 bg-gradient-to-t from-slate-50 to-transparent" />

      <style>{`
        @keyframes float {
          0%, 100% { transform: translateY(0px) scale(1); }
          50% { transform: translateY(-30px) scale(1.05); }
        }
      `}</style>
    </section>
  );
}

// ─── Intro Band ─────────────────────────────────────────────────────────────────
function IntroBand() {
  return (
    <section className="bg-slate-50 py-20 px-5 sm:px-8">
      <div className="max-w-4xl mx-auto text-center">
        <h2 className="text-2xl sm:text-3xl lg:text-4xl font-bold text-slate-900 mb-5 leading-tight">
          Everything Your Hardware Business Needs in One Place
        </h2>
        <p className="text-base sm:text-lg text-slate-600 leading-relaxed">
          HardFlow brings your daily business operations together, helping you know what you have in stock, what you have sold, what customers owe you, what you have spent, and how your business is performing.
        </p>
      </div>
    </section>
  );
}

// ─── Features Bento ─────────────────────────────────────────────────────────────
const FEATURES = [
  {
    icon: '📦',
    title: 'Smart Inventory Management',
    desc: 'Know exactly what is in stock. Track every stock movement, receive low stock alerts, manage damaged items, and keep a complete stock history.',
    accent: '#1e40af',
    size: 'large',
  },
  {
    icon: '🧾',
    title: 'Fast & Flexible Sales',
    desc: 'Handle retail and wholesale sales, quotations, different price levels, discounts, credit sales, invoices, and receipts.',
    accent: '#f59e0b',
    size: 'normal',
  },
  {
    icon: '👥',
    title: 'Customer & Debt Tracking',
    desc: 'Keep customer records, track outstanding debts, record partial payments, monitor overdue balances, and generate customer statements.',
    accent: '#0284c7',
    size: 'normal',
  },
  {
    icon: '🛒',
    title: 'Purchases & Stock Receiving',
    desc: 'Record purchases, receive goods in full or partially, compare ordered and received quantities, and maintain accurate purchasing history.',
    accent: '#16a34a',
    size: 'normal',
  },
  {
    icon: '💰',
    title: 'Know Where Your Money Goes',
    desc: 'Track cash, mobile money, bank payments, expenses, customer payments, and daily cash balances from one place.',
    accent: '#dc2626',
    size: 'normal',
  },
  {
    icon: '🔐',
    title: 'Control Your Business',
    desc: 'Give each employee the right level of access. Owners, managers, cashiers, storekeepers, accountants, and salespeople can have different permissions.',
    accent: '#7c3aed',
    size: 'large',
  },
];

function FeatureCard({ feature }: { feature: typeof FEATURES[0] }) {
  return (
    <div
      className={`group relative bg-white rounded-2xl p-6 border border-slate-100 hover:border-slate-200 hover:shadow-lg transition-all duration-300 hover:-translate-y-1 overflow-hidden ${
        feature.size === 'large' ? 'sm:col-span-2' : ''
      }`}
    >
      {/* Accent bar */}
      <div
        className="absolute top-0 left-0 right-0 h-0.5 opacity-0 group-hover:opacity-100 transition-opacity duration-300"
        style={{ background: feature.accent }}
      />
      <div
        className="w-12 h-12 rounded-xl flex items-center justify-center text-2xl mb-4"
        style={{ background: `${feature.accent}15` }}
      >
        {feature.icon}
      </div>
      <h3 className="text-base font-bold text-slate-900 mb-2">{feature.title}</h3>
      <p className="text-sm text-slate-500 leading-relaxed">{feature.desc}</p>
    </div>
  );
}

function FeaturesSection() {
  return (
    <section id="features" className="bg-slate-50 py-20 px-5 sm:px-8">
      <div className="max-w-6xl mx-auto">
        <div className="text-center mb-12">
          <span className="inline-block px-3 py-1 rounded-full bg-[#1e40af]/10 text-[#1e40af] text-xs font-semibold uppercase tracking-wider mb-3">
            Features
          </span>
          <h2 className="text-2xl sm:text-3xl lg:text-4xl font-bold text-slate-900">
            Built for How Hardware Businesses Actually Work
          </h2>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {FEATURES.map(f => (
            <FeatureCard key={f.title} feature={f} />
          ))}
        </div>
      </div>
    </section>
  );
}

// ─── Owner Insights ──────────────────────────────────────────────────────────────
const STATS = [
  { label: "Today\'s Sales", value: 'TZS 4.2M', icon: '📈', color: '#1e40af', bg: '#eff6ff' },
  { label: 'Gross Profit', value: 'TZS 1.1M', icon: '💹', color: '#16a34a', bg: '#f0fdf4' },
  { label: 'Customer Debts', value: 'TZS 8.7M', icon: '⚠️', color: '#dc2626', bg: '#fef2f2' },
  { label: 'Stock Value', value: 'TZS 62M', icon: '📦', color: '#0284c7', bg: '#f0f9ff' },
  { label: 'Expenses', value: 'TZS 320K', icon: '💸', color: '#d97706', bg: '#fffbeb' },
  { label: 'Low Stock', value: '14 Items', icon: '🔴', color: '#7c3aed', bg: '#f5f3ff' },
];

function OwnerInsights() {
  return (
    <section id="solutions" className="bg-white py-24 px-5 sm:px-8">
      <div className="max-w-6xl mx-auto">
        <div className="grid lg:grid-cols-2 gap-16 items-center">
          {/* Left */}
          <div>
            <span className="inline-block px-3 py-1 rounded-full bg-[#f59e0b]/10 text-[#d97706] text-xs font-semibold uppercase tracking-wider mb-4">
              Business Intelligence
            </span>
            <h2 className="text-3xl sm:text-4xl font-bold text-slate-900 leading-tight mb-5">
              Know What Is Happening in Your Business
            </h2>
            <p className="text-slate-600 text-base leading-relaxed mb-8">
              From today's sales to your current stock value, HardFlow gives you the information you need to make better decisions — without waiting for end-of-day reports.
            </p>
            <Link
              href="/login"
              className="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-[#1e40af] text-white font-semibold text-sm hover:bg-[#1d3a9e] transition-colors shadow-sm"
            >
              See Your Dashboard
              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
              </svg>
            </Link>
          </div>

          {/* Right — dashboard mockup */}
          <div className="relative">
            <div className="bg-slate-900 rounded-2xl p-4 shadow-2xl">
              {/* Fake topbar */}
              <div className="flex items-center gap-2 mb-4 px-1">
                <div className="w-3 h-3 rounded-full bg-red-500/60" />
                <div className="w-3 h-3 rounded-full bg-yellow-500/60" />
                <div className="w-3 h-3 rounded-full bg-green-500/60" />
                <div className="ml-3 flex-1 h-5 rounded bg-slate-700/60" />
              </div>
              <div className="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                {STATS.map(s => (
                  <div
                    key={s.label}
                    className="rounded-xl p-3"
                    style={{ background: s.bg }}
                  >
                    <div className="flex items-center justify-between mb-1.5">
                      <span className="text-xs font-medium text-slate-500">{s.label}</span>
                      <span className="text-base">{s.icon}</span>
                    </div>
                    <p className="text-sm font-bold" style={{ color: s.color }}>{s.value}</p>
                  </div>
                ))}
              </div>
              {/* Fake chart bar */}
              <div className="mt-3 bg-slate-800 rounded-xl p-3">
                <div className="flex items-end gap-1.5 h-12">
                  {[40, 65, 45, 80, 55, 90, 70, 85, 60, 75, 95, 50].map((h, i) => (
                    <div
                      key={i}
                      className="flex-1 rounded-sm"
                      style={{ height: `${h}%`, background: i === 11 ? '#f59e0b' : '#1e40af', opacity: i === 11 ? 1 : 0.5 }}
                    />
                  ))}
                </div>
              </div>
            </div>
            {/* Glow */}
            <div className="absolute -inset-4 rounded-3xl bg-[#1e40af]/10 blur-2xl -z-10" />
          </div>
        </div>
      </div>
    </section>
  );
}

// ─── Multi-Branch ────────────────────────────────────────────────────────────────
function MultiBranch() {
  return (
    <section className="bg-slate-50 py-24 px-5 sm:px-8">
      <div className="max-w-6xl mx-auto">
        <div className="grid lg:grid-cols-5 gap-10 items-center">
          {/* Visual */}
          <div className="lg:col-span-2 flex justify-center">
            <div className="relative w-56 h-56">
              {/* Central hub */}
              <div className="absolute inset-0 m-auto w-20 h-20 rounded-2xl bg-[#1e40af] flex items-center justify-center shadow-xl z-10">
                <span className="text-3xl">🏢</span>
              </div>
              {/* Branch nodes */}
              {[
                { label: 'Branch A', top: '0%', left: '50%', emoji: '🏪' },
                { label: 'Branch B', top: '50%', left: '0%', emoji: '🏪' },
                { label: 'Branch C', top: '50%', left: '100%', emoji: '🏪' },
                { label: 'Branch D', top: '100%', left: '50%', emoji: '🏪' },
              ].map(b => (
                <div
                  key={b.label}
                  className="absolute -translate-x-1/2 -translate-y-1/2 w-12 h-12 rounded-xl bg-white border-2 border-[#1e40af]/20 flex items-center justify-center shadow-md text-xl"
                  style={{ top: b.top, left: b.left }}
                >
                  {b.emoji}
                </div>
              ))}
              {/* Connecting lines */}
              <svg className="absolute inset-0 w-full h-full" viewBox="0 0 224 224">
                <line x1="112" y1="112" x2="112" y2="28" stroke="#1e40af" strokeWidth="1.5" strokeDasharray="4 3" opacity="0.3" />
                <line x1="112" y1="112" x2="28" y2="112" stroke="#1e40af" strokeWidth="1.5" strokeDasharray="4 3" opacity="0.3" />
                <line x1="112" y1="112" x2="196" y2="112" stroke="#1e40af" strokeWidth="1.5" strokeDasharray="4 3" opacity="0.3" />
                <line x1="112" y1="112" x2="112" y2="196" stroke="#1e40af" strokeWidth="1.5" strokeDasharray="4 3" opacity="0.3" />
              </svg>
            </div>
          </div>

          {/* Content */}
          <div className="lg:col-span-3">
            <span className="inline-block px-3 py-1 rounded-full bg-[#1e40af]/10 text-[#1e40af] text-xs font-semibold uppercase tracking-wider mb-4">
              Multi-Branch Ready
            </span>
            <h2 className="text-3xl sm:text-4xl font-bold text-slate-900 leading-tight mb-5">
              Built to Grow With Your Business
            </h2>
            <p className="text-slate-600 text-base leading-relaxed mb-8">
              Whether you operate one hardware shop today or multiple branches tomorrow, HardFlow is designed to grow with you. Manage your business, branches, employees, products, and transactions from one system.
            </p>
            <div className="grid grid-cols-2 gap-3">
              {[
                'Centralised product catalogue',
                'Branch-level reporting',
                'Inter-branch stock transfers',
                'Per-branch user access',
                'Consolidated financials',
                'Scalable from day one',
              ].map(item => (
                <div key={item} className="flex items-center gap-2 text-sm text-slate-700">
                  <span className="w-4 h-4 rounded-full bg-[#1e40af]/10 flex items-center justify-center shrink-0">
                    <svg className="w-2.5 h-2.5 text-[#1e40af]" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={3}>
                      <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                  </span>
                  {item}
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

// ─── Security / Accountability ───────────────────────────────────────────────────
const AUDIT_ITEMS = [
  { action: 'Sale recorded', user: 'Cashier — Amina', time: '2 min ago', icon: '🧾' },
  { action: 'Price changed', user: 'Manager — John', time: '15 min ago', icon: '✏️' },
  { action: 'Stock received', user: 'Storekeeper — Ali', time: '1 hr ago', icon: '📦' },
  { action: 'Expense approved', user: 'Owner — David', time: '2 hr ago', icon: '✅' },
  { action: 'Inventory adjusted', user: 'Manager — John', time: '3 hr ago', icon: '🔧' },
];

function SecuritySection() {
  return (
    <section id="how-it-works" className="bg-white py-24 px-5 sm:px-8">
      <div className="max-w-6xl mx-auto">
        <div className="grid lg:grid-cols-2 gap-16 items-center">
          {/* Audit log mockup */}
          <div className="order-2 lg:order-1">
            <div className="bg-slate-900 rounded-2xl overflow-hidden shadow-2xl">
              <div className="px-4 py-3 border-b border-slate-700 flex items-center gap-2">
                <span className="text-slate-400 text-xs font-mono">Activity Log — Today</span>
                <span className="ml-auto w-2 h-2 rounded-full bg-green-400 animate-pulse" />
              </div>
              <div className="divide-y divide-slate-800">
                {AUDIT_ITEMS.map((item, i) => (
                  <div key={i} className="flex items-center gap-3 px-4 py-3">
                    <span className="text-lg">{item.icon}</span>
                    <div className="flex-1 min-w-0">
                      <p className="text-sm font-medium text-slate-200 truncate">{item.action}</p>
                      <p className="text-xs text-slate-500 truncate">{item.user}</p>
                    </div>
                    <span className="text-xs text-slate-600 shrink-0">{item.time}</span>
                  </div>
                ))}
              </div>
            </div>
          </div>

          {/* Content */}
          <div className="order-1 lg:order-2">
            <span className="inline-block px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-semibold uppercase tracking-wider mb-4">
              Accountability
            </span>
            <h2 className="text-3xl sm:text-4xl font-bold text-slate-900 leading-tight mb-5">
              Every Important Action Is Accountable
            </h2>
            <p className="text-slate-600 text-base leading-relaxed mb-6">
              Know who made a sale, changed a price, received stock, approved an expense, or adjusted inventory. HardFlow keeps important business activities traceable and controlled.
            </p>
            <div className="space-y-3">
              {[
                { icon: '👁️', text: 'Full audit trail for every transaction' },
                { icon: '🔒', text: 'Role-based access — staff see only what they need' },
                { icon: '📋', text: 'Approval workflows for sensitive actions' },
                { icon: '🚨', text: 'Alerts for unusual activity patterns' },
              ].map(item => (
                <div key={item.text} className="flex items-start gap-3">
                  <span className="text-lg mt-0.5">{item.icon}</span>
                  <p className="text-sm text-slate-700">{item.text}</p>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

// ─── Stop Guessing ───────────────────────────────────────────────────────────────
function StopGuessing() {
  const lines = [
    { know: 'what you sold.', icon: '🧾' },
    { know: 'what is in stock.', icon: '📦' },
    { know: 'who owes you.', icon: '👥' },
    { know: 'what you spent.', icon: '💸' },
    { know: 'how your business is performing.', icon: '📊' },
  ];

  return (
    <section
      className="py-24 px-5 sm:px-8 relative overflow-hidden"
      style={{ background: 'linear-gradient(135deg, #0f172a 0%, #1e3a8a 60%, #1e40af 100%)' }}
    >
      <div className="absolute inset-0 opacity-5 pointer-events-none"
        style={{
          backgroundImage: `radial-gradient(circle, white 1px, transparent 1px)`,
          backgroundSize: '40px 40px',
        }}
      />
      <div className="max-w-4xl mx-auto text-center relative">
        <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-4">
          Stop Guessing.{' '}
          <span className="text-transparent bg-clip-text" style={{ backgroundImage: 'linear-gradient(90deg, #f59e0b, #fcd34d)' }}>
            Start Knowing.
          </span>
        </h2>
        <p className="text-white/60 text-base mb-12">HardFlow keeps your hardware business flowing.</p>

        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-12">
          {lines.map((l, i) => (
            <div
              key={i}
              className={`flex items-center gap-3 bg-white/8 border border-white/10 rounded-xl px-5 py-4 text-left backdrop-blur-sm ${
                i === 4 ? 'sm:col-span-2 lg:col-span-1' : ''
              }`}
            >
              <span className="text-2xl">{l.icon}</span>
              <p className="text-white/90 text-sm font-medium">
                <span className="text-white/50">Know </span>{l.know}
              </p>
            </div>
          ))}
        </div>

        <p className="text-white/50 text-sm italic">HardFlow — Your Hardware Business, Under Control.</p>
      </div>
    </section>
  );
}

// ─── Final CTA ───────────────────────────────────────────────────────────────────
function FinalCTA() {
  return (
    <section id="pricing" className="bg-slate-50 py-24 px-5 sm:px-8">
      <div className="max-w-3xl mx-auto text-center">
        <div className="bg-white rounded-3xl border border-slate-100 shadow-xl p-10 sm:p-14">
          <span className="text-4xl mb-5 block">🚀</span>
          <h2 className="text-3xl sm:text-4xl font-bold text-slate-900 mb-4 leading-tight">
            Ready to Take Control of Your Hardware Business?
          </h2>
          <p className="text-slate-600 text-base leading-relaxed mb-8 max-w-xl mx-auto">
            Bring your sales, inventory, customers, purchases, payments, and reports together with HardFlow.
          </p>
          <Link
            href="/login"
            className="inline-flex items-center gap-2 px-8 py-4 rounded-xl bg-[#1e40af] text-white font-bold text-base hover:bg-[#1d3a9e] transition-all duration-200 shadow-lg shadow-blue-500/20 hover:shadow-blue-500/30 hover:-translate-y-0.5"
          >
            Get Started with HardFlow
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
            </svg>
          </Link>
          <p className="mt-4 text-xs text-slate-400">No credit card required · Set up in minutes</p>
        </div>
      </div>
    </section>
  );
}

// ─── Footer ──────────────────────────────────────────────────────────────────────
function Footer() {
  return (
    <footer id="contact" className="bg-slate-900 text-slate-400 py-10 px-5 sm:px-8">
      <div className="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
        <div className="flex items-center gap-2.5">
          <AppLogo size={24} className="brightness-0 invert opacity-60" />
          <span className="text-sm font-semibold text-slate-300">HardFlow</span>
          <span className="text-slate-600 text-xs ml-1">— Your Hardware Business, Under Control.</span>
        </div>
        <div className="flex items-center gap-5 text-xs">
          {['About', 'Features', 'Pricing', 'Contact'].map(l => (
            <a key={l} href={`#${l.toLowerCase()}`} className="hover:text-slate-200 transition-colors">
              {l}
            </a>
          ))}
        </div>
        <p className="text-xs text-slate-600">© 2026 HardFlow. All rights reserved.</p>
      </div>
    </footer>
  );
}

// ─── Page ────────────────────────────────────────────────────────────────────────
export default function LandingPage() {
  return (
    <div className="min-h-screen">
      <Navbar />
      <Hero />
      <IntroBand />
      <FeaturesSection />
      <OwnerInsights />
      <MultiBranch />
      <SecuritySection />
      <StopGuessing />
      <FinalCTA />
      <Footer />
    </div>
  );
}
