<?php
declare(strict_types=1);

/**
 * Chạy chay_migration() cho TẤT CẢ tổ chức đăng ký trong registry.db, kể cả
 * tổ chức lâu không ai đăng nhập (chay_migration() trong config/db.php chỉ chạy
 * khi có request tới đúng DB đó). Dùng khi nâng cấp schema, chạy thủ công hoặc
 * như 1 bước trong quy trình deploy.
 *
 * Cách dùng: php scripts/migrate_all.php
 */

require __DIR__ . '/../config/registry.php';
require __DIR__ . '/../config/migration_nghiep_vu.php';

$registry = ket_noi_registry();
$to_chuc_list = $registry->query('SELECT id, ma_to_chuc, ten, db_path FROM to_chuc ORDER BY id ASC')->fetchAll();

if (!$to_chuc_list) {
  echo "Chua co to chuc nao trong registry." . PHP_EOL;
  exit(0);
}

$thanh_cong = 0;
$loi = 0;
foreach ($to_chuc_list as $tc) {
  $db_path = (string)$tc['db_path'];
  if (!file_exists($db_path)) {
    echo "[BO QUA] {$tc['ma_to_chuc']}: khong tim thay file {$db_path}" . PHP_EOL;
    $loi++;
    continue;
  }
  try {
    $pdo = new PDO('sqlite:' . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
    chay_migration($pdo);
    echo "[OK] {$tc['ma_to_chuc']} ({$tc['ten']})" . PHP_EOL;
    $thanh_cong++;
  } catch (Throwable $e) {
    echo "[LOI] {$tc['ma_to_chuc']}: " . $e->getMessage() . PHP_EOL;
    $loi++;
  }
}

echo "Hoan tat: {$thanh_cong} thanh cong, {$loi} loi/bo qua." . PHP_EOL;
if ($loi > 0) {
  exit(1);
}
