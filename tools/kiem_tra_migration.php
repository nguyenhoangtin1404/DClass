<?php
declare(strict_types=1);

/**
 * Dry-run AN TOÀN cho migration nâng cấp CSDL production (chay_backfill_so_huu trong
 * config/migration_nghiep_vu.php): sao chép file CSDL thật ra 1 bản tạm, chạy migration TRÊN
 * BẢN SAO đó, rồi in ra chính xác migration sẽ thay đổi những gì. KHÔNG BAO GIỜ ghi vào file gốc.
 *
 * Cách dùng:
 *   php tools/kiem_tra_migration.php /duong/dan/toi/ung_dung.db
 *
 * Sau khi xem báo cáo, có thể tự mở file .kiemtra.db bằng `sqlite3` để kiểm tra thêm, rồi tự xoá
 * đi. Script không tự xoá để bạn có thời gian xem lại nếu cần.
 */

require __DIR__ . '/../config/migration_nghiep_vu.php';

$nguon = $argv[1] ?? '';
if ($nguon === '') {
  fwrite(STDERR, "Dung: php tools/kiem_tra_migration.php /duong/dan/toi/ung_dung.db" . PHP_EOL);
  exit(1);
}
if (!file_exists($nguon)) {
  fwrite(STDERR, "Khong tim thay file: {$nguon}" . PHP_EOL);
  exit(1);
}
if (!is_readable($nguon)) {
  fwrite(STDERR, "Khong doc duoc file (kiem tra quyen): {$nguon}" . PHP_EOL);
  exit(1);
}

$ban_sao = dirname($nguon) . '/' . basename($nguon, '.db') . '.kiemtra.db';
if (file_exists($ban_sao)) {
  fwrite(STDERR, "File tam '{$ban_sao}' da ton tai tu lan chay truoc - xoa no roi chay lai." . PHP_EOL);
  exit(1);
}
if (!copy($nguon, $ban_sao)) {
  fwrite(STDERR, "Khong sao chep duoc file - kiem tra dung luong dia/quyen ghi thu muc." . PHP_EOL);
  exit(1);
}
echo "Da sao chep '{$nguon}' -> '{$ban_sao}' (file goc KHONG bi dung toi)." . PHP_EOL;

function ket_noi_kiem_tra(string $duong_dan): PDO
{
  $pdo = new PDO('sqlite:' . $duong_dan);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  $pdo->exec('PRAGMA foreign_keys = ON');
  return $pdo;
}

// null nghia la bang/cot chua ton tai (CSDL production cu, truoc khi co cot nguoi_tao_id) - PHAI
// phan biet voi 0 (cot da co nhung khong co dong nao khop), neu khong se bao cao sai la "khong co
// gi can backfill" trong khi thuc ra la "chua co cot de ma dem".
function dem(PDO $pdo, string $sql): ?int
{
  try {
    return (int)$pdo->query($sql)->fetchColumn();
  } catch (Throwable $e) {
    return null;
  }
}

function hien(?int $v): string
{
  return $v === null ? '(cột/bảng chưa tồn tại)' : (string)$v;
}

function bao_cao(PDO $pdo): array
{
  return [
    'giao_vien' => dem($pdo, 'SELECT COUNT(*) FROM giao_vien'),
    'giao_vien_admin' => dem($pdo, "SELECT COUNT(*) FROM giao_vien WHERE vai_tro='ADMIN'"),
    'lop_hoc' => dem($pdo, 'SELECT COUNT(*) FROM lop_hoc'),
    'giao_vien_lop' => dem($pdo, 'SELECT COUNT(*) FROM giao_vien_lop'),
    'ly_do' => dem($pdo, 'SELECT COUNT(*) FROM ly_do'),
    'ly_do_chua_co_chu' => dem($pdo, 'SELECT COUNT(*) FROM ly_do WHERE nguoi_tao_id IS NULL'),
    'qua_tang' => dem($pdo, 'SELECT COUNT(*) FROM qua_tang'),
    'qua_tang_chua_co_chu' => dem($pdo, 'SELECT COUNT(*) FROM qua_tang WHERE nguoi_tao_id IS NULL'),
    'hoc_sinh' => dem($pdo, 'SELECT COUNT(*) FROM hoc_sinh'),
    'so_cai_diem' => dem($pdo, 'SELECT COUNT(*) FROM so_cai_diem'),
    'da_migrate_truoc_do' => dem($pdo, "SELECT COUNT(*) FROM migrations_ap_dung WHERE ten='backfill_so_huu_theo_giao_vien_2026_08'"),
  ];
}

try {
  $pdo = ket_noi_kiem_tra($ban_sao);
  $truoc = bao_cao($pdo);

  echo PHP_EOL . "=== TRUOC MIGRATION ===" . PHP_EOL;
  foreach ($truoc as $k => $v) { echo "  {$k}: " . hien($v) . PHP_EOL; }

  if ($truoc['da_migrate_truoc_do'] > 0) {
    echo PHP_EOL . "CSDL nay DA duoc migrate truoc do - chay lai se khong lam gi them (idempotent)." . PHP_EOL;
  }

  chay_migration($pdo);
  $sau = bao_cao($pdo);

  echo PHP_EOL . "=== SAU MIGRATION ===" . PHP_EOL;
  foreach ($sau as $k => $v) { echo "  {$k}: " . hien($v) . PHP_EOL; }

  echo PHP_EOL . "=== THAY DOI ===" . PHP_EOL;
  echo "  giao_vien_lop: " . hien($truoc['giao_vien_lop']) . " -> " . hien($sau['giao_vien_lop']) . " (tang do gan lai quyen cho tai khoan ADMIN cu vao cac lop dang co)" . PHP_EOL;
  echo "  ly_do: " . hien($truoc['ly_do']) . " -> " . hien($sau['ly_do']) . " (tang do nhan ban ly do dung chung cu cho tung giao vien)" . PHP_EOL;
  echo "  qua_tang: " . hien($truoc['qua_tang']) . " -> " . hien($sau['qua_tang']) . " (tang do nhan ban qua dung chung cu cho tung giao vien)" . PHP_EOL;
  echo "  hoc_sinh: " . hien($truoc['hoc_sinh']) . " -> " . hien($sau['hoc_sinh']) . " (PHAI luon bang nhau - migration khong dung toi hoc_sinh)" . PHP_EOL;
  echo "  so_cai_diem: " . hien($truoc['so_cai_diem']) . " -> " . hien($sau['so_cai_diem']) . " (PHAI luon bang nhau - migration khong dung toi lich su)" . PHP_EOL;

  if ($sau['hoc_sinh'] !== $truoc['hoc_sinh'] || $sau['so_cai_diem'] !== $truoc['so_cai_diem']) {
    echo PHP_EOL . "!!! CANH BAO: so luong hoc_sinh/so_cai_diem thay doi - DUNG LAI VA KIEM TRA, day khong phai hanh vi mong doi cua migration. !!!" . PHP_EOL;
  } else {
    echo PHP_EOL . "OK: hoc_sinh va lich su diem khong bi dung toi." . PHP_EOL;
  }
} catch (Throwable $e) {
  fwrite(STDERR, "Loi khi kiem tra: " . $e->getMessage() . PHP_EOL);
  exit(1);
}

echo PHP_EOL . "Ban sao da migrate nam o: {$ban_sao}" . PHP_EOL;
echo "Co the tu mo bang 'sqlite3 {$ban_sao}' de xem chi tiet, roi tu xoa file nay khi xong." . PHP_EOL;
echo "File goc '{$nguon}' hoan toan khong bi dung toi." . PHP_EOL;
