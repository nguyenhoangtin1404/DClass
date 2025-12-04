<?php
header('Content-Type: text/html; charset=utf-8');
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/tro_giup.php';
require __DIR__ . '/../lib/ghi_nho.php';
if (isset($_SESSION['giao_vien_id']) || thu_cookie_ghi_nho($pdo)) { header('Location: trang_chinh.php'); exit; }
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Đăng nhập</title>
  <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <link rel="apple-touch-icon" href="favicon.svg">
  <link rel="stylesheet" href="vendor/bootswatch/bootstrap.min.css">
  <link rel="stylesheet" href="vendor/bootstrap-icons/bootstrap-icons.css">
  <link rel="stylesheet" href="vendor/aos/aos.css">
  <link rel="stylesheet" href="vendor/theme.css">
  <style>
    body.login-bg {
      min-height: 100vh;
      background: radial-gradient(circle at 25% 20%, #3b82f6 0, #2563eb 28%, #111827 65%, #0b1220 100%);
      color: #e8edff;
    }
    .glass-card {
      background: rgba(255, 255, 255, 0.12);
      border: 1px solid rgba(255, 255, 255, 0.18);
      box-shadow: 0 15px 50px rgba(0, 0, 0, 0.35);
      backdrop-filter: blur(12px);
      color: #e9eefb;
    }
    .glass-card h5,
    .glass-card h6,
    .glass-card p,
    .glass-card .text-dark { color: #f9fbff !important; }
    .glass-card .text-muted,
    .glass-card small,
    .glass-card label,
    .glass-card .form-check-label,
    .glass-card .text-secondary { color: #f1f4ff !important; }
    .glass-card .form-label { color: #f1f5ff !important; font-weight: 700; letter-spacing: .02em; }
    .glass-card .form-control,
    .glass-card .input-group-text {
      background: rgba(255, 255, 255, 0.15);
      border-color: rgba(255, 255, 255, 0.2);
      color: #0f172a;
    }
    .glass-card .form-control::placeholder { color: #6b7280; }
    .icon-bubble {
      width: 48px;
      height: 48px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #c7d2fe, #93c5fd);
      color: #0b1220;
    }
    .login-hero-blur {
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.15);
      border-radius: 14px;
      color: #f7fbff;
    }
    .login-hero-blur h2 { color: #f7fbff; text-shadow: 0 6px 18px rgba(0,0,0,0.18); }
    .login-hero-blur p { color: #f0f5ff; }
    .login-hero-blur small { color: #f8fbff !important; }
    .brand-chip {
      display: inline-flex;
      align-items: center;
      gap: .5rem;
      padding: .55rem 1.1rem;
      border-radius: 999px;
      background: #4c8dfd;
      color: #0b1220;
      font-weight: 600;
      box-shadow: 0 8px 25px rgba(76, 141, 253, 0.35);
    }
    .brand-chip i { color: #f8fafc; }
    .brand-chip span { color: #0b3a99; }
    .full-height-center {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .login-shell {
      width: 100%;
      max-width: 1180px;
    }
    .badge-contrast {
      background: #d9f99d;
      color: #166534;
      border: 1px solid rgba(22, 101, 52, 0.25);
    }
    .badge-contrast-secondary {
      background: #e0e7ff;
      color: #312e81;
      border: 1px solid rgba(49, 46, 129, 0.25);
    }
  </style>
</head>
<body class="login-bg">
<div class="container py-5 safe-bottom full-height-center">
  <div class="row align-items-center justify-content-center g-4 login-shell">
        <div class="col-lg-5 col-xl-4">
      <div class="card glass-card border-0" data-aos="fade-up">
        <div class="card-body p-4">
          <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
              <div class="brand-chip">
                <i class="bi bi-stars"></i><span>DClass</span>
              </div>
              <h5 class="mt-3 mb-0 text-dark">Quản lý điểm thưởng</h5>
              <div class="text-muted small">Theo dõi học sinh, cộng điểm, đổi quà</div>
            </div>
            <div class="rounded-circle icon-bubble">
              <i class="bi bi-shield-lock-fill fs-5"></i>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small text-uppercase fw-semibold text-muted">Tài khoản</label>
            <div class="input-group input-group-lg">
              <span class="input-group-text"><i class="bi bi-person"></i></span>
              <input id="u" class="form-control" placeholder="Tên đăng nhập" autocomplete="username">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small text-uppercase fw-semibold text-muted">Mật khẩu</label>
            <div class="input-group input-group-lg">
              <span class="input-group-text"><i class="bi bi-lock"></i></span>
              <input id="p" type="password" class="form-control" placeholder="••••••••" autocomplete="current-password">
            </div>
          </div>
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="ghi_nho">
              <label class="form-check-label" for="ghi_nho">Ghi nhớ đăng nhập</label>
            </div>
            <div class="text-muted small">Hỗ trợ 24/7</div>
          </div>
          <button id="btn" class="btn btn-primary w-100 btn-lg shadow-sm">Đăng nhập</button>
          <div id="msg" class="small text-danger mt-2"></div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
(function(){
  const elBtn = document.getElementById('btn');
  const elU = document.getElementById('u');
  const elP = document.getElementById('p');
  const elMsg = document.getElementById('msg');
  if (!elBtn || !elU || !elP) return;

  try {
    elP.value = '';
    elU.value = '';
    const m = document.cookie.match(/(?:^|; )gv_u=([^;]*)/);
    if (m) { elU.value = decodeURIComponent(m[1]); }
  } catch(_e){}

  const thucHienDangNhap = async()=> {
    const chk = document.getElementById('ghi_nho');
    const body = { ten_dang_nhap: elU.value, mat_khau: elP.value, ghi_nho: !!(chk && chk.checked) };
    try{
      const r = await fetch('../api/dang_nhap.php?hanh_dong=dang_nhap',{
        method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body)
      });
      let j = null; try{ j = await r.json(); }catch(_e){}
      if (j && j.ok) { location.href='trang_chinh.php'; return; }
      if (j && j.thong_bao === 'qua_so_lan') {
        const sl = j.du_lieu && j.du_lieu.so_lan ? j.du_lieu.so_lan : 3;
        elMsg.textContent = 'Đăng nhập sai quá 3 lần ('+ sl +'/3). Thử lại sau 10 phút.';
        return;
      }
      if (j && j.thong_bao === 'dang_nhap_that_bai') {
        const sl2 = j.du_lieu && j.du_lieu.so_lan ? j.du_lieu.so_lan : 1;
        const con = j.du_lieu && (j.du_lieu.con_lai !== undefined) ? j.du_lieu.con_lai : (3 - sl2);
        elMsg.textContent = 'Sai tài khoản hoặc mật khẩu ('+ sl2 +'/3, còn '+ Math.max(0,con) +' lần).';
        return;
      }
      elMsg.textContent='Sai tài khoản hoặc mật khẩu';
    }catch(_e){ elMsg.textContent='Không thể kết nối máy chủ.'; }
  };

  [elU, elP].forEach(el=>{
    el.addEventListener('keydown', e=>{ if(e.key === 'Enter'){ e.preventDefault(); thucHienDangNhap(); } });
  });
  elBtn.addEventListener('click', thucHienDangNhap);
})();
</script>
<script src="vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="vendor/aos/aos.js"></script>
<script>AOS.init({ duration: 350, once: true, easing: 'ease-out' });</script>
</body>
</html>
