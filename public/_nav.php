<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$active = basename($_SERVER['PHP_SELF'] ?? '');
?>
<nav class="navbar navbar-light bg-light px-3">
  <a class="navbar-brand" href="/public/trang_chinh.php">DClass</a>
  <div class="ms-auto d-flex gap-2">
    <a class="btn btn-outline-secondary btn-sm <?php echo $active==='hoc_sinh_quan_ly.php'?'active':''; ?>" href="/public/hoc_sinh_quan_ly.php"><i class="bi bi-people me-1"></i>Học sinh</a>
    <a class="btn btn-outline-secondary btn-sm <?php echo $active==='lich_su.php'?'active':''; ?>" href="/public/lich_su.php"><i class="bi bi-clock-history me-1"></i>Lịch sử</a>
    <a class="btn btn-outline-secondary btn-sm <?php echo $active==='cau_hinh.php'?'active':''; ?>" href="/public/cau_hinh.php"><i class="bi bi-gear me-1"></i>Cấu hình</a>
    <button class="btn btn-outline-danger btn-sm" id="dang_xuat"><i class="bi bi-box-arrow-right me-1"></i>Đăng xuất</button>
  </div>
</nav>
<script>
  (function(){
    const btn = document.getElementById('dang_xuat');
    if(btn){ btn.onclick = async()=>{ try{ await fetch('/api/dang_nhap.php?hanh_dong=dang_xuat',{method:'POST'});}catch(_e){} location.href='/public/dang_nhap.php'; }; }
  })();
</script>

