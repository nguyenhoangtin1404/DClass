<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ToChucTest extends TestCase
{
  public function testChuanHoaHostBoCongVaVietThuong(): void
  {
    $this->assertSame('truong-abc.example.com', chuan_hoa_host('Truong-ABC.Example.COM:8443'));
    $this->assertSame('localhost', chuan_hoa_host('localhost:8000'));
  }

  public function testChuanHoaHostGiuNguyenIpv6TrongNgoac(): void
  {
    $this->assertSame('[::1]', chuan_hoa_host('[::1]:8000'));
  }

  public function testXacDinhToChucTheoDomainKhopChinhXac(): void
  {
    $registry = tao_pdo_registry_test();
    chen_to_chuc($registry, 'truong_a', 'Truong A', 'truong-a.example.com');
    chen_to_chuc($registry, 'truong_b', 'Truong B', 'truong-b.example.com');

    $tc = xac_dinh_to_chuc_theo_domain($registry, 'truong-a.example.com');
    $this->assertNotNull($tc);
    $this->assertSame('truong_a', $tc['ma_to_chuc']);
  }

  public function testXacDinhToChucKhongPhanBietHoaThuongVaBoCong(): void
  {
    $registry = tao_pdo_registry_test();
    chen_to_chuc($registry, 'truong_a', 'Truong A', 'truong-a.example.com');

    $tc = xac_dinh_to_chuc_theo_domain($registry, 'Truong-A.Example.COM:8443');
    $this->assertNotNull($tc);
    $this->assertSame('truong_a', $tc['ma_to_chuc']);
  }

  public function testXacDinhToChucTraVeNullKhiKhongKhop(): void
  {
    $registry = tao_pdo_registry_test();
    chen_to_chuc($registry, 'truong_a', 'Truong A', 'truong-a.example.com');

    $this->assertNull(xac_dinh_to_chuc_theo_domain($registry, 'khong-ton-tai.example.com'));
    $this->assertNull(xac_dinh_to_chuc_theo_domain($registry, ''));
  }

  public function testXacDinhToChucBoQuaToChucBiKhoa(): void
  {
    $registry = tao_pdo_registry_test();
    chen_to_chuc($registry, 'truong_a', 'Truong A', 'truong-a.example.com', hoat_dong: false);

    $this->assertNull(xac_dinh_to_chuc_theo_domain($registry, 'truong-a.example.com'));
  }
}
