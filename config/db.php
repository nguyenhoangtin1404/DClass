<?php
declare(strict_types=1);
session_start();
$DB_PATH = __DIR__ . '/../data/ung_dung.db';
$DB_DIR = dirname($DB_PATH);
$lan_dau = !file_exists($DB_PATH);
// Tự tạo thư mục dữ liệu nếu chưa tồn tại để tránh lỗi kết nối SQLite lần đầu
if (!is_dir($DB_DIR)) {
  @mkdir($DB_DIR, 0777, true);
}
try {
  $pdo = new PDO('sqlite:' . $DB_PATH);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  $pdo->exec("PRAGMA foreign_keys = ON");
  // Chuẩn hóa mã loại lịch sử: chuyển mã cũ sang mã mới để code gọn hơn
  try {
    $pdo->exec("UPDATE so_cai_diem SET loai='CONG_DIEM' WHERE loai='CONG'");
    $pdo->exec("UPDATE so_cai_diem SET loai='DOI_DIEM' WHERE loai='QUY_DOI'");
  } catch (Throwable $___e) { /* bảng có thể chưa tồn tại ở lần chạy đầu */ }
  // Bổ sung cột mới cho hoc_sinh nếu thiếu
  try {
    $cols = $pdo->query("PRAGMA table_info(hoc_sinh)")->fetchAll();
    $tenCols = array_map(fn($c) => $c['name'] ?? '', $cols);
    if (!in_array('gioi_tinh', $tenCols, true)) { $pdo->exec("ALTER TABLE hoc_sinh ADD COLUMN gioi_tinh TEXT"); }
    if (!in_array('ngay_sinh', $tenCols, true)) { $pdo->exec("ALTER TABLE hoc_sinh ADD COLUMN ngay_sinh TEXT"); }
    if (!in_array('anh_dai_dien_url', $tenCols, true)) { $pdo->exec("ALTER TABLE hoc_sinh ADD COLUMN anh_dai_dien_url TEXT"); }
  } catch (Throwable $___e2) { /* ignore */ }
  // Bổ sung cột mới cho qua_tang nếu thiếu
  try {
    $cols2 = $pdo->query("PRAGMA table_info(qua_tang)")->fetchAll();
    $tenCols2 = array_map(fn($c) => $c['name'] ?? '', $cols2);
    if (!in_array('anh_url', $tenCols2, true)) { $pdo->exec("ALTER TABLE qua_tang ADD COLUMN anh_url TEXT"); }
  } catch (Throwable $___e3) { /* ignore */ }
} catch (Exception $e) { http_response_code(500); echo 'Loi ket noi CSDL'; exit; }
if ($lan_dau) {
  $luoc_do = file_get_contents(__DIR__ . '/luoc_do.sql');
  $pdo->exec($luoc_do);
  $stmt = $pdo->prepare("INSERT INTO giao_vien(ten_dang_nhap, mat_khau_bam) VALUES(?,?)");
  $stmt->execute(['gv1', password_hash('123456', PASSWORD_DEFAULT)]);
  $pdo->exec("INSERT INTO lop_hoc(ten) VALUES ('4A'),('4B'),('4C')");
  $pdo->exec("INSERT INTO ly_do(tieu_de, bien_diem, dang_hoat_dong) VALUES ('Giup ban',2,1), ('Hoan thanh som',1,1), ('Noi chuyen rieng',-1,1)");
  $pdo->exec("INSERT INTO qua_tang(ten, gia_diem, ton_kho, dang_hoat_dong) VALUES ('Sticker',3,-1,1), ('But chi',5,50,1), ('Tui mu',8,20,1)");
}
