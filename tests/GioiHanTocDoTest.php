<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class GioiHanTocDoTest extends TestCase
{
  private function chenTho(PDO $pdo, string $khoa, int $soLan, int $khoaDen, int $capNhatLucEpoch): void
  {
    $pdo->prepare(
      'INSERT INTO dem_thu_that_bai(khoa, so_lan, khoa_den, cap_nhat_luc) VALUES(?,?,?,?)'
    )->execute([$khoa, $soLan, $khoaDen, gmdate('Y-m-d H:i:s', $capNhatLucEpoch)]);
  }

  public function testDemMoiBatDauTuKhong(): void
  {
    $pdo = tao_pdo_test();
    $d = doc_dem_that_bai($pdo, 'dn|1.2.3.4|test');
    $this->assertSame(0, $d['so_lan']);
    $this->assertSame(0, $d['khoa_den']);
  }

  public function testGhiNhanThatBaiTangDanTungLan(): void
  {
    $pdo = tao_pdo_test();
    $k = 'dn|1.2.3.4|test';
    ghi_nhan_that_bai($pdo, $k, 5);
    ghi_nhan_that_bai($pdo, $k, 5);
    $d = ghi_nhan_that_bai($pdo, $k, 5);
    $this->assertSame(3, $d['so_lan']);
    $this->assertSame(0, $d['khoa_den']);
  }

  public function testGhiNhanThatBaiKhoaKhiVuotNguong(): void
  {
    $pdo = tao_pdo_test();
    $k = 'dn|1.2.3.4|test';
    for ($i = 1; $i <= 4; $i++) {
      $d = ghi_nhan_that_bai($pdo, $k, 5);
      $this->assertSame(0, $d['khoa_den'], "chưa khoá ở lần thứ $i");
    }
    $d5 = ghi_nhan_that_bai($pdo, $k, 5);
    $this->assertSame(5, $d5['so_lan']);
    $this->assertGreaterThan(time(), $d5['khoa_den']);
  }

  /**
   * Tái hiện đúng lỗ hổng race condition: đọc-rồi-tính-rồi-ghi ở tầng PHP (cách làm cũ) có thể
   * "mất" lần tăng khi nhiều request thất bại đồng thời cùng đọc một giá trị cũ. ghi_nhan_that_bai
   * dùng UPSERT với so_lan = so_lan + 1 (tương đối) nên nhiều kết nối riêng biệt cùng tăng 1 khoá
   * vẫn phải cộng dồn đúng, không kết nối nào "ghi đè" mất lần tăng của kết nối khác.
   */
  public function testTangDongThoiTuNhieuKetNoiKhongMatLanTang(): void
  {
    $pdoChinh = tao_pdo_test();
    $duongDan = tempnam(sys_get_temp_dir(), 'dclass_race_');
    $this->assertNotFalse($duongDan);
    unlink($duongDan);
    $taoKetNoi = function () use ($duongDan): PDO {
      $pdo = new PDO('sqlite:' . $duongDan);
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
      $pdo->exec('PRAGMA busy_timeout = 5000');
      $pdo->exec('PRAGMA journal_mode = WAL');
      return $pdo;
    };
    $pdo0 = $taoKetNoi();
    foreach (
      array_filter(array_map('trim', explode(';', (string)file_get_contents(__DIR__ . '/../config/luoc_do.sql'))))
      as $lenh
    ) {
      if ($lenh !== '') { $pdo0->exec($lenh); }
    }
    chay_migration($pdo0);

    $k = 'dn|1.2.3.4|race';
    $soKetNoi = 20;
    for ($i = 0; $i < $soKetNoi; $i++) {
      ghi_nhan_that_bai($taoKetNoi(), $k, 5);
    }

    $ketQua = doc_dem_that_bai($pdo0, $k);
    $this->assertSame($soKetNoi, $ketQua['so_lan']);
    $this->assertGreaterThan(time(), $ketQua['khoa_den']);
    @unlink($duongDan);
    @unlink($duongDan . '-wal');
    @unlink($duongDan . '-shm');
  }

  public function testDocKhoaHetHanTuDongVeKhongVaXoaHang(): void
  {
    $pdo = tao_pdo_test();
    $k = 'dn|1.2.3.4|test';
    // khoa_den đã qua, cap_nhat_luc (khi lệnh khoá này được ghi) cũng đã cũ hơn cửa sổ.
    $this->chenTho($pdo, $k, 5, time() - 10, time() - (GIOI_HAN_CUA_SO_GIAY + 10));

    $d = doc_dem_that_bai($pdo, $k);
    $this->assertSame(0, $d['so_lan']);
    $this->assertSame(0, $d['khoa_den']);

    $st = $pdo->prepare('SELECT COUNT(*) FROM dem_thu_that_bai WHERE khoa = ?');
    $st->execute([$k]);
    $this->assertSame(0, (int)$st->fetchColumn());
  }

  /**
   * Lỗi đã sửa: một đếm CHƯA từng bị khoá (khoa_den=0, so_lan 1-4) trước đây không bao giờ hết
   * hạn - một lần sai xảy ra rất lâu sau đó (ngoài cửa sổ) sẽ bị cộng dồn nhầm vào đợt sai cũ,
   * dẫn đến khoá oan ngay từ lần sai đầu tiên của một đợt hoàn toàn mới.
   */
  public function testDemChuaKhoaNhungQuaCuaSoTuDongResetKhiDoc(): void
  {
    $pdo = tao_pdo_test();
    $k = 'dn|1.2.3.4|test';
    $this->chenTho($pdo, $k, 4, 0, time() - (GIOI_HAN_CUA_SO_GIAY + 10));

    $this->assertSame(0, doc_dem_that_bai($pdo, $k)['so_lan']);
  }

  public function testDemChuaKhoaNhungQuaCuaSoResetKhiGhiNhanThatBaiMoi(): void
  {
    $pdo = tao_pdo_test();
    $k = 'dn|1.2.3.4|test';
    $this->chenTho($pdo, $k, 4, 0, time() - (GIOI_HAN_CUA_SO_GIAY + 10));

    // Một lần sai mới, rất lâu sau đợt cũ -> phải bắt đầu lại từ 1, không phải 5 (thứ tự cũ+1).
    $d = ghi_nhan_that_bai($pdo, $k, 5);
    $this->assertSame(1, $d['so_lan']);
    $this->assertSame(0, $d['khoa_den']);
  }

  public function testDemConTrongCuaSoKhongBiResetOan(): void
  {
    $pdo = tao_pdo_test();
    $k = 'dn|1.2.3.4|test';
    $this->chenTho($pdo, $k, 4, 0, time() - 60);

    $this->assertSame(4, doc_dem_that_bai($pdo, $k)['so_lan']);
  }

  public function testXoaDemThatBaiVeKhong(): void
  {
    $pdo = tao_pdo_test();
    $k = 'dn|1.2.3.4|test';
    ghi_nhan_that_bai($pdo, $k, 5);
    ghi_nhan_that_bai($pdo, $k, 5);
    xoa_dem_that_bai($pdo, $k);

    $this->assertSame(0, doc_dem_that_bai($pdo, $k)['so_lan']);
  }

  public function testCacKhoaKhacNhauDocLap(): void
  {
    $pdo = tao_pdo_test();
    ghi_nhan_that_bai($pdo, 'dn|1.2.3.4|alice', 5);
    ghi_nhan_that_bai($pdo, 'dn|1.2.3.4|alice', 5);
    ghi_nhan_that_bai($pdo, 'dn|1.2.3.4|alice', 5);
    ghi_nhan_that_bai($pdo, 'dn|1.2.3.4|bob', 5);

    $this->assertSame(3, doc_dem_that_bai($pdo, 'dn|1.2.3.4|alice')['so_lan']);
    $this->assertSame(1, doc_dem_that_bai($pdo, 'dn|1.2.3.4|bob')['so_lan']);
  }
}
