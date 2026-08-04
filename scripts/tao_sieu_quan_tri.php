<?php
declare(strict_types=1);

/**
 * CLI: tao tai khoan sieu_quan_tri (van hanh nen tang, quan ly danh sach to chuc).
 * Khong tu seed tai khoan mac dinh khi khoi tao registry.db - phai tao thu cong
 * bang script nay de tranh lap lai loi "mat khau mac dinh doan duoc" (xem P0.10).
 *
 * Cach dung:
 *   php scripts/tao_sieu_quan_tri.php --ten_dang_nhap=van_hanh --mat_khau=xxxx
 */

require __DIR__ . '/../config/registry.php';

$options = getopt('', ['ten_dang_nhap:', 'mat_khau:']);

function loi_thoat_sqt(string $msg): never {
  fwrite(STDERR, $msg . PHP_EOL);
  exit(1);
}

$ten = trim((string)($options['ten_dang_nhap'] ?? ''));
$mat_khau = (string)($options['mat_khau'] ?? '');

if ($ten === '' || $mat_khau === '') {
  loi_thoat_sqt('Thieu --ten_dang_nhap hoac --mat_khau. Vi du: php scripts/tao_sieu_quan_tri.php --ten_dang_nhap=van_hanh --mat_khau=xxxx');
}
if (strlen($mat_khau) < 8) {
  loi_thoat_sqt('Mat khau qua ngan, can toi thieu 8 ky tu.');
}

$registry = ket_noi_registry();
$st = $registry->prepare('SELECT 1 FROM sieu_quan_tri WHERE ten_dang_nhap=?');
$st->execute([$ten]);
if ($st->fetchColumn()) {
  loi_thoat_sqt("Tai khoan sieu quan tri '{$ten}' da ton tai.");
}

$registry->prepare('INSERT INTO sieu_quan_tri(ten_dang_nhap, mat_khau_bam) VALUES(?,?)')
         ->execute([$ten, password_hash($mat_khau, PASSWORD_DEFAULT)]);

echo "Da tao tai khoan sieu quan tri '{$ten}'." . PHP_EOL;
