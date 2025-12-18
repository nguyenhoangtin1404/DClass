<?php
declare(strict_types=1);
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/tro_giup.php';
/** @var \PDO $pdo Global PDO instance from config/db.php */

yeu_cau_dang_nhap();

$la_admin = (($_SESSION['vai_tro'] ?? '') === 'ADMIN');
$lop_gan = [];
if (!$la_admin) {
  $st = $pdo->prepare("SELECT lop_hoc_id FROM giao_vien_lop WHERE giao_vien_id=?");
  $st->execute([(int)$_SESSION['giao_vien_id']]);
  $lop_gan = array_map(fn($r) => (int)$r['lop_hoc_id'], $st->fetchAll());
  if (!$lop_gan) { $lop_gan = [0]; }
}

function dem(PDO $pdo, string $sql, array $params = []): int {
  $st = $pdo->prepare($sql);
  $st->execute($params);
  $v = $st->fetchColumn();
  return (int)($v ?: 0);
}

if ($la_admin) {
  $tong_cong = (int)($pdo->query("SELECT COALESCE(SUM(CASE WHEN sc.loai='CONG_DIEM' THEN sc.bien_diem ELSE 0 END),0)
                                  FROM so_cai_diem sc
                                  JOIN hoc_sinh hs ON hs.id=sc.hoc_sinh_id
                                  WHERE hs.dang_hoat_dong=1")->fetchColumn() ?: 0);
  $tong_doi = (int)($pdo->query("SELECT COALESCE(SUM(CASE WHEN sc.loai='DOI_DIEM' THEN sc.bien_diem ELSE 0 END),0)
                                 FROM so_cai_diem sc
                                 JOIN hoc_sinh hs ON hs.id=sc.hoc_sinh_id
                                 WHERE hs.dang_hoat_dong=1")->fetchColumn() ?: 0);
  $top5 = $pdo->query("SELECT h.ho_ten, COALESCE(v.so_du,0) AS so_du
                       FROM hoc_sinh h
                       LEFT JOIN vi_diem v ON v.hoc_sinh_id=h.id
                       WHERE h.dang_hoat_dong=1
                       ORDER BY so_du DESC, h.ho_ten ASC
                       LIMIT 5")->fetchAll();
} else {
  $place = implode(',', array_fill(0, count($lop_gan), '?'));
  $tong_cong = dem(
    $pdo,
    "SELECT COALESCE(SUM(CASE WHEN sc.loai='CONG_DIEM' THEN sc.bien_diem ELSE 0 END),0)
     FROM so_cai_diem sc
     JOIN hoc_sinh hs ON hs.id=sc.hoc_sinh_id
     WHERE hs.dang_hoat_dong=1 AND hs.lop_hoc_id IN ($place)",
    $lop_gan
  );
  $tong_doi = dem(
    $pdo,
    "SELECT COALESCE(SUM(CASE WHEN sc.loai='DOI_DIEM' THEN sc.bien_diem ELSE 0 END),0)
     FROM so_cai_diem sc
     JOIN hoc_sinh hs ON hs.id=sc.hoc_sinh_id
     WHERE hs.dang_hoat_dong=1 AND hs.lop_hoc_id IN ($place)",
    $lop_gan
  );
  $st = $pdo->prepare("SELECT h.ho_ten, COALESCE(v.so_du,0) AS so_du
                       FROM hoc_sinh h
                       LEFT JOIN vi_diem v ON v.hoc_sinh_id=h.id
                       WHERE h.dang_hoat_dong=1 AND h.lop_hoc_id IN ($place)
                       ORDER BY so_du DESC, h.ho_ten ASC
                       LIMIT 5");
  $st->execute($lop_gan);
  $top5 = $st->fetchAll();
}

$lop_placeholders = $la_admin ? '' : implode(',', array_fill(0, count($lop_gan), '?'));
$join_lop = "JOIN hoc_sinh hs ON hs.id=sc.hoc_sinh_id";
$where_lop = $la_admin ? '' : "AND hs.lop_hoc_id IN ($lop_placeholders)";
$params_lop = $la_admin ? [] : $lop_gan;
$where_active = "AND hs.dang_hoat_dong=1";

$bat_dau = (new DateTimeImmutable('today -29 days'))->format('Y-m-d');
$sqlXuHuong = "SELECT date(sc.tao_luc) AS ngay,
  SUM(CASE WHEN sc.loai='CONG_DIEM' THEN sc.bien_diem ELSE 0 END) AS cong,
  SUM(CASE WHEN sc.loai='DOI_DIEM' THEN -sc.bien_diem ELSE 0 END) AS doi,
  COUNT(*) AS giao_dich
  FROM so_cai_diem sc
  $join_lop
  WHERE date(sc.tao_luc) >= ? $where_lop $where_active
  GROUP BY date(sc.tao_luc) ORDER BY ngay";
$st = $pdo->prepare($sqlXuHuong);
$st->execute(array_merge([$bat_dau], $params_lop));
$xu_huong_raw = $st->fetchAll(PDO::FETCH_ASSOC);
$xu_huong_map = [];
foreach ($xu_huong_raw as $r) {
  $xu_huong_map[$r['ngay']] = [
    'cong' => (int)($r['cong'] ?? 0),
    'doi' => (int)($r['doi'] ?? 0),
    'giao_dich' => (int)($r['giao_dich'] ?? 0),
  ];
}
$xu_huong_nhan = [];
$xu_huong_cong = [];
$xu_huong_doi = [];
$xu_huong_gd = [];
$start = new DateTimeImmutable('today -29 days');
$end = new DateTimeImmutable('tomorrow');
for ($d = $start; $d < $end; $d = $d->modify('+1 day')) {
  $key = $d->format('Y-m-d');
  $xu_huong_nhan[] = $d->format('d/m');
  $xu_huong_cong[] = $xu_huong_map[$key]['cong'] ?? 0;
  $xu_huong_doi[] = $xu_huong_map[$key]['doi'] ?? 0;
  $xu_huong_gd[] = $xu_huong_map[$key]['giao_dich'] ?? 0;
}

$sqlQua = "SELECT qt.ten, COUNT(*) AS so_lan, SUM(-sc.bien_diem) AS tong_diem
  FROM so_cai_diem sc
  JOIN qua_tang qt ON qt.id=sc.qua_tang_id
  $join_lop
  WHERE sc.loai='DOI_DIEM' $where_lop $where_active
  GROUP BY qt.id, qt.ten
  ORDER BY so_lan DESC, tong_diem DESC, qt.ten ASC
  LIMIT 3";
$st = $pdo->prepare($sqlQua);
$st->execute($params_lop);
$top_qua = $st->fetchAll(PDO::FETCH_ASSOC);

$sqlLyDo = "SELECT ld.tieu_de, COUNT(*) AS so_lan, SUM(sc.bien_diem) AS tong_diem
  FROM so_cai_diem sc
  JOIN ly_do ld ON ld.id=sc.ly_do_id
  $join_lop
  WHERE sc.loai='CONG_DIEM' $where_lop $where_active
  GROUP BY ld.id, ld.tieu_de
  ORDER BY so_lan DESC, ld.tieu_de ASC
  LIMIT 7";
$st = $pdo->prepare($sqlLyDo);
$st->execute($params_lop);
$top_ly_do = $st->fetchAll(PDO::FETCH_ASSOC);

$top5_labels = array_map(fn($r) => (string)($r['ho_ten'] ?? ''), $top5);
$top5_values = array_map(fn($r) => (int)($r['so_du'] ?? 0), $top5);
$top5_thong_ke = null;
if ($top5_values) {
  $vals = $top5_values;
  sort($vals);
  $cnt = count($vals) - 1;
  $top5_thong_ke = [
    'min' => $vals[0],
    'max' => $vals[$cnt],
    'p25' => $vals[(int)floor($cnt * 0.25)],
    'median' => $vals[(int)floor($cnt * 0.5)],
    'p75' => $vals[(int)floor($cnt * 0.75)],
  ];
}

json_phan_hoi(true, [
  'tong_cong' => $tong_cong,
  'tong_doi' => $tong_doi,
  'xu_huong' => [
    'labels' => $xu_huong_nhan,
    'cong' => $xu_huong_cong,
    'doi' => $xu_huong_doi,
    'giao_dich' => $xu_huong_gd,
  ],
  'top_qua' => $top_qua,
  'top_ly_do' => $top_ly_do,
  'top_so_du' => [
    'labels' => $top5_labels,
    'values' => $top5_values,
    'stats' => $top5_thong_ke,
  ],
]);
