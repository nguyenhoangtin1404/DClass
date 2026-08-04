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

// Xác định tổ chức (tenant) hiện tại dựa trên Host header, TRƯỚC khi mở session/kết nối
// CSDL nghiệp vụ. Chế độ single-tenant cũ (chưa từng tạo to_chuc nào trong registry) giữ
// nguyên hành vi: dùng thẳng $env['db_path'], không đụng registry.
require __DIR__ . '/registry.php';
$TO_CHUC_HIEN_TAI = null;
try {
  $registry = ket_noi_registry();
  $so_luong_to_chuc = (int)($registry->query('SELECT COUNT(*) c FROM to_chuc')->fetch()['c'] ?? 0);
} catch (Throwable $e) {
  http_response_code(500); echo 'Loi ket noi CSDL'; exit;
}
if ($so_luong_to_chuc > 0) {
  $TO_CHUC_HIEN_TAI = xac_dinh_to_chuc_theo_domain($registry, $_SERVER['HTTP_HOST'] ?? '');
  if ($TO_CHUC_HIEN_TAI === null) {
    http_response_code(404);
    echo 'Khong tim thay to chuc ung voi ten mien nay, hoac to chuc da bi khoa.';
    exit;
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
// Phòng thủ theo chiều sâu: dù cookie session đã bị trình duyệt cô lập theo domain, session
// trên server (file sess_*) không tự tách theo tenant. Nếu session đang mang to_chuc_id khác
// với tổ chức vừa xác định theo Host header hiện tại (VD: session id bị đánh cắp/replay sang
// domain của tổ chức khác), huỷ session ngay và coi như chưa đăng nhập.
if ($TO_CHUC_HIEN_TAI !== null && isset($_SESSION['to_chuc_id']) && (int)$_SESSION['to_chuc_id'] !== (int)$TO_CHUC_HIEN_TAI['id']) {
  $_SESSION = [];
  session_unset();
  session_destroy();
  session_start();
}
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
$DB_PATH = $TO_CHUC_HIEN_TAI['db_path'] ?? ($env['db_path'] ?? (__DIR__ . '/../data/ung_dung.db'));
$DB_DIR = dirname($DB_PATH);
$lan_dau = !file_exists($DB_PATH);
if ($lan_dau && $TO_CHUC_HIEN_TAI !== null) {
  // CSDL của tổ chức đã đăng ký trong registry nhưng file bị thiếu (xoá nhầm/di chuyển sai
  // chỗ...) - không được tự seed tài khoản mặc định yếu (gv1/123456) cho 1 tổ chức online,
  // việc đó chỉ chạy qua scripts/tao_to_chuc.php lúc khởi tạo. Báo lỗi rõ để vận hành xử lý.
  http_response_code(500);
  echo 'Loi cau hinh: khong tim thay file CSDL cua to chuc. Lien he quan tri vien.';
  exit;
}
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

// Nếu DB đã tồn tại, chạy migrate; nếu lần đầu (chỉ xảy ra ở chế độ single-tenant cũ), seed
// xong rồi migrate để đảm bảo schema mới nhất
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
