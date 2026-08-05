import 'package:connectivity_plus_platform_interface/connectivity_plus_platform_interface.dart';
import 'package:dclass_mobile/db/app_database.dart';
import 'package:dclass_mobile/models/hoc_sinh.dart';
import 'package:dclass_mobile/models/ly_do.dart';
import 'package:dclass_mobile/outbox/outbox_repository.dart';
import 'package:dclass_mobile/repositories/danh_muc_repository.dart';
import 'package:dclass_mobile/repositories/diem_repository.dart';
import 'package:dclass_mobile/screens/students_screen.dart';
import 'package:dclass_mobile/sync/sync_engine.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:sqflite/sqflite.dart';
import 'package:sqflite_common_ffi/sqflite_ffi.dart';

import 'fakes/fake_connectivity_platform.dart';
import 'fakes/fake_diem_api.dart';

void main() {
  late FakeDiemApi api;
  late Database db;
  late DanhMucRepository danhMuc;
  late DiemRepository diem;
  late SyncEngine syncEngine;

  setUp(() async {
    ConnectivityPlatform.instance = FakeConnectivityPlatform();
    api = FakeDiemApi()
      ..hocSinhTraVe = [
        HocSinh(
          id: 1,
          ma: 'HS1',
          hoTen: 'Nguyễn An',
          lopHocId: 1,
          tenLop: '1A',
          soDu: 5,
        ),
      ]
      ..lyDoTraVe = [LyDo(id: 1, tieuDe: 'Giúp bạn', bienDiem: 2)];
    db = await openAppDatabase(path: inMemoryDatabasePath);
    danhMuc = DanhMucRepository(api: api, db: db);
    final outbox = OutboxRepository(db);
    diem = DiemRepository(api: api, outbox: outbox);
    syncEngine = SyncEngine(api: api, outbox: outbox, danhMuc: danhMuc);
  });

  Widget boc() => MaterialApp(
        home: StudentsScreen(
            danhMuc: danhMuc, diem: diem, syncEngine: syncEngine),
      );

  testWidgets('lists students loaded from the repository', (tester) async {
    await tester.pumpWidget(boc());
    await tester.pumpAndSettle();

    expect(find.text('Nguyễn An'), findsOneWidget);
    expect(find.text('5 điểm'), findsOneWidget);
  });

  testWidgets('tapping a student opens the action sheet', (tester) async {
    await tester.pumpWidget(boc());
    await tester.pumpAndSettle();

    await tester.tap(find.text('Nguyễn An'));
    await tester.pumpAndSettle();

    expect(find.text('Cộng điểm...'), findsOneWidget);
    expect(find.text('Đổi quà...'), findsOneWidget);
    expect(find.text('Xem lịch sử...'), findsOneWidget);
  });

  testWidgets('adding points via a reason shows a confirmation snackbar', (
    tester,
  ) async {
    api.hangDoiKetQua.add({'so_du': 7});
    await tester.pumpWidget(boc());
    await tester.pumpAndSettle();

    await tester.tap(find.text('Nguyễn An'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Cộng điểm...'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Giúp bạn'));
    await tester.pumpAndSettle();

    expect(find.text('Đã cộng điểm cho Nguyễn An'), findsOneWidget);
  });

  testWidgets('shows an empty state when there are no students', (
    tester,
  ) async {
    api.hocSinhTraVe = [];
    await tester.pumpWidget(boc());
    await tester.pumpAndSettle();

    expect(find.text('Chưa có học sinh nào'), findsOneWidget);
  });
}
