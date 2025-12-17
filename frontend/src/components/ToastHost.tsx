import { useEffect } from 'react';

type Toast = { id: number; message: string; variant?: 'success' | 'info' | 'warning' | 'danger' };
const toasts: Toast[] = [];
let lastId = 0;

function removeToast(id: number) {
  const idx = toasts.findIndex((x) => x.id === id);
  if (idx >= 0) {
    toasts.splice(idx, 1);
    render();
  }
}

function render() {
  const container = document.getElementById('toastContainer');
  if (!container) return;
  container.innerHTML = '';
  toasts.forEach((t) => {
    const el = document.createElement('div');
    el.className = `toast show fade align-items-center border-0 text-bg-${t.variant || 'info'}`;
    el.setAttribute('role', 'alert');
    el.setAttribute('aria-live', 'assertive');
    el.setAttribute('aria-atomic', 'true');
    const inner = document.createElement('div');
    inner.className = 'd-flex';
    const body = document.createElement('div');
    body.className = 'toast-body';
    body.textContent = t.message;
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn-close btn-close-white me-2 m-auto';
    btn.setAttribute('data-bs-dismiss', 'toast');
    btn.setAttribute('aria-label', 'Close');
    btn.addEventListener('click', () => removeToast(t.id));
    inner.appendChild(body);
    inner.appendChild(btn);
    el.appendChild(inner);
    container.appendChild(el);
    // simple auto-hide
    setTimeout(() => removeToast(t.id), 4000);
  });
}

// eslint-disable-next-line react-refresh/only-export-components
export function pushToast(message: string, variant?: Toast['variant']) {
  toasts.push({ id: ++lastId, message, variant });
  render();
}

export default function ToastHost() {
  useEffect(() => {
    const host = document.createElement('div');
    host.id = 'toastContainer';
    host.className = 'toast-container position-fixed top-0 end-0 p-3';
    document.body.appendChild(host);
    render();
    return () => {
      host.remove();
    };
  }, []);
  return null;
}
