<?php
declare(strict_types=1);
// Đảm bảo không có output nào trước khi set headers
if (ob_get_level()) {
  ob_clean();
}

// Chỉ cần session, không cần DB
$env = [];
$env_file = __DIR__ . '/../config/env.php';
if (file_exists($env_file)) {
  $env = require $env_file;
}
if (!headers_sent() && isset($env['session_name']) && is_string($env['session_name'])) {
  session_name($env['session_name']);
}
session_start();

require __DIR__ . '/../lib/captcha.php';

try {
  // Tạo CAPTCHA mới
  $text = tao_captcha();
  luu_captcha_answer($text);
  $image_data = tao_hinh_captcha($text);
  
  // Kiểm tra xem là PNG hay SVG
  $is_svg = (substr($image_data, 0, 5) === '<?xml' || substr($image_data, 0, 4) === '<svg');
  
  // Set headers để tránh cache (phải set trước khi output)
  if ($is_svg) {
    header('Content-Type: image/svg+xml');
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
  if (ob_get_level()) {
    ob_clean();
  }
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo 'Lỗi tạo CAPTCHA: ' . htmlspecialchars($e->getMessage());
  exit;
}

