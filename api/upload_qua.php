<?php
declare(strict_types=1);
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/tro_giup.php';

yeu_cau_dang_nhap();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); json_phan_hoi(false, null, 'method_not_allowed'); }

$qua_tang_id = isset($_POST['qua_tang_id']) ? (int)$_POST['qua_tang_id'] : 0;
if ($qua_tang_id <= 0) json_phan_hoi(false, null, 'thieu_qua_tang_id');
if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) json_phan_hoi(false, null, 'thieu_file');

$file = $_FILES['file'];
$mime = mime_content_type($file['tmp_name']);
$allowed = [ 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp' ];
if (!isset($allowed[$mime])) json_phan_hoi(false, null, 'dinh_dang_khong_ho_tro');

$ext = $allowed[$mime];
$baseDir = realpath(__DIR__ . '/..');
$uploadDir = $baseDir . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR . 'qua';
if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0777, true); }

$name = 'qt_' . $qua_tang_id . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$dest = $uploadDir . DIRECTORY_SEPARATOR . $name;
if (!move_uploaded_file($file['tmp_name'], $dest)) { json_phan_hoi(false, null, 'khong_luu_duoc_file'); }

$url = '/upload/qua/' . $name;
// đảm bảo cột tồn tại
try { $pdo->exec("ALTER TABLE qua_tang ADD COLUMN anh_url TEXT"); } catch (Throwable $e) {}
$pdo->prepare('UPDATE qua_tang SET anh_url=? WHERE id=?')->execute([$url, $qua_tang_id]);
json_phan_hoi(true, ['url' => $url]);

