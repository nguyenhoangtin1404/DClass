<?php
declare(strict_types=1);
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/tro_giup.php';
require __DIR__ . '/../lib/gioi_han_toc_do.php';
/** @var \PDO $pdo Global PDO instance from config/db.php */

// Upload ảnh CHUNG, không gắn với bản ghi nào: nhận file -> trả về { url }.
// Dùng khi cần URL ảnh trước khi bản ghi tồn tại (vd form "Thêm học sinh" chưa có id).
// Khác upload_avatar.php / upload_qua.php ở chỗ không đụng DB và không cần quyền trên bản ghi.

yeu_cau_dang_nhap();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  http_response_code(405);
  json_phan_hoi(false, null, 'method_not_allowed');
}

// Chống lạm dụng: endpoint này ghi file mà không ràng buộc bản ghi, nên giới hạn số lần/IP
// giống anti-spam đăng ký (lib/gioi_han_toc_do.php). Ngưỡng rộng, chỉ chặn upload hàng loạt.
$khoa_gh = 'up_anh|' . ($_SERVER['REMOTE_ADDR'] ?? 'na');
if (doc_dem_that_bai($pdo, $khoa_gh)['khoa_den'] > time()) {
  json_phan_hoi(false, null, 'qua_so_lan');
}
ghi_nhan_that_bai($pdo, $khoa_gh, 60);

if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
  json_phan_hoi(false, null, 'thieu_file');
}
$file = $_FILES['file'];
if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) json_phan_hoi(false, null, 'upload_error');
if (($file['size'] ?? 0) > 2 * 1024 * 1024) json_phan_hoi(false, null, 'file_qua_lon');

$imgType = @exif_imagetype($file['tmp_name']);
$allowed = [
  IMAGETYPE_JPEG => 'jpg',
  IMAGETYPE_PNG  => 'png',
  IMAGETYPE_GIF  => 'gif',
  IMAGETYPE_WEBP => 'webp',
];
if (!isset($allowed[$imgType])) json_phan_hoi(false, null, 'dinh_dang_khong_ho_tro');
if ($imgType === IMAGETYPE_WEBP && !function_exists('imagewebp')) json_phan_hoi(false, null, 'khong_ho_tro_webp');
$ext = $allowed[$imgType];

$info = @getimagesize($file['tmp_name']);
if (!$info || !isset($info[0], $info[1])) json_phan_hoi(false, null, 'anh_khong_hop_le');
[$w, $h] = [$info[0], $info[1]];
if ($w <= 0 || $h <= 0 || $w > 4000 || $h > 4000 || ($w * $h) > 16000000) json_phan_hoi(false, null, 'anh_qua_lon');

$gdAvailable = function_exists('imagecreatefromstring');
$baseDir = realpath(__DIR__ . '/..');
$uploadDir = $baseDir . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR . 'avatar';
if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0755, true); }

$name = 'up_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$dest = $uploadDir . DIRECTORY_SEPARATOR . $name;

// Re-encode để loại payload lạ; GIF hoặc thiếu GD thì move sau khi đã kiểm định dạng/kích thước.
if (!$gdAvailable || $imgType === IMAGETYPE_GIF) {
  if (!move_uploaded_file($file['tmp_name'], $dest)) json_phan_hoi(false, null, 'khong_luu_duoc_file');
} else {
  $data = @file_get_contents($file['tmp_name']);
  if ($data === false) json_phan_hoi(false, null, 'khong_doc_duoc_file');
  $img = @imagecreatefromstring($data);
  if (!$img) json_phan_hoi(false, null, 'anh_khong_hop_le');
  $maxDim = 1600;
  $newW = $w; $newH = $h;
  if ($w > $maxDim || $h > $maxDim) {
    if ($w >= $h) { $newW = $maxDim; $newH = (int)round($h * $maxDim / $w); }
    else { $newH = $maxDim; $newW = (int)round($w * $maxDim / $h); }
  }
  $outImg = $img;
  if (($newW !== $w || $newH !== $h) && function_exists('imagescale')) {
    $scaled = imagescale($img, $newW, $newH, IMG_BILINEAR_FIXED);
    if ($scaled) { $outImg = $scaled; }
  }
  if (!$outImg) { imagedestroy($img); json_phan_hoi(false, null, 'khong_luu_duoc_file'); }
  $saveOk = false;
  if ($imgType === IMAGETYPE_JPEG) { $saveOk = imagejpeg($outImg, $dest, 85); }
  elseif ($imgType === IMAGETYPE_PNG) { $saveOk = imagepng($outImg, $dest, 6); }
  elseif ($imgType === IMAGETYPE_WEBP) { $saveOk = imagewebp($outImg, $dest, 85); }
  imagedestroy($outImg);
  if ($outImg !== $img) { imagedestroy($img); }
  if (!$saveOk) json_phan_hoi(false, null, 'khong_luu_duoc_file');
}

$url = '/upload/avatar/' . $name;
ghi_log($pdo, (int)$_SESSION['giao_vien_id'], 'upload_anh', 'Upload ảnh chung => ' . $url);
json_phan_hoi(true, ['url' => $url]);
