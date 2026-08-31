<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Khoá lại lý do vì sao api/ly_do_quan_tri.php và api/qua_tang_quan_tri.php phải lấy
 * $pdo->lastInsertId() NGAY sau INSERT, TRƯỚC khi gọi ghi_log():
 * ghi_log() chèn thêm 1 dòng vào nhat_ky, làm lastInsertId() trả về id của nhat_ky.
 * Nếu lấy sau ghi_log() thì API trả 'id' sai -> client (form thêm quà) gắn ảnh nhầm bản ghi.
 */
final class GhiLogLastInsertIdTest extends TestCase
{
  public function testLastInsertIdBiDoiSauKhiGhiLog(): void
  {
    $pdo = tao_pdo_test();
    $gv = chen_giao_vien($pdo);

    // nhat_ky tích luỹ nhiều dòng theo thời gian -> id của nó chạy trước qua_tang.
    for ($i = 0; $i < 5; $i++) {
      ghi_log($pdo, $gv, 'khoi_dong', 'dòng log #' . $i);
    }

    $pdo->prepare("INSERT INTO qua_tang(ten, gia_diem, ton_kho, dang_hoat_dong, nguoi_tao_id) VALUES('Bút chì', 5, 1, 1, ?)")
        ->execute([$gv]);
    $quaId = (int)$pdo->lastInsertId();

    ghi_log($pdo, $gv, 'them_qua', 'Thêm quà Bút chì');
    $sauGhiLog = (int)$pdo->lastInsertId();

    $this->assertNotSame(
      $quaId,
      $sauGhiLog,
      'ghi_log() chèn nhat_ky nên lastInsertId() không còn là id của qua_tang'
    );
    $this->assertSame(
      $quaId,
      (int)$pdo->query("SELECT id FROM qua_tang WHERE ten='Bút chì'")->fetchColumn(),
      'id thật của quà vẫn là giá trị lấy được ngay sau INSERT'
    );
  }

  public function testGhiLogNuotLoiKhiThieuBang(): void
  {
    // ghi_log() bọc try/catch: thiếu bảng nhat_ky cũng không được ném lỗi ra ngoài.
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    ghi_log($pdo, 1, 'x', 'y');
    $this->assertTrue(true);
  }
}
