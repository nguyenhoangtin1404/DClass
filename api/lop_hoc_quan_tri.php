<?php
declare(strict_types=1);
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/tro_giup.php';

yeu_cau_dang_nhap();

// Đảm bảo có cột dang_hoat_dong (migrate nhẹ nếu thiếu)
try {
  $cols = $pdo->query("PRAGMA table_info(lop_hoc)")->fetchAll();
  $co_cot = false; foreach ($cols as $c) { if (($c['name'] ?? '') === 'dang_hoat_dong') { $co_cot = true; break; } }
  if (!$co_cot) { $pdo->exec("ALTER TABLE lop_hoc ADD COLUMN dang_hoat_dong INTEGER DEFAULT 1"); }
} catch (Throwable $e) {}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
  $st = $pdo->query("SELECT id, ten, COALESCE(dang_hoat_dong,1) AS dang_hoat_dong FROM lop_hoc ORDER BY dang_hoat_dong DESC, ten ASC");
  json_phan_hoi(true, $st->fetchAll());
}

if ($method === 'POST') {
  $hanh_dong = $_GET['hanh_dong'] ?? '';
  $b = than_json();
  if ($hanh_dong === 'them') {
    $ten = trim((string)($b['ten'] ?? ''));
    if ($ten === '') return json_phan_hoi(false, null, 'thieu_ten');
    $st = $pdo->prepare('INSERT INTO lop_hoc(ten, dang_hoat_dong) VALUES(?,1)');
    $st->execute([$ten]);
    return json_phan_hoi(true, ['id' => (int)$pdo->lastInsertId()]);
  }
  if ($hanh_dong === 'sua') {
    $id = (int)($b['id'] ?? 0);
    $ten = isset($b['ten']) ? trim((string)$b['ten']) : null;
    if ($id <= 0) return json_phan_hoi(false, null, 'thieu_id');
    if ($ten === null) return json_phan_hoi(false, null, 'khong_co_truong_cap_nhat');
    $pdo->prepare('UPDATE lop_hoc SET ten=? WHERE id=?')->execute([$ten, $id]);
    return json_phan_hoi(true);
  }
  if ($hanh_dong === 'bat_tat') {
    $id = (int)($b['id'] ?? 0);
    $trang_thai = (int)($b['dang_hoat_dong'] ?? 1);
    if ($id <= 0) return json_phan_hoi(false, null, 'thieu_id');
    $pdo->prepare('UPDATE lop_hoc SET dang_hoat_dong=? WHERE id=?')->execute([$trang_thai?1:0, $id]);
    return json_phan_hoi(true);
  }
  if ($hanh_dong === 'xoa') {
    $id = (int)($b['id'] ?? 0);
    if ($id <= 0) return json_phan_hoi(false, null, 'thieu_id');
    $pdo->prepare('DELETE FROM lop_hoc WHERE id=?')->execute([$id]);
    return json_phan_hoi(true);
  }
}

http_response_code(404);
json_phan_hoi(false, null, 'khong_tim_thay');

