<?php
declare(strict_types=1);
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/tro_giup.php';
/** @var \PDO $pdo Global PDO instance from config/db.php */

yeu_cau_dang_nhap();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); json_phan_hoi(false, null, 'method_not_allowed'); }

$hoc_sinh_id = isset($_POST['hoc_sinh_id']) ? (int)$_POST['hoc_sinh_id'] : 0;
if ($hoc_sinh_id <= 0) json_phan_hoi(false, null, 'thieu_hoc_sinh_id');
// Kiểm tra học sinh tồn tại và quyền của GV
$st = $pdo->prepare('SELECT lop_hoc_id, anh_dai_dien_url FROM hoc_sinh WHERE id=?');
$st->execute([$hoc_sinh_id]);
$hs = $st->fetch();
if (!$hs) json_phan_hoi(false, null, 'hoc_sinh_khong_ton_tai');
$la_admin = (($_SESSION['vai_tro'] ?? '') === 'ADMIN');
if (!$la_admin) {
  $lop_hoc_id = (int)($hs['lop_hoc_id'] ?? 0);
  if ($lop_hoc_id <= 0) json_phan_hoi(false, null, 'khong_du_quyen');
  $stL = $pdo->prepare("SELECT 1 FROM giao_vien_lop WHERE giao_vien_id=? AND lop_hoc_id=?");
  $stL->execute([(int)$_SESSION['giao_vien_id'], $lop_hoc_id]);
  if (!$stL->fetchColumn()) json_phan_hoi(false, null, 'khong_du_quyen');
}

if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) json_phan_hoi(false, null, 'thieu_file');
$file = $_FILES['file'];
if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) json_phan_hoi(false, null, 'upload_error');
// Giới hạn 2MB để tránh DOS dung lượng
if (($file['size'] ?? 0) > 2 * 1024 * 1024) json_phan_hoi(false, null, 'file_qua_lon');

$imgType = @exif_imagetype($file['tmp_name']);
$allowed = [
  IMAGETYPE_JPEG => 'jpg',
  IMAGETYPE_PNG  => 'png',
  IMAGETYPE_GIF  => 'gif',
  IMAGETYPE_WEBP => 'webp'
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

$randBytes = function(int $len){
  if (function_exists('random_bytes')) return random_bytes($len);
  if (function_exists('openssl_random_pseudo_bytes')) return openssl_random_pseudo_bytes($len);
  // Fallback (không an toàn bằng nhưng đủ để đặt tên file)
  return substr(md5(uniqid((string)mt_rand(), true)), 0, $len);
};
$name = 'hs_' . $hoc_sinh_id . '_' . date('Ymd_His') . '_' . bin2hex($randBytes(4)) . '.' . $ext;
$dest = $uploadDir . DIRECTORY_SEPARATOR . $name;
// Nếu GD không sẵn có, hoặc GIF, chỉ move file sau khi đã kiểm tra định dạng/size.
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
  } elseif ($newW !== $w || $newH !== $h) {
    // Không có imagescale -> giữ nguyên kích thước gốc
    $newW = $w; $newH = $h;
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
$pdo->prepare('UPDATE hoc_sinh SET anh_dai_dien_url=? WHERE id=?')->execute([$url, $hoc_sinh_id]);
// Xóa file cũ nếu nằm trong thư mục upload/avatar
if (!empty($hs['anh_dai_dien_url']) && strpos($hs['anh_dai_dien_url'], '/upload/avatar/') === 0) {
  $old = $baseDir . $hs['anh_dai_dien_url'];
  if (is_file($old)) { @unlink($old); }
}
ghi_log($pdo, (int)$_SESSION['giao_vien_id'], 'upload_avatar', 'Upload avatar cho hs '.$hoc_sinh_id.' => '.$url);
json_phan_hoi(true, ['url' => $url]);

