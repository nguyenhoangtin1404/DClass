<?php
declare(strict_types=1);

$options = getopt('', [
  'seed',
  'no-backup',
  'export-hoc-sinh::',
  'export-so-cai::',
  'import-hoc-sinh::',
  'import-so-cai::',
  'liet-ke-tai-khoan',
  'dat-lai-mat-khau::',
  'mat-khau-moi::'
]);

$env = [];
$env_file = __DIR__ . '/../config/env.php';
if (file_exists($env_file)) {
  $env = require $env_file;
}

$db_path = $env['db_path'] ?? (__DIR__ . '/../data/ung_dung.db');
$schema = __DIR__ . '/../config/luoc_do.sql';

function ket_noi(PDO &$pdo = null, string $db_path = ''): PDO
{
  $dir = dirname($db_path);
  if (!is_dir($dir)) {
    @mkdir($dir, 0777, true);
  }
  $pdo = new PDO('sqlite:' . $db_path);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  $pdo->exec('PRAGMA foreign_keys = ON');
  return $pdo;
}

function backup_csdl(string $db_path): ?string
{
  if (!file_exists($db_path)) {
    return null;
  }
  $backup_dir = dirname($db_path);
  $stamp = date('Ymd_His');
  $backup = $backup_dir . '/backup_' . $stamp . '.db';
  if (!copy($db_path, $backup)) {
    throw new RuntimeException('Khong the sao luu CSDL');
  }
  return $backup;
}

function thuc_thi_ddl(PDO $pdo, string $schema_file): void
{
  $sql = file_get_contents($schema_file);
  $cau_lenh = array_filter(array_map('trim', explode(';', $sql)));
  foreach ($cau_lenh as $lenh) {
    if ($lenh !== '') {
      try {
        $pdo->exec($lenh);
      } catch (Throwable $e) {
        // Bỏ qua nếu bảng/cột đã tồn tại, giúp migrate idempotent
      }
    }
  }
}

function seed_mau(PDO $pdo): void
{
  // Chỉ chạy khi có --seed tường minh (dùng cho dev/demo cục bộ), không tự động chạy khi mới
  // tạo CSDL nữa - giáo viên thật tự đăng ký tài khoản qua api/dang_nhap.php?hanh_dong=dang_ky.
  $da_co = (int)$pdo->query('SELECT COUNT(*) FROM giao_vien')->fetchColumn();
  if ($da_co > 0) {
    return;
  }
  $pdo->prepare('INSERT INTO giao_vien(ten_dang_nhap, mat_khau_bam, vai_tro, phai_doi_mat_khau) VALUES(?,?,?,1)')
      ->execute(['gv1', password_hash('123456', PASSWORD_DEFAULT), 'GV']);
  $gv_id = (int)$pdo->lastInsertId();
  $pdo->exec("INSERT INTO lop_hoc(ten) VALUES ('4A'),('4B'),('4C')");
  $st = $pdo->prepare('INSERT INTO giao_vien_lop(giao_vien_id, lop_hoc_id) SELECT ?, id FROM lop_hoc');
  $st->execute([$gv_id]);
  $pdo->prepare("INSERT INTO ly_do(tieu_de, bien_diem, dang_hoat_dong, nguoi_tao_id) VALUES ('Giup ban',2,1,?), ('Hoan thanh som',1,1,?), ('Noi chuyen rieng',-1,1,?)")
      ->execute([$gv_id, $gv_id, $gv_id]);
  $pdo->prepare("INSERT INTO qua_tang(ten, gia_diem, ton_kho, dang_hoat_dong, nguoi_tao_id) VALUES ('Sticker',3,-1,1,?), ('But chi',5,50,1,?), ('Tui mu',8,20,1,?)")
      ->execute([$gv_id, $gv_id, $gv_id]);
  echo "Da seed du lieu mau: tai khoan 'gv1' / '123456' (bat buoc doi mat khau ngay lan dau dang nhap)." . PHP_EOL;
}

function dat_lai_mat_khau(PDO $pdo, string $ten_dang_nhap, string $mat_khau_moi): void
{
  $st = $pdo->prepare('SELECT id FROM giao_vien WHERE ten_dang_nhap=?');
  $st->execute([$ten_dang_nhap]);
  $id = $st->fetchColumn();
  if (!$id) {
    throw new RuntimeException("Khong tim thay tai khoan '{$ten_dang_nhap}'");
  }
  $bam = password_hash($mat_khau_moi, PASSWORD_DEFAULT);
  $pdo->prepare('UPDATE giao_vien SET mat_khau_bam=? WHERE id=?')->execute([$bam, $id]);
  echo "Da dat lai mat khau cho '{$ten_dang_nhap}'." . PHP_EOL;
}

function liet_ke_tai_khoan(PDO $pdo): void
{
  $rows = $pdo->query("SELECT gv.id, gv.ten_dang_nhap, gv.tao_luc,
                               (SELECT COUNT(*) FROM giao_vien_lop gl WHERE gl.giao_vien_id = gv.id) AS so_lop
                        FROM giao_vien gv ORDER BY gv.ten_dang_nhap ASC")->fetchAll();
  if (!$rows) { echo "Chua co tai khoan nao." . PHP_EOL; return; }
  foreach ($rows as $r) {
    echo "#{$r['id']}\t{$r['ten_dang_nhap']}\t{$r['so_lop']} lop\ttao luc {$r['tao_luc']}" . PHP_EOL;
  }
}

function xuat_csv(PDO $pdo, string $sql, array $cot, string $dich): void
{
  $st = $pdo->query($sql);
  $fh = fopen($dich, 'w');
  if ($fh === false) {
    throw new RuntimeException('Khong mo duoc file ' . $dich);
  }
  fputcsv($fh, $cot);
  while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
    $dong = [];
    foreach ($cot as $c) {
      $dong[] = $row[$c] ?? '';
    }
    fputcsv($fh, $dong);
  }
  fclose($fh);
}

function nhap_csv(PDO $pdo, string $bang, array $cot, string $nguon): void
{
  if (!file_exists($nguon)) {
    throw new RuntimeException('Khong tim thay file ' . $nguon);
  }
  $fh = fopen($nguon, 'r');
  if ($fh === false) {
    throw new RuntimeException('Khong mo duoc file ' . $nguon);
  }
  $header = fgetcsv($fh);
  if ($header === false || array_map('trim', $header) !== $cot) {
    throw new RuntimeException('Header CSV khong dung thu tu: ' . implode(',', $cot));
  }
  $pdo->beginTransaction();
  try {
    $place = implode(',', array_fill(0, count($cot), '?'));
    $sql = 'INSERT INTO ' . $bang . '(' . implode(',', $cot) . ') VALUES(' . $place . ')';
    $st = $pdo->prepare($sql);
    while (($row = fgetcsv($fh)) !== false) {
      $st->execute($row);
    }
    $pdo->commit();
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    throw $e;
  } finally {
    fclose($fh);
  }
}

$vua_tao = !file_exists($db_path);
$pdo = ket_noi($pdo, $db_path);

if (!$vua_tao && !isset($options['no-backup'])) {
  $backup = backup_csdl($db_path);
  if ($backup) {
    echo "Da sao luu: {$backup}" . PHP_EOL;
  }
}

thuc_thi_ddl($pdo, $schema);
if (isset($options['seed'])) {
  seed_mau($pdo);
}

if (isset($options['liet-ke-tai-khoan'])) {
  liet_ke_tai_khoan($pdo);
}
if (isset($options['dat-lai-mat-khau'])) {
  $ten = (string)$options['dat-lai-mat-khau'];
  $mk_moi = isset($options['mat-khau-moi']) ? (string)$options['mat-khau-moi'] : '';
  if ($ten === '' || $mk_moi === '') {
    fwrite(STDERR, "Dung: php tools/cai_dat.php --dat-lai-mat-khau=ten_dang_nhap --mat-khau-moi=xxxx" . PHP_EOL);
    exit(1);
  }
  dat_lai_mat_khau($pdo, $ten, $mk_moi);
}

if (isset($options['export-hoc-sinh'])) {
  $dich = $options['export-hoc-sinh'] ?: (__DIR__ . '/../data/hoc_sinh.csv');
  xuat_csv($pdo, 'SELECT id, ma, ho_ten, stt, lop_hoc_id, anh_dai_dien_url, dang_hoat_dong, tao_luc FROM hoc_sinh', ['id','ma','ho_ten','stt','lop_hoc_id','anh_dai_dien_url','dang_hoat_dong','tao_luc'], $dich);
  echo "Da xuat hoc_sinh ra {$dich}" . PHP_EOL;
}
if (isset($options['export-so-cai'])) {
  $dich = $options['export-so-cai'] ?: (__DIR__ . '/../data/so_cai_diem.csv');
  xuat_csv($pdo, 'SELECT id, hoc_sinh_id, giao_vien_id, loai, ly_do_id, qua_tang_id, bien_diem, so_du_sau, ghi_chu, tao_luc FROM so_cai_diem', ['id','hoc_sinh_id','giao_vien_id','loai','ly_do_id','qua_tang_id','bien_diem','so_du_sau','ghi_chu','tao_luc'], $dich);
  echo "Da xuat so_cai_diem ra {$dich}" . PHP_EOL;
}
if (isset($options['import-hoc-sinh'])) {
  nhap_csv($pdo, 'hoc_sinh', ['id','ma','ho_ten','stt','lop_hoc_id','anh_dai_dien_url','dang_hoat_dong','tao_luc'], $options['import-hoc-sinh']);
  echo "Da nhap hoc_sinh tu CSV" . PHP_EOL;
}
if (isset($options['import-so-cai'])) {
  nhap_csv($pdo, 'so_cai_diem', ['id','hoc_sinh_id','giao_vien_id','loai','ly_do_id','qua_tang_id','bien_diem','so_du_sau','ghi_chu','tao_luc'], $options['import-so-cai']);
  echo "Da nhap so_cai_diem tu CSV" . PHP_EOL;
}

echo "Hoan tat cai dat/migrate CSDL" . PHP_EOL;

