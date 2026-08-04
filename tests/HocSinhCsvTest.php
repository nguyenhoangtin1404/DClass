<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class HocSinhCsvTest extends TestCase
{
  public function testTaoMoiHocSinhTheoMaKhiChuaTonTai(): void
  {
    $pdo = tao_pdo_test();
    $gv_id = chen_giao_vien($pdo, 'gv_a');
    $lop_id = chen_lop($pdo, '4A');
    gan_giao_vien_vao_lop($pdo, $gv_id, $lop_id);

    $kq = hoc_sinh_upsert_theo_ma($pdo, [$lop_id], 'HS001', 'Nguyen Van A', $lop_id, null, null, null, null);

    $this->assertNotNull($kq);
    $this->assertTrue($kq['moi']);
    $st = $pdo->prepare('SELECT ho_ten, lop_hoc_id FROM hoc_sinh WHERE id=?');
    $st->execute([$kq['id']]);
    $row = $st->fetch();
    $this->assertSame('Nguyen Van A', $row['ho_ten']);
    $this->assertSame($lop_id, (int)$row['lop_hoc_id']);
  }

  public function testCapNhatHocSinhTrongLopCuaChinhMinh(): void
  {
    $pdo = tao_pdo_test();
    $gv_id = chen_giao_vien($pdo, 'gv_a');
    $lop_id = chen_lop($pdo, '4A');
    gan_giao_vien_vao_lop($pdo, $gv_id, $lop_id);
    hoc_sinh_upsert_theo_ma($pdo, [$lop_id], 'HS001', 'Ten Cu', $lop_id, null, null, null, null);

    $kq = hoc_sinh_upsert_theo_ma($pdo, [$lop_id], 'HS001', 'Ten Moi', $lop_id, null, null, null, null);

    $this->assertNotNull($kq);
    $this->assertFalse($kq['moi']);
    $st = $pdo->prepare('SELECT ho_ten FROM hoc_sinh WHERE ma=?');
    $st->execute(['HS001']);
    $this->assertSame('Ten Moi', $st->fetchColumn());
    $this->assertSame(1, (int)$pdo->query('SELECT COUNT(*) FROM hoc_sinh')->fetchColumn());
  }

  public function testTuChoiGhiDeHocSinhCuaGiaoVienKhac(): void
  {
    $pdo = tao_pdo_test();
    $gv_a = chen_giao_vien($pdo, 'gv_a');
    $gv_b = chen_giao_vien($pdo, 'gv_b', '123456', 'GV');
    $lop_a = chen_lop($pdo, '4A');
    $lop_b = chen_lop($pdo, '4B');
    gan_giao_vien_vao_lop($pdo, $gv_a, $lop_a);
    gan_giao_vien_vao_lop($pdo, $gv_b, $lop_b);

    // gv_a tao hoc sinh HS001 trong lop cua minh
    hoc_sinh_upsert_theo_ma($pdo, [$lop_a], 'HS001', 'Hoc sinh cua A', $lop_a, null, null, null, null);

    // gv_b nhap CSV co dong trung ma "HS001" - phai bi tu choi, KHONG duoc ghi de
    $kq = hoc_sinh_upsert_theo_ma($pdo, [$lop_b], 'HS001', 'Bi hack boi B', $lop_b, null, null, null, null);

    $this->assertNull($kq);
    $st = $pdo->prepare('SELECT ho_ten, lop_hoc_id FROM hoc_sinh WHERE ma=?');
    $st->execute(['HS001']);
    $row = $st->fetch();
    $this->assertSame('Hoc sinh cua A', $row['ho_ten']);
    $this->assertSame($lop_a, (int)$row['lop_hoc_id']);
    $this->assertSame(1, (int)$pdo->query('SELECT COUNT(*) FROM hoc_sinh')->fetchColumn());
  }

  public function testChoPhepNhanHocSinhChuaGanLopVaoLopCuaMinh(): void
  {
    $pdo = tao_pdo_test();
    $gv_id = chen_giao_vien($pdo, 'gv_a');
    $lop_id = chen_lop($pdo, '4A');
    gan_giao_vien_vao_lop($pdo, $gv_id, $lop_id);
    // Hoc sinh HS002 ton tai nhung chua thuoc lop nao (lop_hoc_id NULL)
    $pdo->prepare('INSERT INTO hoc_sinh(ma, ho_ten) VALUES(?,?)')->execute(['HS002', 'Chua co lop']);

    $kq = hoc_sinh_upsert_theo_ma($pdo, [$lop_id], 'HS002', 'Da nhan vao lop', $lop_id, null, null, null, null);

    $this->assertNotNull($kq);
    $this->assertFalse($kq['moi']);
    $st = $pdo->prepare('SELECT lop_hoc_id FROM hoc_sinh WHERE ma=?');
    $st->execute(['HS002']);
    $this->assertSame($lop_id, (int)$st->fetchColumn());
  }
}
