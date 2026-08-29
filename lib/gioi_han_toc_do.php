<?php
declare(strict_types=1);

/**
 * Đếm số lần thử liên tiếp thất bại theo 1 khoá bất kỳ (vd "dn|<ip>|<ten_dang_nhap>" cho đăng
 * nhập, "dk|<ip>" cho đăng ký) - lưu bền trong bảng dem_thu_that_bai thay vì $_SESSION. Session
 * là theo trình duyệt/cookie: một client không giữ cookie giữa các lần thử (script tấn công mặc
 * định, curl không cookie-jar...) luôn được cấp "phiên mới" với bộ đếm về 0, vô hiệu hoàn toàn
 * mọi giới hạn dựa trên session.
 *
 * Cửa sổ tính "còn hiệu lực" của 1 đợt sai (giây) - cũng dùng làm thời lượng khoá khi vượt
 * ngưỡng. Gộp làm một hằng số cho đơn giản (ứng dụng ở quy mô nhỏ, không cần tách rate-window
 * và lock-duration thành 2 khái niệm khác nhau).
 */
const GIOI_HAN_CUA_SO_GIAY = 600; // 10 phút

/** @return array{so_lan:int, khoa_den:int} */
function doc_dem_that_bai(PDO $pdo, string $khoa): array
{
  try {
    $st = $pdo->prepare('SELECT so_lan, khoa_den, cap_nhat_luc FROM dem_thu_that_bai WHERE khoa = ?');
    $st->execute([$khoa]);
    $r = $st->fetch();
  } catch (Throwable $e) {
    return ['so_lan' => 0, 'khoa_den' => 0];
  }
  if (!$r) { return ['so_lan' => 0, 'khoa_den' => 0]; }
  // Quá lâu không có lần sai nào mới (dù đang khoá hay chưa) -> coi như chưa từng sai, dọn luôn
  // để bảng không phình vô hạn. cap_nhat_luc được ghi lại ở MỌI lần tăng (kể cả lần đặt khoá),
  // nên một khoá đã hết hạn tự nhiên trùng với cap_nhat_luc cũng đã cũ hơn cửa sổ này.
  $cap_nhat = strtotime((string)($r['cap_nhat_luc'] ?? '') . ' UTC') ?: 0;
  if ($cap_nhat > 0 && $cap_nhat <= time() - GIOI_HAN_CUA_SO_GIAY) {
    xoa_dem_that_bai($pdo, $khoa);
    return ['so_lan' => 0, 'khoa_den' => 0];
  }
  return ['so_lan' => (int)$r['so_lan'], 'khoa_den' => (int)$r['khoa_den']];
}

/**
 * Ghi nhận 1 lần thất bại cho $khoa - tăng đếm, tự khoá nếu vượt $nguong, tất cả trong 1 câu SQL
 * nguyên tử (atomic UPSERT) thay vì đọc-rồi-tính-rồi-ghi ở tầng PHP. Đọc-rồi-ghi có race thật:
 * N request thất bại đồng thời có thể tất cả cùng đọc so_lan=0, mỗi request tự tính 0+1=1 rồi ghi
 * đè - kết quả sau N lần sai thật vẫn chỉ là 1, không bao giờ chạm ngưỡng CAPTCHA/khoá dù tấn
 * công dồn dập. UPSERT với `so_lan = so_lan + 1` (tương đối, không phải giá trị tuyệt đối tính
 * trước) để SQLite tự tuần tự hoá các lần ghi cùng dòng.
 *
 * @return array{so_lan:int, khoa_den:int} trạng thái THẬT ngay sau khi ghi nhận.
 */
function ghi_nhan_that_bai(PDO $pdo, string $khoa, int $nguong): array
{
  try {
    $st = $pdo->prepare(
      "INSERT INTO dem_thu_that_bai(khoa, so_lan, khoa_den, cap_nhat_luc)
       VALUES(:khoa, 1, 0, datetime('now'))
       ON CONFLICT(khoa) DO UPDATE SET
         so_lan = CASE
           WHEN dem_thu_that_bai.cap_nhat_luc <= datetime('now', :cua_so)
             THEN 1
           ELSE dem_thu_that_bai.so_lan + 1
         END,
         khoa_den = CASE
           WHEN dem_thu_that_bai.cap_nhat_luc <= datetime('now', :cua_so)
             THEN 0
           WHEN dem_thu_that_bai.so_lan + 1 >= CAST(:nguong AS INTEGER)
             THEN CAST(strftime('%s', 'now') AS INTEGER) + CAST(:thoi_luong_khoa AS INTEGER)
           ELSE dem_thu_that_bai.khoa_den
         END,
         cap_nhat_luc = datetime('now')"
    );
    $st->execute([
      'khoa' => $khoa,
      'cua_so' => '-' . GIOI_HAN_CUA_SO_GIAY . ' seconds',
      'nguong' => $nguong,
      'thoi_luong_khoa' => GIOI_HAN_CUA_SO_GIAY,
    ]);
  } catch (Throwable $e) {
    // Best-effort: lỗi ghi đếm không nên chặn hẳn đăng nhập. Coi như 1 lần sai, chưa khoá.
    return ['so_lan' => 1, 'khoa_den' => 0];
  }
  // $khoa chứa tên đăng nhập CHƯA xác thực (vd "dn|<ip>|<ten_dang_nhap_bat_ky>") - kẻ tấn công có
  // thể gửi một tên ngẫu nhiên mỗi lần để buộc mỗi request tạo 1 hàng mới. doc_dem_that_bai() chỉ
  // dọn 1 hàng khi CHÍNH khoá đó được đọc lại - một khoá "dùng 1 lần rồi bỏ" như vậy không bao
  // giờ được đọc lại nên không bao giờ được dọn, làm bảng phình vô hạn. Dọn toàn cục ngẫu nhiên
  // (không phải mỗi lần, để không tốn 1 DELETE full-scan trên mọi request) để tự giới hạn kích
  // thước bảng theo thời gian bất kể khoá có bao giờ được đọc lại hay không.
  if (random_int(1, 20) === 1) {
    don_dep_dem_that_bai_qua_han($pdo);
  }
  // Đọc lại trên cùng kết nối - luôn thấy ít nhất lần ghi vừa rồi của chính request này.
  $st2 = $pdo->prepare('SELECT so_lan, khoa_den FROM dem_thu_that_bai WHERE khoa = ?');
  $st2->execute([$khoa]);
  $r = $st2->fetch();
  return $r
    ? ['so_lan' => (int)$r['so_lan'], 'khoa_den' => (int)$r['khoa_den']]
    : ['so_lan' => 1, 'khoa_den' => 0];
}

function xoa_dem_that_bai(PDO $pdo, string $khoa): void
{
  try { $pdo->prepare('DELETE FROM dem_thu_that_bai WHERE khoa = ?')->execute([$khoa]); }
  catch (Throwable $e) { /* ignore */ }
}

/** Dọn mọi hàng đã quá cửa sổ hiệu lực, bất kể khoá đó có bao giờ được đọc lại hay không. */
function don_dep_dem_that_bai_qua_han(PDO $pdo): void
{
  try {
    $pdo->prepare(
      "DELETE FROM dem_thu_that_bai WHERE cap_nhat_luc <= datetime('now', :cua_so)"
    )->execute(['cua_so' => '-' . GIOI_HAN_CUA_SO_GIAY . ' seconds']);
  } catch (Throwable $e) { /* ignore */ }
}
