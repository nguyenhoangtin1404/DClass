<?php
header('Content-Type: text/html; charset=utf-8');
require __DIR__ . '/../config/db.php'; require __DIR__ . '/../lib/tro_giup.php'; require __DIR__ . '/../lib/ghi_nho.php';
if (isset($_SESSION['giao_vien_id']) || thu_cookie_ghi_nho($pdo)) { header('Location: trang_chinh.php'); exit; }
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Đăng nhập</title>
<link rel="icon" type="image/svg+xml" href="favicon.svg"><link rel="apple-touch-icon" href="favicon.svg">
<link rel="stylesheet" href="vendor/bootswatch/bootstrap.min.css"><link rel="stylesheet" href="vendor/bootstrap-icons/bootstrap-icons.css"><link rel="stylesheet" href="vendor/aos/aos.css">  <link rel="stylesheet" href="theme.css"></head><body class="bg-light">
<div class="container py-5 safe-bottom"><div class="row justify-content-center"><div class="col-md-4"><div class="card shadow-sm" data-aos="fade-up"><div class="card-body">
<h5 class="mb-3">Giáo viên đăng nhập</h5>
<div class="mb-2"><label class="form-label">Tài khoản</label><input id="u" class="form-control" value="gv1"></div>
<div class="mb-2"><label class="form-label">Mật khẩu</label><input id="p" type="password" class="form-control" value="123456"></div>
<button id="btn" class="btn btn-primary w-100 btn-lg">Đăng nhập</button>
<div id="msg" class="small text-danger mt-2"></div>
</div></div></div></div></div>
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

  try {
    if (!document.getElementById('ghi_nho')) {
      const wrap = document.createElement('div');
      wrap.className = 'form-check mb-2';
      wrap.innerHTML = '<input class="form-check-input" type="checkbox" id="ghi_nho">' +
                       '<label class="form-check-label" for="ghi_nho">Ghi nhớ đăng nhập</label>';
      elBtn.parentNode.insertBefore(wrap, elBtn);
    }
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
        elMsg.textContent = 'Đăng nhập sai quá 3 lần ('+ sl +'/3). Thử đăng lại sau 10 phút.';
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
</body></html>



