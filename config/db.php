<?php
declare(strict_types=1);
session_start();
$DB_PATH = __DIR__ . '/../data/ung_dung.db';
$lan_dau = !file_exists($DB_PATH);
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
