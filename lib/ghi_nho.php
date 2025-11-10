<?php
declare(strict_types=1);

// Hỗ trợ cookie "ghi nhớ đăng nhập"

function bi_mat_he_thong(): string {
  $pepper = 'DClass_Pepper_v1';
  return hash('sha256', __DIR__ . '|' . $pepper);
}

function tao_chu_ky_cookie(string $ten_dang_nhap, string $mat_khau_bam, string $user_agent): string {
  $du_lieu = $ten_dang_nhap . '|' . $mat_khau_bam . '|' . $user_agent;
  return hash_hmac('sha256', $du_lieu, bi_mat_he_thong());
}

function dat_cookie_ghi_nho(string $ten_dang_nhap, string $mat_khau_bam): void {
  $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
  $token = tao_chu_ky_cookie($ten_dang_nhap, $mat_khau_bam, $ua);
  $expires = time() + 60*60*24*30; // 30 ngày
  $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
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
  $ten = $_COOKIE['gv_u'] ?? '';
  $tok = $_COOKIE['gv_t'] ?? '';
  if ($ten === '' || $tok === '') return false;
  $st = $pdo->prepare('SELECT id, ten_dang_nhap, mat_khau_bam FROM giao_vien WHERE ten_dang_nhap=?');
  $st->execute([$ten]);
  $gv = $st->fetch(); if (!$gv) return false;
  $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
  $ky = tao_chu_ky_cookie($gv['ten_dang_nhap'], $gv['mat_khau_bam'], $ua);
  if (!hash_equals($ky, $tok)) return false;
  $_SESSION['giao_vien_id'] = (int)$gv['id'];
  $_SESSION['ten_dang_nhap'] = $gv['ten_dang_nhap'];
  return true;
}

