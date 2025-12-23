<?php
declare(strict_types=1);
// Đảm bảo không có output nào trước khi set headers
// Tắt output buffering và xóa tất cả buffer
if (ob_get_level() > 0) {
  while (ob_get_level() > 0) {
    @ob_end_clean();
  }
}
// Bắt đầu output buffering mới để kiểm soát output
@ob_start();

// Chỉ cần session, không cần DB
// Không require config/db.php vì nó sẽ start session và kết nối DB không cần thiết
$env = [];
$env_file = __DIR__ . '/../config/env.php';
if (file_exists($env_file)) {
  $env = require $env_file;
}

// Start session nếu chưa start (kiểm tra trước khi set name)
if (session_status() === PHP_SESSION_NONE) {
  if (!headers_sent() && isset($env['session_name']) && is_string($env['session_name'])) {
    session_name($env['session_name']);
  }
  @session_start(); // @ để tránh warning nếu đã start
}

require __DIR__ . '/../lib/captcha.php';

try {
  // Tạo CAPTCHA mới
  $text = tao_captcha();
  if (empty($text)) {
    throw new RuntimeException('Không thể tạo text CAPTCHA');
  }
  
  luu_captcha_answer($text);
  $image_data = tao_hinh_captcha($text);
  
  if (empty($image_data)) {
    throw new RuntimeException('Không thể tạo hình ảnh CAPTCHA');
  }
  
  // Kiểm tra xem là PNG hay SVG
  $is_svg = (substr($image_data, 0, 5) === '<?xml' || substr($image_data, 0, 4) === '<svg');
  
  // Set headers để tránh cache (phải set trước khi output)
  if ($is_svg) {
    header('Content-Type: image/svg+xml; charset=utf-8');
  } else {
    header('Content-Type: image/png');
  }
  header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
  header('Pragma: no-cache');
  header('Expires: 0');
  
  // Output image
  echo $image_data;
  exit;
} catch (Throwable $e) {
  // Xóa output buffer nếu có
  while (ob_get_level() > 0) {
    @ob_end_clean();
  }
  
  // Log error để debug
  @error_log('CAPTCHA Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
  
  // Nếu headers chưa được gửi, set error response
  if (!headers_sent()) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    // Trả về thông báo lỗi để debug (có thể bật DEBUG mode để xem chi tiết)
    $error_msg = 'Lỗi tạo CAPTCHA';
    // Hiển thị chi tiết lỗi nếu có thể (chỉ trong development)
    if (isset($_GET['debug']) || (defined('DEBUG') && DEBUG)) {
      $error_msg .= ': ' . htmlspecialchars($e->getMessage()) . ' (Line: ' . $e->getLine() . ')';
    }
    echo $error_msg;
  }
  exit;
}

