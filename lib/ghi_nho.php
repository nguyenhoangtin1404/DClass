<?php
declare(strict_types=1);

// Hỗ trợ cookie "ghi nhớ đăng nhập"

function bi_mat_he_thong(): string {
  // Secret riêng theo môi trường, cấu hình trong config/env.php (khoá 'remember_secret').
  // Không dùng giá trị mặc định cố định trong code: nếu thiếu, tính năng "ghi nhớ đăng nhập" sẽ tự tắt.
  $secret = $GLOBALS['env']['remember_secret'] ?? '';
  return is_string($secret) ? $secret : '';
}

function tao_chu_ky_cookie(string $ten_dang_nhap, string $mat_khau_bam, string $user_agent): string {
  $du_lieu = $ten_dang_nhap . '|' . $mat_khau_bam . '|' . $user_agent;
  return hash_hmac('sha256', $du_lieu, bi_mat_he_thong());
}

function dat_cookie_ghi_nho(string $ten_dang_nhap, string $mat_khau_bam): void {
  if (bi_mat_he_thong() === '') return; // Chưa cấu hình remember_secret -> không phát cookie
  $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
  $token = tao_chu_ky_cookie($ten_dang_nhap, $mat_khau_bam, $ua);
  $expires = time() + 60*60*24*30; // 30 ngày
  $secure = dang_https();
  setcookie('gv_u', $ten_dang_nhap, [
    'expires' => $expires,
    'path' => '/',
    'secure' => $secure,
    'httponly' => false,
    'samesite' => 'Lax',
  ]);
  setcookie('gv_t', $token, [
    'expires' => $expires,
    'path' => '/',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
}

function xoa_cookie_ghi_nho(): void {
  foreach (['gv_u'=>false, 'gv_t'=>true] as $k => $httpOnly) {
    setcookie($k, '', [
      'expires' => time() - 3600,
      'path' => '/',
      'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
      'httponly' => $httpOnly,
      'samesite' => 'Lax',
    ]);
  }
}

function thu_cookie_ghi_nho(PDO $pdo): bool {
  if (bi_mat_he_thong() === '') return false;
  $ten = $_COOKIE['gv_u'] ?? '';
  $tok = $_COOKIE['gv_t'] ?? '';
  if ($ten === '' || $tok === '') return false;
  $st = $pdo->prepare('SELECT id, ten_dang_nhap, mat_khau_bam, vai_tro, phai_doi_mat_khau FROM giao_vien WHERE ten_dang_nhap=?');
  $st->execute([$ten]);
  $gv = $st->fetch(); if (!$gv) return false;
  $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
  $ky = tao_chu_ky_cookie($gv['ten_dang_nhap'], $gv['mat_khau_bam'], $ua);
  if (!hash_equals($ky, $tok)) return false;
  session_regenerate_id(true); // chống session fixation khi tự đăng nhập lại qua cookie
  $_SESSION['giao_vien_id'] = (int)$gv['id'];
  $_SESSION['ten_dang_nhap'] = $gv['ten_dang_nhap'];
  $_SESSION['vai_tro'] = $gv['vai_tro'] ?? 'GV';
  $_SESSION['phai_doi_mat_khau'] = (bool)((int)($gv['phai_doi_mat_khau'] ?? 0));
  $_SESSION['to_chuc_id'] = $GLOBALS['TO_CHUC_HIEN_TAI']['id'] ?? null;
  return true;
}

