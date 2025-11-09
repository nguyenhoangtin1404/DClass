<?php
header('Content-Type: text/html; charset=utf-8');
require __DIR__ . '/../config/db.php'; require __DIR__ . '/../lib/tro_giup.php';
if (isset($_SESSION['giao_vien_id'])) { header('Location: /public/trang_chinh.php'); exit; }
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Đăng nhập</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/morph/bootstrap.min.css"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"><link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css">  <link rel="stylesheet" href="/public/theme.css"></head><body class="bg-light">
<div class="container py-5 safe-bottom"><div class="row justify-content-center"><div class="col-md-4"><div class="card shadow-sm" data-aos="fade-up"><div class="card-body">
<h5 class="mb-3">Giáo viên đăng nhập</h5>
<div class="mb-2"><label class="form-label">Tài khoản</label><input id="u" class="form-control" value="gv1"></div>
<div class="mb-2"><label class="form-label">Mật khẩu</label><input id="p" type="password" class="form-control" value="123456"></div>
<button id="btn" class="btn btn-primary w-100 btn-lg">Đăng nhập</button><div id="msg" class="small text-danger mt-2"></div>
</div></div></div></div></div>
<script>
btn.onclick = async () => {
  const r = await fetch('/api/dang_nhap.php?hanh_dong=dang_nhap',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({ten_dang_nhap:u.value, mat_khau:p.value})});
  const j = await r.json(); if (j.ok) location.href='/public/trang_chinh.php'; else msg.textContent='Sai tài khoản hoặc mật khẩu';
};
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>AOS.init({ duration: 350, once: true, easing: 'ease-out' });</script>
</body></html>



