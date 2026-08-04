<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DangKyTest extends TestCase
{
  public function testDangKyTaoTaiKhoanVaSeedMacDinh(): void
  {
    $pdo = tao_pdo_test();

    $gv = dang_ky_giao_vien($pdo, 'GiaoVienA', 'matkhau123');

    $this->assertSame('giaoviena', $gv['ten_dang_nhap']);
    $this->assertGreaterThan(0, $gv['id']);

    $st = $pdo->prepare('SELECT phai_doi_mat_khau FROM giao_vien WHERE id=?');
    $st->execute([$gv['id']]);
    $this->assertSame(0, (int)$st->fetchColumn());

    $st = $pdo->prepare('SELECT COUNT(*) FROM ly_do WHERE nguoi_tao_id=?');
    $st->execute([$gv['id']]);
    $this->assertSame(3, (int)$st->fetchColumn());

    $st = $pdo->prepare('SELECT COUNT(*) FROM qua_tang WHERE nguoi_tao_id=?');
    $st->execute([$gv['id']]);
    $this->assertSame(2, (int)$st->fetchColumn());
  }

  public function testDangKyTuChoiTenDangNhapKhongHopLe(): void
  {
    $pdo = tao_pdo_test();
    $this->expectException(Exception::class);
    $this->expectExceptionMessage('ten_dang_nhap_khong_hop_le');
    dang_ky_giao_vien($pdo, 'ab', 'matkhau123');
  }

  public function testDangKyTuChoiMatKhauQuaNgan(): void
  {
    $pdo = tao_pdo_test();
    $this->expectException(Exception::class);
    $this->expectExceptionMessage('mat_khau_qua_ngan');
    dang_ky_giao_vien($pdo, 'giaovienb', '123');
  }

  public function testDangKyTuChoiTrungTenDangNhap(): void
  {
    $pdo = tao_pdo_test();
    dang_ky_giao_vien($pdo, 'giaovienc', 'matkhau123');

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('ten_dang_nhap_da_ton_tai');
    dang_ky_giao_vien($pdo, 'GiaoVienC', 'matkhaukhac');
  }

  public function testDangKyKhongLamRoiCsdlKhiTrungTen(): void
  {
    $pdo = tao_pdo_test();
    dang_ky_giao_vien($pdo, 'giaoviend', 'matkhau123');
    try {
      dang_ky_giao_vien($pdo, 'giaoviend', 'matkhaukhac');
    } catch (Exception $e) {
      // bo qua, chi kiem tra khong con giao dich treo
    }
    $this->assertFalse($pdo->inTransaction());
    $this->assertSame(1, (int)$pdo->query('SELECT COUNT(*) FROM giao_vien')->fetchColumn());
  }
}
