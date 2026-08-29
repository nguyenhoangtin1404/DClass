<?php
declare(strict_types=1);

/**
 * Đếm số lần thử liên tiếp thất bại theo 1 khoá bất kỳ (vd "dn|<ip>|<ten_dang_nhap>" cho đăng
 * nhập, "dk|<ip>" cho đăng ký) - lưu bền trong bảng dem_thu_that_bai thay vì $_SESSION. Session
 * là theo trình duyệt/cookie: một client không giữ cookie giữa các lần thử (script tấn công mặc
 * định, curl không cookie-jar...) luôn được cấp "phiên mới" với bộ đếm về 0, vô hiệu hoàn toàn
 * mọi giới hạn dựa trên session.
 */

/** @return array{so_lan:int, khoa_den:int} */
function doc_dem_that_bai(PDO $pdo, string $khoa): array
{
  try {
    $st = $pdo->prepare('SELECT so_lan, khoa_den FROM dem_thu_that_bai WHERE khoa = ?');
    $st->execute([$khoa]);
    $r = $st->fetch();
  } catch (Throwable $e) {
    return ['so_lan' => 0, 'khoa_den' => 0];
  }
  if (!$r) { return ['so_lan' => 0, 'khoa_den' => 0]; }
  $khoa_den = (int)$r['khoa_den'];
  // Khoá đã hết hạn -> coi như chưa từng sai (dọn luôn để bảng không phình vô hạn).
  if ($khoa_den > 0 && $khoa_den <= time()) {
    xoa_dem_that_bai($pdo, $khoa);
    return ['so_lan' => 0, 'khoa_den' => 0];
  }
  return ['so_lan' => (int)$r['so_lan'], 'khoa_den' => $khoa_den];
}

function ghi_dem_that_bai(PDO $pdo, string $khoa, int $so_lan, int $khoa_den): void
{
  try {
    $pdo->prepare(
      "INSERT INTO dem_thu_that_bai(khoa, so_lan, khoa_den, cap_nhat_luc)
       VALUES(?, ?, ?, datetime('now'))
       ON CONFLICT(khoa) DO UPDATE SET
         so_lan = excluded.so_lan,
         khoa_den = excluded.khoa_den,
         cap_nhat_luc = excluded.cap_nhat_luc"
    )->execute([$khoa, $so_lan, $khoa_den]);
  } catch (Throwable $e) { /* best-effort: lỗi ghi đếm không nên chặn hẳn đăng nhập */ }
}

function xoa_dem_that_bai(PDO $pdo, string $khoa): void
{
  try { $pdo->prepare('DELETE FROM dem_thu_that_bai WHERE khoa = ?')->execute([$khoa]); }
  catch (Throwable $e) { /* ignore */ }
}
