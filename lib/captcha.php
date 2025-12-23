<?php
declare(strict_types=1);

/**
 * Tạo chuỗi CAPTCHA ngẫu nhiên
 * @return string Chuỗi 4-5 ký tự (chữ in hoa và số, tránh 0/O, 1/I)
 */
function tao_captcha(): string {
  // Chỉ dùng chữ in hoa và số, loại bỏ các ký tự dễ nhầm lẫn
  $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Bỏ I, O, 0, 1
  // Dùng random_int() thay vì rand() để an toàn hơn
  $length = function_exists('random_int') ? random_int(5, 6) : rand(5, 6);
  $text = '';
  $chars_len = strlen($chars);
  for ($i = 0; $i < $length; $i++) {
    $idx = function_exists('random_int') ? random_int(0, $chars_len - 1) : rand(0, $chars_len - 1);
    $text .= $chars[$idx];
  }
  return $text;
}

/**
 * Tạo hình ảnh CAPTCHA
 * @param string $text Text hiển thị trên CAPTCHA
 * @return string Binary data của hình ảnh PNG hoặc SVG nếu GD không có
 * @throws RuntimeException Nếu không thể tạo CAPTCHA
 */
function tao_hinh_captcha(string $text): string {
  // Nếu GD không có, tạo SVG đơn giản
  if (!function_exists('imagecreatetruecolor')) {
    return tao_captcha_svg($text);
  }
  
  $width = 150;
  $height = 50;
  
  // Tạo image
  $img = imagecreatetruecolor($width, $height);
  
  // Màu nền sáng
  $bg_color = imagecolorallocate($img, 240, 240, 240);
  imagefill($img, 0, 0, $bg_color);
  
  // Màu text đậm
  $text_color = imagecolorallocate($img, 30, 30, 30);
  
  // Helper function để dùng random_int nếu có
  $safe_rand = function_exists('random_int') 
    ? function($min, $max) { return random_int($min, $max); }
    : function($min, $max) { return rand($min, $max); };
  
  // Thêm nhiễu nền (điểm ngẫu nhiên) - tăng số lượng
  for ($i = 0; $i < 150; $i++) {
    $r = $safe_rand(140, 200);
    $g = $safe_rand(140, 200);
    $b = $safe_rand(140, 200);
    $noise_color = imagecolorallocate($img, $r, $g, $b);
    imagesetpixel($img, $safe_rand(0, $width - 1), $safe_rand(0, $height - 1), $noise_color);
  }
  
  // Thêm đường cong ngẫu nhiên - tăng số lượng
  for ($i = 0; $i < 5; $i++) {
    $gray = $safe_rand(170, 210);
    $line_color = imagecolorallocate($img, $gray, $gray, $gray);
    imageline($img, $safe_rand(0, $width - 1), $safe_rand(0, $height - 1), $safe_rand(0, $width - 1), $safe_rand(0, $height - 1), $line_color);
  }
  
  // Vẽ text với font built-in
  $font_size = 5; // Font size 5 là font lớn nhất trong built-in fonts
  $text_width = imagefontwidth($font_size) * strlen($text);
  $text_height = imagefontheight($font_size);
  $x = ($width - $text_width) / 2;
  $y = ($height - $text_height) / 2;
  
  // Vẽ text với độ lệch nhẹ để tạo hiệu ứng méo - tăng độ lệch
  $len = strlen($text);
  for ($i = 0; $i < $len; $i++) {
    $char_x = $x + ($i * imagefontwidth($font_size)) + $safe_rand(-2, 2);
    $char_y = $y + $safe_rand(-5, 5); // Lệch ngẫu nhiên theo chiều dọc
    imagestring($img, $font_size, $char_x, $char_y, $text[$i], $text_color);
  }
  
  // Thêm nhiễu phía trước text - tăng số lượng
  for ($i = 0; $i < 50; $i++) {
    $r = $safe_rand(100, 220);
    $g = $safe_rand(100, 220);
    $b = $safe_rand(100, 220);
    $noise_color = imagecolorallocate($img, $r, $g, $b);
    imagesetpixel($img, $safe_rand(0, $width - 1), $safe_rand(0, $height - 1), $noise_color);
  }
  
  // Output PNG
  ob_start();
  imagepng($img);
  $image_data = ob_get_contents();
  ob_end_clean();
  imagedestroy($img);
  
  return $image_data;
}

/**
 * Lưu CAPTCHA answer vào session
 * @param string $text Text của CAPTCHA
 * @return void
 */
function luu_captcha_answer(string $text): void {
  $_SESSION['captcha_answer'] = [
    'text' => $text,
    'expires' => time() + 300 // 5 phút
  ];
}

/**
 * Xác thực CAPTCHA
 * @param string $user_input Input từ người dùng
 * @return bool True nếu hợp lệ, false nếu không
 */
function xac_thuc_captcha(string $user_input): bool {
  if (!isset($_SESSION['captcha_answer'])) {
    return false;
  }
  
  $captcha_data = $_SESSION['captcha_answer'];
  
  // Kiểm tra hết hạn
  if (time() > $captcha_data['expires']) {
    unset($_SESSION['captcha_answer']);
    return false;
  }
  
  // So sánh (case-insensitive, xóa khoảng trắng)
  $user_clean = strtoupper(trim(str_replace(' ', '', $user_input)));
  $answer_clean = strtoupper(trim(str_replace(' ', '', $captcha_data['text'])));
  
  $valid = hash_equals($answer_clean, $user_clean);
  
  // Xóa answer sau khi verify (dù đúng hay sai)
  unset($_SESSION['captcha_answer']);
  
  return $valid;
}

/**
 * Tạo CAPTCHA dạng SVG (fallback khi không có GD library)
 * @param string $text Text hiển thị trên CAPTCHA
 * @return string SVG data
 */
function tao_captcha_svg(string $text): string {
  if (empty($text)) {
    throw new RuntimeException('Text CAPTCHA không được rỗng');
  }
  
  $width = 180;
  $height = 60;
  $font_size = 28;
  $chars = str_split($text);
  $char_count = count($chars);
  if ($char_count === 0) {
    throw new RuntimeException('Không thể chia text thành ký tự');
  }
  $char_width = $width / $char_count;
  
  // Helper function để dùng random_int nếu có
  $safe_rand = function_exists('random_int') 
    ? function($min, $max) { return random_int($min, $max); }
    : function($min, $max) { return rand($min, $max); };
  
  $svg = '<?xml version="1.0" encoding="UTF-8"?>';
  $svg .= '<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">';
  
  // Nền với gradient để khó extract
  $svg .= '<defs>';
  $svg .= '<linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">';
  $svg .= '<stop offset="0%" style="stop-color:#f0f0f0;stop-opacity:1" />';
  $svg .= '<stop offset="100%" style="stop-color:#e0e0e0;stop-opacity:1" />';
  $svg .= '</linearGradient>';
  $svg .= '</defs>';
  $svg .= '<rect width="' . $width . '" height="' . $height . '" fill="url(#bg)"/>';
  
  // Thêm nhiễu (đường ngẫu nhiên) - tăng số lượng
  for ($i = 0; $i < 8; $i++) {
    $x1 = $safe_rand(0, $width - 1);
    $y1 = $safe_rand(0, $height - 1);
    $x2 = $safe_rand(0, $width - 1);
    $y2 = $safe_rand(0, $height - 1);
    $gray = $safe_rand(170, 210);
    $svg .= '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2 . '" stroke="rgb(' . $gray . ',' . $gray . ',' . $gray . ')" stroke-width="' . $safe_rand(1, 2) . '"/>';
  }
  
  // Thêm điểm nhiễu - tăng số lượng
  for ($i = 0; $i < 50; $i++) {
    $x = $safe_rand(0, $width - 1);
    $y = $safe_rand(0, $height - 1);
    $gray = $safe_rand(140, 190);
    $r = $safe_rand(1, 2);
    $svg .= '<circle cx="' . $x . '" cy="' . $y . '" r="' . $r . '" fill="rgb(' . $gray . ',' . $gray . ',' . $gray . ')"/>';
  }
  
  // Vẽ text với độ lệch và làm khó extract bằng cách:
  // 1. Thêm nhiều text element chồng lên nhau với opacity khác nhau
  // 2. Xoay và lệch nhiều hơn
  // 3. Thêm text giả để đánh lừa
  foreach ($chars as $index => $char) {
    $base_x = ($index * $char_width) + ($char_width / 2) - ($font_size / 3);
    $base_y = ($height / 2) + ($font_size / 3);
    
    // Vẽ text chính với nhiều lớp để khó extract
    for ($layer = 0; $layer < 2; $layer++) {
      $x = $base_x + $safe_rand(-2, 2);
      $y = $base_y + $safe_rand(-6, 6);
      $rotation = $safe_rand(-20, 20);
      $opacity = $layer === 0 ? '1' : '0.3';
      $svg .= '<text x="' . $x . '" y="' . $y . '" font-family="Arial, sans-serif" font-size="' . $font_size . '" font-weight="bold" fill="#1e1e1e" opacity="' . $opacity . '" transform="rotate(' . $rotation . ' ' . $x . ' ' . $y . ')">' . htmlspecialchars($char) . '</text>';
    }
  }
  
  // Thêm text giả để đánh lừa (chỉ hiển thị, không phải answer)
  $fake_chars = ['A', 'B', 'C', 'D', 'E', '2', '3', '4', '5'];
  for ($i = 0; $i < 3; $i++) {
    $fake_char = $fake_chars[$safe_rand(0, count($fake_chars) - 1)];
    $x = $safe_rand(10, $width - 10);
    $y = $safe_rand(15, $height - 10);
    $rotation = $safe_rand(-25, 25);
    $svg .= '<text x="' . $x . '" y="' . $y . '" font-family="Arial, sans-serif" font-size="' . ($font_size - 4) . '" font-weight="normal" fill="#888" opacity="0.2" transform="rotate(' . $rotation . ' ' . $x . ' ' . $y . ')">' . htmlspecialchars($fake_char) . '</text>';
  }
  
  $svg .= '</svg>';
  return $svg;
}

/**
 * Xóa CAPTCHA answer khỏi session
 * @return void
 */
function xoa_captcha(): void {
  unset($_SESSION['captcha_answer']);
}

