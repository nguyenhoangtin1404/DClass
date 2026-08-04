<?php
declare(strict_types=1);
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/tro_giup.php';
/** @var \PDO $pdo Global PDO instance from config/db.php */

// Không còn quản lý tài khoản người khác (không có ADMIN toàn cục) - chỉ còn tự phục vụ:
// đổi mật khẩu của chính mình, xem danh sách lớp của chính mình.
yeu_cau_dang_nhap();
$gv_id = (int)$_SESSION['giao_vien_id'];

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
  // Trả danh sách lớp của chính giáo viên đang đăng nhập
  $st = $pdo->prepare("SELECT lop_hoc_id FROM giao_vien_lop WHERE giao_vien_id=?");
  $st->execute([$gv_id]);
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
  $st->execute([$gv_id]);
  $gv = $st->fetch();
  if (!$gv || !password_verify($mk_cu, $gv['mat_khau_bam'])) json_phan_hoi(false, null, 'mat_khau_cu_khong_dung');
  $bam = password_hash($mk_moi, PASSWORD_DEFAULT);
  $pdo->prepare('UPDATE giao_vien SET mat_khau_bam=?, phai_doi_mat_khau=0 WHERE id=?')->execute([$bam, (int)$gv['id']]);
  $_SESSION['phai_doi_mat_khau'] = false;
  ghi_log($pdo, $gv_id, 'doi_mat_khau', 'Đổi mật khẩu tài khoản '.$_SESSION['ten_dang_nhap']);
  json_phan_hoi(true);
}

http_response_code(404);
json_phan_hoi(false, null, 'khong_tim_thay');
