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
require_once __DIR__ . '/../lib/hoc_sinh_nghiep_vu.php';
require_once __DIR__ . '/../lib/tro_giup.php';
require_once __DIR__ . '/../lib/gioi_han_toc_do.php';
require_once __DIR__ . '/../config/migration_nghiep_vu.php';

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
  // Chạy migrate để có đủ cột được thêm sau này (gioi_tinh, ngay_sinh, nguoi_tao_id...) - đúng
  // như schema thật config/db.php tạo ra, tránh test pass giả vì thiếu cột so với production.
  chay_migration($pdo);
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

function chen_ly_do(PDO $pdo, string $tieu_de = 'Cham chi', int $bien_diem = 2, ?int $nguoi_tao_id = null): int
{
  $st = $pdo->prepare('INSERT INTO ly_do(tieu_de, bien_diem, dang_hoat_dong, nguoi_tao_id) VALUES(?,?,1,?)');
  $st->execute([$tieu_de, $bien_diem, $nguoi_tao_id]);
  return (int)$pdo->lastInsertId();
}

function chen_qua(PDO $pdo, string $ten = 'Sticker', int $gia_diem = 3, int $ton_kho = 10, ?int $nguoi_tao_id = null): int
{
  $st = $pdo->prepare('INSERT INTO qua_tang(ten, gia_diem, ton_kho, dang_hoat_dong, nguoi_tao_id) VALUES(?,?,?,1,?)');
  $st->execute([$ten, $gia_diem, $ton_kho, $nguoi_tao_id]);
  return (int)$pdo->lastInsertId();
}

function gan_giao_vien_vao_lop(PDO $pdo, int $giao_vien_id, int $lop_hoc_id): void
{
  $st = $pdo->prepare('INSERT INTO giao_vien_lop(giao_vien_id, lop_hoc_id) VALUES(?, ?)');
  $st->execute([$giao_vien_id, $lop_hoc_id]);
}

/**
 * Dựng CSDL đúng như bản TRƯỚC khi có cột nguoi_tao_id/bảng migrations_ap_dung, để test
 * chay_migration()/chay_backfill_so_huu() có backfill đúng cho CSDL production đã có dữ liệu
 * thật từ trước khi nâng cấp lên bản sở hữu theo giáo viên.
 */
function tao_pdo_truoc_nang_cap(): PDO
{
  $pdo = new PDO('sqlite::memory:');
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  $pdo->exec('PRAGMA foreign_keys = ON');
  $pdo->exec("CREATE TABLE lop_hoc (id INTEGER PRIMARY KEY AUTOINCREMENT, ten TEXT NOT NULL, dang_hoat_dong INTEGER DEFAULT 1)");
  $pdo->exec("CREATE TABLE giao_vien (id INTEGER PRIMARY KEY AUTOINCREMENT, ten_dang_nhap TEXT UNIQUE NOT NULL, mat_khau_bam TEXT NOT NULL, vai_tro TEXT DEFAULT 'GV', tao_luc TEXT DEFAULT (datetime('now')))");
  $pdo->exec("CREATE TABLE giao_vien_lop (giao_vien_id INTEGER NOT NULL, lop_hoc_id INTEGER NOT NULL, PRIMARY KEY (giao_vien_id, lop_hoc_id))");
  $pdo->exec("CREATE TABLE hoc_sinh (id INTEGER PRIMARY KEY AUTOINCREMENT, ma TEXT UNIQUE, ho_ten TEXT NOT NULL, lop_hoc_id INTEGER, dang_hoat_dong INTEGER DEFAULT 1)");
  $pdo->exec("CREATE TABLE ly_do (id INTEGER PRIMARY KEY AUTOINCREMENT, tieu_de TEXT NOT NULL, bien_diem INTEGER NOT NULL, dang_hoat_dong INTEGER DEFAULT 1)");
  $pdo->exec("CREATE TABLE qua_tang (id INTEGER PRIMARY KEY AUTOINCREMENT, ten TEXT NOT NULL, gia_diem INTEGER NOT NULL, ton_kho INTEGER DEFAULT 0, dang_hoat_dong INTEGER DEFAULT 1)");
  $pdo->exec("CREATE TABLE vi_diem (hoc_sinh_id INTEGER PRIMARY KEY, so_du INTEGER NOT NULL DEFAULT 0)");
  $pdo->exec("CREATE TABLE so_cai_diem (id INTEGER PRIMARY KEY AUTOINCREMENT, hoc_sinh_id INTEGER, giao_vien_id INTEGER, loai TEXT, ly_do_id INTEGER, qua_tang_id INTEGER, bien_diem INTEGER, so_du_sau INTEGER, ghi_chu TEXT, tao_luc TEXT DEFAULT (datetime('now')))");
  return $pdo;
}

