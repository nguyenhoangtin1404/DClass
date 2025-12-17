import { FormEvent, useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { apiClient } from '@/api/client';
import { useAuth } from '@/state/auth';
import { pushToast } from '@/components/ToastHost';

export default function Login() {
  const assetBase = import.meta.env.BASE_URL || '/';
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [remember, setRemember] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const navigate = useNavigate();
  const { setAuth } = useAuth();

  useEffect(() => {
    try {
      const m = document.cookie.match(/(?:^|; )gv_u=([^;]*)/);
      if (m) {
        setUsername(decodeURIComponent(m[1]));
        setRemember(true);
      }
    } catch (_e) {
      /* ignore cookie parsing errors */
    }
  }, []);

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);
    const res = await apiClient.post<{ vai_tro: string; ten_dang_nhap: string; so_lan?: number; con_lai?: number }>(
      '/dang_nhap.php?hanh_dong=dang_nhap',
      {
        ten_dang_nhap: username,
        mat_khau: password,
        ghi_nho: remember,
      }
    );
    setLoading(false);
    if (res.ok && res.du_lieu) {
      setAuth({ isAuthenticated: true, role: String(res.du_lieu.vai_tro || 'UNKNOWN'), username: res.du_lieu.ten_dang_nhap });
      navigate('/');
    } else {
      if (res.thong_bao === 'qua_so_lan') {
        const sl = (res.du_lieu as { so_lan?: number } | undefined)?.so_lan ?? 3;
        setError(`Dang nhap sai qua 3 lan (${sl}/3). Thu lai sau 10 phut.`);
        return;
      }
      if (res.thong_bao === 'dang_nhap_that_bai') {
        const sl = (res.du_lieu as { so_lan?: number; con_lai?: number } | undefined)?.so_lan ?? 1;
        const con = (res.du_lieu as { so_lan?: number; con_lai?: number } | undefined)?.con_lai ?? Math.max(0, 3 - sl);
        setError(`Sai tai khoan hoac mat khau (${sl}/3, con ${Math.max(0, con)} lan).`);
        return;
      }
      setError(res.thong_bao || 'Dang nhap that bai');
      pushToast(res.thong_bao || 'Dang nhap that bai', 'danger');
    }
  };

  return (
    <div className="login-bg">
      <div className="container py-5 safe-bottom full-height-center">
        <div className="row align-items-center justify-content-center g-4 login-shell">
          <div className="col-lg-5 col-xl-4">
            <div className="card glass-card border-0" data-aos="fade-up">
              <div className="card-body p-4">
                <div className="d-flex align-items-start justify-content-between mb-4">
                  <div>
                    <div className="brand-chip">
                      <img src={`${assetBase}upload/star.png`} alt="Ngoi sao" className="brand-icon" />
                      <span>DClass</span>
                    </div>
                    <h5 className="mt-3 mb-0 text-dark">Quản lý điểm thưởng</h5>
                    <div className="text-muted small">Theo dõi học sinh, cộng điểm, đổi quà</div>
                  </div>
                  <div className="rounded-circle icon-bubble mt-1">
                    <i className="bi bi-shield-lock-fill fs-5" />
                  </div>
                </div>
                <form onSubmit={onSubmit} className="d-grid gap-3">
                  <div className="mb-1">
                    <label className="form-label small text-uppercase fw-semibold text-muted">Tài khoản</label>
                    <div className="input-group input-group-lg">
                      <span className="input-group-text">
                        <i className="bi bi-person" />
                      </span>
                      <input
                        className="form-control"
                        placeholder="Tên đăng nhập"
                        value={username}
                        onChange={(e) => setUsername(e.target.value)}
                        autoComplete="username"
                        disabled={loading}
                      />
                    </div>
                  </div>
                  <div className="mb-1">
                    <label className="form-label small text-uppercase fw-semibold text-muted">Mật khẩu</label>
                    <div className="input-group input-group-lg">
                      <span className="input-group-text">
                        <i className="bi bi-lock" />
                      </span>
                      <input
                        className="form-control"
                        type="password"
                        placeholder="Nhập mật khẩu"
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        autoComplete="current-password"
                        disabled={loading}
                      />
                    </div>
                  </div>
                  <div className="d-flex align-items-center justify-content-between mb-1">
                    <div className="form-check">
                      <input className="form-check-input" type="checkbox" id="remember" checked={remember} onChange={(e) => setRemember(e.target.checked)} />
                      <label className="form-check-label" htmlFor="remember">
                        Ghi nhớ đăng nhập
                      </label>
                    </div>
                    <div className="text-muted small">Hỗ trợ 24/7</div>
                  </div>
                  {error && <div className="small text-danger mt-1">{error}</div>}
                  <button className="btn btn-primary w-100 btn-lg shadow-sm" type="submit" disabled={loading}>
                    {loading ? 'Đang đăng nhập...' : 'Đăng nhập'}
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
