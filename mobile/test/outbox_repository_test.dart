import 'package:dclass_mobile/db/app_database.dart';
import 'package:dclass_mobile/outbox/hanh_dong_cho.dart';
import 'package:dclass_mobile/outbox/outbox_repository.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:sqflite_common_ffi/sqflite_ffi.dart';

HanhDongCho _hanhDongMau({String clientActionId = 'ca-1', int hocSinhId = 1}) {
  return HanhDongCho(
    clientActionId: clientActionId,
    loai: LoaiHanhDong.congDiem,
    hocSinhId: hocSinhId,
    lyDoId: 1,
    taoLuc: DateTime.now().toIso8601String(),
  );
}

void main() {
  late OutboxRepository outbox;

  setUp(() async {
    final db = await openAppDatabase(path: inMemoryDatabasePath);
    outbox = OutboxRepository(db);
  });

  test(
    'layDanhSachChoXuLy returns rows in strict insertion (id) order',
    () async {
      await outbox.themMoi(_hanhDongMau(clientActionId: 'ca-1'));
      await outbox.themMoi(_hanhDongMau(clientActionId: 'ca-2'));
      await outbox.themMoi(_hanhDongMau(clientActionId: 'ca-3'));

      final ds = await outbox.layDanhSachChoXuLy();

      expect(ds.map((e) => e.clientActionId), ['ca-1', 'ca-2', 'ca-3']);
      expect(ds.every((e) => e.trangThai == TrangThaiHanhDong.choXuLy), isTrue);
    },
  );

  test(
    'a permanently-failed action is excluded from layDanhSachChoXuLy',
    () async {
      final id1 = await outbox.themMoi(_hanhDongMau(clientActionId: 'ca-1'));
      await outbox.themMoi(_hanhDongMau(clientActionId: 'ca-2'));

      await outbox.danhDauLoiVinhVien(id1, 'het_hang');

      final choXuLy = await outbox.layDanhSachChoXuLy();
      expect(choXuLy.map((e) => e.clientActionId), ['ca-2']);

      final loi = await outbox.layDanhSachLoiVinhVien();
      expect(loi, hasLength(1));
      expect(loi.single.clientActionId, 'ca-1');
      expect(loi.single.loiMa, 'het_hang');
    },
  );

  test('quetDangGuiConLai resets leftover in-flight rows to pending', () async {
    final id = await outbox.themMoi(_hanhDongMau());
    await outbox.danDauDangGui(id);

    // Simulate the app being killed mid-send: the row is stuck "dang_gui".
    var ds = await outbox.layDanhSachChoXuLy();
    expect(ds.single.trangThai, TrangThaiHanhDong.dangGui);

    await outbox.quetDangGuiConLai();

    ds = await outbox.layDanhSachChoXuLy();
    expect(ds.single.trangThai, TrangThaiHanhDong.choXuLy);
  });

  test('datLaiChoXuLy resets state and increments the attempt count', () async {
    final id = await outbox.themMoi(_hanhDongMau());
    await outbox.danDauDangGui(id);

    await outbox.datLaiChoXuLy(id);

    final ds = await outbox.layDanhSachChoXuLy();
    expect(ds.single.trangThai, TrangThaiHanhDong.choXuLy);
    expect(ds.single.soLanThu, 1);
  });

  test(
    'xoa removes a permanently-failed action for good (user discard)',
    () async {
      final id = await outbox.themMoi(_hanhDongMau());
      await outbox.danhDauLoiVinhVien(id, 'khong_du_diem');

      await outbox.xoa(id);

      expect(await outbox.layDanhSachLoiVinhVien(), isEmpty);
      expect(await outbox.layDanhSachChoXuLy(), isEmpty);
    },
  );

  test('xoaSauKhiThanhCong removes a completed action', () async {
    final id = await outbox.themMoi(_hanhDongMau());
    await outbox.xoaSauKhiThanhCong(id);
    expect(await outbox.layDanhSachChoXuLy(), isEmpty);
  });

  test(
    'demSoLuongChoXuLy counts everything except permanent failures',
    () async {
      await outbox.themMoi(_hanhDongMau(clientActionId: 'ca-1'));
      final id2 = await outbox.themMoi(_hanhDongMau(clientActionId: 'ca-2'));
      await outbox.themMoi(_hanhDongMau(clientActionId: 'ca-3'));
      await outbox.danhDauLoiVinhVien(id2, 'het_hang');

      expect(await outbox.demSoLuongChoXuLy(), 2);
    },
  );
}
