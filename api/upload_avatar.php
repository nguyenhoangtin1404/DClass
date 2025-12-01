<?php
declare(strict_types=1);
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/tro_giup.php';

yeu_cau_dang_nhap();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); json_phan_hoi(false, null, 'method_not_allowed'); }

$hoc_sinh_id = isset($_POST['hoc_sinh_id']) ? (int)$_POST['hoc_sinh_id'] : 0;
if ($hoc_sinh_id <= 0) json_phan_hoi(false, null, 'thieu_hoc_sinh_id');
// Kiểm tra học sinh tồn tại
$st = $pdo->prepare('SELECT 1 FROM hoc_sinh WHERE id=?');
$st->execute([$hoc_sinh_id]);
if (!$st->fetchColumn()) json_phan_hoi(false, null, 'hoc_sinh_khong_ton_tai');

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
$ext = $allowed[$imgType];

$baseDir = realpath(__DIR__ . '/..');
$uploadDir = $baseDir . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR . 'avatar';
if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0755, true); }

$name = 'hs_' . $hoc_sinh_id . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$dest = $uploadDir . DIRECTORY_SEPARATOR . $name;
if (!move_uploaded_file($file['tmp_name'], $dest)) { json_phan_hoi(false, null, 'khong_luu_duoc_file'); }

$url = '/upload/avatar/' . $name;
$pdo->prepare('UPDATE hoc_sinh SET anh_dai_dien_url=? WHERE id=?')->execute([$url, $hoc_sinh_id]);
json_phan_hoi(true, ['url' => $url]);

