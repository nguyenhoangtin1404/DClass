import { create } from 'zustand';

export type UserRole = 'ADMIN' | 'GV' | 'UNKNOWN';

interface AuthState {
  isAuthenticated: boolean;
  role: UserRole;
  username: string;
  setAuth: (payload: { isAuthenticated: boolean; role?: UserRole; username?: string }) => void;
  logout: () => void;
}

export const useAuth = create<AuthState>((set) => ({
  isAuthenticated: false,
  role: 'UNKNOWN',
  username: '',
  setAuth: ({ isAuthenticated, role, username }) =>
    set((prev) => ({
      isAuthenticated,
      role: role ?? prev.role,
      username: username ?? prev.username,
    })),
  logout: () => set({ isAuthenticated: false, role: 'UNKNOWN', username: '' }),
}));