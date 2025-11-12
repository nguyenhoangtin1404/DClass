<?php
header('Content-Type: text/html; charset=utf-8');
require __DIR__ . '/../config/db.php'; require __DIR__ . '/../lib/tro_giup.php';
if (!isset($_SESSION['giao_vien_id'])) { header('Location: /public/dang_nhap.php'); exit; }

function dem($pdo, $sql){ $v = $pdo->query($sql)->fetchColumn(); return (int)($v ?: 0); }

$tong_hoc_sinh = dem($pdo, "SELECT COUNT(*) FROM hoc_sinh");
$hoc_sinh_hd   = dem($pdo, "SELECT COUNT(*) FROM hoc_sinh WHERE dang_hoat_dong=1");
$so_lop        = dem($pdo, "SELECT COUNT(*) FROM lop_hoc WHERE dang_hoat_dong=1");
$so_giao_vien  = dem($pdo, "SELECT COUNT(*) FROM giao_vien");
$so_giao_dich  = dem($pdo, "SELECT COUNT(*) FROM so_cai_diem");
$tong_cong     = (int)($pdo->query("SELECT COALESCE(SUM(CASE WHEN loai='CONG_DIEM' THEN bien_diem ELSE 0 END),0) FROM so_cai_diem")->fetchColumn() ?: 0);
$tong_doi      = (int)($pdo->query("SELECT COALESCE(SUM(CASE WHEN loai='DOI_DIEM' THEN bien_diem ELSE 0 END),0) FROM so_cai_diem")->fetchColumn() ?: 0);

$top5 = $pdo->query("SELECT h.ho_ten, COALESCE(v.so_du,0) AS so_du FROM hoc_sinh h LEFT JOIN vi_diem v ON v.hoc_sinh_id=h.id ORDER BY so_du DESC, h.ho_ten ASC LIMIT 5")->fetchAll();
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Báo cáo & Thống kê</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/morph/bootstrap.min.css"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"><link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css"><link rel="stylesheet" href="/public/theme.css"></head><body>
<?php include __DIR__ . '/_nav.php'; ?>
<div class="container py-3 safe-bottom">
  <div class="d-flex align-items-center justify-content-between"><h5>Báo cáo & Thống kê</h5></div>
  <div class="row g-3 mt-1" data-aos="fade-up">
    <div class="col-sm-6 col-md-3"><div class="card shadow-sm"><div class="card-body">
      <div class="text-muted small">Học sinh (hoạt động)</div>
      <div class="fs-4 fw-semibold"><?php echo number_format($hoc_sinh_hd); ?></div>
      <div class="small text-muted">Tổng: <?php echo number_format($tong_hoc_sinh); ?></div>
    </div></div></div>
    <div class="col-sm-6 col-md-3"><div class="card shadow-sm"><div class="card-body">
      <div class="text-muted small">Lớp học</div>
      <div class="fs-4 fw-semibold"><?php echo number_format($so_lop); ?></div>
    </div></div></div>
    <div class="col-sm-6 col-md-3"><div class="card shadow-sm"><div class="card-body">
      <div class="text-muted small">Giáo viên</div>
      <div class="fs-4 fw-semibold"><?php echo number_format($so_giao_vien); ?></div>
    </div></div></div>
    <div class="col-sm-6 col-md-3"><div class="card shadow-sm"><div class="card-body">
      <div class="text-muted small">Giao dịch</div>
      <div class="fs-4 fw-semibold"><?php echo number_format($so_giao_dich); ?></div>
    </div></div></div>
  </div>
  <div class="row g-3 mt-1" data-aos="fade-up">
    <div class="col-sm-6"><div class="card shadow-sm"><div class="card-body">
      <div class="text-muted small">Tổng điểm cộng</div>
      <div class="fs-4 fw-semibold text-success">+<?php echo number_format($tong_cong); ?></div>
    </div></div></div>
    <div class="col-sm-6"><div class="card shadow-sm"><div class="card-body">
      <div class="text-muted small">Tổng điểm đổi</div>
      <div class="fs-4 fw-semibold text-danger"><?php echo number_format($tong_doi); ?></div>
    </div></div></div>
  </div>
  <div class="row g-3 mt-1" data-aos="fade-up">
    <div class="col-12 col-lg-6"><div class="card shadow-sm"><div class="card-body">
      <div class="d-flex align-items-center justify-content-between"><h6 class="mb-0">Top 5 số dư</h6></div>
      <div class="table-responsive mt-2"><table class="table table-sm mb-0">
        <thead><tr><th>Học sinh</th><th class="text-end">Số dư</th></tr></thead>
        <tbody>
          <?php foreach ($top5 as $r): ?>
            <tr><td><?php echo htmlspecialchars($r['ho_ten'] ?? '', ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?></td><td class="text-end"><?php echo number_format((int)($r['so_du'] ?? 0)); ?></td></tr>
          <?php endforeach; if (!$top5): ?><tr><td colspan="2" class="text-muted">Chưa có dữ liệu</td></tr><?php endif; ?>
        </tbody>
      </table></div>
    </div></div></div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>AOS.init({ duration: 350, once: true, easing: 'ease-out' });</script>
</body></html>

