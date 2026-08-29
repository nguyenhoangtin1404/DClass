<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class GioiHanTocDoTest extends TestCase
{
  public function testDemMoiBatDauTuKhong(): void
  {
    $pdo = tao_pdo_test();
    $d = doc_dem_that_bai($pdo, 'dn|1.2.3.4|test');
    $this->assertSame(0, $d['so_lan']);
    $this->assertSame(0, $d['khoa_den']);
  }

  public function testGhiDemLuuBenQuaCacLanDocDocLap(): void
  {
    $pdo = tao_pdo_test();
    $k = 'dn|1.2.3.4|test';
    ghi_dem_that_bai($pdo, $k, 4, 0);

    // Mô phỏng đúng lỗ hổng cũ: một request hoàn toàn độc lập (không có $_SESSION nào được
    // truyền qua) đọc lại cùng khoá này vẫn phải thấy bộ đếm đã lưu, chứ không phải về 0.
    $d = doc_dem_that_bai($pdo, $k);
    $this->assertSame(4, $d['so_lan']);
  }

  public function testKhoaConHieuLucQuaLanDocDocLap(): void
  {
    $pdo = tao_pdo_test();
    $k = 'dn|1.2.3.4|test';
    $khoaDen = time() + 600;
    ghi_dem_that_bai($pdo, $k, 5, $khoaDen);

    $d = doc_dem_that_bai($pdo, $k);
    $this->assertSame($khoaDen, $d['khoa_den']);
    $this->assertGreaterThan(time(), $d['khoa_den']);
  }

  public function testKhoaHetHanTuDongVeKhongVaXoaHang(): void
  {
    $pdo = tao_pdo_test();
    $k = 'dn|1.2.3.4|test';
    ghi_dem_that_bai($pdo, $k, 5, time() - 10);

    $d = doc_dem_that_bai($pdo, $k);
    $this->assertSame(0, $d['so_lan']);
    $this->assertSame(0, $d['khoa_den']);

    $st = $pdo->prepare('SELECT COUNT(*) FROM dem_thu_that_bai WHERE khoa = ?');
    $st->execute([$k]);
    $this->assertSame(0, (int)$st->fetchColumn());
  }

  public function testXoaDemThatBaiVeKhong(): void
  {
    $pdo = tao_pdo_test();
    $k = 'dn|1.2.3.4|test';
    ghi_dem_that_bai($pdo, $k, 3, 0);
    xoa_dem_that_bai($pdo, $k);

    $d = doc_dem_that_bai($pdo, $k);
    $this->assertSame(0, $d['so_lan']);
  }

  public function testCacKhoaKhacNhauDocLap(): void
  {
    $pdo = tao_pdo_test();
    ghi_dem_that_bai($pdo, 'dn|1.2.3.4|alice', 3, 0);
    ghi_dem_that_bai($pdo, 'dn|1.2.3.4|bob', 1, 0);

    $this->assertSame(3, doc_dem_that_bai($pdo, 'dn|1.2.3.4|alice')['so_lan']);
    $this->assertSame(1, doc_dem_that_bai($pdo, 'dn|1.2.3.4|bob')['so_lan']);
  }
}
