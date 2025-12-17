import React, { useEffect } from 'react';
import { BrowserRouter } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import AOS from 'aos';
import App from './App';

const queryClient = new QueryClient();

export function Root() {
  useEffect(() => {
    AOS.init({ duration: 350, once: true, easing: 'ease-out' });
  }, []);

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
