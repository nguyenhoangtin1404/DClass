<?php
declare(strict_types=1);
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/tro_giup.php';
$hanh_dong = $_GET['hanh_dong'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hanh_dong === 'dang_nhap') {
  $b = than_json(); $ten = $b['ten_dang_nhap'] ?? ''; $mk = $b['mat_khau'] ?? '';
  $st = $pdo->prepare("SELECT * FROM giao_vien WHERE ten_dang_nhap=?"); $st->execute([$ten]);
  $gv = $st->fetch(); if ($gv && password_verify($mk, $gv['mat_khau_bam'])) {
    $_SESSION['giao_vien_id'] = (int)$gv['id']; $_SESSION['ten_dang_nhap'] = $gv['ten_dang_nhap'];
    json_phan_hoi(true, ['ten_dang_nhap'=>$gv['ten_dang_nhap']]);
  }
  json_phan_hoi(false, null, 'dang_nhap_that_bai');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hanh_dong === 'dang_xuat') { session_destroy(); json_phan_hoi(true); }
http_response_code(404); json_phan_hoi(false, null, 'khong_tim_thay');
