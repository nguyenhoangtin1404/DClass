<?php
declare(strict_types=1);

function tim_giao_vien_theo_ten(PDO $pdo, string $ten_dang_nhap): ?array
{
  $st = $pdo->prepare('SELECT * FROM giao_vien WHERE ten_dang_nhap = ?');
  $st->execute([$ten_dang_nhap]);
  $gv = $st->fetch();
  return $gv ?: null;
}

function kiem_tra_dang_nhap(PDO $pdo, string $ten_dang_nhap, string $mat_khau): ?array
{
  $gv = tim_giao_vien_theo_ten($pdo, $ten_dang_nhap);
  if ($gv && password_verify($mat_khau, $gv['mat_khau_bam'])) {
    return $gv;
  }
  return null;
}

/**
 * Tự đăng ký tài khoản giáo viên mới: kiểm tra định dạng tên đăng nhập/độ dài mật khẩu, tạo tài
 * khoản, seed vài lý do/quà mặc định để không trống trơn. Ném Exception với mã lỗi (khớp
 * $thong_bao trong json_phan_hoi) nếu không hợp lệ - không đụng gì tới session/HTTP, gọi từ
 * api/dang_nhap.php.
 */
function dang_ky_giao_vien(PDO $pdo, string $ten_dang_nhap, string $mat_khau): array
{
  $ten = trim($ten_dang_nhap);
  $ten_norm = strtolower($ten);
  if (!preg_match('/^[a-zA-Z0-9_.]{3,32}$/', $ten)) {
    throw new Exception('ten_dang_nhap_khong_hop_le');
  }
  if (strlen($mat_khau) < 6) {
    throw new Exception('mat_khau_qua_ngan');
  }
  if (tim_giao_vien_theo_ten($pdo, $ten_norm) !== null) {
    throw new Exception('ten_dang_nhap_da_ton_tai');
  }

  try {
    $pdo->beginTransaction();
    $st = $pdo->prepare("INSERT INTO giao_vien(ten_dang_nhap, mat_khau_bam, vai_tro, phai_doi_mat_khau) VALUES(?,?,?,0)");
    $st->execute([$ten_norm, password_hash($mat_khau, PASSWORD_DEFAULT), 'GV']);
    $gv_id = (int)$pdo->lastInsertId();
    // Seed vài lý do/quà mặc định để tài khoản mới không trống trơn, giáo viên có thể sửa/xóa sau.
    $pdo->prepare("INSERT INTO ly_do(tieu_de, bien_diem, dang_hoat_dong, nguoi_tao_id) VALUES
                    ('Giúp bạn',2,1,?), ('Hoàn thành sớm',1,1,?), ('Nói chuyện riêng',-1,1,?)")
        ->execute([$gv_id, $gv_id, $gv_id]);
    $pdo->prepare("INSERT INTO qua_tang(ten, gia_diem, ton_kho, dang_hoat_dong, nguoi_tao_id) VALUES
                    ('Sticker',3,-1,1,?), ('Bút chì',5,50,1,?)")
        ->execute([$gv_id, $gv_id]);
    $pdo->commit();
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    throw new Exception('ten_dang_nhap_da_ton_tai');
  }

  return ['id' => $gv_id, 'ten_dang_nhap' => $ten_norm];
}

