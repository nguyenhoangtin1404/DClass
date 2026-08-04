<?php
declare(strict_types=1);

/**
 * CLI provisioning: tạo 1 tổ chức (tenant) mới.
 * - Tạo file data/{ma_to_chuc}.db từ config/luoc_do.sql, seed tài khoản ADMIN đầu tiên
 *   (bắt buộc đổi mật khẩu ngay lần đăng nhập đầu).
 * - Đăng ký tổ chức vào registry.db (bảng to_chuc).
 *
 * Cách dùng:
 *   php scripts/tao_to_chuc.php --ma=truong_abc --ten="Truong Tieu Hoc ABC" --admin=admin1 [--mat_khau=xxxx]
 * Nếu không truyền --mat_khau, hệ thống tự sinh mật khẩu ngẫu nhiên và in ra 1 lần duy nhất.
 */

require __DIR__ . '/../config/registry.php';

$options = getopt('', ['ma:', 'ten:', 'admin::', 'mat_khau::']);

function loi_thoat(string $msg): never {
  fwrite(STDERR, $msg . PHP_EOL);
  exit(1);
}

$ma_to_chuc = trim((string)($options['ma'] ?? ''));
$ten = trim((string)($options['ten'] ?? ''));
$admin_user = trim((string)($options['admin'] ?? 'admin'));
$mat_khau = isset($options['mat_khau']) ? (string)$options['mat_khau'] : null;

if ($ma_to_chuc === '' || $ten === '') {
  loi_thoat('Thieu --ma hoac --ten. Vi du: php scripts/tao_to_chuc.php --ma=truong_abc --ten="Truong ABC" --admin=admin1');
}
if (!preg_match('/^[a-z0-9_]{2,32}$/', $ma_to_chuc)) {
  loi_thoat('Ma to chuc chi duoc chua chu thuong/so/gach duoi, 2-32 ky tu (vd: truong_abc).');
}
if ($admin_user === '') {
  loi_thoat('Ten dang nhap admin khong duoc rong.');
}

$registry = ket_noi_registry();
if (tim_to_chuc_theo_ma($registry, $ma_to_chuc) !== null) {
  loi_thoat("Ma to chuc '{$ma_to_chuc}' da ton tai trong registry.");
}

$data_dir = __DIR__ . '/../data';
if (!is_dir($data_dir)) {
  @mkdir($data_dir, 0777, true);
}
$db_path = $data_dir . '/' . $ma_to_chuc . '.db';
if (file_exists($db_path)) {
  loi_thoat("File CSDL '{$db_path}' da ton tai - khong ghi de.");
}

$mat_khau_tu_sinh = false;
if ($mat_khau === null || $mat_khau === '') {
  $mat_khau = bin2hex(random_bytes(6)); // 12 ky tu hex ngau nhien
  $mat_khau_tu_sinh = true;
}

$pdo = new PDO('sqlite:' . $db_path);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->exec('PRAGMA foreign_keys = ON');
$luoc_do = file_get_contents(__DIR__ . '/../config/luoc_do.sql');
$pdo->exec($luoc_do);
$pdo->prepare('INSERT INTO giao_vien(ten_dang_nhap, mat_khau_bam, vai_tro, phai_doi_mat_khau) VALUES(?,?,?,1)')
    ->execute([$admin_user, password_hash($mat_khau, PASSWORD_DEFAULT), 'ADMIN']);

$registry->prepare('INSERT INTO to_chuc(ma_to_chuc, ten, db_path, dang_hoat_dong) VALUES(?,?,?,1)')
         ->execute([$ma_to_chuc, $ten, $db_path]);

echo "Da tao to chuc '{$ma_to_chuc}' ({$ten})." . PHP_EOL;
echo "CSDL: {$db_path}" . PHP_EOL;
echo "Tai khoan ADMIN dau tien: {$admin_user}" . PHP_EOL;
if ($mat_khau_tu_sinh) {
  echo "Mat khau tam (chi hien 1 lan, bat buoc doi ngay khi dang nhap): {$mat_khau}" . PHP_EOL;
}
