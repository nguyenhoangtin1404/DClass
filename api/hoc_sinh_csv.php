<?php
declare(strict_types=1);
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/tro_giup.php';
/** @var \PDO $pdo Global PDO instance from config/db.php */

require __DIR__ . '/../lib/diem_nghiep_vu.php';
require __DIR__ . '/../lib/hoc_sinh_nghiep_vu.php';
yeu_cau_dang_nhap();
$gv_id = (int)$_SESSION['giao_vien_id'];
$lop_gan = lop_duoc_gan($pdo, $gv_id);

$hanh_dong = $_GET['hanh_dong'] ?? '';
$detectDelimiter = function(string $line) {
  $candidates = [',',';',"\t"];
  $bestDelim = ',';
  $bestCount = 1;
  foreach ($candidates as $delim) {
    $parts = str_getcsv($line, $delim);
    if (count($parts) > $bestCount) { $bestCount = count($parts); $bestDelim = $delim; }
  }
  return $bestDelim;
};
$chuan_key = function ($s) {
  $k = strtolower(trim((string)$s));
  // Thử bỏ dấu bằng iconv, nếu không có iconv thì tự thay thế ký tự tiếng Việt
  $c = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $k);
  if ($c === false) {
    $tieng_viet = [
      'à'=>'a','á'=>'a','ả'=>'a','ã'=>'a','ạ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ẳ'=>'a','ẵ'=>'a','ặ'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ẩ'=>'a','ẫ'=>'a','ậ'=>'a',
      'đ'=>'d',
      'è'=>'e','é'=>'e','ẻ'=>'e','ẽ'=>'e','ẹ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ể'=>'e','ễ'=>'e','ệ'=>'e',
      'ì'=>'i','í'=>'i','ỉ'=>'i','ĩ'=>'i','ị'=>'i',
      'ò'=>'o','ó'=>'o','ỏ'=>'o','õ'=>'o','ọ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ổ'=>'o','ỗ'=>'o','ộ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ở'=>'o','ỡ'=>'o','ợ'=>'o',
      'ù'=>'u','ú'=>'u','ủ'=>'u','ũ'=>'u','ụ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ử'=>'u','ữ'=>'u','ự'=>'u',
      'ỳ'=>'y','ý'=>'y','ỷ'=>'y','ỹ'=>'y','ỵ'=>'y'
    ];
    $tieng_viet += array_change_key_case($tieng_viet, CASE_UPPER);
    $k = strtr($k, $tieng_viet);
  } else {
    $k = $c;
  }
  $k = preg_replace('/[^a-z0-9]+/', '_', $k);
  return trim($k, '_');
};
$find_idx = function(array $map, array $aliases) {
  foreach ($aliases as $a) {
    if (array_key_exists($a, $map)) return $map[$a];
  }
  return null;
};
$chuan_ngay = function ($s) {
  $s = trim((string)$s);
  if ($s === '') return '';
  if (preg_match('/^(\\d{1,2})[\\/](\\d{1,2})[\\/](\\d{4})$/', $s, $m)) {
    return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
  }
  return $s;
};
$chuan_gioi = function ($s) use ($chuan_key) {
  $v = trim((string)$s);
  if ($v === '') return '';
  // Bỏ dấu và chuẩn hóa
  $ascii = strtoupper($chuan_key($v));
  // chuan_key thay dấu bằng underscore, nên khôi phục về chữ để so map
  $ascii = str_replace('_', '', $ascii);
  if (in_array($ascii, ['0','NAM','MALE','M'], true)) return 'NAM';
  if (in_array($ascii, ['1','NU','NỮ','FEMALE','F'], true)) return 'NU';
  if (in_array($ascii, ['KHAC','X','OTHER'], true)) return 'KHAC';
  return $v;
};
// Chống "CSV/Formula Injection": Excel/Sheets/LibreOffice diễn giải một ô bắt đầu bằng =, +, -,
// @ (hoặc tab/CR) như công thức khi mở file - vô hại với chính hệ thống này (chỉ ghi/đọc text
// thô) nhưng nguy hiểm cho người mở file .csv xuất ra bằng phần mềm bảng tính. Tiền tố bằng dấu
// nháy đơn buộc phần mềm đó hiển thị nguyên văn thay vì tính công thức - không đổi dữ liệu trong
// CSDL, chỉ áp dụng lúc xuất.
//
// Luôn thêm ĐÚNG 1 dấu nháy (kể cả khi giá trị gốc trong CSDL đã tự bắt đầu bằng nháy+ký tự công
// thức, vd "'=HS01") thay vì chỉ kiểm tra ký tự đầu tiên - nếu không, giá trị gốc kiểu đó sẽ đi
// qua export mà không bị đổi, rồi bo_tien_to_an_toan_csv() lúc nhập lại sẽ tưởng nhầm dấu nháy đó
// là do chính nó thêm vào và xoá mất, biến "'=HS01" thành "=HS01" - một mã học sinh khác hẳn
// (nhập lại theo mã sẽ tạo bản ghi trùng/đè nhầm học sinh khác). Đếm số dấu nháy dẫn đầu để luôn
// thêm/bớt đúng 1 dấu, khớp nhau ở cả hai chiều bất kể giá trị gốc có bao nhiêu dấu nháy.
$can_an_toan_csv = function (string $s): bool {
  $sauKhiBoNhay = ltrim($s, "'");
  return $sauKhiBoNhay !== '' && strpbrk($sauKhiBoNhay[0], "=+-@\t\r") !== false;
};
$an_toan_csv = function ($v) use ($can_an_toan_csv) {
  $s = (string)($v ?? '');
  return $can_an_toan_csv($s) ? ("'" . $s) : $s;
};
// Ngược lại của $an_toan_csv, dùng khi ĐỌC một file CSV để nhập: nếu chính hệ thống này đã xuất
// ra 1 dấu nháy đơn ở đầu ô để chặn formula injection, bỏ ĐÚNG 1 dấu đó đi khi nhập lại - nếu
// không, xuất rồi nhập lại chính file đó sẽ tạo học sinh trùng (mã bị đổi thành "'=HS01") hoặc
// làm bẩn tên học sinh vĩnh viễn bằng dấu nháy thừa.
$bo_tien_to_an_toan_csv = function ($v) use ($can_an_toan_csv) {
  $s = (string)($v ?? '');
  if ($s !== '' && $s[0] === "'" && $can_an_toan_csv(substr($s, 1))) {
    return substr($s, 1);
  }
  return $s;
};
$chuan_lop_id = function ($v) {
  $raw = trim((string)$v);
  if ($raw === '' || $raw === '0') return null;
  if (!ctype_digit($raw)) return null;
  $id = (int)$raw;
  return $id > 0 ? $id : null;
};

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
  if (!$lop_gan) { fclose($out); exit; }
  $place = implode(',', array_fill(0, count($lop_gan), '?'));
  $sql .= " AND s.lop_hoc_id IN ($place)";
  $pr = array_merge($pr, $lop_gan);
  $sql .= " ORDER BY s.ho_ten ASC";
  $st = $pdo->prepare($sql); $st->execute($pr);
  while ($row = $st->fetch()) { fputcsv($out, [
    $row['id'],$an_toan_csv($row['ma']),$an_toan_csv($row['ho_ten']),$row['lop_hoc_id'],
    $an_toan_csv($row['ten_lop']),$row['so_du'],$an_toan_csv($row['anh_dai_dien_url']),
    $an_toan_csv($row['gioi_tinh']),$an_toan_csv($row['ngay_sinh'])
  ]); }
  fclose($out); exit;
}

// Xem trước CSV (không ghi DB)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hanh_dong === 'xem_truoc') {
  if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
    json_phan_hoi(false, null, 'thieu_file');
  }
  $tmp = $_FILES['file']['tmp_name'];
  $fh = fopen($tmp, 'r'); if (!$fh) json_phan_hoi(false, null, 'khong_mo_duoc_file');
  // Skip BOM
  $bom = fread($fh, 3);
  if ($bom !== "\xEF\xBB\xBF") { fseek($fh, 0); }
  $firstLine = fgets($fh);
  if ($firstLine === false) { fclose($fh); json_phan_hoi(false, null, 'file_rong'); }
  $delimiter = $detectDelimiter($firstLine);
  $header = str_getcsv($firstLine, $delimiter);
  if (!$header || count($header) === 0) { fclose($fh); json_phan_hoi(false, null, 'file_rong'); }
  $map = [];
  foreach ($header as $i => $h) {
    $k = $chuan_key($h);
    if ($k === '') continue;
    $k2 = preg_replace('/[^a-z0-9]/', '', $k);
    foreach ([$k, $k2] as $kk) {
      if ($kk !== '' && !array_key_exists($kk, $map)) { $map[$kk] = $i; }
    }
  }
  $idxHoTen = $find_idx($map, ['ho_ten', 'ho_va_ten', 'hovaten', 'ho', 'ten']);
  $idxMa = $find_idx($map, ['ma', 'ma_hoc_sinh', 'mahocsinh', 'ma_hs', 'maso']);
  $idxLop = $find_idx($map, ['lop_hoc_id', 'lophocid', 'lop', 'lop_id']);
  $idxAnh = $find_idx($map, ['anh_dai_dien_url', 'anhdaidienurl', 'anh_dai_dien', 'anhdaidien', 'avatar', 'anh']);
  $idxGioi = $find_idx($map, ['gioi_tinh', 'gioitinh', 'gender']);
  $idxNgay = $find_idx($map, ['ngay_sinh', 'ngaysinh', 'ngay_thang_nam_sinh', 'ngaythangnamsinh', 'ngay_thang_sinh', 'ngaysinhnat', 'ngay_sinh_nhat']);
  $idxStt = $find_idx($map, ['stt']);
  if ($idxHoTen === null && isset($map['stt']) && $map['stt'] === 0 && count($header) >= 2) {
    $idxHoTen = 1;
  }
  // Fallback thứ tự cột phổ biến file 4C: STT, Họ và tên, Mã học sinh, Ngày tháng năm sinh, Giới tính, (Thôn/Khu Phố), lop_hoc_id
  if ($idxHoTen === null && count($header) >= 2) { $idxHoTen = 1; }
  if ($idxMa === null && count($header) >= 3) { $idxMa = 2; }
  if ($idxNgay === null && count($header) >= 4) { $idxNgay = 3; }
  if ($idxGioi === null && count($header) >= 5) { $idxGioi = 4; }
  if ($idxHoTen === null) { fclose($fh); json_phan_hoi(false, null, 'thieu_cot_ho_ten'); }
  $dong = 0; $mau = [];
  while (($row = fgetcsv($fh, 0, $delimiter)) !== false) {
    $dong++;
    $ho_ten = $bo_tien_to_an_toan_csv(trim((string)($row[$idxHoTen] ?? '')));
    $ma = $idxMa !== null ? $bo_tien_to_an_toan_csv(trim((string)($row[$idxMa] ?? ''))) : '';
    $lop_hoc_id = $idxLop !== null ? $chuan_lop_id($row[$idxLop] ?? '') : null;
    $gioi = $idxGioi !== null ? $chuan_gioi((string)($row[$idxGioi] ?? '')) : '';
    $ngay = $idxNgay !== null ? $chuan_ngay((string)($row[$idxNgay] ?? '')) : '';
    $stt = null;
    if ($idxStt !== null) {
      $raw = trim((string)($row[$idxStt] ?? ''));
      if ($raw !== '' && ctype_digit($raw)) { $stt = (int)$raw; }
    }
    if ($ho_ten === '' && $ma === '') { continue; }
    if (count($mau) < 5) {
      $mau[] = [
        'ho_ten' => $ho_ten,
        'ma' => $ma,
        'lop_hoc_id' => $lop_hoc_id,
        'gioi_tinh' => $gioi,
        'ngay_sinh' => $ngay,
        'stt' => $stt,
      ];
    }
  }
  fclose($fh);
  json_phan_hoi(true, ['tong_dong'=>$dong, 'mau'=>$mau]);
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
  // Đọc dòng đầu và tự động nhận diện delimiter (comma/semicolon/tab) để tránh lỗi khi file tách cột khác dấu phẩy
  $firstLine = fgets($fh);
  if ($firstLine === false) { fclose($fh); json_phan_hoi(false, null, 'file_rong'); }
  $delimiter = $detectDelimiter($firstLine);
  $header = str_getcsv($firstLine, $delimiter);
  if (!$header || count($header) === 0) { fclose($fh); json_phan_hoi(false, null, 'file_rong'); }
  // Chuẩn hóa tên cột để nhận dạng file 4C (Họ và tên, Mã học sinh, Ngày tháng năm sinh, Giới tính, Thôn/Khu Phố...) và file CSV xuất từ hệ thống
  $map = [];
  foreach ($header as $i => $h) {
    $k = $chuan_key($h);
    if ($k === '') continue;
    $k2 = preg_replace('/[^a-z0-9]/', '', $k);
    foreach ([$k, $k2] as $kk) {
      if ($kk !== '' && !array_key_exists($kk, $map)) { $map[$kk] = $i; }
    }
  }
  $idxHoTen = $find_idx($map, ['ho_ten', 'ho_va_ten', 'hovaten', 'ho', 'ten']);
  $idxMa = $find_idx($map, ['ma', 'ma_hoc_sinh', 'mahocsinh', 'ma_hs', 'maso']);
  $idxLop = $find_idx($map, ['lop_hoc_id', 'lophocid', 'lop', 'lop_id']);
  $idxAnh = $find_idx($map, ['anh_dai_dien_url', 'anhdaidienurl', 'anh_dai_dien', 'anhdaidien', 'avatar', 'anh']);
  $idxGioi = $find_idx($map, ['gioi_tinh', 'gioitinh', 'gender']);
  $idxNgay = $find_idx($map, ['ngay_sinh', 'ngaysinh', 'ngay_thang_nam_sinh', 'ngaythangnamsinh', 'ngay_thang_sinh', 'ngaysinhnat', 'ngay_sinh_nhat']);
  $idxStt = $find_idx($map, ['stt']);
  // Fallback: nếu có cột STT ở vị trí 0 và chưa nhận ra họ tên, giả định cột 2 là họ tên (phổ biến với file 4C)
  if ($idxHoTen === null && isset($map['stt']) && $map['stt'] === 0 && count($header) >= 2) {
    $idxHoTen = 1;
  }
  // Fallback thứ tự cột phổ biến file 4C
  if ($idxHoTen === null && count($header) >= 2) { $idxHoTen = 1; }
  if ($idxMa === null && count($header) >= 3) { $idxMa = 2; }
  if ($idxNgay === null && count($header) >= 4) { $idxNgay = 3; }
  if ($idxGioi === null && count($header) >= 5) { $idxGioi = 4; }
  if ($idxHoTen === null) { fclose($fh); json_phan_hoi(false, null, 'thieu_cot_ho_ten'); }
  $them = 0; $cap_nhat = 0; $dong = 0;
  $pdo->beginTransaction();
  try {
    while (($row = fgetcsv($fh, 0, $delimiter)) !== false) {
      $dong++;
      $ho_ten = $bo_tien_to_an_toan_csv(trim((string)($row[$idxHoTen] ?? '')));
      $ma = $idxMa !== null ? $bo_tien_to_an_toan_csv(trim((string)($row[$idxMa] ?? ''))) : '';
      $lop_hoc_id = $idxLop !== null ? $chuan_lop_id($row[$idxLop] ?? '') : null;
      $anh = $idxAnh !== null ? $bo_tien_to_an_toan_csv(trim((string)($row[$idxAnh] ?? ''))) : '';
      $gioi = $idxGioi !== null ? $chuan_gioi((string)($row[$idxGioi] ?? '')) : '';
      $ngay = $idxNgay !== null ? $chuan_ngay((string)($row[$idxNgay] ?? '')) : '';
      $stt = null;
      if ($idxStt !== null) {
        $rawStt = trim((string)($row[$idxStt] ?? ''));
        if ($rawStt !== '' && ctype_digit($rawStt)) { $stt = (int)$rawStt; }
      }
      if ($lop_hoc_id !== null && !in_array($lop_hoc_id, $lop_gan, true)) { continue; }
      if ($ho_ten === '' && $ma === '') { continue; }
      // Upsert theo 'ma' nếu có, ngược lại tạo mới theo ho_ten. 'ma' là duy nhất toàn CSDL (dùng
      // chung cho mọi giáo viên) - hoc_sinh_upsert_theo_ma() tự kiểm tra học sinh trùng mã đó có
      // thuộc lớp của mình không trước khi cập nhật, tránh 1 giáo viên ghi đè lên học sinh của
      // giáo viên khác chỉ bằng cách trùng mã trong file CSV.
      if ($ma !== '') {
        $kq = hoc_sinh_upsert_theo_ma($pdo, $lop_gan, $ma, $ho_ten, $lop_hoc_id, $anh, $gioi, $ngay, $stt);
        if ($kq === null) { continue; }
        if ($kq['moi']) { $them++; } else { $cap_nhat++; }
      } else {
        $st = $pdo->prepare("INSERT INTO hoc_sinh(ma, ho_ten, stt, lop_hoc_id, anh_dai_dien_url, gioi_tinh, ngay_sinh, dang_hoat_dong) VALUES(?,?,?,?,?,?,?,1)");
        $st->execute([null, $ho_ten, $stt, $lop_hoc_id, $anh ?: null, $gioi ?: null, $ngay ?: null]);
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
