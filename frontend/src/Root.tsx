import React, { useEffect } from 'react';
import { BrowserRouter } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import AOS from 'aos';
import App from './App';
import { apiClient } from './api/client';
import { useAuth } from './state/auth';

const queryClient = new QueryClient();

export function Root() {
  const { setAuth } = useAuth();

  useEffect(() => {
    AOS.init({ duration: 350, once: true, easing: 'ease-out' });
  }, []);

  useEffect(() => {
    let active = true;
    const fetchSession = async () => {
      const res = await apiClient.get<{ ten_dang_nhap: string; vai_tro: string }>('/dang_nhap.php?hanh_dong=kiem_tra');
      if (!active) return;
      if (res.ok && res.du_lieu) {
        setAuth({ isAuthenticated: true, role: res.du_lieu.vai_tro as 'ADMIN' | 'GV' | 'UNKNOWN', username: res.du_lieu.ten_dang_nhap });
      }
    };
    fetchSession();
    return () => {
      active = false;
    };
  }, [setAuth]);

  return (
    <React.StrictMode>
      <QueryClientProvider client={queryClient}>
        <BrowserRouter basename="/react">
          <App />
        </BrowserRouter>
      </QueryClientProvider>
    </React.StrictMode>
  );
}
