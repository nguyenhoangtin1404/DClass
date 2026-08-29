import 'package:dclass_mobile/api_client.dart';
import 'package:dclass_mobile/db/app_database.dart';
import 'package:dclass_mobile/outbox/hanh_dong_cho.dart';
import 'package:dclass_mobile/outbox/outbox_repository.dart';
import 'package:dclass_mobile/repositories/danh_muc_repository.dart';
import 'package:dclass_mobile/sync/sync_engine.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:sqflite/sqflite.dart';
import 'package:sqflite_common_ffi/sqflite_ffi.dart';

import 'fakes/fake_diem_api.dart';

HanhDongCho _hanhDongCongDiem({
  required String clientActionId,
  int hocSinhId = 1,
}) {
  return HanhDongCho(
    clientActionId: clientActionId,
    loai: LoaiHanhDong.congDiem,
    hocSinhId: hocSinhId,
    lyDoId: 1,
    taoLuc: DateTime.now().toIso8601String(),
  );
}

void main() {
  late FakeDiemApi api;
  late Database db;
  late OutboxRepository outbox;
  late DanhMucRepository danhMuc;
  late SyncEngine engine;

  setUp(() async {
    api = FakeDiemApi();
    db = await openAppDatabase(path: inMemoryDatabasePath);
    outbox = OutboxRepository(db);
    danhMuc = DanhMucRepository(api: api, db: db);
    engine = SyncEngine(api: api, outbox: outbox, danhMuc: danhMuc);
  });

  test('replays queued actions sequentially, in insertion order', () async {
    await outbox.themMoi(_hanhDongCongDiem(clientActionId: 'ca-1'));
    await outbox.themMoi(_hanhDongCongDiem(clientActionId: 'ca-2'));
    await outbox.themMoi(_hanhDongCongDiem(clientActionId: 'ca-3'));
    api.hangDoiKetQua.addAll([
      {'so_du': 1},
      {'so_du': 2},
      {'so_du': 3},
    ]);

    await engine.chaySync();

    expect(api.lenhDaGoi, ['congDiem:ca-1', 'congDiem:ca-2', 'congDiem:ca-3']);
    expect(await outbox.layDanhSachChoXuLy(), isEmpty);
  });

  test('success reconciles the cached balance from the response', () async {
    await db.insert('hoc_sinh_cache', {
      'id': 1,
      'ho_ten': 'An',
      'so_du_may_chu': 0,
    });
    await outbox.themMoi(_hanhDongCongDiem(clientActionId: 'ca-1'));
    api.hangDoiKetQua.add({'so_du': 42});

    await engine.chaySync();

    final rows = await db.query('hoc_sinh_cache', where: 'id = 1');
    expect(rows.single['so_du_may_chu'], 42);
    expect(await outbox.layDanhSachChoXuLy(), isEmpty);
  });

  test(
    'a permanent failure is marked loi_vinh_vien and the batch continues',
    () async {
      await outbox.themMoi(_hanhDongCongDiem(clientActionId: 'ca-1'));
      await outbox.themMoi(_hanhDongCongDiem(clientActionId: 'ca-2'));
      api.hangDoiKetQua.addAll([
        ApiException('het_hang'),
        {'so_du': 9},
      ]);

      await engine.chaySync();

      expect(api.lenhDaGoi, ['congDiem:ca-1', 'congDiem:ca-2']);
      final loi = await outbox.layDanhSachLoiVinhVien();
      expect(loi.single.clientActionId, 'ca-1');
      expect(loi.single.loiMa, 'het_hang');
      expect(await outbox.layDanhSachChoXuLy(), isEmpty); // ca-2 applied
    },
  );

  test(
    'a transient failure re-queues the item and halts the rest of the batch',
    () async {
      await outbox.themMoi(_hanhDongCongDiem(clientActionId: 'ca-1'));
      await outbox.themMoi(_hanhDongCongDiem(clientActionId: 'ca-2'));
      api.hangDoiKetQua.add(Exception('SocketException: mat ket noi'));
      // Only one scripted result: proves ca-2 is never attempted.

      await engine.chaySync();

      expect(api.lenhDaGoi, ['congDiem:ca-1']); // ca-2 not attempted
      final choXuLy = await outbox.layDanhSachChoXuLy();
      expect(choXuLy.map((e) => e.clientActionId), ['ca-1', 'ca-2']);
      expect(choXuLy.first.trangThai, TrangThaiHanhDong.choXuLy);
      expect(choXuLy.first.soLanThu, 1);
      expect(choXuLy.last.soLanThu, 0); // untouched
    },
  );

  test('a retried action reuses the same client_action_id', () async {
    await outbox.themMoi(_hanhDongCongDiem(clientActionId: 'ca-1'));
    api.hangDoiKetQua.add(Exception('timeout'));
    await engine.chaySync(); // fails transiently, re-queued

    api.hangDoiKetQua.add({'so_du': 5});
    await engine.chaySync(); // retried

    expect(api.lenhDaGoi, ['congDiem:ca-1', 'congDiem:ca-1']);
    expect(await outbox.layDanhSachChoXuLy(), isEmpty);
  });

  test('concurrent chaySync calls collapse into a single pass', () async {
    await outbox.themMoi(_hanhDongCongDiem(clientActionId: 'ca-1'));
    api.hangDoiKetQua.add({'so_du': 1});

    final f1 = engine.chaySync();
    final f2 = engine.chaySync();
    await Future.wait([f1, f2]);

    expect(api.lenhDaGoi, hasLength(1));
  });

  test(
    'a dead token halts the batch, re-queues the item, and sets '
    'phienHetHan instead of marking loi_vinh_vien',
    () async {
      await outbox.themMoi(_hanhDongCongDiem(clientActionId: 'ca-1'));
      await outbox.themMoi(_hanhDongCongDiem(clientActionId: 'ca-2'));
      api.hangDoiKetQua.add(ApiException('loi_http_401'));
      // Only one scripted result: proves ca-2 is never attempted.

      await engine.chaySync();

      expect(api.lenhDaGoi, ['congDiem:ca-1']); // ca-2 not attempted
      expect(engine.phienHetHan, isTrue);
      expect(await outbox.layDanhSachLoiVinhVien(), isEmpty);
      final choXuLy = await outbox.layDanhSachChoXuLy();
      expect(choXuLy.map((e) => e.clientActionId), ['ca-1', 'ca-2']);
    },
  );

  test(
    'a leftover dang_gui row is swept back to pending on the next run',
    () async {
      final id = await outbox.themMoi(
        _hanhDongCongDiem(clientActionId: 'ca-1'),
      );
      await outbox.danDauDangGui(id); // simulate an app kill mid-send

      api.hangDoiKetQua.add({'so_du': 7});
      await engine.chaySync();

      expect(api.lenhDaGoi, ['congDiem:ca-1']);
      expect(await outbox.layDanhSachChoXuLy(), isEmpty);
    },
  );
}
