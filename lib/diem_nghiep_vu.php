<?php
declare(strict_types=1);

/**
 * Các hàm nghiệp vụ điểm để dùng chung cho API và kiểm thử.
 */
function lop_duoc_gan(PDO $pdo, int $giao_vien_id): array
{
  $st = $pdo->prepare('SELECT lop_hoc_id FROM giao_vien_lop WHERE giao_vien_id = ?');
  $st->execute([$giao_vien_id]);
  return array_map(fn($r) => (int)$r['lop_hoc_id'], $st->fetchAll());
}

function kiem_tra_quyen_lop(PDO $pdo, int $giao_vien_id, int $hoc_sinh_id): void
{
  $lop_giao_vien = lop_duoc_gan($pdo, $giao_vien_id);
  // Chưa sở hữu lớp nào -> không có quyền trên bất kỳ học sinh nào (không phải cho qua như
  // trước đây - mỗi giáo viên tự đăng ký, không có ai đứng trên để "chưa gán = tạm cho qua").
  if (!$lop_giao_vien) {
    throw new Exception('khong_du_quyen');
  }
  $st = $pdo->prepare('SELECT lop_hoc_id FROM hoc_sinh WHERE id = ?');
  $st->execute([$hoc_sinh_id]);
  $lop = (int)$st->fetchColumn();
  if (!$lop || !in_array($lop, $lop_giao_vien, true)) {
    throw new Exception('khong_du_quyen');
  }
}

function lay_bien_diem(PDO $pdo, int $giao_vien_id, int $ly_do_id): int
{
  $st = $pdo->prepare('SELECT bien_diem FROM ly_do WHERE id = ? AND dang_hoat_dong = 1 AND nguoi_tao_id = ?');
  $st->execute([$ly_do_id, $giao_vien_id]);
  $r = $st->fetch();
  if (!$r) {
    throw new Exception('ly_do_khong_hop_le');
  }
  return (int)$r['bien_diem'];
}

function dam_bao_vi(PDO $pdo, int $hoc_sinh_id): void
{
  $pdo->prepare('INSERT INTO vi_diem(hoc_sinh_id, so_du) VALUES(?, 0)
                 ON CONFLICT(hoc_sinh_id) DO NOTHING')->execute([$hoc_sinh_id]);
}

function doc_so_du(PDO $pdo, int $hoc_sinh_id): int
{
  $st = $pdo->prepare('SELECT so_du FROM vi_diem WHERE hoc_sinh_id = ?');
  $st->execute([$hoc_sinh_id]);
  $so_du = $st->fetchColumn();
  if ($so_du === false) {
    throw new Exception('khong_tim_thay_vi');
  }
  return (int)$so_du;
}

/**
 * Nếu client_action_id đã tồn tại (giao dịch này đã được áp dụng trước đó - app di động gửi lại
 * do mất mạng giữa chừng khi đồng bộ), trả về kết quả cũ thay vì báo lỗi hoặc cộng/trừ điểm lần 2.
 */
function tim_giao_dich_da_ap_dung(PDO $pdo, string $client_action_id): ?array
{
  $st = $pdo->prepare('SELECT so_du_sau FROM so_cai_diem WHERE client_action_id = ?');
  $st->execute([$client_action_id]);
  $r = $st->fetch();
  return $r ? ['so_du' => (int)$r['so_du_sau']] : null;
}

function cong_diem_giao_vien(PDO $pdo, int $giao_vien_id, int $hoc_sinh_id, int $ly_do_id, string $ghi_chu = '', ?string $client_action_id = null): array
{
  kiem_tra_quyen_lop($pdo, $giao_vien_id, $hoc_sinh_id);
  if ($client_action_id !== null && $client_action_id !== '') {
    $da_co = tim_giao_dich_da_ap_dung($pdo, $client_action_id);
    if ($da_co !== null) { return $da_co; }
  }
  $pdo->beginTransaction();
  try {
    dam_bao_vi($pdo, $hoc_sinh_id);
    $bien = lay_bien_diem($pdo, $giao_vien_id, $ly_do_id);
    $stUp = $pdo->prepare('UPDATE vi_diem SET so_du = so_du + ? WHERE hoc_sinh_id = ?');
    $stUp->execute([$bien, $hoc_sinh_id]);
    $so_du_moi = doc_so_du($pdo, $hoc_sinh_id);
    $pdo->prepare('INSERT INTO so_cai_diem(hoc_sinh_id, giao_vien_id, loai, ly_do_id, bien_diem, so_du_sau, ghi_chu, client_action_id)
                   VALUES(?,?,?,?,?,?,?,?)')
        ->execute([$hoc_sinh_id, $giao_vien_id, 'CONG_DIEM', $ly_do_id, $bien, $so_du_moi, $ghi_chu, $client_action_id ?: null]);
    $pdo->commit();
    return ['so_du' => $so_du_moi];
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    throw $e;
  }
}

function quy_doi_qua_tang(PDO $pdo, int $giao_vien_id, int $hoc_sinh_id, int $qua_tang_id, string $ghi_chu = '', ?string $client_action_id = null): array
{
  kiem_tra_quyen_lop($pdo, $giao_vien_id, $hoc_sinh_id);
  if ($client_action_id !== null && $client_action_id !== '') {
    $da_co = tim_giao_dich_da_ap_dung($pdo, $client_action_id);
    if ($da_co !== null) { return $da_co; }
  }
  $st = $pdo->prepare('SELECT gia_diem, ton_kho FROM qua_tang WHERE id = ? AND dang_hoat_dong = 1 AND nguoi_tao_id = ?');
  $st->execute([$qua_tang_id, $giao_vien_id]);
  $q = $st->fetch();
  if (!$q) {
    throw new Exception('qua_khong_hop_le');
  }
  $gia_db = (int)$q['gia_diem'];
  $ton = (int)$q['ton_kho'];
  $gia = $gia_db;

  $pdo->beginTransaction();
  try {
    dam_bao_vi($pdo, $hoc_sinh_id);
    if ($ton === 0) {
      throw new Exception('het_hang');
    }
    $stDeduct = $pdo->prepare('UPDATE vi_diem SET so_du = so_du - ? WHERE hoc_sinh_id = ? AND so_du >= ?');
    $stDeduct->execute([$gia, $hoc_sinh_id, $gia]);
    if ($stDeduct->rowCount() === 0) {
      throw new Exception('khong_du_diem');
    }
    $so_du_moi = doc_so_du($pdo, $hoc_sinh_id);
    if ($ton > 0) {
      $pdo->prepare('UPDATE qua_tang SET ton_kho = ton_kho - 1 WHERE id = ?')->execute([$qua_tang_id]);
    }
    $pdo->prepare('INSERT INTO so_cai_diem(hoc_sinh_id, giao_vien_id, loai, qua_tang_id, bien_diem, so_du_sau, ghi_chu, client_action_id)
                   VALUES(?,?,?,?,?,?,?,?)')
        ->execute([$hoc_sinh_id, $giao_vien_id, 'DOI_DIEM', $qua_tang_id, -$gia, $so_du_moi, $ghi_chu, $client_action_id ?: null]);
    $pdo->commit();
    return ['so_du' => $so_du_moi];
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    throw $e;
  }
}

