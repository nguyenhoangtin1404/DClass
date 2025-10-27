<?php
declare(strict_types=1);
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/tro_giup.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$hanh_dong = $_GET['hanh_dong'] ?? '';

if ($method === 'GET') {
  yeu_cau_dang_nhap();
  $tu_khoa = trim($_GET['tu_khoa'] ?? '');
  $lop_hoc_id = $_GET['lop_hoc_id'] ?? '';
  $tat_ca = isset($_GET['tat_ca']) ? (int)$_GET['tat_ca'] : 0;
  $sql = "SELECT s.id, s.ma, s.ho_ten, s.lop_hoc_id,
                 s.anh_dai_dien_url, s.gioi_tinh, s.ngay_sinh,
                 s.dang_hoat_dong,
                 l.ten AS ten_lop, IFNULL(v.so_du,0) AS so_du
          FROM hoc_sinh s
          LEFT JOIN lop_hoc l ON l.id = s.lop_hoc_id
          LEFT JOIN vi_diem v ON v.hoc_sinh_id = s.id
          WHERE 1=1";
  $pr = [];
  if (!$tat_ca) { $sql .= " AND s.dang_hoat_dong=1"; }
  if ($tu_khoa !== '') { $sql .= " AND (s.ho_ten LIKE ? OR s.ma LIKE ?)"; $like = like_mau($tu_khoa); $pr = [ $like, $like ]; }
  if ($lop_hoc_id !== '') { $sql .= " AND s.lop_hoc_id = ?"; $pr[] = (int)$lop_hoc_id; }
  $sql .= " ORDER BY s.ho_ten ASC LIMIT 200";
  $st = $pdo->prepare($sql); $st->execute($pr);
  json_phan_hoi(true, $st->fetchAll());
}

if ($method === 'POST' && $hanh_dong === 'sua') {
  yeu_cau_dang_nhap();
  $b = than_json();
  $id = (int)($b['id'] ?? 0);
  if ($id <= 0) json_phan_hoi(false, null, 'thieu_id');
  $set = [];$pr=[];
  if (array_key_exists('ma',$b)) { $set[]='ma=?'; $pr[] = (trim((string)$b['ma']) ?: null); }
  if (array_key_exists('ho_ten',$b)) { $ht=trim((string)$b['ho_ten']); if ($ht==='') json_phan_hoi(false, null, 'thieu_ho_ten'); $set[]='ho_ten=?'; $pr[] = $ht; }
  if (array_key_exists('lop_hoc_id',$b)) { $lv = $b['lop_hoc_id']; $set[]='lop_hoc_id=?'; $pr[] = ($lv===null || $lv==='' ? null : (int)$lv); }
  if (array_key_exists('anh_dai_dien_url',$b)) { $set[]='anh_dai_dien_url=?'; $pr[] = (trim((string)$b['anh_dai_dien_url']) ?: null); }
  if (array_key_exists('gioi_tinh',$b)) { $set[]='gioi_tinh=?'; $pr[] = (trim((string)$b['gioi_tinh']) ?: null); }
  if (array_key_exists('ngay_sinh',$b)) { $set[]='ngay_sinh=?'; $pr[] = (trim((string)$b['ngay_sinh']) ?: null); }
  if (array_key_exists('dang_hoat_dong',$b)) { $set[]='dang_hoat_dong=?'; $pr[] = ((int)$b['dang_hoat_dong']?1:0); }
  if (!$set) json_phan_hoi(false, null, 'khong_co_truong_cap_nhat');
  $pr[] = $id;
  try { $pdo->prepare('UPDATE hoc_sinh SET '.implode(',', $set).' WHERE id=?')->execute($pr); json_phan_hoi(true); }
  catch (Throwable $e) { json_phan_hoi(false, null, 'loi_cap_nhat'); }
}

if ($method === 'POST' && $hanh_dong === 'bat_tat') {
  yeu_cau_dang_nhap();
  $b = than_json();
  $id = (int)($b['id'] ?? 0);
  $trang_thai = (int)($b['dang_hoat_dong'] ?? 1) ? 1 : 0;
  if ($id <= 0) json_phan_hoi(false, null, 'thieu_id');
  $pdo->prepare('UPDATE hoc_sinh SET dang_hoat_dong=? WHERE id=?')->execute([$trang_thai, $id]);
  json_phan_hoi(true);
}

if ($method === 'POST') {
  yeu_cau_dang_nhap();
  $b = than_json();
  $ho_ten = trim($b['ho_ten'] ?? '');
  if ($ho_ten === '') json_phan_hoi(false, null, 'thieu_ho_ten');
  $ma = trim($b['ma'] ?? '');
  $lop = isset($b['lop_hoc_id']) ? (int)$b['lop_hoc_id'] : null;
  $anh = trim($b['anh_dai_dien_url'] ?? '');
  $gioi_tinh = trim($b['gioi_tinh'] ?? '');
  $ngay_sinh = trim($b['ngay_sinh'] ?? '');
  $st = $pdo->prepare('INSERT INTO hoc_sinh(ma, ho_ten, lop_hoc_id, anh_dai_dien_url, gioi_tinh, ngay_sinh) VALUES(?,?,?,?,?,?)');
  $st->execute([$ma ?: null, $ho_ten, $lop, $anh ?: null, $gioi_tinh ?: null, $ngay_sinh ?: null]);
  $id = (int)$pdo->lastInsertId();
  $pdo->prepare('INSERT OR IGNORE INTO vi_diem(hoc_sinh_id, so_du) VALUES(?,0)')->execute([$id]);
  json_phan_hoi(true, ['id'=>$id]);
}

http_response_code(404);
json_phan_hoi(false, null, 'khong_tim_thay');
