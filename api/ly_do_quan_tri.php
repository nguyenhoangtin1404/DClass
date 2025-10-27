<?php
declare(strict_types=1);
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/tro_giup.php';

yeu_cau_dang_nhap();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
  $st = $pdo->query("SELECT id, tieu_de, bien_diem, dang_hoat_dong FROM ly_do ORDER BY dang_hoat_dong DESC, bien_diem DESC, tieu_de ASC");
  json_phan_hoi(true, $st->fetchAll());
}

if ($method === 'POST') {
  $hanh_dong = $_GET['hanh_dong'] ?? '';
  $b = than_json();
  if ($hanh_dong === 'them') {
    $tieu_de = trim((string)($b['tieu_de'] ?? ''));
    $bien_diem = (int)($b['bien_diem'] ?? 0);
    if ($tieu_de === '') return json_phan_hoi(false, null, 'thieu_tieu_de');
    $st = $pdo->prepare('INSERT INTO ly_do(tieu_de, bien_diem, dang_hoat_dong) VALUES(?,?,1)');
    $st->execute([$tieu_de, $bien_diem]);
    return json_phan_hoi(true, ['id' => (int)$pdo->lastInsertId()]);
  }
  if ($hanh_dong === 'sua') {
    $id = (int)($b['id'] ?? 0);
    $tieu_de = isset($b['tieu_de']) ? trim((string)$b['tieu_de']) : null;
    $bien_diem = isset($b['bien_diem']) ? (int)$b['bien_diem'] : null;
    if ($id <= 0) return json_phan_hoi(false, null, 'thieu_id');
    $set = [];$pr=[];
    if ($tieu_de !== null) { $set[]='tieu_de=?'; $pr[]=$tieu_de; }
    if ($bien_diem !== null) { $set[]='bien_diem=?'; $pr[]=$bien_diem; }
    if (!$set) return json_phan_hoi(false, null, 'khong_co_truong_cap_nhat');
    $pr[] = $id;
    $pdo->prepare('UPDATE ly_do SET '.implode(',', $set).' WHERE id=?')->execute($pr);
    return json_phan_hoi(true);
  }
  if ($hanh_dong === 'bat_tat') {
    $id = (int)($b['id'] ?? 0);
    $trang_thai = (int)($b['dang_hoat_dong'] ?? 1);
    if ($id <= 0) return json_phan_hoi(false, null, 'thieu_id');
    $pdo->prepare('UPDATE ly_do SET dang_hoat_dong=? WHERE id=?')->execute([$trang_thai?1:0, $id]);
    return json_phan_hoi(true);
  }
  if ($hanh_dong === 'xoa') {
    $id = (int)($b['id'] ?? 0);
    if ($id <= 0) return json_phan_hoi(false, null, 'thieu_id');
    $pdo->prepare('DELETE FROM ly_do WHERE id=?')->execute([$id]);
    return json_phan_hoi(true);
  }
}

http_response_code(404);
json_phan_hoi(false, null, 'khong_tim_thay');

