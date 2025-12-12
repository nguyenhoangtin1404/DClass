<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DiemTest extends TestCase
{
  public function testCongDiemTangSoDuVaGhiSoCai(): void
  {
    $pdo = tao_pdo_test();
    $gv_id = chen_giao_vien($pdo);
    $lop_id = chen_lop($pdo, '4A');
    $hs_id = chen_hoc_sinh($pdo, 'HS 1', $lop_id);
    $ly_do_id = chen_ly_do($pdo, 'Cham chi', 2);

    $ket_qua = cong_diem_giao_vien($pdo, $gv_id, true, $hs_id, $ly_do_id, 'thu nghiem');

    $this->assertSame(2, $ket_qua['so_du']);
    $st = $pdo->query('SELECT COUNT(*) FROM so_cai_diem WHERE loai="CONG_DIEM"');
    $this->assertSame('1', $st->fetchColumn());
  }

  public function testQuyDoiTruDiemVaCapNhatTonKho(): void
  {
    $pdo = tao_pdo_test();
    $gv_id = chen_giao_vien($pdo);
    $lop_id = chen_lop($pdo, '4A');
    $hs_id = chen_hoc_sinh($pdo, 'HS 1', $lop_id);
    $qua_id = chen_qua($pdo, 'Sticker', 3, 1);
    $pdo->prepare('INSERT INTO vi_diem(hoc_sinh_id, so_du) VALUES(?, ?)')->execute([$hs_id, 10]);

    $ket_qua = quy_doi_qua_tang($pdo, $gv_id, true, $hs_id, $qua_id, null, 'doi qua');

    $this->assertSame(7, $ket_qua['so_du']);
    $st = $pdo->prepare('SELECT ton_kho FROM qua_tang WHERE id = ?');
    $st->execute([$qua_id]);
    $this->assertSame(0, (int)$st->fetchColumn());
    $st = $pdo->query('SELECT COUNT(*) FROM so_cai_diem WHERE loai="DOI_DIEM"');
    $this->assertSame('1', $st->fetchColumn());
  }

  public function testCongDiemTuChoiNeuKhongDuQuyen(): void
  {
    $pdo = tao_pdo_test();
    $gv_id = chen_giao_vien($pdo, 'gv2', '123456', 'GV');
    $lop_admin = chen_lop($pdo, '4A');
    $lop_khac = chen_lop($pdo, '4B');
    $hs_id = chen_hoc_sinh($pdo, 'HS 2', $lop_admin);
    $ly_do_id = chen_ly_do($pdo, 'Cham chi', 2);
    gan_giao_vien_vao_lop($pdo, $gv_id, $lop_khac);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('khong_du_quyen');
    cong_diem_giao_vien($pdo, $gv_id, false, $hs_id, $ly_do_id, 'thu');
  }

  public function testKiemTraDangNhapThanhCongVaThatBai(): void
  {
    $pdo = tao_pdo_test();
    chen_giao_vien($pdo, 'gv_login', '123456', 'ADMIN');

    $thanh_cong = kiem_tra_dang_nhap($pdo, 'gv_login', '123456');
    $that_bai = kiem_tra_dang_nhap($pdo, 'gv_login', 'sai_mat_khau');

    $this->assertNotNull($thanh_cong);
    $this->assertNull($that_bai);
  }
}

