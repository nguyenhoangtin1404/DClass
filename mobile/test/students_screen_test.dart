import 'package:connectivity_plus_platform_interface/connectivity_plus_platform_interface.dart';
import 'package:dclass_mobile/db/app_database.dart';
import 'package:dclass_mobile/models/hoc_sinh.dart';
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

  Widget dungLen() {
    final outbox = OutboxRepository(db);
    return MaterialApp(
      home: StudentsScreen(
        danhMuc: DanhMucRepository(api: api, db: db),
        diem: DiemRepository(api: api, outbox: outbox),
        syncEngine: SyncEngine(
          api: api,
          outbox: outbox,
          danhMuc: DanhMucRepository(api: api, db: db),
        ),
      ),
    );
  }

  setUp(() async {
    ConnectivityPlatform.instance = FakeConnectivityPlatform();
    api = FakeDiemApi();
    db = await openAppDatabase(path: inMemoryDatabasePath);
  });

  // Uses two plain pump()s rather than pumpAndSettle() throughout this file:
  // SyncStatusBadge's connectivity icon never "settles" the way
  // pumpAndSettle() wants (same reason sync_status_badge_test.dart never
  // uses it either) - one pump lets the FutureBuilder's data through, the
  // second lets the resulting setState rebuild land.

  testWidgets('shows an empty state when there are no students', (
    tester,
  ) async {
    await tester.pumpWidget(dungLen());
    await tester.pump();
    await tester.pump();

    expect(find.text('Chưa có học sinh nào'), findsOneWidget);
  });

  testWidgets('lists students with their code, class, gender and DOB', (
    tester,
  ) async {
    api.hocSinhTraVe = [
      HocSinh(
        id: 1,
        ma: 'HS01',
        hoTen: 'Nguyen Van An',
        lopHocId: 1,
        tenLop: '4A',
        soDu: 12,
        gioiTinh: 'NAM',
        ngaySinh: '2016-05-12',
        stt: 3,
      ),
    ];
    await tester.pumpWidget(dungLen());
    await tester.pump();
    await tester.pump();

    expect(find.text('Nguyen Van An'), findsOneWidget);
    expect(find.textContaining('STT 3'), findsOneWidget);
    expect(find.textContaining('HS01'), findsOneWidget);
    expect(find.textContaining('4A'), findsOneWidget);
    expect(find.text('Nam · 12/05/2016'), findsOneWidget);
    expect(find.text('12 đ'), findsOneWidget);
  });

  testWidgets(
    'omits the gender/DOB line entirely when neither is known',
    (tester) async {
      api.hocSinhTraVe = [
        HocSinh(
          id: 1,
          ma: null,
          hoTen: 'Binh',
          lopHocId: null,
          tenLop: null,
          soDu: 0,
        ),
      ];
      await tester.pumpWidget(dungLen());
      await tester.pump();
      await tester.pump();

      expect(find.textContaining('·'), findsNothing);
    },
  );

  testWidgets('typing in the search box filters the visible list', (
    tester,
  ) async {
    api.hocSinhTraVe = [
      HocSinh(id: 1, ma: null, hoTen: 'An', lopHocId: 1, tenLop: '4A', soDu: 0),
      HocSinh(
          id: 2, ma: null, hoTen: 'Binh', lopHocId: 1, tenLop: '4A', soDu: 0),
    ];
    await tester.pumpWidget(dungLen());
    await tester.pump();
    await tester.pump();

    expect(find.text('An'), findsOneWidget);
    expect(find.text('Binh'), findsOneWidget);

    await tester.enterText(find.byType(TextField).first, 'an');
    await tester.pump(const Duration(milliseconds: 350));
    await tester.pump();
    await tester.pump();

    expect(find.text('An'), findsOneWidget);
    expect(find.text('Binh'), findsNothing);
  });

  testWidgets(
      'class filter pills only appear when there is more than one class',
      (tester) async {
    api.hocSinhTraVe = [
      HocSinh(id: 1, ma: null, hoTen: 'An', lopHocId: 1, tenLop: '4A', soDu: 0),
    ];
    await tester.pumpWidget(dungLen());
    await tester.pump();
    await tester.pump();

    expect(find.text('Tất cả lớp'), findsNothing);
  });

  testWidgets('tapping a class pill filters to that class', (tester) async {
    api.hocSinhTraVe = [
      HocSinh(id: 1, ma: null, hoTen: 'An', lopHocId: 1, tenLop: '4A', soDu: 0),
      HocSinh(
          id: 2, ma: null, hoTen: 'Binh', lopHocId: 2, tenLop: '4B', soDu: 0),
    ];
    await tester.pumpWidget(dungLen());
    await tester.pump();
    await tester.pump();

    expect(find.text('Tất cả lớp'), findsOneWidget);

    await tester.tap(find.text('4B'));
    await tester.pump();
    await tester.pump();

    expect(find.text('Binh'), findsOneWidget);
    expect(find.text('An'), findsNothing);
  });
}
