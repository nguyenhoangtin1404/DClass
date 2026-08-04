<?php
declare(strict_types=1);

error_reporting(E_ALL);
date_default_timezone_set('UTC');

$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
  require $autoload;
}

require_once __DIR__ . '/../lib/diem_nghiep_vu.php';
require_once __DIR__ . '/../lib/dang_nhap_nghiep_vu.php';

function tao_pdo_test(): PDO
{
  $pdo = new PDO('sqlite::memory:');
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  $pdo->exec('PRAGMA foreign_keys = ON');
  $sql = file_get_contents(__DIR__ . '/../config/luoc_do.sql');
  $doan = array_filter(array_map('trim', explode(';', $sql)));
  foreach ($doan as $lenh) {
    if ($lenh !== '') {
      $pdo->exec($lenh);
    }
  }
  return $pdo;
}

function chen_giao_vien(PDO $pdo, string $ten = 'gv1', string $mat_khau = '123456', string $vai_tro = 'ADMIN'): int
{
  $st = $pdo->prepare('INSERT INTO giao_vien(ten_dang_nhap, mat_khau_bam, vai_tro) VALUES(?,?,?)');
  $st->execute([$ten, password_hash($mat_khau, PASSWORD_DEFAULT), $vai_tro]);
  return (int)$pdo->lastInsertId();
}

function chen_lop(PDO $pdo, string $ten = '4A'): int
{
  $st = $pdo->prepare('INSERT INTO lop_hoc(ten) VALUES(?)');
  $st->execute([$ten]);
  return (int)$pdo->lastInsertId();
}

function chen_hoc_sinh(PDO $pdo, string $ho_ten = 'Hoc sinh', ?int $lop_hoc_id = null): int
{
  $st = $pdo->prepare('INSERT INTO hoc_sinh(ho_ten, lop_hoc_id, dang_hoat_dong) VALUES(?,?,1)');
  $st->execute([$ho_ten, $lop_hoc_id]);
  return (int)$pdo->lastInsertId();
}

function chen_ly_do(PDO $pdo, string $tieu_de = 'Cham chi', int $bien_diem = 2): int
{
  $st = $pdo->prepare('INSERT INTO ly_do(tieu_de, bien_diem, dang_hoat_dong) VALUES(?,?,1)');
  $st->execute([$tieu_de, $bien_diem]);
  return (int)$pdo->lastInsertId();
}

function chen_qua(PDO $pdo, string $ten = 'Sticker', int $gia_diem = 3, int $ton_kho = 10): int
{
  $st = $pdo->prepare('INSERT INTO qua_tang(ten, gia_diem, ton_kho, dang_hoat_dong) VALUES(?,?,?,1)');
  $st->execute([$ten, $gia_diem, $ton_kho]);
  return (int)$pdo->lastInsertId();
}

function gan_giao_vien_vao_lop(PDO $pdo, int $giao_vien_id, int $lop_hoc_id): void
{
  $st = $pdo->prepare('INSERT INTO giao_vien_lop(giao_vien_id, lop_hoc_id) VALUES(?, ?)');
  $st->execute([$giao_vien_id, $lop_hoc_id]);
}

