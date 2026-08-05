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
  $st2 = $pdo->prepare("SELECT api_token_bam FROM giao_vien WHERE id=?");
  $st2->execute([$gv_id]);
  $co_token = (bool)$st2->fetchColumn();
  json_phan_hoi(true, ['lop_hoc_ids' => $ids, 'co_token_api' => $co_token]);
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

if ($hanh_dong === 'tao_token_api') {
  // 1 token đang hoạt động/giáo viên (đơn giản hóa cho MVP app di động) - tạo mới sẽ thay thế
  // token cũ, thiết bị đang dùng token cũ sẽ bị đăng xuất khỏi API. Chỉ trả token gốc lúc này,
  // không lưu lại được nữa - chỉ lưu bản băm (sha256) trong CSDL.
  $token = bin2hex(random_bytes(32));
  $token_bam = hash('sha256', $token);
  $pdo->prepare('UPDATE giao_vien SET api_token_bam=? WHERE id=?')->execute([$token_bam, $gv_id]);
  ghi_log($pdo, $gv_id, 'tao_token_api', 'Tạo token API mới cho ứng dụng di động');
  json_phan_hoi(true, ['token' => $token]);
}

if ($hanh_dong === 'thu_hoi_token_api') {
  $pdo->prepare('UPDATE giao_vien SET api_token_bam=NULL WHERE id=?')->execute([$gv_id]);
  ghi_log($pdo, $gv_id, 'thu_hoi_token_api', 'Thu hồi token API');
  json_phan_hoi(true);
}

http_response_code(404);
json_phan_hoi(false, null, 'khong_tim_thay');
