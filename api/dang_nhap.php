<?php
declare(strict_types=1);
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/tro_giup.php';
require __DIR__ . '/../lib/ghi_nho.php';
require __DIR__ . '/../lib/dang_nhap_nghiep_vu.php';
/** @var \PDO $pdo Global PDO instance from config/db.php */
$hanh_dong = $_GET['hanh_dong'] ?? '';
// Reset khóa đăng nhập (xóa bộ đếm sai trong session của trình duyệt hiện tại)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hanh_dong === 'dang_nhap') {
  $b = than_json(); $ten = $b['ten_dang_nhap'] ?? ''; $mk = $b['mat_khau'] ?? '';
  $ten_norm = strtolower(trim((string)$ten));
  $bo_qua_khoa = false;
  try { $stW = $pdo->prepare("SELECT het_han FROM reset_khoa WHERE ten_dang_nhap=?"); $stW->execute([$ten_norm]); $w = $stW->fetch(); if ($w && (int)$w['het_han'] > time()) { $bo_qua_khoa = true; } } catch (Throwable $____w) { /* ignore */ }
  // Chống dò mật khẩu cơ bản: giới hạn 3 lần sai, khoá 5 phút
  $k = ($_SERVER['REMOTE_ADDR'] ?? 'na') . '|' . strtolower(trim((string)$ten));
  $ban = $_SESSION['dn_sai'][$k]['khoa_den'] ?? 0;
  $sl_hien_tai = (int)($_SESSION['dn_sai'][$k]['so_lan'] ?? 0);
  // Tự động reset sau khi hết thời gian khoá
  if ($ban && $ban <= time()) { unset($_SESSION['dn_sai'][$k]); $ban = 0; $sl_hien_tai = 0; }
  if ($ban > time() && !$bo_qua_khoa) { json_phan_hoi(false, ['so_lan'=>$sl_hien_tai, 'khoa_den'=>$ban], 'qua_so_lan'); }
  $gv = kiem_tra_dang_nhap($pdo, $ten, $mk);
  if ($gv) {
    // Đăng nhập thành công
    $_SESSION['giao_vien_id'] = (int)$gv['id']; $_SESSION['ten_dang_nhap'] = $gv['ten_dang_nhap']; $_SESSION['vai_tro'] = $gv['vai_tro'] ?? 'GV';
    // Reset đếm sai
    if (isset($_SESSION['dn_sai'][$k])) unset($_SESSION['dn_sai'][$k]);
    if ($bo_qua_khoa) { try { $pdo->prepare("DELETE FROM reset_khoa WHERE ten_dang_nhap=?")->execute([$ten_norm]); } catch (Throwable $____d) { /* ignore */ } }
    // Ghi nhớ nếu có yêu cầu
    $ghi_nho = (bool)($b['ghi_nho'] ?? false);
    if ($ghi_nho) { dat_cookie_ghi_nho($gv['ten_dang_nhap'], $gv['mat_khau_bam']); }
    json_phan_hoi(true, ['ten_dang_nhap'=>$gv['ten_dang_nhap']]);
  }
  // Thất bại -> tăng đếm và khoá nếu vượt quá
  $sl = (int)($_SESSION['dn_sai'][$k]['so_lan'] ?? 0) + 1;
  $ban_den = ($sl >= 3 && !$bo_qua_khoa) ? time() + 10*60 : 0; // khóa 10 phút
  $_SESSION['dn_sai'][$k] = ['so_lan' => $sl, 'khoa_den' => $ban_den];
  if ($ban_den) { json_phan_hoi(false, ['so_lan'=>$sl, 'khoa_den'=>$ban_den], 'qua_so_lan'); }
  $con_lai = max(0, 3 - $sl);
  json_phan_hoi(false, ['so_lan'=>$sl, 'con_lai'=>$con_lai], 'dang_nhap_that_bai');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hanh_dong === 'dang_xuat') { xoa_cookie_ghi_nho(); session_destroy(); json_phan_hoi(true); }
http_response_code(404); json_phan_hoi(false, null, 'khong_tim_thay');
