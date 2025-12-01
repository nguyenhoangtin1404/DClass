<?php
declare(strict_types=1);
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/tro_giup.php';

yeu_cau_dang_nhap();
$is_admin = (($_SESSION['vai_tro'] ?? '') === 'ADMIN');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
// GET
if ($method === 'GET') {
  $hanh_dong_get = $_GET['hanh_dong'] ?? '';
  // Trả danh sách giáo viên (để gán vào lớp)
  if ($hanh_dong_get === 'ds') {
    $st = $pdo->query("SELECT id, ten_dang_nhap FROM giao_vien ORDER BY ten_dang_nhap ASC");
    json_phan_hoi(true, ['giao_vien' => $st->fetchAll()]);
  }
  if ($hanh_dong_get === 'ds_tk') {
    if (!$is_admin) { json_phan_hoi(false, null, 'khong_du_quyen'); }
    $st = $pdo->query("SELECT id, ten_dang_nhap, COALESCE(vai_tro,'GV') AS vai_tro FROM giao_vien ORDER BY ten_dang_nhap ASC");
    json_phan_hoi(true, ['tai_khoan' => $st->fetchAll()]);
  }
  // Trả danh sách lớp đang quản lý (giáo viên hiện tại hoặc id truyền vào)
  $gvId = isset($_GET['giao_vien_id']) ? (int)$_GET['giao_vien_id'] : (int)$_SESSION['giao_vien_id'];
  if ($gvId <= 0) { json_phan_hoi(false, null, 'thieu_giao_vien_id'); }
  $st = $pdo->prepare("SELECT lop_hoc_id FROM giao_vien_lop WHERE giao_vien_id=?");
  $st->execute([$gvId]);
  $ids = array_map(fn($r)=> (int)$r['lop_hoc_id'], $st->fetchAll());
  json_phan_hoi(true, ['lop_hoc_ids' => $ids]);
}
if ($method !== 'POST') { http_response_code(404); json_phan_hoi(false, null, 'khong_tim_thay'); }

$hanh_dong = $_GET['hanh_dong'] ?? '';
$b = than_json();

if ($hanh_dong === 'doi_mat_khau') {
  $mk_cu = (string)($b['mat_khau_cu'] ?? '');
  $mk_moi = (string)($b['mat_khau_moi'] ?? '');
  if ($mk_moi === '') json_phan_hoi(false, null, 'thieu_mat_khau_moi');
  $st = $pdo->prepare('SELECT id, mat_khau_bam FROM giao_vien WHERE id=?');
  $st->execute([$_SESSION['giao_vien_id']]);
  $gv = $st->fetch();
  if (!$gv || !password_verify($mk_cu, $gv['mat_khau_bam'])) json_phan_hoi(false, null, 'mat_khau_cu_khong_dung');
  $bam = password_hash($mk_moi, PASSWORD_DEFAULT);
  $pdo->prepare('UPDATE giao_vien SET mat_khau_bam=? WHERE id=?')->execute([$bam, (int)$gv['id']]);
  ghi_log($pdo, (int)$_SESSION['giao_vien_id'], 'doi_mat_khau', 'Đổi mật khẩu tài khoản '.$_SESSION['ten_dang_nhap']);
  json_phan_hoi(true);
}

if ($hanh_dong === 'them') {
  if (!$is_admin) json_phan_hoi(false, null, 'khong_du_quyen');
  $ten = trim((string)($b['ten_dang_nhap'] ?? ''));
  $mk = (string)($b['mat_khau'] ?? '');
  $vai_tro = strtoupper(trim((string)($b['vai_tro'] ?? 'GV'))) ?: 'GV';
  if ($ten === '' || $mk === '') json_phan_hoi(false, null, 'thieu_truong');
  try {
    $pdo->prepare('INSERT INTO giao_vien(ten_dang_nhap, mat_khau_bam, vai_tro) VALUES(?,?,?)')
        ->execute([$ten, password_hash($mk, PASSWORD_DEFAULT), $vai_tro]);
    ghi_log($pdo, (int)$_SESSION['giao_vien_id'], 'them_giao_vien', 'Thêm GV '.$ten.' (vai_trò '.$vai_tro.')');
    json_phan_hoi(true, ['id' => (int)$pdo->lastInsertId()]);
  } catch (Throwable $e) {
    json_phan_hoi(false, null, 'ten_dang_nhap_da_ton_tai');
  }
}

if ($hanh_dong === 'cap_nhat_lop') {
  $gvId = isset($b['giao_vien_id']) ? (int)$b['giao_vien_id'] : (int)$_SESSION['giao_vien_id'];
  if ($gvId <= 0) json_phan_hoi(false, null, 'thieu_giao_vien_id');
  // Kiểm tra giáo viên tồn tại
  $st = $pdo->prepare("SELECT 1 FROM giao_vien WHERE id=?");
  $st->execute([$gvId]);
  if (!$st->fetchColumn()) json_phan_hoi(false, null, 'giao_vien_khong_ton_tai');

  $ids = isset($b['lop_hoc_ids']) && is_array($b['lop_hoc_ids']) ? $b['lop_hoc_ids'] : [];
  $clean = [];
  foreach ($ids as $v) {
    $iv = (int)$v;
    if ($iv > 0 && !in_array($iv, $clean, true)) $clean[] = $iv;
  }
  $pdo->beginTransaction();
  try {
    $pdo->prepare("DELETE FROM giao_vien_lop WHERE giao_vien_id=?")->execute([$gvId]);
    $ins = $pdo->prepare("INSERT INTO giao_vien_lop(giao_vien_id, lop_hoc_id) VALUES(?,?)");
    foreach ($clean as $lid) { $ins->execute([$gvId, $lid]); }
    $pdo->commit();
    ghi_log($pdo, (int)$_SESSION['giao_vien_id'], 'cap_nhat_lop_giao_vien', 'Gán lớp '.implode(',', $clean).' cho GV '.$gvId);
    json_phan_hoi(true, ['giao_vien_id'=>$gvId, 'lop_hoc_ids'=>$clean]);
  } catch (Throwable $e) {
    $pdo->rollBack();
    json_phan_hoi(false, null, 'loi_cap_nhat_lop');
  }
}

if ($hanh_dong === 'cap_nhat_vai_tro') {
  if (!$is_admin) json_phan_hoi(false, null, 'khong_du_quyen');
  $id = (int)($b['id'] ?? 0);
  $vai_tro = strtoupper(trim((string)($b['vai_tro'] ?? 'GV'))) ?: 'GV';
  if ($id <= 0) json_phan_hoi(false, null, 'thieu_id');
  $pdo->prepare("UPDATE giao_vien SET vai_tro=? WHERE id=?")->execute([$vai_tro, $id]);
  ghi_log($pdo, (int)$_SESSION['giao_vien_id'], 'cap_nhat_vai_tro', 'Cập nhật vai trò '.$vai_tro.' cho GV '.$id);
  json_phan_hoi(true, ['id'=>$id, 'vai_tro'=>$vai_tro]);
}

http_response_code(404);
json_phan_hoi(false, null, 'khong_tim_thay');
