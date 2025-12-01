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
  $chi_cua_toi = isset($_GET['cua_toi']) ? (int)$_GET['cua_toi'] : 0;
  if ($chi_cua_toi) {
    $st = $pdo->prepare("SELECT l.id, l.ten, COALESCE(l.dang_hoat_dong,1) AS dang_hoat_dong,
                                (SELECT group_concat(gl2.giao_vien_id, ',') FROM giao_vien_lop gl2 WHERE gl2.lop_hoc_id = l.id) AS gv_ids
                         FROM lop_hoc l
                         WHERE EXISTS (SELECT 1 FROM giao_vien_lop gl WHERE gl.lop_hoc_id = l.id AND gl.giao_vien_id = ?)
                         ORDER BY l.dang_hoat_dong DESC, l.ten ASC");
    $st->execute([(int)$_SESSION['giao_vien_id']]);
  } else {
    $st = $pdo->query("SELECT l.id, l.ten, COALESCE(l.dang_hoat_dong,1) AS dang_hoat_dong,
                              (SELECT group_concat(gl.giao_vien_id, ',') FROM giao_vien_lop gl WHERE gl.lop_hoc_id = l.id) AS gv_ids
                       FROM lop_hoc l
                       ORDER BY l.dang_hoat_dong DESC, l.ten ASC");
  }
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
    $gv_ids = isset($b['giao_vien_ids']) && is_array($b['giao_vien_ids']) ? $b['giao_vien_ids'] : [];
    if ($ten === '') return json_phan_hoi(false, null, 'thieu_ten');
    $st = $pdo->prepare('INSERT INTO lop_hoc(ten, dang_hoat_dong) VALUES(?,1)');
    $st->execute([$ten]);
    $lopId = (int)$pdo->lastInsertId();
    // Gán giáo viên nếu có
    $ins = $pdo->prepare("INSERT INTO giao_vien_lop(giao_vien_id, lop_hoc_id) VALUES(?,?)");
    foreach ($gv_ids as $gid) { $gid = (int)$gid; if ($gid>0) { $ins->execute([$gid, $lopId]); } }
    ghi_log($pdo, (int)$_SESSION['giao_vien_id'], 'them_lop', 'Thêm lớp '.$ten.' (id '.$lopId.')');
    return json_phan_hoi(true, ['id' => $lopId]);
  }
  if ($hanh_dong === 'sua') {
    $id = (int)($b['id'] ?? 0);
    $ten = isset($b['ten']) ? trim((string)$b['ten']) : null;
    $gv_ids = isset($b['giao_vien_ids']) && is_array($b['giao_vien_ids']) ? $b['giao_vien_ids'] : null;
    if ($id <= 0) return json_phan_hoi(false, null, 'thieu_id');
    if ($ten === null && $gv_ids === null) return json_phan_hoi(false, null, 'khong_co_truong_cap_nhat');
    if ($ten !== null) { $pdo->prepare('UPDATE lop_hoc SET ten=? WHERE id=?')->execute([$ten, $id]); }
    if ($gv_ids !== null) {
      $pdo->prepare("DELETE FROM giao_vien_lop WHERE lop_hoc_id=?")->execute([$id]);
      $ins = $pdo->prepare("INSERT INTO giao_vien_lop(giao_vien_id, lop_hoc_id) VALUES(?,?)");
      foreach ($gv_ids as $gid) { $gid = (int)$gid; if ($gid>0) { $ins->execute([$gid, $id]); } }
    }
    ghi_log($pdo, (int)$_SESSION['giao_vien_id'], 'sua_lop', 'Sửa lớp id '.$id.($ten!==null?(' ten='.$ten):''));
    return json_phan_hoi(true);
  }
  if ($hanh_dong === 'bat_tat') {
    $id = (int)($b['id'] ?? 0);
    $trang_thai = (int)($b['dang_hoat_dong'] ?? 1);
    if ($id <= 0) return json_phan_hoi(false, null, 'thieu_id');
    $pdo->prepare('UPDATE lop_hoc SET dang_hoat_dong=? WHERE id=?')->execute([$trang_thai?1:0, $id]);
    ghi_log($pdo, (int)$_SESSION['giao_vien_id'], 'bat_tat_lop', 'Bật/tắt lớp id '.$id.' => '.($trang_thai?1:0));
    return json_phan_hoi(true);
  }
  if ($hanh_dong === 'xoa') {
    $id = (int)($b['id'] ?? 0);
    if ($id <= 0) return json_phan_hoi(false, null, 'thieu_id');
    $pdo->prepare('DELETE FROM lop_hoc WHERE id=?')->execute([$id]);
    ghi_log($pdo, (int)$_SESSION['giao_vien_id'], 'xoa_lop', 'Xóa lớp id '.$id);
    return json_phan_hoi(true);
  }
}

http_response_code(404);
json_phan_hoi(false, null, 'khong_tim_thay');
