import { Link, useLocation } from 'react-router-dom';
import { useAuth } from '@/state/auth';
import ToastHost from './ToastHost';

function NavItem({ to, label }: { to: string; label: string }) {
  const { pathname } = useLocation();
  const isActive = pathname === to;
  return (
    <Link className={`btn btn-sm nav-square-link ${isActive ? 'active' : ''}`} to={to}>
      {label}
    </Link>
  );
}

export default function Layout({ children }: { children: React.ReactNode }) {
  const { logout, role } = useAuth();
  return (
    <div className="app-shell">
      <nav className="navbar navbar-expand-lg nav-glass px-3">
        <Link className="navbar-brand nav-brand" to="/">
          <i className="bi bi-book me-1" />
          DClass
        </Link>
        <div className="ms-auto d-flex flex-wrap gap-2 py-2 py-lg-0">
          <NavItem to="/" label="Trang chủ" />
          <NavItem to="/lich-su" label="Lịch sử" />
          <NavItem to="/bao-cao" label="Báo cáo" />
          {role === 'ADMIN' && <NavItem to="/cau-hinh" label="Cấu hình" />}
          <button className="btn btn-sm nav-square-link btn-logout" onClick={logout}>
            <i className="bi bi-box-arrow-right me-1" />
            Đăng xuất
          </button>
        </div>
      </nav>
      <div className="container py-3 safe-bottom">{children}</div>
      <ToastHost />
    </div>
  );
}
