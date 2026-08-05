import 'package:dclass_mobile/db/app_database.dart';
import 'package:dclass_mobile/models/hoc_sinh.dart';
import 'package:dclass_mobile/outbox/hanh_dong_cho.dart';
import 'package:dclass_mobile/outbox/outbox_repository.dart';
import 'package:dclass_mobile/repositories/danh_muc_repository.dart';
import 'package:dclass_mobile/screens/failed_actions_screen.dart';
import 'package:dclass_mobile/sync/sync_engine.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:sqflite/sqflite.dart';
import 'package:sqflite_common_ffi/sqflite_ffi.dart';

import 'fakes/fake_diem_api.dart';

Future<int> _themHanhDongLoi(
  OutboxRepository outbox, {
  required int hocSinhId,
  required String maLoi,
}) async {
  final id = await outbox.themMoi(
    HanhDongCho(
      clientActionId: 'ca-$hocSinhId',
      loai: LoaiHanhDong.congDiem,
      hocSinhId: hocSinhId,
      lyDoId: 1,
      taoLuc: DateTime.now().toIso8601String(),
    ),
  );
  await outbox.danhDauLoiVinhVien(id, maLoi);
  return id;
}

void main() {
  late FakeDiemApi api;
  late Database db;
  late OutboxRepository outbox;
  late DanhMucRepository danhMuc;
  late SyncEngine syncEngine;

  setUp(() async {
    api = FakeDiemApi()
      ..hocSinhTraVe = [
        HocSinh(
          id: 1,
          ma: null,
          hoTen: 'Nguyễn An',
          lopHocId: 1,
          tenLop: '1A',
          soDu: 5,
        ),
      ];
    db = await openAppDatabase(path: inMemoryDatabasePath);
    outbox = OutboxRepository(db);
    danhMuc = DanhMucRepository(api: api, db: db);
    syncEngine = SyncEngine(api: api, outbox: outbox, danhMuc: danhMuc);
  });

  Widget boc() => MaterialApp(
        home: FailedActionsScreen(
          outbox: outbox,
          syncEngine: syncEngine,
          danhMuc: danhMuc,
        ),
      );

  testWidgets('shows an empty state when nothing failed permanently', (
    tester,
  ) async {
    await tester.pumpWidget(boc());
    await tester.pumpAndSettle();

    expect(find.text('Không có thao tác lỗi nào'), findsOneWidget);
  });

  testWidgets('lists a failed action with the student name and message', (
    tester,
  ) async {
    await _themHanhDongLoi(outbox, hocSinhId: 1, maLoi: 'khong_du_diem');
    await tester.pumpWidget(boc());
    await tester.pumpAndSettle();

    expect(find.text('Nguyễn An'), findsOneWidget);
    expect(find.text('Học sinh không đủ điểm để đổi quà'), findsOneWidget);
  });

  testWidgets('discarding a failed action removes it from the list', (
    tester,
  ) async {
    await _themHanhDongLoi(outbox, hocSinhId: 1, maLoi: 'het_hang');
    await tester.pumpWidget(boc());
    await tester.pumpAndSettle();

    await tester.tap(find.byIcon(Icons.delete_outline));
    await tester.pumpAndSettle();

    expect(find.text('Nguyễn An'), findsNothing);
    expect(find.text('Không có thao tác lỗi nào'), findsOneWidget);
  });
}
