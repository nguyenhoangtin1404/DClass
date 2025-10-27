<?php
declare(strict_types=1);
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/tro_giup.php';

yeu_cau_dang_nhap();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
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
  json_phan_hoi(true);
}

if ($hanh_dong === 'them') {
  $ten = trim((string)($b['ten_dang_nhap'] ?? ''));
  $mk = (string)($b['mat_khau'] ?? '');
  if ($ten === '' || $mk === '') json_phan_hoi(false, null, 'thieu_truong');
  try {
    $pdo->prepare('INSERT INTO giao_vien(ten_dang_nhap, mat_khau_bam) VALUES(?,?)')
        ->execute([$ten, password_hash($mk, PASSWORD_DEFAULT)]);
    json_phan_hoi(true, ['id' => (int)$pdo->lastInsertId()]);
  } catch (Throwable $e) {
    json_phan_hoi(false, null, 'ten_dang_nhap_da_ton_tai');
  }
}

http_response_code(404);
json_phan_hoi(false, null, 'khong_tim_thay');

