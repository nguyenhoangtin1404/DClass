import 'package:dclass_mobile/api_client.dart';
import 'package:dclass_mobile/db/app_database.dart';
import 'package:dclass_mobile/outbox/outbox_repository.dart';
import 'package:dclass_mobile/repositories/diem_repository.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:sqflite_common_ffi/sqflite_ffi.dart';

import 'fakes/fake_diem_api.dart';

void main() {
  late FakeDiemApi api;
  late OutboxRepository outbox;
  late DiemRepository repo;

  setUp(() async {
    api = FakeDiemApi();
    final db = await openAppDatabase(path: inMemoryDatabasePath);
    outbox = OutboxRepository(db);
    repo = DiemRepository(api: api, outbox: outbox);
  });

  test('online success: applies immediately, nothing queued', () async {
    api.hangDoiKetQua.add({'so_du': 15});

    final ketQua = await repo.themCongDiem(hocSinhId: 1, lyDoId: 2);

    expect(ketQua, KetQuaGhiDiem.daApDungNgay);
    expect(await outbox.layDanhSachChoXuLy(), isEmpty);
    expect(api.lenhDaGoi, hasLength(1));
  });

  test('transient failure: queues the action instead of throwing', () async {
    api.hangDoiKetQua.add(Exception('SocketException: mat ket noi'));

    final ketQua = await repo.themCongDiem(hocSinhId: 1, lyDoId: 2);

    expect(ketQua, KetQuaGhiDiem.dangCho);
    final ds = await outbox.layDanhSachChoXuLy();
    expect(ds, hasLength(1));
    expect(ds.single.hocSinhId, 1);
    expect(ds.single.lyDoId, 2);
  });

  test('permanent failure: rethrows and does not queue', () async {
    api.hangDoiKetQua.add(ApiException('het_hang'));

    await expectLater(
      () => repo.themDoiQua(hocSinhId: 1, quaTangId: 5),
      throwsA(isA<ApiException>()),
    );
    expect(await outbox.layDanhSachChoXuLy(), isEmpty);
  });

  test(
    'the same clientActionId used in the API call is stored on the queued row',
    () async {
      api.hangDoiKetQua.add(Exception('timeout'));
      await repo.themCongDiem(hocSinhId: 1, lyDoId: 2);

      final goiVoi = api.lenhDaGoi.single.split(':').last;
      final ds = await outbox.layDanhSachChoXuLy();
      expect(ds.single.clientActionId, goiVoi);
    },
  );
}
