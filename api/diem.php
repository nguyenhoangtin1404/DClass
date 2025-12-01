<?php
declare(strict_types=1);
require __DIR__ . '/../config/db.php'; require __DIR__ . '/../lib/tro_giup.php';
function lay_bien_diem(PDO $pdo, int $ly_do_id): int {
  $st = $pdo->prepare("SELECT bien_diem FROM ly_do WHERE id=? AND dang_hoat_dong=1"); $st->execute([$ly_do_id]);
  $r = $st->fetch(); if (!$r) throw new Exception('ly_do_khong_hop_le'); return (int)$r['bien_diem'];
}
function dam_bao_vi(PDO $pdo, int $hoc_sinh_id): int {
  $st = $pdo->prepare("SELECT so_du FROM vi_diem WHERE hoc_sinh_id=?"); $st->execute([$hoc_sinh_id]);
  $row = $st->fetch(); if (!$row) { $pdo->prepare("INSERT INTO vi_diem(hoc_sinh_id, so_du) VALUES(?,0)")->execute([$hoc_sinh_id]); return 0; }
  return (int)$row['so_du'];
}
$hanh_dong = $_GET['hanh_dong'] ?? '';
$la_admin = (($_SESSION['vai_tro'] ?? '') === 'ADMIN');
// Lấy danh sách lớp được gán cho GV hiện tại (admin => null để hiểu là tất cả)
function lop_duoc_gan(PDO $pdo, bool $la_admin): array {
  if ($la_admin) return [];
  $st = $pdo->prepare("SELECT lop_hoc_id FROM giao_vien_lop WHERE giao_vien_id=?");
  $st->execute([(int)$_SESSION['giao_vien_id']]);
  return array_map(fn($r)=>(int)$r['lop_hoc_id'], $st->fetchAll());
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hanh_dong === 'cong') {
  yeu_cau_dang_nhap(); $gv_id = (int)$_SESSION['giao_vien_id']; $b = than_json();
  $hs_id = (int)($b['hoc_sinh_id'] ?? 0); $ly_do_id = (int)($b['ly_do_id'] ?? 0); $ghi_chu = trim($b['ghi_chu'] ?? '');
  if (!$hs_id || !$ly_do_id) json_phan_hoi(false, null, 'thieu_thong_tin');
  if (!$la_admin) {
    $stChk = $pdo->prepare("SELECT lop_hoc_id FROM hoc_sinh WHERE id=?");
    $stChk->execute([$hs_id]);
    $lop = (int)$stChk->fetchColumn();
    $lop_gan = lop_duoc_gan($pdo, $la_admin);
    if ($lop && $lop_gan && !in_array($lop, $lop_gan, true)) json_phan_hoi(false, null, 'khong_du_quyen');
  }
  try {
    $pdo->beginTransaction(); $so_du = dam_bao_vi($pdo, $hs_id); $bien = lay_bien_diem($pdo, $ly_do_id); $so_du_moi = $so_du + $bien;
    $pdo->prepare("UPDATE vi_diem SET so_du=? WHERE hoc_sinh_id=?")->execute([$so_du_moi, $hs_id]);
    $pdo->prepare("INSERT INTO so_cai_diem(hoc_sinh_id, giao_vien_id, loai, ly_do_id, bien_diem, so_du_sau, ghi_chu) VALUES(?,?,?,?,?,?,?)")
        ->execute([$hs_id, $gv_id, 'CONG_DIEM', $ly_do_id, $bien, $so_du_moi, $ghi_chu]);
    $pdo->commit(); json_phan_hoi(true, ['so_du'=>$so_du_moi]);
  } catch (Exception $e) { $pdo->rollBack(); json_phan_hoi(false, null, $e->getMessage()); }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hanh_dong === 'quy_doi') {
  yeu_cau_dang_nhap(); $gv_id = (int)$_SESSION['giao_vien_id']; $b = than_json();
  $hs_id = (int)($b['hoc_sinh_id'] ?? 0); $qua_id = (int)($b['qua_tang_id'] ?? 0); $ghi_chu = trim($b['ghi_chu'] ?? '');
  $scratch_cost = isset($b['scratch_cost']) ? (int)$b['scratch_cost'] : null;
  if ($scratch_cost !== null && $scratch_cost <= 0) $scratch_cost = null;
  if (!$hs_id || !$qua_id) json_phan_hoi(false, null, 'thieu_thong_tin');
  if (!$la_admin) {
    $stChk = $pdo->prepare("SELECT lop_hoc_id FROM hoc_sinh WHERE id=?");
    $stChk->execute([$hs_id]);
    $lop = (int)$stChk->fetchColumn();
    $lop_gan = lop_duoc_gan($pdo, $la_admin);
    if ($lop && $lop_gan && !in_array($lop, $lop_gan, true)) json_phan_hoi(false, null, 'khong_du_quyen');
  }
  $st = $pdo->prepare("SELECT gia_diem, ton_kho FROM qua_tang WHERE id=? AND dang_hoat_dong=1"); $st->execute([$qua_id]);
  $q = $st->fetch(); if (!$q) json_phan_hoi(false, null, 'qua_khong_hop_le');
  $gia_db = (int)$q['gia_diem']; $ton = (int)$q['ton_kho'];
  $gia = $scratch_cost !== null ? $scratch_cost : $gia_db;
  try {
    $pdo->beginTransaction(); $so_du = dam_bao_vi($pdo, $hs_id);
    if ($so_du < $gia) throw new Exception('Không đủ điểm để đổi'); if ($ton >= 0 and $ton <= 0) throw new Exception('het_hang');
    $so_du_moi = $so_du - $gia; $pdo->prepare("UPDATE vi_diem SET so_du=? WHERE hoc_sinh_id=?")->execute([$so_du_moi, $hs_id]);
    if ($ton >= 0) { $pdo->prepare("UPDATE qua_tang SET ton_kho=ton_kho-1 WHERE id=?")->execute([$qua_id]); }
    $pdo->prepare("INSERT INTO so_cai_diem(hoc_sinh_id, giao_vien_id, loai, qua_tang_id, bien_diem, so_du_sau, ghi_chu) VALUES(?,?,?,?,?,?,?)")
        ->execute([$hs_id, $gv_id, 'DOI_DIEM', $qua_id, -$gia, $so_du_moi, $ghi_chu]);
    $pdo->commit(); json_phan_hoi(true, ['so_du'=>$so_du_moi]);
  } catch (Exception $e) { $pdo->rollBack(); json_phan_hoi(false, null, $e->getMessage()); }
}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $hanh_dong === 'lich_su') {
  yeu_cau_dang_nhap(); $hs_id = isset($_GET['hoc_sinh_id']) ? (int)$_GET['hoc_sinh_id'] : 0;
  $la_admin = (($_SESSION['vai_tro'] ?? '') === 'ADMIN');
  // Lọc theo lớp mà giáo viên hiện tại được gán (trừ admin)
  $lop_duoc_gan = [];
  if (!$la_admin) {
    $stL = $pdo->prepare("SELECT lop_hoc_id FROM giao_vien_lop WHERE giao_vien_id=?");
    $stL->execute([(int)$_SESSION['giao_vien_id']]);
    $lop_duoc_gan = array_map(fn($r) => (int)$r['lop_hoc_id'], $stL->fetchAll());
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
  }
  if ($hs_id) {
    $sql .= " AND sc.hoc_sinh_id=?";
    $pr[] = $hs_id;
  }
  $sql .= " ORDER BY sc.id DESC LIMIT 200"; $st = $pdo->prepare($sql); $st->execute($pr); json_phan_hoi(true, $st->fetchAll());
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $hanh_dong === 'thong_ke') {
  yeu_cau_dang_nhap();
  $lop_gan = lop_duoc_gan($pdo, $la_admin);
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
