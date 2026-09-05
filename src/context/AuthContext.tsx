'use client';
import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';

export type UserRole = 'owner' | 'branch_manager' | 'cashier' | 'storekeeper' | 'accountant' | 'viewer';

export interface AuthUser {
  id: string;
  name: string;
  email: string;
  role: UserRole;
  branch: string;
  initials: string;
}

interface AuthContextValue {
  user: AuthUser | null;
  isLoading: boolean;
  login: (email: string, password: string) => Promise<{ success: boolean; error?: string }>;
  logout: () => void;
}

// Mock users — replace with real API calls when Laravel backend is ready
const MOCK_USERS: (AuthUser & { password: string })[] = [
  {
    id: '1',
    name: 'James Mwangi',
    email: 'owner@hardflow.co',
    password: 'owner123',
    role: 'owner',
    branch: 'Dar es Salaam Main',
    initials: 'JM',
  },
  {
    id: '2',
    name: 'Amina Saleh',
    email: 'manager@hardflow.co',
    password: 'manager123',
    role: 'branch_manager',
    branch: 'Mwanza Branch',
    initials: 'AS',
  },
  {
    id: '3',
    name: 'Peter Odhiambo',
    email: 'cashier@hardflow.co',
    password: 'cashier123',
    role: 'cashier',
    branch: 'Dar es Salaam Main',
    initials: 'PO',
  },
  {
    id: '4',
    name: 'Grace Kimani',
    email: 'accountant@hardflow.co',
    password: 'accountant123',
    role: 'accountant',
    branch: 'Dar es Salaam Main',
    initials: 'GK',
  },
];

const SESSION_KEY = 'hardflow_auth_user';

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<AuthUser | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    try {
      const stored = localStorage.getItem(SESSION_KEY);
      if (stored) {
        setUser(JSON.parse(stored));
      }
    } catch {
      // ignore parse errors
    } finally {
      setIsLoading(false);
    }
  }, []);

  const login = useCallback(async (email: string, password: string): Promise<{ success: boolean; error?: string }> => {
    // Simulate network latency — remove when wiring to Laravel
    await new Promise(r => setTimeout(r, 800));

    const found = MOCK_USERS.find(
      u => u.email.toLowerCase() === email.toLowerCase() && u.password === password
    );

    if (!found) {
      return { success: false, error: 'Invalid email or password.' };
    }

    const { password: _pw, ...authUser } = found;
    setUser(authUser);
    localStorage.setItem(SESSION_KEY, JSON.stringify(authUser));
    return { success: true };
  }, []);

  const logout = useCallback(() => {
    setUser(null);
    localStorage.removeItem(SESSION_KEY);
  }, []);

  return (
    <AuthContext.Provider value={{ user, isLoading, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used inside AuthProvider');
  return ctx;
}
