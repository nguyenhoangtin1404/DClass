<?php
declare(strict_types=1);
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/tro_giup.php';
require __DIR__ . '/../lib/diem_nghiep_vu.php';
/** @var \PDO $pdo Global PDO instance from config/db.php */
$hanh_dong = $_GET['hanh_dong'] ?? '';
$la_admin = (($_SESSION['vai_tro'] ?? '') === 'ADMIN');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hanh_dong === 'cong') {
  yeu_cau_dang_nhap(); $gv_id = (int)$_SESSION['giao_vien_id']; $b = than_json();
  $hs_id = (int)($b['hoc_sinh_id'] ?? 0); $ly_do_id = (int)($b['ly_do_id'] ?? 0); $ghi_chu = trim($b['ghi_chu'] ?? '');
  if (!$hs_id || !$ly_do_id) json_phan_hoi(false, null, 'thieu_thong_tin');
  try {
    $kq = cong_diem_giao_vien($pdo, $gv_id, $la_admin, $hs_id, $ly_do_id, $ghi_chu);
    json_phan_hoi(true, $kq);
  } catch (Exception $e) { json_phan_hoi(false, null, $e->getMessage()); }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hanh_dong === 'quy_doi') {
  yeu_cau_dang_nhap(); $gv_id = (int)$_SESSION['giao_vien_id']; $b = than_json();
  $hs_id = (int)($b['hoc_sinh_id'] ?? 0); $qua_id = (int)($b['qua_tang_id'] ?? 0); $ghi_chu = trim($b['ghi_chu'] ?? '');
  if (!$hs_id || !$qua_id) json_phan_hoi(false, null, 'thieu_thong_tin');
  try {
    $kq = quy_doi_qua_tang($pdo, $gv_id, $la_admin, $hs_id, $qua_id, $ghi_chu);
    json_phan_hoi(true, $kq);
  } catch (Exception $e) { json_phan_hoi(false, null, $e->getMessage()); }
}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $hanh_dong === 'lich_su') {
  yeu_cau_dang_nhap(); $hs_id = isset($_GET['hoc_sinh_id']) ? (int)$_GET['hoc_sinh_id'] : 0;
  $la_admin = (($_SESSION['vai_tro'] ?? '') === 'ADMIN');
  // Lọc theo lớp mà giáo viên hiện tại được gán (trừ admin)
  $lop_duoc_gan = [];
  if (!$la_admin) {
    $lop_duoc_gan = lop_duoc_gan($pdo, $la_admin, (int)$_SESSION['giao_vien_id']);
    if (!$lop_duoc_gan) { json_phan_hoi(true, []); }
  }
  $sql = "SELECT sc.id, sc.loai, sc.bien_diem, sc.so_du_sau, sc.ghi_chu, sc.tao_luc, hs.ho_ten, ld.tieu_de AS ly_do, qt.ten AS qua
          FROM so_cai_diem sc
          JOIN hoc_sinh hs ON hs.id = sc.hoc_sinh_id
          LEFT JOIN ly_do ld ON ld.id = sc.ly_do_id
          LEFT JOIN qua_tang qt ON qt.id = sc.qua_tang_id
          WHERE 1=1";
  $pr = [];
  if (!$la_admin) {
    $place = implode(',', array_fill(0, count($lop_duoc_gan), '?'));
    $sql .= " AND hs.lop_hoc_id IN ($place)";
    $pr = $lop_duoc_gan;
    // GV chỉ xem lịch sử cộng điểm của lớp mình
    $sql .= " AND sc.loai='CONG_DIEM'";
  }
  if ($hs_id) {
    $sql .= " AND sc.hoc_sinh_id=?";
    $pr[] = $hs_id;
  }
  $sql .= " ORDER BY sc.id DESC LIMIT 200"; $st = $pdo->prepare($sql); $st->execute($pr); json_phan_hoi(true, $st->fetchAll());
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $hanh_dong === 'thong_ke') {
  yeu_cau_dang_nhap();
  $lop_gan = lop_duoc_gan($pdo, $la_admin, (int)$_SESSION['giao_vien_id']);
  $pr = [];
  $cond_lop = [];
  if (!$la_admin) {
    if (!$lop_gan) { json_phan_hoi(true, ['top_so_du'=>[], 'top_cong_diem'=>[], 'top_doi_diem'=>[], 'ton_kho'=>[], 'qua_ua_thich'=>[]]); }
    $cond_lop[] = "hs.lop_hoc_id IN (" . implode(',', array_fill(0, count($lop_gan), '?')) . ")";
    $pr = $lop_gan;
  }
  $where_lop = $cond_lop ? ('WHERE ' . implode(' AND ', $cond_lop)) : '';
  // Top số dư hiện tại
  $sqlTopSoDu = "SELECT hs.id, hs.ho_ten, IFNULL(v.so_du,0) AS so_du
                 FROM hoc_sinh hs
                 LEFT JOIN vi_diem v ON v.hoc_sinh_id = hs.id
                 $where_lop
                 ORDER BY so_du DESC, hs.ho_ten ASC
                 LIMIT 10";
  $st = $pdo->prepare($sqlTopSoDu); $st->execute($pr); $top_so_du = $st->fetchAll();
  // Top cộng điểm (tổng điểm cộng và số lần)
  $conds = $cond_lop;
  $conds[] = "sc.loai='CONG_DIEM'";
  $where_cong = 'WHERE ' . implode(' AND ', $conds);
  $sqlTopCong = "SELECT sc.hoc_sinh_id AS id, hs.ho_ten,
                        SUM(sc.bien_diem) AS tong_cong,
                        COUNT(*) AS so_lan
                 FROM so_cai_diem sc
                 JOIN hoc_sinh hs ON hs.id = sc.hoc_sinh_id
                 $where_cong
                 GROUP BY sc.hoc_sinh_id
                 ORDER BY tong_cong DESC, so_lan DESC
                 LIMIT 10";
  $st = $pdo->prepare($sqlTopCong); $st->execute($pr); $top_cong_diem = $st->fetchAll();
  // Top đổi điểm (số lần đổi và tổng điểm đã đổi)
  $conds_doi = $cond_lop;
  $conds_doi[] = "sc.loai='DOI_DIEM'";
  $where_doi = 'WHERE ' . implode(' AND ', $conds_doi);
  $sqlTopDoi = "SELECT sc.hoc_sinh_id AS id, hs.ho_ten,
                        COUNT(*) AS so_lan,
                        SUM(-sc.bien_diem) AS tong_doi
                 FROM so_cai_diem sc
                 JOIN hoc_sinh hs ON hs.id = sc.hoc_sinh_id
                 $where_doi
                 GROUP BY sc.hoc_sinh_id
                 ORDER BY so_lan DESC, tong_doi DESC
                 LIMIT 10";
  $st = $pdo->prepare($sqlTopDoi); $st->execute($pr); $top_doi_diem = $st->fetchAll();
  // Tồn kho quà
  $ton_kho = $pdo->query("SELECT id, ten, gia_diem, ton_kho
                          FROM qua_tang
                          WHERE dang_hoat_dong=1
                          ORDER BY CASE WHEN ton_kho < 0 THEN 1 ELSE 0 END ASC, ton_kho ASC, ten ASC")->fetchAll();
  // Quà được yêu thích (đổi nhiều nhất)
  if ($la_admin) {
    $sqlQuaUa = "SELECT qt.id, qt.ten,
                        COUNT(*) AS so_lan,
                        SUM(-sc.bien_diem) AS tong_diem
                 FROM so_cai_diem sc
                 JOIN qua_tang qt ON qt.id = sc.qua_tang_id
                 GROUP BY qt.id
                 ORDER BY so_lan DESC, tong_diem DESC
                 LIMIT 10";
    $qua_ua_thich = $pdo->query($sqlQuaUa)->fetchAll();
  } else {
    $place_q = implode(',', array_fill(0, count($lop_gan), '?'));
    $sqlQuaUa = "SELECT qt.id, qt.ten,
                        COUNT(*) AS so_lan,
                        SUM(-sc.bien_diem) AS tong_diem
                 FROM so_cai_diem sc
                 JOIN qua_tang qt ON qt.id = sc.qua_tang_id
                 JOIN hoc_sinh hs ON hs.id = sc.hoc_sinh_id
                 WHERE hs.lop_hoc_id IN ($place_q)
                 GROUP BY qt.id
                 ORDER BY so_lan DESC, tong_diem DESC
                 LIMIT 10";
    $st = $pdo->prepare($sqlQuaUa); $st->execute($lop_gan); $qua_ua_thich = $st->fetchAll();
  }
  json_phan_hoi(true, [
    'top_so_du' => $top_so_du,
    'top_cong_diem' => $top_cong_diem,
    'top_doi_diem' => $top_doi_diem,
    'ton_kho' => $ton_kho,
    'qua_ua_thich' => $qua_ua_thich,
  ]);
}
http_response_code(404); json_phan_hoi(false, null, 'khong_tim_thay');
