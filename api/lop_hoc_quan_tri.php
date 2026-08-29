<?php
declare(strict_types=1);
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/tro_giup.php';
/** @var \PDO $pdo Global PDO instance from config/db.php */

yeu_cau_dang_nhap();
$gv_id = (int)$_SESSION['giao_vien_id'];

function so_huu_lop(PDO $pdo, int $giao_vien_id, int $lop_hoc_id): bool {
  $st = $pdo->prepare('SELECT 1 FROM giao_vien_lop WHERE giao_vien_id=? AND lop_hoc_id=?');
  $st->execute([$giao_vien_id, $lop_hoc_id]);
  return (bool)$st->fetchColumn();
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
  // Luôn chỉ trả về lớp mà giáo viên hiện tại sở hữu (không còn khái niệm admin thấy hết).
  $st = $pdo->prepare("SELECT l.id, l.ten, COALESCE(l.dang_hoat_dong,1) AS dang_hoat_dong,
                              (SELECT group_concat(gl2.giao_vien_id, ',') FROM giao_vien_lop gl2 WHERE gl2.lop_hoc_id = l.id) AS gv_ids
                       FROM lop_hoc l
                       WHERE EXISTS (SELECT 1 FROM giao_vien_lop gl WHERE gl.lop_hoc_id = l.id AND gl.giao_vien_id = ?)
                       ORDER BY l.dang_hoat_dong DESC, l.ten ASC");
  $st->execute([$gv_id]);
  $rows = $st->fetchAll();
  foreach ($rows as &$r) {
    $ids = [];
    if (!empty($r['gv_ids'])) {
      foreach (explode(',', (string)$r['gv_ids']) as $p) {
        $v = (int)$p; if ($v) $ids[] = $v;
      }
    }
    $r['giao_vien_ids'] = $ids;
    unset($r['gv_ids']);
  }
  json_phan_hoi(true, $rows);
}

if ($method === 'POST') {
  $hanh_dong = $_GET['hanh_dong'] ?? '';
  $b = than_json();
  if ($hanh_dong === 'them') {
    $ten = trim((string)($b['ten'] ?? ''));
    if ($ten === '') return json_phan_hoi(false, null, 'thieu_ten');
    $st = $pdo->prepare('INSERT INTO lop_hoc(ten, dang_hoat_dong) VALUES(?,1)');
    $st->execute([$ten]);
    $lopId = (int)$pdo->lastInsertId();
    $pdo->prepare("INSERT INTO giao_vien_lop(giao_vien_id, lop_hoc_id) VALUES(?,?)")->execute([$gv_id, $lopId]);
    ghi_log($pdo, $gv_id, 'them_lop', 'Thêm lớp '.$ten.' (id '.$lopId.')');
    return json_phan_hoi(true, ['id' => $lopId]);
  }
  if ($hanh_dong === 'sua') {
    $id = (int)($b['id'] ?? 0);
    if ($id <= 0) return json_phan_hoi(false, null, 'thieu_id');
    if (!so_huu_lop($pdo, $gv_id, $id)) return json_phan_hoi(false, null, 'khong_du_quyen');
    $ten = isset($b['ten']) ? trim((string)$b['ten']) : null;
    if ($ten === null) return json_phan_hoi(false, null, 'khong_co_truong_cap_nhat');
    $pdo->prepare('UPDATE lop_hoc SET ten=? WHERE id=?')->execute([$ten, $id]);
    ghi_log($pdo, $gv_id, 'sua_lop', 'Sửa lớp id '.$id.' ten='.$ten);
    return json_phan_hoi(true);
  }
  if ($hanh_dong === 'bat_tat') {
    $id = (int)($b['id'] ?? 0);
    $trang_thai = (int)($b['dang_hoat_dong'] ?? 1);
    if ($id <= 0) return json_phan_hoi(false, null, 'thieu_id');
    if (!so_huu_lop($pdo, $gv_id, $id)) return json_phan_hoi(false, null, 'khong_du_quyen');
    $pdo->prepare('UPDATE lop_hoc SET dang_hoat_dong=? WHERE id=?')->execute([$trang_thai?1:0, $id]);
    ghi_log($pdo, $gv_id, 'bat_tat_lop', 'Bật/tắt lớp id '.$id.' => '.($trang_thai?1:0));
    return json_phan_hoi(true);
  }
  if ($hanh_dong === 'xoa') {
    $id = (int)($b['id'] ?? 0);
    if ($id <= 0) return json_phan_hoi(false, null, 'thieu_id');
    if (!so_huu_lop($pdo, $gv_id, $id)) return json_phan_hoi(false, null, 'khong_du_quyen');
    // hoc_sinh.lop_hoc_id tham chiếu lop_hoc(id) không có ON DELETE - xoá thẳng khi còn học sinh
    // (kể cả đã tắt) sẽ ném PDOException do vi phạm khoá ngoại (PRAGMA foreign_keys=ON), không
    // được bắt ở đâu khác -> lỗi 500 thay vì thông báo rõ ràng. Kiểm tra trước để trả lỗi sạch sẽ.
    $stCon = $pdo->prepare('SELECT COUNT(1) FROM hoc_sinh WHERE lop_hoc_id=?');
    $stCon->execute([$id]);
    if ((int)$stCon->fetchColumn() > 0) return json_phan_hoi(false, null, 'lop_con_hoc_sinh');
    $pdo->prepare('DELETE FROM lop_hoc WHERE id=?')->execute([$id]);
    ghi_log($pdo, $gv_id, 'xoa_lop', 'Xóa lớp id '.$id);
    return json_phan_hoi(true);
  }
}

http_response_code(404);
json_phan_hoi(false, null, 'khong_tim_thay');
