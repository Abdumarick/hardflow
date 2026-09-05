'use client';
import React, { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/context/AuthContext';
import AppLogo from '@/components/ui/AppLogo';
import { Eye, EyeOff, LogIn, AlertCircle, ChevronDown } from 'lucide-react';

const DEMO_ACCOUNTS = [
  { label: 'Business Owner', email: 'owner@hardflow.co', password: 'owner123' },
  { label: 'Branch Manager', email: 'manager@hardflow.co', password: 'manager123' },
  { label: 'Cashier', email: 'cashier@hardflow.co', password: 'cashier123' },
  { label: 'Accountant', email: 'accountant@hardflow.co', password: 'accountant123' },
];

export default function LoginPage() {
  const { login, user, isLoading } = useAuth();
  const router = useRouter();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [showDemoPanel, setShowDemoPanel] = useState(false);

  // Redirect if already authenticated
  useEffect(() => {
    if (!isLoading && user) {
      router.replace('/');
    }
  }, [user, isLoading, router]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    if (!email.trim()) { setError('Email is required.'); return; }
    if (!password) { setError('Password is required.'); return; }

    setSubmitting(true);
    const result = await login(email.trim(), password);
    setSubmitting(false);

    if (result.success) {
      router.replace('/');
    } else {
      setError(result.error ?? 'Login failed. Please try again.');
    }
  };

  const fillDemo = (acc: typeof DEMO_ACCOUNTS[0]) => {
    setEmail(acc.email);
    setPassword(acc.password);
    setError('');
    setShowDemoPanel(false);
  };

  if (isLoading) {
    return (
      <div className="min-h-screen bg-background flex items-center justify-center">
        <div className="w-8 h-8 border-2 border-primary border-t-transparent rounded-full animate-spin" />
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-background flex">
      {/* Left decorative panel */}
      <div className="hidden lg:flex lg:w-[52%] xl:w-[56%] relative overflow-hidden bg-gradient-to-br from-primary via-primary/90 to-primary/70 flex-col justify-between p-12">
        {/* Background pattern */}
        <div className="absolute inset-0 opacity-10"
          style={{
            backgroundImage: `radial-gradient(circle at 20% 50%, white 1px, transparent 1px),
              radial-gradient(circle at 80% 20%, white 1px, transparent 1px)`,
            backgroundSize: '60px 60px',
          }}
        />
        <div className="absolute -bottom-32 -left-32 w-96 h-96 rounded-full bg-white/5 blur-3xl" />
        <div className="absolute -top-20 -right-20 w-80 h-80 rounded-full bg-white/8 blur-2xl" />

        {/* Logo */}
        <div className="relative flex items-center gap-3">
          <AppLogo size={40} className="brightness-0 invert" />
          <span className="text-2xl font-bold text-white tracking-tight">HardFlow</span>
        </div>

        {/* Center content */}
        <div className="relative space-y-6">
          <div className="space-y-3">
            <p className="text-white/60 text-sm font-medium uppercase tracking-widest">Hardware Business Management</p>
            <h1 className="text-4xl xl:text-5xl font-bold text-white leading-tight">
              Run your hardware<br />business smarter.
            </h1>
            <p className="text-white/70 text-lg leading-relaxed max-w-md">
              Sales, inventory, purchases, expenses, and team management — all in one platform built for Tanzanian hardware businesses.
            </p>
          </div>

          {/* Stats */}
          <div className="grid grid-cols-3 gap-4 pt-4">
            {[
              { value: '12+', label: 'Modules' },
              { value: '6', label: 'User Roles' },
              { value: '100%', label: 'Local Currency' },
            ].map(stat => (
              <div key={stat.label} className="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                <p className="text-2xl font-bold text-white">{stat.value}</p>
                <p className="text-white/60 text-xs mt-0.5">{stat.label}</p>
              </div>
            ))}
          </div>
        </div>

        <p className="relative text-white/40 text-xs">© 2026 HardFlow. All rights reserved.</p>
      </div>

      {/* Right login panel */}
      <div className="flex-1 flex flex-col items-center justify-center px-6 sm:px-10 py-12">
        {/* Mobile logo */}
        <div className="lg:hidden flex items-center gap-2 mb-10">
          <AppLogo size={32} />
          <span className="text-xl font-bold text-foreground">HardFlow</span>
        </div>

        <div className="w-full max-w-sm space-y-8">
          {/* Header */}
          <div className="space-y-1.5">
            <h2 className="text-2xl font-bold text-foreground">Welcome back</h2>
            <p className="text-muted-foreground text-sm">Sign in to your account to continue</p>
          </div>

          {/* Form */}
          <form onSubmit={handleSubmit} className="space-y-5" noValidate>
            {/* Error */}
            {error && (
              <div className="flex items-start gap-2.5 px-3.5 py-3 rounded-lg bg-danger/8 border border-danger/20 text-danger text-sm">
                <AlertCircle size={16} className="shrink-0 mt-0.5" />
                <span>{error}</span>
              </div>
            )}

            {/* Email */}
            <div className="space-y-1.5">
              <label htmlFor="email" className="text-sm font-medium text-foreground">
                Email address
              </label>
              <input
                id="email"
                type="email"
                autoComplete="email"
                value={email}
                onChange={e => { setEmail(e.target.value); setError(''); }}
                placeholder="you@hardflow.co"
                className="w-full h-11 px-3.5 rounded-lg border border-border bg-background text-foreground text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
              />
            </div>

            {/* Password */}
            <div className="space-y-1.5">
              <div className="flex items-center justify-between">
                <label htmlFor="password" className="text-sm font-medium text-foreground">
                  Password
                </label>
                <button
                  type="button"
                  className="text-xs text-primary hover:underline"
                  tabIndex={-1}
                >
                  Forgot password?
                </button>
              </div>
              <div className="relative">
                <input
                  id="password"
                  type={showPassword ? 'text' : 'password'}
                  autoComplete="current-password"
                  value={password}
                  onChange={e => { setPassword(e.target.value); setError(''); }}
                  placeholder="Enter your password"
                  className="w-full h-11 px-3.5 pr-10 rounded-lg border border-border bg-background text-foreground text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(v => !v)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                  tabIndex={-1}
                  aria-label={showPassword ? 'Hide password' : 'Show password'}
                >
                  {showPassword ? <EyeOff size={16} /> : <Eye size={16} />}
                </button>
              </div>
            </div>

            {/* Submit */}
            <button
              type="submit"
              disabled={submitting}
              className="w-full h-11 flex items-center justify-center gap-2 rounded-lg bg-primary text-primary-foreground text-sm font-semibold hover:bg-primary/90 active:scale-[0.98] transition-all disabled:opacity-60 disabled:cursor-not-allowed"
            >
              {submitting ? (
                <div className="w-4 h-4 border-2 border-primary-foreground border-t-transparent rounded-full animate-spin" />
              ) : (
                <>
                  <LogIn size={16} />
                  Sign in
                </>
              )}
            </button>
          </form>

          {/* Demo accounts */}
          <div className="space-y-2">
            <button
              type="button"
              onClick={() => setShowDemoPanel(v => !v)}
              className="w-full flex items-center justify-between px-3.5 py-2.5 rounded-lg border border-border bg-muted/40 text-sm text-muted-foreground hover:text-foreground hover:bg-muted/70 transition-colors"
            >
              <span className="font-medium">Demo accounts</span>
              <ChevronDown size={14} className={`transition-transform ${showDemoPanel ? 'rotate-180' : ''}`} />
            </button>

            {showDemoPanel && (
              <div className="rounded-lg border border-border bg-card overflow-hidden divide-y divide-border">
                {DEMO_ACCOUNTS.map(acc => (
                  <button
                    key={acc.email}
                    type="button"
                    onClick={() => fillDemo(acc)}
                    className="w-full flex items-center justify-between px-3.5 py-2.5 text-left hover:bg-muted/50 transition-colors"
                  >
                    <div>
                      <p className="text-sm font-medium text-foreground">{acc.label}</p>
                      <p className="text-xs text-muted-foreground">{acc.email}</p>
                    </div>
                    <span className="text-xs text-muted-foreground font-mono bg-muted px-2 py-0.5 rounded">
                      {acc.password}
                    </span>
                  </button>
                ))}
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
