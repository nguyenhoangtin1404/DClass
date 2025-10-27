<?php
declare(strict_types=1);
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/tro_giup.php';

yeu_cau_dang_nhap();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Đảm bảo cột ảnh tồn tại
try {
  $cols = $pdo->query("PRAGMA table_info(qua_tang)")->fetchAll();
  $co_anh = false; foreach ($cols as $c) { if (($c['name'] ?? '') === 'anh_url') { $co_anh = true; break; } }
  if (!$co_anh) { $pdo->exec("ALTER TABLE qua_tang ADD COLUMN anh_url TEXT"); }
} catch (Throwable $e) {}

if ($method === 'GET') {
  $st = $pdo->query("SELECT id, ten, gia_diem, ton_kho, dang_hoat_dong, anh_url FROM qua_tang ORDER BY dang_hoat_dong DESC, gia_diem ASC, ten ASC");
  json_phan_hoi(true, $st->fetchAll());
}

if ($method === 'POST') {
  $hanh_dong = $_GET['hanh_dong'] ?? '';
  $b = than_json();
  if ($hanh_dong === 'them') {
    $ten = trim((string)($b['ten'] ?? ''));
    $gia_diem = (int)($b['gia_diem'] ?? 0);
    $ton_kho = (int)($b['ton_kho'] ?? 0);
    if ($ten === '') return json_phan_hoi(false, null, 'thieu_ten');
    $anh_url = isset($b['anh_url']) ? trim((string)$b['anh_url']) : null;
    $st = $pdo->prepare('INSERT INTO qua_tang(ten, gia_diem, ton_kho, dang_hoat_dong, anh_url) VALUES(?,?,?,?,?)');
    $st->execute([$ten, $gia_diem, $ton_kho, 1, ($anh_url?:null)]);
    return json_phan_hoi(true, ['id' => (int)$pdo->lastInsertId()]);
  }
  if ($hanh_dong === 'sua') {
    $id = (int)($b['id'] ?? 0);
    $ten = isset($b['ten']) ? trim((string)$b['ten']) : null;
    $gia_diem = isset($b['gia_diem']) ? (int)$b['gia_diem'] : null;
    $ton_kho = isset($b['ton_kho']) ? (int)$b['ton_kho'] : null;
    $anh_url = array_key_exists('anh_url',$b) ? trim((string)$b['anh_url']) : null;
    if ($id <= 0) return json_phan_hoi(false, null, 'thieu_id');
    $set=[];$pr=[];
    if ($ten !== null) { $set[]='ten=?'; $pr[]=$ten; }
    if ($gia_diem !== null) { $set[]='gia_diem=?'; $pr[]=$gia_diem; }
    if ($ton_kho !== null) { $set[]='ton_kho=?'; $pr[]=$ton_kho; }
    if ($anh_url !== null) { $set[]='anh_url=?'; $pr[] = ($anh_url!=='' ? $anh_url : null); }
    if (!$set) return json_phan_hoi(false, null, 'khong_co_truong_cap_nhat');
    $pr[]=$id;
    $pdo->prepare('UPDATE qua_tang SET '.implode(',', $set).' WHERE id=?')->execute($pr);
    return json_phan_hoi(true);
  }
  if ($hanh_dong === 'bat_tat') {
    $id = (int)($b['id'] ?? 0);
    $trang_thai = (int)($b['dang_hoat_dong'] ?? 1);
    if ($id <= 0) return json_phan_hoi(false, null, 'thieu_id');
    $pdo->prepare('UPDATE qua_tang SET dang_hoat_dong=? WHERE id=?')->execute([$trang_thai?1:0, $id]);
    return json_phan_hoi(true);
  }
  if ($hanh_dong === 'xoa') {
    $id = (int)($b['id'] ?? 0);
    if ($id <= 0) return json_phan_hoi(false, null, 'thieu_id');
    $pdo->prepare('DELETE FROM qua_tang WHERE id=?')->execute([$id]);
    return json_phan_hoi(true);
  }
}

http_response_code(404);
json_phan_hoi(false, null, 'khong_tim_thay');

