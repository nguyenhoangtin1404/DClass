<?php
header('Content-Type: text/html; charset=utf-8');
require __DIR__ . '/../config/db.php'; require __DIR__ . '/../lib/tro_giup.php';
if (!isset($_SESSION['giao_vien_id'])) { header('Location: dang_nhap.php'); exit; }
$la_admin = (($_SESSION['vai_tro'] ?? '') === 'ADMIN');
// Lớp được gán cho GV (admin thấy tất cả)
$lop_gan = [];
if (!$la_admin) {
  $st = $pdo->prepare("SELECT lop_hoc_id FROM giao_vien_lop WHERE giao_vien_id=?");
  $st->execute([(int)$_SESSION['giao_vien_id']]);
  $lop_gan = array_map(fn($r)=>(int)$r['lop_hoc_id'], $st->fetchAll());
  if (!$lop_gan) { $lop_gan = [0]; } // để tránh query trống
}
function dem($pdo, $sql, $params=[]){ $st=$pdo->prepare($sql); $st->execute($params); $v = $st->fetchColumn(); return (int)($v ?: 0); }

if ($la_admin) {
  $tong_cong     = (int)($pdo->query("SELECT COALESCE(SUM(CASE WHEN sc.loai='CONG_DIEM' THEN sc.bien_diem ELSE 0 END),0) FROM so_cai_diem sc JOIN hoc_sinh hs ON hs.id=sc.hoc_sinh_id WHERE hs.dang_hoat_dong=1")->fetchColumn() ?: 0);
  $tong_doi      = (int)($pdo->query("SELECT COALESCE(SUM(CASE WHEN sc.loai='DOI_DIEM' THEN sc.bien_diem ELSE 0 END),0) FROM so_cai_diem sc JOIN hoc_sinh hs ON hs.id=sc.hoc_sinh_id WHERE hs.dang_hoat_dong=1")->fetchColumn() ?: 0);
  $top5 = $pdo->query("SELECT h.ho_ten, COALESCE(v.so_du,0) AS so_du FROM hoc_sinh h LEFT JOIN vi_diem v ON v.hoc_sinh_id=h.id WHERE h.dang_hoat_dong=1 ORDER BY so_du DESC, h.ho_ten ASC LIMIT 5")->fetchAll();
} else {
  $place = implode(',', array_fill(0, count($lop_gan), '?'));
  $tong_cong     = dem($pdo, "SELECT COALESCE(SUM(CASE WHEN sc.loai='CONG_DIEM' THEN sc.bien_diem ELSE 0 END),0) FROM so_cai_diem sc JOIN hoc_sinh hs ON hs.id=sc.hoc_sinh_id WHERE hs.dang_hoat_dong=1 AND hs.lop_hoc_id IN ($place)", $lop_gan);
  $tong_doi      = dem($pdo, "SELECT COALESCE(SUM(CASE WHEN sc.loai='DOI_DIEM' THEN sc.bien_diem ELSE 0 END),0) FROM so_cai_diem sc JOIN hoc_sinh hs ON hs.id=sc.hoc_sinh_id WHERE hs.dang_hoat_dong=1 AND hs.lop_hoc_id IN ($place)", $lop_gan);
  $st = $pdo->prepare("SELECT h.ho_ten, COALESCE(v.so_du,0) AS so_du FROM hoc_sinh h LEFT JOIN vi_diem v ON v.hoc_sinh_id=h.id WHERE h.dang_hoat_dong=1 AND h.lop_hoc_id IN ($place) ORDER BY so_du DESC, h.ho_ten ASC LIMIT 5");
  $st->execute($lop_gan);
  $top5 = $st->fetchAll();
}
$lop_placeholders = $la_admin ? '' : implode(',', array_fill(0, count($lop_gan), '?'));
$join_lop = "JOIN hoc_sinh hs ON hs.id=sc.hoc_sinh_id";
$where_lop = $la_admin ? '' : "AND hs.lop_hoc_id IN ($lop_placeholders)";
$params_lop = $la_admin ? [] : $lop_gan;
// Luôn chỉ lấy học sinh đang hoạt động
$where_active = "AND hs.dang_hoat_dong=1";
// Xu hướng 30 ngày (line)
$bat_dau = (new DateTimeImmutable('today -29 days'))->format('Y-m-d');
$sqlXuHuong = "SELECT date(sc.tao_luc) AS ngay,
  SUM(CASE WHEN sc.loai='CONG_DIEM' THEN sc.bien_diem ELSE 0 END) AS cong,
  SUM(CASE WHEN sc.loai='DOI_DIEM' THEN -sc.bien_diem ELSE 0 END) AS doi,
  COUNT(*) AS giao_dich
  FROM so_cai_diem sc
  $join_lop
  WHERE date(sc.tao_luc) >= ? $where_lop $where_active
  GROUP BY date(sc.tao_luc) ORDER BY ngay";
$st = $pdo->prepare($sqlXuHuong); $st->execute(array_merge([$bat_dau], $params_lop));
$xu_huong_raw = $st->fetchAll(PDO::FETCH_ASSOC);
$xu_huong_map = [];
foreach ($xu_huong_raw as $r) {
  $xu_huong_map[$r['ngay']] = [
    'cong' => (int)($r['cong'] ?? 0),
    'doi' => (int)($r['doi'] ?? 0),
    'giao_dich' => (int)($r['giao_dich'] ?? 0),
  ];
}
$xu_huong_nhan = []; $xu_huong_cong = []; $xu_huong_doi = []; $xu_huong_gd = [];
$start = new DateTimeImmutable('today -29 days'); $end = new DateTimeImmutable('tomorrow');
for ($d = $start; $d < $end; $d = $d->modify('+1 day')) {
  $key = $d->format('Y-m-d');
  $xu_huong_nhan[] = $d->format('d/m');
  $xu_huong_cong[] = $xu_huong_map[$key]['cong'] ?? 0;
  $xu_huong_doi[] = $xu_huong_map[$key]['doi'] ?? 0;
  $xu_huong_gd[]  = $xu_huong_map[$key]['giao_dich'] ?? 0;
}
// Top 3 quà đổi nhiều nhất
$sqlQua = "SELECT qt.ten, COUNT(*) AS so_lan, SUM(-sc.bien_diem) AS tong_diem
  FROM so_cai_diem sc
  JOIN qua_tang qt ON qt.id=sc.qua_tang_id
  $join_lop
  WHERE sc.loai='DOI_DIEM' $where_lop $where_active
  GROUP BY qt.id, qt.ten
  ORDER BY so_lan DESC, tong_diem DESC, qt.ten ASC
  LIMIT 3";
$st = $pdo->prepare($sqlQua); $st->execute($params_lop);
$top_qua = $st->fetchAll(PDO::FETCH_ASSOC);
// Top lý do cộng điểm
$sqlLyDo = "SELECT ld.tieu_de, COUNT(*) AS so_lan, SUM(sc.bien_diem) AS tong_diem
  FROM so_cai_diem sc
  JOIN ly_do ld ON ld.id=sc.ly_do_id
  $join_lop
  WHERE sc.loai='CONG_DIEM' $where_lop $where_active
  GROUP BY ld.id, ld.tieu_de
  ORDER BY so_lan DESC, ld.tieu_de ASC
  LIMIT 7";
$st = $pdo->prepare($sqlLyDo); $st->execute($params_lop);
$top_ly_do = $st->fetchAll(PDO::FETCH_ASSOC);
// Chuẩn bị data top 5 số dư cho biểu đồ
$top5_labels = array_map(fn($r) => (string)($r['ho_ten'] ?? ''), $top5);
$top5_values = array_map(fn($r) => (int)($r['so_du'] ?? 0), $top5);
$top5_thong_ke = null;
if ($top5_values) {
  $vals = $top5_values; sort($vals);
  $cnt = count($vals) - 1;
  $top5_thong_ke = [
    'min' => $vals[0],
    'max' => $vals[$cnt],
    'p25' => $vals[(int)floor($cnt * 0.25)],
    'median' => $vals[(int)floor($cnt * 0.5)],
    'p75' => $vals[(int)floor($cnt * 0.75)],
  ];
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Báo cáo & Thống kê</title>
<link rel="stylesheet" href="vendor/bootswatch/bootstrap.min.css"><link rel="stylesheet" href="vendor/bootstrap-icons/bootstrap-icons.css"><link rel="stylesheet" href="vendor/aos/aos.css"><link rel="stylesheet" href="vendor/theme.css"></head><body>
<?php include __DIR__ . '/_nav.php'; ?>
<div class="container py-3 safe-bottom">
  <div class="d-flex align-items-center justify-content-between"><h5>Báo cáo & Thống kê</h5></div>
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
    <div class="col-12"><div class="card shadow-sm"><div class="card-body">
      <div class="d-flex align-items-center justify-content-between"><h6 class="mb-0">Xu hướng 30 ngày</h6><div class="text-muted small">Điểm cộng / điểm đổi / giao dịch</div></div>
      <div class="mt-2" style="height:280px;"><?php if ($xu_huong_nhan): ?><canvas id="chart-xu-huong" aria-label="Biểu đồ xu hướng 30 ngày" role="img"></canvas><?php else: ?><div class="text-muted small">Chưa có dữ liệu</div><?php endif; ?></div>
    </div></div></div>
  </div>
  <div class="row g-3 mt-1" data-aos="fade-up">
    <div class="col-lg-6"><div class="card shadow-sm h-100"><div class="card-body">
      <div class="d-flex align-items-center justify-content-between"><h6 class="mb-0">Top 3 quà được đổi</h6></div>
      <div class="mt-3" style="height:260px;"><?php if ($top_qua): ?><canvas id="chart-qua" aria-label="Biểu đồ top quà đổi" role="img"></canvas><?php else: ?><div class="text-muted small">Chưa có giao dịch đổi quà</div><?php endif; ?></div>
    </div></div></div>
    <div class="col-lg-6"><div class="card shadow-sm h-100"><div class="card-body">
      <div class="d-flex align-items-center justify-content-between"><h6 class="mb-0">Top lý do cộng điểm</h6></div>
      <div class="mt-3" style="height:260px;"><?php if ($top_ly_do): ?><canvas id="chart-ly-do" aria-label="Biểu đồ lý do cộng điểm" role="img"></canvas><?php else: ?><div class="text-muted small">Chưa có giao dịch cộng điểm</div><?php endif; ?></div>
    </div></div></div>
  </div>
  <div class="row g-3 mt-1" data-aos="fade-up">
    <div class="col-12"><div class="card shadow-sm"><div class="card-body">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2"><h6 class="mb-0">Phân bố top 5 số dư</h6></div>
      <?php if ($top5): ?>
        <div class="mt-3" style="height:260px;"><canvas id="chart-top5" aria-label="Histogram top 5 số dư" role="img"></canvas></div>
        <?php if ($top5_thong_ke): ?>
          <div class="d-flex flex-wrap gap-3 align-items-center mt-3 small text-muted">
            <div>P25: <span class="fw-semibold text-body"><?php echo number_format($top5_thong_ke['p25']); ?></span></div>
            <div>Median: <span class="fw-semibold text-body"><?php echo number_format($top5_thong_ke['median']); ?></span></div>
            <div>P75: <span class="fw-semibold text-body"><?php echo number_format($top5_thong_ke['p75']); ?></span></div>
            <div>Min: <span class="fw-semibold text-body"><?php echo number_format($top5_thong_ke['min']); ?></span></div>
            <div>Max: <span class="fw-semibold text-body"><?php echo number_format($top5_thong_ke['max']); ?></span></div>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <div class="text-muted small mt-2">Chưa có dữ liệu số dư</div>
      <?php endif; ?>
    </div></div></div>
  </div>

</div>
<script src="vendor/chartjs/chart.umd.min.js"></script>
<script src="vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="vendor/aos/aos.js"></script>
<script>
AOS.init({ duration: 350, once: true, easing: 'ease-out' });
const xuHuongLabels = <?php echo json_encode($xu_huong_nhan, JSON_UNESCAPED_UNICODE); ?>;
const xuHuongCong = <?php echo json_encode($xu_huong_cong, JSON_UNESCAPED_UNICODE); ?>;
const xuHuongDoi = <?php echo json_encode($xu_huong_doi, JSON_UNESCAPED_UNICODE); ?>;
const xuHuongGd  = <?php echo json_encode($xu_huong_gd, JSON_UNESCAPED_UNICODE); ?>;
if (xuHuongLabels.length && document.getElementById('chart-xu-huong')) {
  new Chart(document.getElementById('chart-xu-huong'), {
    type: 'line',
    data: {
      labels: xuHuongLabels,
      datasets: [
        { label: 'Điểm cộng', data: xuHuongCong, borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,0.08)', tension: 0.3, fill: true },
        { label: 'Điểm đổi', data: xuHuongDoi, borderColor: '#dc3545', backgroundColor: 'rgba(220,53,69,0.08)', tension: 0.3, fill: true },
        { label: 'Giao dịch', data: xuHuongGd, borderColor: '#6c757d', backgroundColor: 'rgba(108,117,125,0.08)', tension: 0.2, yAxisID: 'y1' }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      scales: {
        y: { beginAtZero: true, title: { display: true, text: 'Điểm' } },
        y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Giao dịch' } }
      }
    }
  });
}
const topQuaLabels = <?php echo json_encode(array_map(fn($r)=>$r['ten'], $top_qua), JSON_UNESCAPED_UNICODE); ?>;
const topQuaData = <?php echo json_encode(array_map(fn($r)=>(int)$r['so_lan'], $top_qua), JSON_UNESCAPED_UNICODE); ?>;
if (topQuaLabels.length && document.getElementById('chart-qua')) {
  new Chart(document.getElementById('chart-qua'), {
    type: 'bar',
    data: { labels: topQuaLabels, datasets: [{ label: 'Số lần đổi', data: topQuaData, backgroundColor: '#0d6efd' }] },
    options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', scales: { x: { beginAtZero: true } } }
  });
}
const topLyDoLabels = <?php echo json_encode(array_map(fn($r)=>$r['tieu_de'], $top_ly_do), JSON_UNESCAPED_UNICODE); ?>;
const topLyDoData = <?php echo json_encode(array_map(fn($r)=>(int)$r['so_lan'], $top_ly_do), JSON_UNESCAPED_UNICODE); ?>;
if (topLyDoLabels.length && document.getElementById('chart-ly-do')) {
  new Chart(document.getElementById('chart-ly-do'), {
    type: 'bar',
    data: { labels: topLyDoLabels, datasets: [{ label: 'Số lần cộng', data: topLyDoData, backgroundColor: '#20c997' }] },
    options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', scales: { x: { beginAtZero: true } } }
  });
}
const top5Labels = <?php echo json_encode($top5_labels, JSON_UNESCAPED_UNICODE); ?>;
const top5Data = <?php echo json_encode($top5_values, JSON_UNESCAPED_UNICODE); ?>;
if (top5Labels.length && document.getElementById('chart-top5')) {
  new Chart(document.getElementById('chart-top5'), {
    type: 'bar',
    data: { labels: top5Labels, datasets: [{ label: 'Số dư', data: top5Data, backgroundColor: '#fd7e14' }] },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, title: { display: true, text: 'Điểm' } } } }
  });
}
</script>
</body></html>
