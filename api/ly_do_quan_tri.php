<?php
declare(strict_types=1);
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/tro_giup.php';
/** @var \PDO $pdo Global PDO instance from config/db.php */

yeu_cau_dang_nhap();
$gv_id = (int)$_SESSION['giao_vien_id'];

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
  $st = $pdo->prepare("SELECT id, tieu_de, bien_diem, dang_hoat_dong FROM ly_do WHERE nguoi_tao_id=? ORDER BY dang_hoat_dong DESC, bien_diem DESC, tieu_de ASC");
  $st->execute([$gv_id]);
  json_phan_hoi(true, $st->fetchAll());
}

if ($method === 'POST') {
  $hanh_dong = $_GET['hanh_dong'] ?? '';
  $b = than_json();
  if ($hanh_dong === 'them') {
    $tieu_de = trim((string)($b['tieu_de'] ?? ''));
    $bien_diem = (int)($b['bien_diem'] ?? 0);
    if ($tieu_de === '') return json_phan_hoi(false, null, 'thieu_tieu_de');
    $st = $pdo->prepare('INSERT INTO ly_do(tieu_de, bien_diem, dang_hoat_dong, nguoi_tao_id) VALUES(?,?,1,?)');
    $st->execute([$tieu_de, $bien_diem, $gv_id]);
    ghi_log($pdo, $gv_id, 'them_ly_do', 'Thêm lý do '.$tieu_de.' (id '.$pdo->lastInsertId().')');
    return json_phan_hoi(true, ['id' => (int)$pdo->lastInsertId()]);
  }
  if ($hanh_dong === 'sua') {
    $id = (int)($b['id'] ?? 0);
    if ($id <= 0) return json_phan_hoi(false, null, 'thieu_id');
    $co = $pdo->prepare('SELECT 1 FROM ly_do WHERE id=? AND nguoi_tao_id=?');
    $co->execute([$id, $gv_id]);
    if (!$co->fetchColumn()) return json_phan_hoi(false, null, 'khong_du_quyen');
    $tieu_de = isset($b['tieu_de']) ? trim((string)$b['tieu_de']) : null;
    $bien_diem = isset($b['bien_diem']) ? (int)$b['bien_diem'] : null;
    $set = [];$pr=[];
    if ($tieu_de !== null) { $set[]='tieu_de=?'; $pr[]=$tieu_de; }
    if ($bien_diem !== null) { $set[]='bien_diem=?'; $pr[]=$bien_diem; }
    if (!$set) return json_phan_hoi(false, null, 'khong_co_truong_cap_nhat');
    $pr[] = $id;
    $pdo->prepare('UPDATE ly_do SET '.implode(',', $set).' WHERE id=?')->execute($pr);
    ghi_log($pdo, $gv_id, 'sua_ly_do', 'Sửa lý do id '.$id.($tieu_de!==null?(' -> '.$tieu_de):''));
    return json_phan_hoi(true);
  }
  if ($hanh_dong === 'bat_tat') {
    $id = (int)($b['id'] ?? 0);
    $trang_thai = (int)($b['dang_hoat_dong'] ?? 1);
    if ($id <= 0) return json_phan_hoi(false, null, 'thieu_id');
    $co = $pdo->prepare('SELECT 1 FROM ly_do WHERE id=? AND nguoi_tao_id=?');
    $co->execute([$id, $gv_id]);
    if (!$co->fetchColumn()) return json_phan_hoi(false, null, 'khong_du_quyen');
    $pdo->prepare('UPDATE ly_do SET dang_hoat_dong=? WHERE id=?')->execute([$trang_thai?1:0, $id]);
    ghi_log($pdo, $gv_id, 'bat_tat_ly_do', 'Bật/tắt lý do id '.$id.' => '.($trang_thai?1:0));
    return json_phan_hoi(true);
  }
  if ($hanh_dong === 'xoa') {
    $id = (int)($b['id'] ?? 0);
    if ($id <= 0) return json_phan_hoi(false, null, 'thieu_id');
    $co = $pdo->prepare('SELECT 1 FROM ly_do WHERE id=? AND nguoi_tao_id=?');
    $co->execute([$id, $gv_id]);
    if (!$co->fetchColumn()) return json_phan_hoi(false, null, 'khong_du_quyen');
    $pdo->prepare('DELETE FROM ly_do WHERE id=?')->execute([$id]);
    ghi_log($pdo, $gv_id, 'xoa_ly_do', 'Xóa lý do id '.$id);
    return json_phan_hoi(true);
  }
}

http_response_code(404);
json_phan_hoi(false, null, 'khong_tim_thay');
