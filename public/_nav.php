<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$active = basename($_SERVER['PHP_SELF'] ?? '');
?>
<nav class="navbar navbar-expand-lg nav-glass px-3">
  <a class="navbar-brand nav-brand" href="trang_chinh.php"><i class="bi bi-book me-1"></i>DClass</a>
  <button class="navbar-toggler border-0 ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="navMain">
    <div class="ms-auto d-flex flex-wrap gap-2 py-2 py-lg-0">
      <a class="btn btn-sm nav-square-link <?php echo $active==='trang_chinh.php'?'active':''; ?>" href="trang_chinh.php" title="Trang chủ" aria-label="Trang chủ"><i class="bi bi-house-fill"></i></a>
      <a class="btn btn-sm nav-square-link <?php echo $active==='lich_su.php'?'active':''; ?>" href="lich_su.php"><i class="bi bi-clock-history me-1"></i>Lịch sử</a>
      <a class="btn btn-sm nav-square-link <?php echo $active==='bao_cao.php'?'active':''; ?>" href="bao_cao.php"><i class="bi bi-bar-chart me-1"></i>Báo cáo</a>
      <a class="btn btn-sm nav-square-link <?php echo $active==='cau_hinh.php'?'active':''; ?>" href="cau_hinh.php"><i class="bi bi-gear me-1"></i>Cấu hình</a>
      <button class="btn btn-sm nav-square-link btn-logout" id="dang_xuat"><i class="bi bi-box-arrow-right me-1"></i>Đăng xuất</button>
    </div>
  </div>
</nav>
<script src="vendor/thong_bao.js"></script>
<script>
  (function(){
    const btn = document.getElementById('dang_xuat');
    if(btn){ btn.onclick = async()=>{ try{ await fetch('../api/dang_nhap.php?hanh_dong=dang_xuat',{method:'POST'});}catch(_e){} location.href='dang_nhap.php'; }; }
  })();
</script>

