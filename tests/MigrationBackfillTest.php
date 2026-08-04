<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class MigrationBackfillTest extends TestCase
{
  public function testBackfillGanAdminCuVaoMoiLopDangCo(): void
  {
    $pdo = tao_pdo_truoc_nang_cap();
    $admin_id = chen_giao_vien($pdo, 'gv1', '123456', 'ADMIN');
    $lop1 = chen_lop($pdo, '4A');
    $lop2 = chen_lop($pdo, '4B');

    chay_migration($pdo);

    $st = $pdo->prepare('SELECT lop_hoc_id FROM giao_vien_lop WHERE giao_vien_id=? ORDER BY lop_hoc_id');
    $st->execute([$admin_id]);
    $this->assertSame([$lop1, $lop2], array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN)));
  }

  public function testBackfillKhongDungToiGiaoVienLopSanCoCuaGvKhac(): void
  {
    $pdo = tao_pdo_truoc_nang_cap();
    $admin_id = chen_giao_vien($pdo, 'gv1', '123456', 'ADMIN');
    $gv2_id = chen_giao_vien($pdo, 'gv2', '123456', 'GV');
    $lop_rieng = chen_lop($pdo, '4A');
    $lop_khac = chen_lop($pdo, '4B');
    gan_giao_vien_vao_lop($pdo, $gv2_id, $lop_rieng);

    chay_migration($pdo);

    $st = $pdo->prepare('SELECT lop_hoc_id FROM giao_vien_lop WHERE giao_vien_id=? ORDER BY lop_hoc_id');
    $st->execute([$gv2_id]);
    $this->assertSame([$lop_rieng], array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN)));
  }

  public function testBackfillNhanBanLyDoVaQuaChoTungGiaoVien(): void
  {
    $pdo = tao_pdo_truoc_nang_cap();
    $gv1 = chen_giao_vien($pdo, 'gv1', '123456', 'ADMIN');
    $gv2 = chen_giao_vien($pdo, 'gv2', '123456', 'GV');
    $pdo->exec("INSERT INTO ly_do(tieu_de, bien_diem) VALUES ('Giup ban', 2)");
    $pdo->exec("INSERT INTO qua_tang(ten, gia_diem, ton_kho) VALUES ('Sticker', 3, -1)");

    chay_migration($pdo);

    $ly_do = $pdo->query('SELECT id, nguoi_tao_id, tieu_de FROM ly_do ORDER BY id')->fetchAll();
    $this->assertCount(2, $ly_do);
    $this->assertSame(1, (int)$ly_do[0]['id']);
    $this->assertSame($gv1, (int)$ly_do[0]['nguoi_tao_id']);
    $this->assertSame($gv2, (int)$ly_do[1]['nguoi_tao_id']);
    $this->assertSame('Giup ban', $ly_do[1]['tieu_de']);

    $qua = $pdo->query('SELECT id, nguoi_tao_id FROM qua_tang ORDER BY id')->fetchAll();
    $this->assertCount(2, $qua);
    $this->assertSame($gv1, (int)$qua[0]['nguoi_tao_id']);
    $this->assertSame($gv2, (int)$qua[1]['nguoi_tao_id']);
  }

  public function testBackfillGiuNguyenIdBanGhiGocDeLichSuVanDung(): void
  {
    $pdo = tao_pdo_truoc_nang_cap();
    $gv1 = chen_giao_vien($pdo, 'gv1', '123456', 'ADMIN');
    chen_giao_vien($pdo, 'gv2', '123456', 'GV');
    $lop = chen_lop($pdo, '4A');
    $hs = chen_hoc_sinh($pdo, 'HS 1', $lop);
    $pdo->exec("INSERT INTO ly_do(tieu_de, bien_diem) VALUES ('Giup ban', 2)");
    $pdo->prepare('INSERT INTO so_cai_diem(hoc_sinh_id, giao_vien_id, loai, ly_do_id, bien_diem, so_du_sau) VALUES(?,?,?,?,?,?)')
        ->execute([$hs, $gv1, 'CONG_DIEM', 1, 2, 2]);

    chay_migration($pdo);

    $st = $pdo->query('SELECT sc.ly_do_id, ld.tieu_de, ld.nguoi_tao_id FROM so_cai_diem sc JOIN ly_do ld ON ld.id = sc.ly_do_id');
    $row = $st->fetch();
    $this->assertSame(1, (int)$row['ly_do_id']);
    $this->assertSame('Giup ban', $row['tieu_de']);
    $this->assertSame($gv1, (int)$row['nguoi_tao_id']);
  }

  public function testBackfillChayLaiKhongLamGiThem(): void
  {
    $pdo = tao_pdo_truoc_nang_cap();
    chen_giao_vien($pdo, 'gv1', '123456', 'ADMIN');
    chen_giao_vien($pdo, 'gv2', '123456', 'GV');
    chen_lop($pdo, '4A');
    $pdo->exec("INSERT INTO ly_do(tieu_de, bien_diem) VALUES ('Giup ban', 2)");

    chay_migration($pdo);
    $so_ly_do_lan1 = (int)$pdo->query('SELECT COUNT(*) FROM ly_do')->fetchColumn();
    $so_giao_vien_lop_lan1 = (int)$pdo->query('SELECT COUNT(*) FROM giao_vien_lop')->fetchColumn();

    chay_migration($pdo);
    $this->assertSame($so_ly_do_lan1, (int)$pdo->query('SELECT COUNT(*) FROM ly_do')->fetchColumn());
    $this->assertSame($so_giao_vien_lop_lan1, (int)$pdo->query('SELECT COUNT(*) FROM giao_vien_lop')->fetchColumn());
  }

  public function testBackfillKhongTuCapQuyenChoLopVaLyDoTaoRaSauNangCap(): void
  {
    $pdo = tao_pdo_truoc_nang_cap();
    $admin_id = chen_giao_vien($pdo, 'gv1', '123456', 'ADMIN');
    $gv2_id = chen_giao_vien($pdo, 'gv2', '123456', 'GV');
    chen_lop($pdo, '4A');

    chay_migration($pdo);

    // Sau nang cap, gv2 tu tao lop moi va ly do moi cua rieng minh
    $lop_moi = chen_lop($pdo, '5A');
    gan_giao_vien_vao_lop($pdo, $gv2_id, $lop_moi);
    $ly_do_moi_id = chen_ly_do($pdo, 'Ly do rieng cua gv2', 5, $gv2_id);

    chay_migration($pdo);

    $st = $pdo->prepare('SELECT 1 FROM giao_vien_lop WHERE giao_vien_id=? AND lop_hoc_id=?');
    $st->execute([$admin_id, $lop_moi]);
    $this->assertFalse($st->fetchColumn(), 'Admin cu khong duoc tu dong gan vao lop tao SAU nang cap');

    $this->assertSame(1, (int)$pdo->query('SELECT COUNT(*) FROM ly_do WHERE id=' . $ly_do_moi_id)->fetchColumn());
  }
}
