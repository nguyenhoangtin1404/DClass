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
    if ($gia_diem <= 0 || $gia_diem > 1000000) return json_phan_hoi(false, null, 'gia_diem_khong_hop_le');
    if ($ton_kho < -1 || $ton_kho > 1000000) return json_phan_hoi(false, null, 'ton_kho_khong_hop_le');
    $stTrung = $pdo->prepare('SELECT id FROM qua_tang WHERE ten = ? COLLATE NOCASE LIMIT 1');
    $stTrung->execute([$ten]);
    if ($stTrung->fetchColumn()) return json_phan_hoi(false, null, 'ten_da_ton_tai');
    $anh_url = isset($b['anh_url']) ? trim((string)$b['anh_url']) : null;
    $st = $pdo->prepare('INSERT INTO qua_tang(ten, gia_diem, ton_kho, dang_hoat_dong, anh_url) VALUES(?,?,?,?,?)');
    $st->execute([$ten, $gia_diem, $ton_kho, 1, ($anh_url?:null)]);
    ghi_log($pdo, (int)$_SESSION['giao_vien_id'], 'them_qua', 'Thêm quà '.$ten.' (id '.$pdo->lastInsertId().')');
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
    if ($ten !== null) {
      if ($ten === '') return json_phan_hoi(false, null, 'thieu_ten');
      $stTrung = $pdo->prepare('SELECT id FROM qua_tang WHERE ten = ? COLLATE NOCASE AND id <> ? LIMIT 1');
      $stTrung->execute([$ten, $id]);
      if ($stTrung->fetchColumn()) return json_phan_hoi(false, null, 'ten_da_ton_tai');
      $set[]='ten=?'; $pr[]=$ten;
    }
    if ($gia_diem !== null) {
      if ($gia_diem <= 0 || $gia_diem > 1000000) return json_phan_hoi(false, null, 'gia_diem_khong_hop_le');
      $set[]='gia_diem=?'; $pr[]=$gia_diem;
    }
    if ($ton_kho !== null) {
      if ($ton_kho < -1 || $ton_kho > 1000000) return json_phan_hoi(false, null, 'ton_kho_khong_hop_le');
      $set[]='ton_kho=?'; $pr[]=$ton_kho;
    }
    if ($anh_url !== null) { $set[]='anh_url=?'; $pr[] = ($anh_url!=='' ? $anh_url : null); }
    if (!$set) return json_phan_hoi(false, null, 'khong_co_truong_cap_nhat');
    $pr[]=$id;
    $pdo->prepare('UPDATE qua_tang SET '.implode(',', $set).' WHERE id=?')->execute($pr);
    ghi_log($pdo, (int)$_SESSION['giao_vien_id'], 'sua_qua', 'Sửa quà id '.$id.($ten!==null?(' -> '.$ten):''));
    return json_phan_hoi(true);
  }
  if ($hanh_dong === 'bat_tat') {
    $id = (int)($b['id'] ?? 0);
    $trang_thai = (int)($b['dang_hoat_dong'] ?? 1);
    if ($id <= 0) return json_phan_hoi(false, null, 'thieu_id');
    $co = $pdo->prepare('SELECT 1 FROM qua_tang WHERE id=?');
    $co->execute([$id]);
    if (!$co->fetchColumn()) return json_phan_hoi(false, null, 'khong_tim_thay');
    $pdo->prepare('UPDATE qua_tang SET dang_hoat_dong=? WHERE id=?')->execute([$trang_thai?1:0, $id]);
    ghi_log($pdo, (int)$_SESSION['giao_vien_id'], 'bat_tat_qua', 'Bật/tắt quà id '.$id.' => '.($trang_thai?1:0));
    return json_phan_hoi(true);
  }
  if ($hanh_dong === 'xoa') {
    $id = (int)($b['id'] ?? 0);
    if ($id <= 0) return json_phan_hoi(false, null, 'thieu_id');
    $st = $pdo->prepare('SELECT anh_url FROM qua_tang WHERE id=?');
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) return json_phan_hoi(false, null, 'khong_tim_thay');
    $stUsed = $pdo->prepare('SELECT COUNT(1) FROM so_cai_diem WHERE qua_tang_id=?');
    $stUsed->execute([$id]);
    if ((int)$stUsed->fetchColumn() > 0) return json_phan_hoi(false, null, 'qua_da_su_dung');
    $pdo->prepare('DELETE FROM qua_tang WHERE id=?')->execute([$id]);
    if (!empty($row['anh_url']) && strpos($row['anh_url'], '/upload/qua/') === 0) {
      $baseDir = realpath(__DIR__ . '/..');
      $old = $baseDir . $row['anh_url'];
      if (is_file($old)) { @unlink($old); }
    }
    ghi_log($pdo, (int)$_SESSION['giao_vien_id'], 'xoa_qua', 'Xóa quà id '.$id);
    return json_phan_hoi(true);
  }
}

http_response_code(404);
json_phan_hoi(false, null, 'khong_tim_thay');

