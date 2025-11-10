<?php
header('Content-Type: text/html; charset=utf-8');
require __DIR__ . '/../config/db.php'; require __DIR__ . '/../lib/tro_giup.php'; require __DIR__ . '/../lib/ghi_nho.php';
if (isset($_SESSION['giao_vien_id']) || thu_cookie_ghi_nho($pdo)) { header('Location: /public/trang_chinh.php'); exit; }
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Đăng nhập</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/morph/bootstrap.min.css"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"><link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css">  <link rel="stylesheet" href="/public/theme.css"></head><body class="bg-light">
<div class="container py-5 safe-bottom"><div class="row justify-content-center"><div class="col-md-4"><div class="card shadow-sm" data-aos="fade-up"><div class="card-body">
<h5 class="mb-3">Giáo viên đăng nhập</h5>
<div class="mb-2"><label class="form-label">Tài khoản</label><input id="u" class="form-control" value="gv1"></div>
<div class="mb-2"><label class="form-label">Mật khẩu</label><input id="p" type="password" class="form-control" value="123456"></div>
<button id="btn" class="btn btn-primary w-100 btn-lg">Đăng nhập</button>
<div id="msg" class="small text-danger mt-2"></div>
</div></div></div></div></div>
<script>
btn.onclick = async () => {
  const r = await fetch('/api/dang_nhap.php?hanh_dong=dang_nhap',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({ten_dang_nhap:u.value, mat_khau:p.value})});
  const j = await r.json(); if (j.ok) location.href='/public/trang_chinh.php'; else msg.textContent='Sai tài khoản hoặc mật khẩu';
};
</script>
<script>
// Bổ sung: ghi nhớ đăng nhập + chống dò mật khẩu (UI)
(function(){
  try { if (typeof p !== 'undefined') p.value=''; } catch(e){}
  try {
    if (typeof u !== 'undefined'){
      u.value='';
      if ((!u.value || u.value.trim()==='')){
        var m = document.cookie.match(/(?:^|; )gv_u=([^;]*)/);
        if (m) { u.value = decodeURIComponent(m[1]); }
      }
    }
  } catch(e){}
  try {
    var btnNode = document.getElementById('btn');
    if (btnNode && !document.getElementById('ghi_nho')) {
      var wrap = document.createElement('div');
      wrap.className = 'form-check mb-2';
      wrap.innerHTML = '<input class="form-check-input" type="checkbox" id="ghi_nho">' +
                       '<label class="form-check-label" for="ghi_nho">Ghi nhớ đăng nhập</label>';
      btnNode.parentNode.insertBefore(wrap, btnNode);
    }
  } catch(e){}
  (function(){
    var elBtn = document.getElementById('btn');
    var elU = document.getElementById('u');
    var elP = document.getElementById('p');
    var elMsg = document.getElementById('msg');
    if (!elBtn || !elU || !elP) return;
    // Hỗ trợ phím Enter để gửi form
    try{
      [elU, elP].forEach(function(el){ el.addEventListener('keydown', function(e){ if(e.key === 'Enter'){ e.preventDefault(); elBtn.click(); } }); });
    }catch(e){}
    // Xoá handler cũ nếu có
    try{ if (typeof btn !== 'undefined') btn.onclick = null; }catch(e){}
    elBtn.addEventListener('click', async function(){
      const chk = document.getElementById('ghi_nho');
      const body = { ten_dang_nhap: elU.value, mat_khau: elP.value, ghi_nho: !!(chk && chk.checked) };
      try{
        const r = await fetch('/api/dang_nhap.php?hanh_dong=dang_nhap',{
          method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body)
        });
        let j = null; try{ j = await r.json(); }catch(_e){}
        if (j && j.ok) { location.href='/public/trang_chinh.php'; return; }
        // Hiển thị số lần sai/khóa nếu có
        if (j && j.thong_bao === 'qua_so_lan') {
          var sl = j.du_lieu && j.du_lieu.so_lan ? j.du_lieu.so_lan : 3;
          elMsg.textContent = 'Đăng nhập sai quá 3 lần ('+ sl +'/3). Tự động mở lại sau 10 phút.';
          return;
        }
        if (j && j.thong_bao === 'dang_nhap_that_bai') {
          var sl2 = j.du_lieu && j.du_lieu.so_lan ? j.du_lieu.so_lan : 1;
          var con = j.du_lieu && (j.du_lieu.con_lai !== undefined) ? j.du_lieu.con_lai : (3 - sl2);
          elMsg.textContent = 'Sai tài khoản hoặc mật khẩu ('+ sl2 +'/3, còn '+ Math.max(0,con) +' lần).';
          return;
        }
        elMsg.textContent='Sai tài khoản hoặc mật khẩu';
      }catch(e){ elMsg.textContent='Không thể kết nối máy chủ.'; }
    });
    // Không còn nút reset dev; khoá tự hết sau 10 phút
  })();
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>AOS.init({ duration: 350, once: true, easing: 'ease-out' });</script>
</body></html>



