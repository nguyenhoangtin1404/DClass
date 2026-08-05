<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class TokenApiTest extends TestCase
{
  protected function tearDown(): void
  {
    unset($_SERVER['HTTP_AUTHORIZATION'], $_SESSION['giao_vien_id'], $_SESSION['phai_doi_mat_khau']);
  }

  public function testXacThucThanhCongVoiTokenDung(): void
  {
    $pdo = tao_pdo_test();
    $gv_id = chen_giao_vien($pdo);
    $token = bin2hex(random_bytes(32));
    $pdo->prepare('UPDATE giao_vien SET api_token_bam=? WHERE id=?')->execute([hash('sha256', $token), $gv_id]);

    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
    $ket_qua = thu_xac_thuc_bang_token($pdo);

    $this->assertTrue($ket_qua);
    $this->assertSame($gv_id, $_SESSION['giao_vien_id']);
  }

  public function testXacThucThatBaiVoiTokenSai(): void
  {
    $pdo = tao_pdo_test();
    $gv_id = chen_giao_vien($pdo);
    $pdo->prepare('UPDATE giao_vien SET api_token_bam=? WHERE id=?')->execute([hash('sha256', 'token-that'), $gv_id]);

    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer token-sai-hoan-toan';
    $ket_qua = thu_xac_thuc_bang_token($pdo);

    $this->assertFalse($ket_qua);
    $this->assertArrayNotHasKey('giao_vien_id', $_SESSION);
  }

  public function testXacThucThatBaiKhiKhongCoHeader(): void
  {
    $pdo = tao_pdo_test();
    chen_giao_vien($pdo);

    $ket_qua = thu_xac_thuc_bang_token($pdo);

    $this->assertFalse($ket_qua);
  }

  public function testThuHoiTokenLamMatHieuLucXacThuc(): void
  {
    $pdo = tao_pdo_test();
    $gv_id = chen_giao_vien($pdo);
    $token = bin2hex(random_bytes(32));
    $pdo->prepare('UPDATE giao_vien SET api_token_bam=? WHERE id=?')->execute([hash('sha256', $token), $gv_id]);
    // Thu hồi token (như hanh_dong=thu_hoi_token_api trong api/giao_vien_quan_tri.php)
    $pdo->prepare('UPDATE giao_vien SET api_token_bam=NULL WHERE id=?')->execute([$gv_id]);

    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
    $ket_qua = thu_xac_thuc_bang_token($pdo);

    $this->assertFalse($ket_qua);
  }
}
