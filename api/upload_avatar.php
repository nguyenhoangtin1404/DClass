<?php
declare(strict_types=1);
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/tro_giup.php';

yeu_cau_dang_nhap();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); json_phan_hoi(false, null, 'method_not_allowed'); }

$hoc_sinh_id = isset($_POST['hoc_sinh_id']) ? (int)$_POST['hoc_sinh_id'] : 0;
if ($hoc_sinh_id <= 0) json_phan_hoi(false, null, 'thieu_hoc_sinh_id');
if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) json_phan_hoi(false, null, 'thieu_file');

$file = $_FILES['file'];
$mime = mime_content_type($file['tmp_name']);
$allowed = [ 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp' ];
if (!isset($allowed[$mime])) json_phan_hoi(false, null, 'dinh_dang_khong_ho_tro');

$ext = $allowed[$mime];
$baseDir = realpath(__DIR__ . '/..');
$uploadDir = $baseDir . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR . 'avatar';
if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0777, true); }

$name = 'hs_' . $hoc_sinh_id . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$dest = $uploadDir . DIRECTORY_SEPARATOR . $name;
if (!move_uploaded_file($file['tmp_name'], $dest)) { json_phan_hoi(false, null, 'khong_luu_duoc_file'); }

$url = '/upload/avatar/' . $name;
$pdo->prepare('UPDATE hoc_sinh SET anh_dai_dien_url=? WHERE id=?')->execute([$url, $hoc_sinh_id]);
json_phan_hoi(true, ['url' => $url]);

