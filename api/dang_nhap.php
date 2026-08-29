<?php
declare(strict_types=1);
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/tro_giup.php';
require __DIR__ . '/../lib/ghi_nho.php';
require __DIR__ . '/../lib/dang_nhap_nghiep_vu.php';
require __DIR__ . '/../lib/captcha.php';
require __DIR__ . '/../lib/gioi_han_toc_do.php';
/** @var \PDO $pdo Global PDO instance from config/db.php */
$hanh_dong = $_GET['hanh_dong'] ?? '';
// Reset khóa đăng nhập (xóa bộ đếm sai trong session của trình duyệt hiện tại)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hanh_dong === 'dang_nhap') {
  $b = than_json(); $ten = $b['ten_dang_nhap'] ?? ''; $mk = $b['mat_khau'] ?? '';
  $ten_norm = strtolower(trim((string)$ten));
  $bo_qua_khoa = false;
  try { $stW = $pdo->prepare("SELECT het_han FROM reset_khoa WHERE ten_dang_nhap=?"); $stW->execute([$ten_norm]); $w = $stW->fetch(); if ($w && (int)$w['het_han'] > time()) { $bo_qua_khoa = true; } } catch (Throwable $____w) { /* ignore */ }
  // Chống dò mật khẩu cơ bản: giới hạn 5 lần sai, khoá 10 phút. Đếm lưu bền theo IP+tên đăng
  // nhập (lib/gioi_han_toc_do.php) - không dùng $_SESSION, vì một client không giữ cookie giữa
  // các lần thử sẽ luôn được cấp "phiên mới" với bộ đếm về 0, vô hiệu hoàn toàn giới hạn này.
  $k = 'dn|' . ($_SERVER['REMOTE_ADDR'] ?? 'na') . '|' . strtolower(trim((string)$ten));
  $dem = doc_dem_that_bai($pdo, $k);
  $ban = $dem['khoa_den'];
  $sl_hien_tai = $dem['so_lan'];
  if ($ban > time() && !$bo_qua_khoa) { json_phan_hoi(false, ['so_lan'=>$sl_hien_tai, 'khoa_den'=>$ban], 'qua_so_lan'); }
  
  // Kiểm tra CAPTCHA nếu đã có 2 lần sai trở lên (yêu cầu CAPTCHA từ lần thử thứ 3, chưa bị khóa)
  $can_captcha = ($sl_hien_tai >= 2 && !$ban && !$bo_qua_khoa);
  if ($can_captcha) {
    $captcha_input = trim($b['captcha_input'] ?? '');
    if ($captcha_input === '') {
      json_phan_hoi(false, ['so_lan'=>$sl_hien_tai], 'yeu_cau_captcha');
    }
    if (!xac_thuc_captcha($captcha_input)) {
      // CAPTCHA sai, trả về lỗi (không tăng đếm, đếm chỉ tăng khi login thất bại)
      json_phan_hoi(false, ['so_lan'=>$sl_hien_tai], 'captcha_khong_hop_le');
    }
  }
  
  $gv = kiem_tra_dang_nhap($pdo, $ten, $mk);
  if ($gv) {
    // Đăng nhập thành công: đổi session ID để chống session fixation, giữ nguyên dữ liệu session hiện có
    session_regenerate_id(true);
    $_SESSION['giao_vien_id'] = (int)$gv['id']; $_SESSION['ten_dang_nhap'] = $gv['ten_dang_nhap']; $_SESSION['vai_tro'] = $gv['vai_tro'] ?? 'GV';
    $_SESSION['phai_doi_mat_khau'] = (bool)((int)($gv['phai_doi_mat_khau'] ?? 0));
    // Reset đếm sai và CAPTCHA
    xoa_dem_that_bai($pdo, $k);
    xoa_captcha();
    if ($bo_qua_khoa) { try { $pdo->prepare("DELETE FROM reset_khoa WHERE ten_dang_nhap=?")->execute([$ten_norm]); } catch (Throwable $____d) { /* ignore */ } }
    // Ghi nhớ nếu có yêu cầu
    $ghi_nho = (bool)($b['ghi_nho'] ?? false);
    if ($ghi_nho) { dat_cookie_ghi_nho($gv['ten_dang_nhap'], $gv['mat_khau_bam']); }
    json_phan_hoi(true, ['ten_dang_nhap'=>$gv['ten_dang_nhap']]);
  }
  // Thất bại -> tăng đếm (nguyên tử) và khoá nếu vượt quá. $bo_qua_khoa (admin đã reset) vẫn ghi
  // nhận lần sai này bình thường - chỉ bỏ qua việc CHẶN request hiện tại, không tắt hẳn bộ đếm.
  $dem_moi = ghi_nhan_that_bai($pdo, $k, 5);
  $sl = $dem_moi['so_lan'];
  $ban_den = $bo_qua_khoa ? 0 : $dem_moi['khoa_den'];
  if ($ban_den) {
    // Đã bị khóa, không cần CAPTCHA nữa
    json_phan_hoi(false, ['so_lan'=>$sl, 'khoa_den'=>$ban_den], 'qua_so_lan');
  }
  $con_lai = max(0, 5 - $sl);
  json_phan_hoi(false, ['so_lan'=>$sl, 'con_lai'=>$con_lai], 'dang_nhap_that_bai');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hanh_dong === 'dang_xuat') { xoa_cookie_ghi_nho(); session_destroy(); json_phan_hoi(true); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hanh_dong === 'dang_ky') {
  // Chống spam tạo tài khoản hàng loạt: tối đa 5 lần thử (thành công hay thất bại)/10 phút mỗi IP.
  // Đếm lưu bền theo IP (lib/gioi_han_toc_do.php), không dùng $_SESSION - lý do như ở đăng nhập.
  $k = 'dk|' . ($_SERVER['REMOTE_ADDR'] ?? 'na');
  if (doc_dem_that_bai($pdo, $k)['khoa_den'] > time()) { json_phan_hoi(false, null, 'qua_so_lan'); }
  $dem_moi = ghi_nhan_that_bai($pdo, $k, 5);
  if ($dem_moi['khoa_den'] > time()) { json_phan_hoi(false, null, 'qua_so_lan'); }

  $b = than_json();
  $ten = trim((string)($b['ten_dang_nhap'] ?? ''));
  $mk = (string)($b['mat_khau'] ?? '');
  try {
    $gv = dang_ky_giao_vien($pdo, $ten, $mk);
    xoa_dem_that_bai($pdo, $k);
    session_regenerate_id(true);
    $_SESSION['giao_vien_id'] = $gv['id']; $_SESSION['ten_dang_nhap'] = $gv['ten_dang_nhap']; $_SESSION['vai_tro'] = 'GV';
    $_SESSION['phai_doi_mat_khau'] = false;
    ghi_log($pdo, $gv['id'], 'dang_ky', 'Tự đăng ký tài khoản '.$gv['ten_dang_nhap']);
    json_phan_hoi(true, ['ten_dang_nhap' => $gv['ten_dang_nhap']]);
  } catch (Exception $e) {
    json_phan_hoi(false, null, $e->getMessage());
  }
}

http_response_code(404); json_phan_hoi(false, null, 'khong_tim_thay');
