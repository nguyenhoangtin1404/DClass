<?php
declare(strict_types=1);
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/tro_giup.php';
/** @var \PDO $pdo Global PDO instance from config/db.php */

yeu_cau_dang_nhap();
$gv_id = (int)$_SESSION['giao_vien_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); json_phan_hoi(false, null, 'method_not_allowed'); }

$qua_tang_id = isset($_POST['qua_tang_id']) ? (int)$_POST['qua_tang_id'] : 0;
if ($qua_tang_id <= 0) json_phan_hoi(false, null, 'thieu_qua_tang_id');
// kiểm tra quà tồn tại, thuộc về chính giáo viên này, và đang hoạt động để tránh ghi nhầm bản ghi
$st = $pdo->prepare("SELECT id, anh_url, dang_hoat_dong FROM qua_tang WHERE id=? AND nguoi_tao_id=?");
$st->execute([$qua_tang_id, $gv_id]);
$qua = $st->fetch();
if (!$qua) json_phan_hoi(false, null, 'khong_du_quyen');
if (!(int)$qua['dang_hoat_dong']) json_phan_hoi(false, null, 'qua_khong_hoat_dong');
if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) json_phan_hoi(false, null, 'thieu_file');
$file = $_FILES['file'];
if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) json_phan_hoi(false, null, 'upload_error');
if (($file['size'] ?? 0) > 2 * 1024 * 1024) json_phan_hoi(false, null, 'file_qua_lon');

$mime = @exif_imagetype($file['tmp_name']);
$allowed = [ IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp' ];
if (!isset($allowed[$mime])) json_phan_hoi(false, null, 'dinh_dang_khong_ho_tro');
if ($mime === IMAGETYPE_WEBP && !function_exists('imagewebp')) json_phan_hoi(false, null, 'khong_ho_tro_webp');

$ext = $allowed[$mime];
$info = @getimagesize($file['tmp_name']);
if (!$info || !isset($info[0], $info[1])) json_phan_hoi(false, null, 'anh_khong_hop_le');
[$w, $h] = [$info[0], $info[1]];
if ($w <= 0 || $h <= 0 || $w > 4000 || $h > 4000 || ($w * $h) > 16000000) json_phan_hoi(false, null, 'anh_qua_lon');
$baseDir = realpath(__DIR__ . '/..');
$uploadDir = $baseDir . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR . 'qua';
if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0755, true); }

$name = 'qt_' . $qua_tang_id . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$dest = $uploadDir . DIRECTORY_SEPARATOR . $name;
// re-encode để loại bỏ payload lạ và giới hạn kích thước; GIF giữ nguyên để tránh mất frame
if ($mime === IMAGETYPE_GIF) {
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
  $outImg = ($newW !== $w || $newH !== $h) ? imagescale($img, $newW, $newH, IMG_BILINEAR_FIXED) : $img;
  if (!$outImg) { imagedestroy($img); json_phan_hoi(false, null, 'khong_luu_duoc_file'); }
  $saveOk = false;
  if ($mime === IMAGETYPE_JPEG) { $saveOk = imagejpeg($outImg, $dest, 85); }
  elseif ($mime === IMAGETYPE_PNG) { $saveOk = imagepng($outImg, $dest, 6); }
  elseif ($mime === IMAGETYPE_WEBP) { $saveOk = imagewebp($outImg, $dest, 85); }
  imagedestroy($outImg);
  if ($outImg !== $img) { imagedestroy($img); } else { /* already destroyed */ }
  if (!$saveOk) json_phan_hoi(false, null, 'khong_luu_duoc_file');
}

$url = '/upload/qua/' . $name;
// đảm bảo cột tồn tại
try { $pdo->exec("ALTER TABLE qua_tang ADD COLUMN anh_url TEXT"); } catch (Throwable $e) {}
$pdo->prepare('UPDATE qua_tang SET anh_url=? WHERE id=?')->execute([$url, $qua_tang_id]);
// dọn ảnh cũ nếu có (chỉ trong thư mục upload/qua để an toàn đường dẫn)
if (!empty($qua['anh_url']) && strpos($qua['anh_url'], '/upload/qua/') === 0) {
  $old = $baseDir . $qua['anh_url'];
  if (is_file($old)) { @unlink($old); }
}
ghi_log($pdo, (int)$_SESSION['giao_vien_id'], 'upload_anh_qua', 'Upload ảnh cho quà '.$qua_tang_id.' => '.$url);
json_phan_hoi(true, ['url' => $url]);

