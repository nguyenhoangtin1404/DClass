<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$active = basename($_SERVER['PHP_SELF'] ?? '');
?>
<style>
  .nav-glass {
    background: #e9f0fb;
    border: 1px solid #d7e1f5;
    color: #1f2a44;
    border-radius: 12px;
    margin: 10px auto;
    box-shadow: 0 10px 22px rgba(25, 75, 150, 0.15);
  }
  .nav-brand {
    font-weight: 800;
    letter-spacing: .04em;
    color: #1f2a44 !important;
  }
  .nav-square-link {
    border-radius: 6px !important;
    color: #5f6b85 !important;
    background: transparent;
    border: 1px solid transparent !important;
    padding: 8px 14px !important;
    font-weight: 600;
    transition: all .15s ease;
  }
  .nav-square-link:hover {
    color: #1d4ed8 !important;
    background: rgba(45, 126, 255, 0.08);
    border-color: rgba(45, 126, 255, 0.15) !important;
  }
  .nav-square-link.active {
    background: #2f8af5;
    color: #fff !important;
    border-color: #2f8af5 !important;
    box-shadow: 0 8px 18px rgba(47, 138, 245, 0.3);
  }
  .btn-logout {
    background: #ff5b5b;
    border-color: #ff5b5b;
    color: #fff !important;
    box-shadow: 0 6px 16px rgba(255,91,91,.35);
  }
  .btn-logout:hover { filter: brightness(1.05); box-shadow: 0 6px 18px rgba(255,91,91,.45); }
</style>
<nav class="navbar navbar-expand-lg nav-glass px-3">
  <a class="navbar-brand nav-brand" href="/public/trang_chinh.php"><i class="bi bi-book me-1"></i>DClass</a>
  <button class="navbar-toggler border-0 ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="navMain">
    <div class="ms-auto d-flex flex-wrap gap-2 py-2 py-lg-0">
      <a class="btn btn-sm nav-square-link <?php echo $active==='hoc_sinh_quan_ly.php'?'active':''; ?>" href="/public/hoc_sinh_quan_ly.php"><i class="bi bi-people me-1"></i>Học sinh</a>
      <a class="btn btn-sm nav-square-link <?php echo $active==='lich_su.php'?'active':''; ?>" href="/public/lich_su.php"><i class="bi bi-clock-history me-1"></i>Lịch sử</a>
      <a class="btn btn-sm nav-square-link <?php echo $active==='bao_cao.php'?'active':''; ?>" href="/public/bao_cao.php"><i class="bi bi-bar-chart me-1"></i>Báo cáo</a>
      <?php if (($_SESSION['vai_tro'] ?? '') === 'ADMIN') { ?>
        <a class="btn btn-sm nav-square-link <?php echo $active==='cau_hinh.php'?'active':''; ?>" href="/public/cau_hinh.php"><i class="bi bi-gear me-1"></i>Cấu hình</a>
      <?php } ?>
      <button class="btn btn-sm nav-square-link btn-logout" id="dang_xuat"><i class="bi bi-box-arrow-right me-1"></i>Đăng xuất</button>
    </div>
  </div>
</nav>
<script>
  (function(){
    const btn = document.getElementById('dang_xuat');
    if(btn){ btn.onclick = async()=>{ try{ await fetch('/api/dang_nhap.php?hanh_dong=dang_xuat',{method:'POST'});}catch(_e){} location.href='/public/dang_nhap.php'; }; }
  })();
</script>

