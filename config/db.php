<?php
declare(strict_types=1);
$env = [];
$env_file = __DIR__ . '/env.php';
if (file_exists($env_file)) {
  $env = require $env_file;
}
// Nhận diện HTTPS kể cả khi chạy sau reverse proxy/load balancer (Nginx, Cloudflare...)
// chỉ tin header X-Forwarded-Proto nếu người vận hành cấu hình proxy đặt header này đúng cách.
if (!function_exists('dang_https')) {
  function dang_https(): bool {
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
    return ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
  }
}
if (!headers_sent() && isset($env['session_name']) && is_string($env['session_name'])) {
  session_name($env['session_name']);
}
if (!headers_sent() && session_status() !== PHP_SESSION_ACTIVE) {
  session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => dang_https(),
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
}
session_start();
if (!headers_sent()) {
  header('X-Content-Type-Options: nosniff');
  header('X-Frame-Options: DENY');
  header('Referrer-Policy: strict-origin-when-cross-origin');
  // CSP nới lỏng cho inline script/style hiện có trong các trang public/*.php (chưa dùng nonce),
  // nhưng vẫn chặn tải tài nguyên từ nguồn ngoài vì toàn bộ asset đã local hoá theo README.
  header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; frame-ancestors 'none'");
  if (dang_https()) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
  }
}
$DB_PATH = $env['db_path'] ?? (__DIR__ . '/../data/ung_dung.db');
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
} catch (Exception $e) { http_response_code(500); echo 'Loi ket noi CSDL'; exit; }

require __DIR__ . '/migration_nghiep_vu.php';

// Nếu DB đã tồn tại, chạy migrate; nếu lần đầu, seed xong rồi migrate để đảm bảo schema mới nhất
if ($lan_dau) {
  $luoc_do = file_get_contents(__DIR__ . '/luoc_do.sql');
  $pdo->exec($luoc_do);
  $stmt = $pdo->prepare("INSERT INTO giao_vien(ten_dang_nhap, mat_khau_bam, vai_tro, phai_doi_mat_khau) VALUES(?,?,?,1)");
  $stmt->execute(['gv1', password_hash('123456', PASSWORD_DEFAULT), 'ADMIN']);
  $pdo->exec("INSERT INTO lop_hoc(ten) VALUES ('4A'),('4B'),('4C')");
  $pdo->exec("INSERT INTO ly_do(tieu_de, bien_diem, dang_hoat_dong) VALUES ('Giup ban',2,1), ('Hoan thanh som',1,1), ('Noi chuyen rieng',-1,1)");
  $pdo->exec("INSERT INTO qua_tang(ten, gia_diem, ton_kho, dang_hoat_dong) VALUES ('Sticker',3,-1,1), ('But chi',5,50,1), ('Tui mu',8,20,1)");
}
chay_migration($pdo);
