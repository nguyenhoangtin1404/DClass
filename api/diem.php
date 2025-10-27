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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hanh_dong === 'cong') {
  yeu_cau_dang_nhap(); $gv_id = (int)$_SESSION['giao_vien_id']; $b = than_json();
  $hs_id = (int)($b['hoc_sinh_id'] ?? 0); $ly_do_id = (int)($b['ly_do_id'] ?? 0); $ghi_chu = trim($b['ghi_chu'] ?? '');
  if (!$hs_id || !$ly_do_id) json_phan_hoi(false, null, 'thieu_thong_tin');
  try {
    $pdo->beginTransaction(); $so_du = dam_bao_vi($pdo, $hs_id); $bien = lay_bien_diem($pdo, $ly_do_id); $so_du_moi = $so_du + $bien;
    $pdo->prepare("UPDATE vi_diem SET so_du=? WHERE hoc_sinh_id=?")->execute([$so_du_moi, $hs_id]);
    $pdo->prepare("INSERT INTO so_cai_diem(hoc_sinh_id, giao_vien_id, loai, ly_do_id, bien_diem, so_du_sau, ghi_chu) VALUES(?,?,?,?,?,?,?)")
        ->execute([$hs_id, $gv_id, 'CONG', $ly_do_id, $bien, $so_du_moi, $ghi_chu]);
    $pdo->commit(); json_phan_hoi(true, ['so_du'=>$so_du_moi]);
  } catch (Exception $e) { $pdo->rollBack(); json_phan_hoi(false, null, $e->getMessage()); }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hanh_dong === 'quy_doi') {
  yeu_cau_dang_nhap(); $gv_id = (int)$_SESSION['giao_vien_id']; $b = than_json();
  $hs_id = (int)($b['hoc_sinh_id'] ?? 0); $qua_id = (int)($b['qua_tang_id'] ?? 0); $ghi_chu = trim($b['ghi_chu'] ?? '');
  if (!$hs_id || !$qua_id) json_phan_hoi(false, null, 'thieu_thong_tin');
  $st = $pdo->prepare("SELECT gia_diem, ton_kho FROM qua_tang WHERE id=? AND dang_hoat_dong=1"); $st->execute([$qua_id]);
  $q = $st->fetch(); if (!$q) json_phan_hoi(false, null, 'qua_khong_hop_le'); $gia = (int)$q['gia_diem']; $ton = (int)$q['ton_kho'];
  try {
    $pdo->beginTransaction(); $so_du = dam_bao_vi($pdo, $hs_id);
    if ($so_du < $gia) throw new Exception('khong_du_diem'); if ($ton >= 0 and $ton <= 0) throw new Exception('het_hang');
    $so_du_moi = $so_du - $gia; $pdo->prepare("UPDATE vi_diem SET so_du=? WHERE hoc_sinh_id=?")->execute([$so_du_moi, $hs_id]);
    if ($ton >= 0) { $pdo->prepare("UPDATE qua_tang SET ton_kho=ton_kho-1 WHERE id=?")->execute([$qua_id]); }
    $pdo->prepare("INSERT INTO so_cai_diem(hoc_sinh_id, giao_vien_id, loai, qua_tang_id, bien_diem, so_du_sau, ghi_chu) VALUES(?,?,?,?,?,?,?)")
        ->execute([$hs_id, $gv_id, 'QUY_DOI', $qua_id, -$gia, $so_du_moi, $ghi_chu]);
    $pdo->commit(); json_phan_hoi(true, ['so_du'=>$so_du_moi]);
  } catch (Exception $e) { $pdo->rollBack(); json_phan_hoi(false, null, $e->getMessage()); }
}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $hanh_dong === 'lich_su') {
  yeu_cau_dang_nhap(); $hs_id = isset($_GET['hoc_sinh_id']) ? (int)$_GET['hoc_sinh_id'] : 0;
  $sql = "SELECT sc.id, sc.loai, sc.bien_diem, sc.so_du_sau, sc.ghi_chu, sc.tao_luc, hs.ho_ten, ld.tieu_de AS ly_do, qt.ten AS qua
          FROM so_cai_diem sc
          JOIN hoc_sinh hs ON hs.id = sc.hoc_sinh_id
          LEFT JOIN ly_do ld ON ld.id = sc.ly_do_id
          LEFT JOIN qua_tang qt ON qt.id = sc.qua_tang_id
          WHERE 1=1";
  $pr=[]; if ($hs_id) { $sql .= " AND sc.hoc_sinh_id=?"; $pr[] = $hs_id; }
  $sql .= " ORDER BY sc.id DESC LIMIT 200"; $st = $pdo->prepare($sql); $st->execute($pr); json_phan_hoi(true, $st->fetchAll());
}
http_response_code(404); json_phan_hoi(false, null, 'khong_tim_thay');
