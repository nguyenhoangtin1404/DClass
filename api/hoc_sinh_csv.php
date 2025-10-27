<?php
declare(strict_types=1);
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/tro_giup.php';

yeu_cau_dang_nhap();

$hanh_dong = $_GET['hanh_dong'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $hanh_dong === 'xuat') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="hoc_sinh.csv"');
  $out = fopen('php://output', 'w');
  // BOM for Excel UTF-8
  echo "\xEF\xBB\xBF";
  fputcsv($out, ['id','ma','ho_ten','lop_hoc_id','ten_lop','so_du','anh_dai_dien_url','gioi_tinh','ngay_sinh']);
  $tu_khoa = trim($_GET['tu_khoa'] ?? '');
  $lop_hoc_id = isset($_GET['lop_hoc_id']) ? (int)$_GET['lop_hoc_id'] : 0;
  $sql = "SELECT s.id, s.ma, s.ho_ten, s.lop_hoc_id, l.ten AS ten_lop, IFNULL(v.so_du,0) AS so_du,
                 s.anh_dai_dien_url, s.gioi_tinh, s.ngay_sinh
          FROM hoc_sinh s
          LEFT JOIN lop_hoc l ON l.id = s.lop_hoc_id
          LEFT JOIN vi_diem v ON v.hoc_sinh_id = s.id
          WHERE s.dang_hoat_dong=1";
  $pr = [];
  if ($tu_khoa !== '') { $sql .= " AND (s.ho_ten LIKE ? OR s.ma LIKE ?)"; $like = like_mau($tu_khoa); $pr = [$like, $like]; }
  if ($lop_hoc_id) { $sql .= " AND s.lop_hoc_id = ?"; $pr[] = $lop_hoc_id; }
  $sql .= " ORDER BY s.ho_ten ASC";
  $st = $pdo->prepare($sql); $st->execute($pr);
  while ($row = $st->fetch()) { fputcsv($out, [
    $row['id'],$row['ma'],$row['ho_ten'],$row['lop_hoc_id'],$row['ten_lop'],$row['so_du'],
    $row['anh_dai_dien_url'],$row['gioi_tinh'],$row['ngay_sinh']
  ]); }
  fclose($out); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hanh_dong === 'nhap') {
  if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
    json_phan_hoi(false, null, 'thieu_file');
  }
  $tmp = $_FILES['file']['tmp_name'];
  $fh = fopen($tmp, 'r'); if (!$fh) json_phan_hoi(false, null, 'khong_mo_duoc_file');
  // Skip BOM
  $bom = fread($fh, 3);
  if ($bom !== "\xEF\xBB\xBF") { fseek($fh, 0); }
  $header = fgetcsv($fh);
  if (!$header) { fclose($fh); json_phan_hoi(false, null, 'file_rong'); }
  // Normalize header keys
  $map = [];
  foreach ($header as $i => $h) {
    $k = strtolower(trim((string)$h));
    $map[$k] = $i;
  }
  $hasHoTen = array_key_exists('ho_ten', $map);
  $hasMa = array_key_exists('ma', $map);
  $hasLop = array_key_exists('lop_hoc_id', $map);
  $hasAnh = array_key_exists('anh_dai_dien_url', $map) || array_key_exists('avatar', $map);
  $hasGioi = array_key_exists('gioi_tinh', $map);
  $hasNgay = array_key_exists('ngay_sinh', $map);
  if (!$hasHoTen) { fclose($fh); json_phan_hoi(false, null, 'thieu_cot_ho_ten'); }
  $them = 0; $cap_nhat = 0; $dong = 0;
  $pdo->beginTransaction();
  try {
    while (($row = fgetcsv($fh)) !== false) {
      $dong++;
      $ho_ten = trim((string)($row[$map['ho_ten']] ?? ''));
      $ma = $hasMa ? trim((string)($row[$map['ma']] ?? '')) : '';
      $lop_hoc_id = $hasLop ? (int)($row[$map['lop_hoc_id']] ?? 0) : null;
      $anh = $hasAnh ? trim((string)($row[$map['anh_dai_dien_url'] ?? ($map['avatar'] ?? null)] ?? '')) : '';
      $gioi = $hasGioi ? trim((string)($row[$map['gioi_tinh']] ?? '')) : '';
      $ngay = $hasNgay ? trim((string)($row[$map['ngay_sinh']] ?? '')) : '';
      if ($ho_ten === '' && $ma === '') { continue; }
      // Upsert theo 'ma' nếu có, ngược lại tạo mới theo ho_ten
      if ($ma !== '') {
        $st = $pdo->prepare("INSERT INTO hoc_sinh(ma, ho_ten, lop_hoc_id, anh_dai_dien_url, gioi_tinh, ngay_sinh, dang_hoat_dong) VALUES(?,?,?,?,?,?,1)
                              ON CONFLICT(ma) DO UPDATE SET
                                ho_ten=excluded.ho_ten,
                                lop_hoc_id=COALESCE(excluded.lop_hoc_id, lop_hoc_id),
                                anh_dai_dien_url=COALESCE(excluded.anh_dai_dien_url, anh_dai_dien_url),
                                gioi_tinh=COALESCE(excluded.gioi_tinh, gioi_tinh),
                                ngay_sinh=COALESCE(excluded.ngay_sinh, ngay_sinh)");
        $ok = $st->execute([$ma ?: null, $ho_ten, $lop_hoc_id, $anh ?: null, $gioi ?: null, $ngay ?: null]);
        // Xác định thêm hay cập nhật
        $st2 = $pdo->prepare("SELECT id FROM hoc_sinh WHERE ma=?"); $st2->execute([$ma]); $id = (int)$st2->fetchColumn();
        $pdo->prepare("INSERT OR IGNORE INTO vi_diem(hoc_sinh_id, so_du) VALUES(?,0)")->execute([$id]);
        // Không dễ phân biệt thêm/cập nhật ở SQLite khi upsert; bỏ qua thống kê chi tiết
      } else {
        $st = $pdo->prepare("INSERT INTO hoc_sinh(ma, ho_ten, lop_hoc_id, anh_dai_dien_url, gioi_tinh, ngay_sinh, dang_hoat_dong) VALUES(?,?,?,?,?,?,1)");
        $st->execute([null, $ho_ten, $lop_hoc_id, $anh ?: null, $gioi ?: null, $ngay ?: null]);
        $id = (int)$pdo->lastInsertId();
        $pdo->prepare("INSERT OR IGNORE INTO vi_diem(hoc_sinh_id, so_du) VALUES(?,0)")->execute([$id]);
        $them++;
      }
    }
    $pdo->commit();
  } catch (Throwable $e) {
    $pdo->rollBack(); fclose($fh); json_phan_hoi(false, null, 'loi_nhap_csv');
  }
  fclose($fh);
  json_phan_hoi(true, ['tong_dong'=>$dong, 'them_moi'=>$them, 'cap_nhat'=>$cap_nhat]);
}

http_response_code(404);
json_phan_hoi(false, null, 'khong_tim_thay');
