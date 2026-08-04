<?php
declare(strict_types=1);

/**
 * Nghiệp vụ nhập CSV học sinh, tách riêng để test được mà không cần mô phỏng HTTP upload.
 */

/**
 * Upsert 1 học sinh theo 'ma' (mã học sinh, duy nhất TOÀN CSDL dùng chung, không tách theo
 * giáo viên). Vì vậy phải tự kiểm tra học sinh trùng mã đó (nếu có) có thuộc lớp của giáo viên
 * đang nhập hay không trước khi cho cập nhật - tránh 1 giáo viên ghi đè học sinh của giáo viên
 * khác chỉ bằng cách trùng mã trong file CSV.
 *
 * Trả về ['id'=>int, 'moi'=>bool] khi upsert thành công, hoặc null nếu bị từ chối vì học sinh
 * trùng mã đó thuộc lớp của giáo viên khác (không nằm trong $lop_gan).
 */
function hoc_sinh_upsert_theo_ma(
  PDO $pdo,
  array $lop_gan,
  string $ma,
  string $ho_ten,
  ?int $lop_hoc_id,
  ?string $anh,
  ?string $gioi_tinh,
  ?string $ngay_sinh,
  ?int $stt
): ?array {
  $stExist = $pdo->prepare("SELECT id, lop_hoc_id FROM hoc_sinh WHERE ma=?");
  $stExist->execute([$ma]);
  $existing = $stExist->fetch();
  if ($existing && (int)$existing['lop_hoc_id'] && !in_array((int)$existing['lop_hoc_id'], $lop_gan, true)) {
    return null;
  }
  if ($existing) {
    $st = $pdo->prepare("UPDATE hoc_sinh SET
                            ho_ten=?,
                            lop_hoc_id=COALESCE(?, lop_hoc_id),
                            anh_dai_dien_url=COALESCE(?, anh_dai_dien_url),
                            gioi_tinh=COALESCE(?, gioi_tinh),
                            ngay_sinh=COALESCE(?, ngay_sinh),
                            stt=COALESCE(?, stt)
                          WHERE id=?");
    $st->execute([$ho_ten, $lop_hoc_id, $anh ?: null, $gioi_tinh ?: null, $ngay_sinh ?: null, $stt, $existing['id']]);
    $id = (int)$existing['id'];
    $moi = false;
  } else {
    $st = $pdo->prepare("INSERT INTO hoc_sinh(ma, ho_ten, stt, lop_hoc_id, anh_dai_dien_url, gioi_tinh, ngay_sinh, dang_hoat_dong) VALUES(?,?,?,?,?,?,?,1)");
    $st->execute([$ma, $ho_ten, $stt, $lop_hoc_id, $anh ?: null, $gioi_tinh ?: null, $ngay_sinh ?: null]);
    $id = (int)$pdo->lastInsertId();
    $moi = true;
  }
  $pdo->prepare("INSERT OR IGNORE INTO vi_diem(hoc_sinh_id, so_du) VALUES(?,0)")->execute([$id]);
  return ['id' => $id, 'moi' => $moi];
}
