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
    $ly_do_id = chen_ly_do($pdo, 'Cham chi', 2, $gv_id);
    gan_giao_vien_vao_lop($pdo, $gv_id, $lop_id);

    $ket_qua = cong_diem_giao_vien($pdo, $gv_id, $hs_id, $ly_do_id, 'thu nghiem');

    $this->assertSame(2, $ket_qua['so_du']);
    $st = $pdo->query('SELECT COUNT(*) FROM so_cai_diem WHERE loai="CONG_DIEM"');
    $this->assertSame(1, (int)$st->fetchColumn());
  }

  public function testQuyDoiTruDiemVaCapNhatTonKho(): void
  {
    $pdo = tao_pdo_test();
    $gv_id = chen_giao_vien($pdo);
    $lop_id = chen_lop($pdo, '4A');
    $hs_id = chen_hoc_sinh($pdo, 'HS 1', $lop_id);
    $qua_id = chen_qua($pdo, 'Sticker', 3, 1, $gv_id);
    gan_giao_vien_vao_lop($pdo, $gv_id, $lop_id);
    $pdo->prepare('INSERT INTO vi_diem(hoc_sinh_id, so_du) VALUES(?, ?)')->execute([$hs_id, 10]);

    $ket_qua = quy_doi_qua_tang($pdo, $gv_id, $hs_id, $qua_id, 'doi qua');

    $this->assertSame(7, $ket_qua['so_du']);
    $st = $pdo->prepare('SELECT ton_kho FROM qua_tang WHERE id = ?');
    $st->execute([$qua_id]);
    $this->assertSame(0, (int)$st->fetchColumn());
    $st = $pdo->query('SELECT COUNT(*) FROM so_cai_diem WHERE loai="DOI_DIEM"');
    $this->assertSame(1, (int)$st->fetchColumn());
  }

  public function testQuyDoiTuChoiNeuKhongDuDiem(): void
  {
    $pdo = tao_pdo_test();
    $gv_id = chen_giao_vien($pdo);
    $lop_id = chen_lop($pdo, '4A');
    $hs_id = chen_hoc_sinh($pdo, 'HS 2', $lop_id);
    $qua_id = chen_qua($pdo, 'Gau bong', 8, 5, $gv_id);
    gan_giao_vien_vao_lop($pdo, $gv_id, $lop_id);
    $pdo->prepare('INSERT INTO vi_diem(hoc_sinh_id, so_du) VALUES(?, ?)')->execute([$hs_id, 5]);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('khong_du_diem');
    quy_doi_qua_tang($pdo, $gv_id, $hs_id, $qua_id, 'test khong du diem');
  }

  public function testCongDiemTuChoiNeuKhongDuQuyen(): void
  {
    $pdo = tao_pdo_test();
    $gv_id = chen_giao_vien($pdo, 'gv2', '123456', 'GV');
    $lop_cua_gv = chen_lop($pdo, '4A');
    $lop_khac = chen_lop($pdo, '4B');
    $hs_id = chen_hoc_sinh($pdo, 'HS 2', $lop_khac);
    $ly_do_id = chen_ly_do($pdo, 'Cham chi', 2, $gv_id);
    gan_giao_vien_vao_lop($pdo, $gv_id, $lop_cua_gv);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('khong_du_quyen');
    cong_diem_giao_vien($pdo, $gv_id, $hs_id, $ly_do_id, 'thu');
  }

  public function testCongDiemTuChoiNeuChuaSoHuuLopNao(): void
  {
    $pdo = tao_pdo_test();
    $gv_id = chen_giao_vien($pdo, 'gv3', '123456', 'GV');
    $lop_id = chen_lop($pdo, '4A');
    $hs_id = chen_hoc_sinh($pdo, 'HS 3', $lop_id);
    $ly_do_id = chen_ly_do($pdo, 'Cham chi', 2, $gv_id);
    // Khong goi gan_giao_vien_vao_lop() - giao vien moi dang ky, chua so huu lop nao.

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('khong_du_quyen');
    cong_diem_giao_vien($pdo, $gv_id, $hs_id, $ly_do_id, 'thu');
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
