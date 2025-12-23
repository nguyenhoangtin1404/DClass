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
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link rel="apple-touch-icon" href="favicon.ico">
  <link rel="stylesheet" href="vendor/bootswatch/bootstrap.min.css">
  <link rel="stylesheet" href="vendor/bootstrap-icons/bootstrap-icons.css">
  <link rel="stylesheet" href="vendor/aos/aos.css">
  <link rel="stylesheet" href="vendor/theme.css">
</head>
<body class="login-bg">
<div class="container py-5 safe-bottom full-height-center">
  <div class="row align-items-center justify-content-center g-4 login-shell">
        <div class="col-lg-5 col-xl-4">
      <div class="card glass-card border-0" data-aos="fade-up">
        <div class="card-body p-4">
          <div class="d-flex align-items-start justify-content-between mb-4">
            <div>
              <div class="brand-chip">
                <img src="../upload/star.png" alt="Ngôi sao" class="brand-icon"><span>DClass</span>
              </div>
              <h5 class="mt-3 mb-0 text-dark">Quản lý điểm thưởng</h5>
              <div class="text-muted small">Theo dõi học sinh, cộng điểm, đổi quà</div>
            </div>
            <div class="rounded-circle icon-bubble mt-1">
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
          <div id="captcha_container" class="mb-3" style="display: none;">
            <label class="form-label small text-uppercase fw-semibold text-muted">Mã xác thực</label>
            <div class="d-flex align-items-center gap-2 mb-2">
              <img id="captcha_img" src="" alt="CAPTCHA" style="border: 1px solid #ddd; border-radius: 4px; cursor: pointer;" title="Click để làm mới">
              <button type="button" id="captcha_refresh" class="btn btn-sm btn-outline-secondary" title="Làm mới">
                <i class="bi bi-arrow-clockwise"></i>
              </button>
            </div>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
              <input id="captcha_input" type="text" class="form-control" placeholder="Nhập mã xác thực" autocomplete="off" maxlength="5">
            </div>
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

  const elCaptchaContainer = document.getElementById('captcha_container');
  const elCaptchaImg = document.getElementById('captcha_img');
  const elCaptchaInput = document.getElementById('captcha_input');
  const elCaptchaRefresh = document.getElementById('captcha_refresh');
  let soLanSai = 0;

  const taiCaptcha = () => {
    if (elCaptchaImg) {
      elCaptchaImg.onerror = () => {
        elMsg.textContent = 'Không thể tải hình ảnh CAPTCHA. Vui lòng thử lại.';
      };
      elCaptchaImg.onload = () => {
        // Xóa thông báo lỗi nếu load thành công
        if (elMsg.textContent.includes('CAPTCHA')) {
          elMsg.textContent = '';
        }
      };
      elCaptchaImg.src = '../api/captcha.php?t=' + Date.now();
    }
  };

  const hienThiCaptcha = (hien) => {
    if (elCaptchaContainer) {
      elCaptchaContainer.style.display = hien ? 'block' : 'none';
      if (hien) {
        taiCaptcha();
        if (elCaptchaInput) elCaptchaInput.value = '';
      }
    }
  };

  if (elCaptchaImg) {
    elCaptchaImg.addEventListener('click', taiCaptcha);
  }
  if (elCaptchaRefresh) {
    elCaptchaRefresh.addEventListener('click', taiCaptcha);
  }

  const thucHienDangNhap = async()=> {
    const chk = document.getElementById('ghi_nho');
    const body = { 
      ten_dang_nhap: elU.value, 
      mat_khau: elP.value, 
      ghi_nho: !!(chk && chk.checked)
    };
    
    // Thêm CAPTCHA input nếu đang hiển thị
    if (elCaptchaContainer && elCaptchaContainer.style.display !== 'none') {
      body.captcha_input = elCaptchaInput ? elCaptchaInput.value : '';
    }
    
    try{
      const r = await fetch('../api/dang_nhap.php?hanh_dong=dang_nhap',{
        method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body)
      });
      let j = null; try{ j = await r.json(); }catch(_e){}
      if (j && j.ok) { 
        hienThiCaptcha(false);
        location.href='trang_chinh.php'; 
        return; 
      }
      if (j && j.thong_bao === 'qua_so_lan') {
        const sl = j.du_lieu && j.du_lieu.so_lan ? j.du_lieu.so_lan : 5;
        elMsg.textContent = 'Đăng nhập sai quá 5 lần ('+ sl +'/5). Thử lại sau 10 phút.';
        hienThiCaptcha(false);
        soLanSai = sl;
        return;
      }
      if (j && j.thong_bao === 'captcha_khong_hop_le') {
        elMsg.textContent = 'Mã xác thực không đúng. Vui lòng thử lại.';
        hienThiCaptcha(true);
        taiCaptcha();
        if (elCaptchaInput) elCaptchaInput.value = '';
        soLanSai = j.du_lieu && j.du_lieu.so_lan ? j.du_lieu.so_lan : soLanSai;
        return;
      }
      if (j && j.thong_bao === 'yeu_cau_captcha') {
        elMsg.textContent = 'Vui lòng nhập mã xác thực.';
        hienThiCaptcha(true);
        soLanSai = j.du_lieu && j.du_lieu.so_lan ? j.du_lieu.so_lan : soLanSai;
        return;
      }
      if (j && j.thong_bao === 'dang_nhap_that_bai') {
        const sl2 = j.du_lieu && j.du_lieu.so_lan ? j.du_lieu.so_lan : 1;
        const con = j.du_lieu && (j.du_lieu.con_lai !== undefined) ? j.du_lieu.con_lai : (5 - sl2);
        soLanSai = sl2;
        if (sl2 >= 3) {
          hienThiCaptcha(true);
          elMsg.textContent = 'Sai tài khoản hoặc mật khẩu ('+ sl2 +'/5). Vui lòng nhập mã xác thực.';
        } else {
          hienThiCaptcha(false);
          elMsg.textContent = 'Sai tài khoản hoặc mật khẩu ('+ sl2 +'/5, còn '+ Math.max(0,con) +' lần).';
        }
        return;
      }
      elMsg.textContent='Sai tài khoản hoặc mật khẩu';
      hienThiCaptcha(false);
    }catch(_e){ elMsg.textContent='Không thể kết nối máy chủ.'; }
  };

  [elU, elP, elCaptchaInput].forEach(el=>{
    if (el) {
      el.addEventListener('keydown', e=>{ if(e.key === 'Enter'){ e.preventDefault(); thucHienDangNhap(); } });
    }
  });
  elBtn.addEventListener('click', thucHienDangNhap);
})();
</script>
<script src="vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="vendor/aos/aos.js"></script>
<script>AOS.init({ duration: 350, once: true, easing: 'ease-out' });</script>
</body>
</html>


