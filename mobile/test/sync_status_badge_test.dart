import 'package:connectivity_plus_platform_interface/connectivity_plus_platform_interface.dart';
import 'package:dclass_mobile/db/app_database.dart';
import 'package:dclass_mobile/outbox/outbox_repository.dart';
import 'package:dclass_mobile/repositories/danh_muc_repository.dart';
import 'package:dclass_mobile/sync/sync_engine.dart';
import 'package:dclass_mobile/widgets/sync_status_badge.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:sqflite/sqflite.dart';
import 'package:sqflite_common_ffi/sqflite_ffi.dart';

import 'fakes/fake_connectivity_platform.dart';
import 'fakes/fake_diem_api.dart';

void main() {
  late FakeDiemApi api;
  late Database db;
  late SyncEngine syncEngine;
  late FakeConnectivityPlatform ketNoi;

  setUp(() async {
    ketNoi = FakeConnectivityPlatform();
    ConnectivityPlatform.instance = ketNoi;
    api = FakeDiemApi();
    db = await openAppDatabase(path: inMemoryDatabasePath);
    final danhMuc = DanhMucRepository(api: api, db: db);
    final outbox = OutboxRepository(db);
    syncEngine = SyncEngine(api: api, outbox: outbox, danhMuc: danhMuc);
  });

  Widget boc() => MaterialApp(
        home: Scaffold(
          appBar: AppBar(actions: [SyncStatusBadge(syncEngine: syncEngine)]),
        ),
      );

  testWidgets('shows a synced icon when there is nothing pending', (
    tester,
  ) async {
    await tester.pumpWidget(boc());
    await tester.pump();

    expect(find.byIcon(Icons.cloud_done), findsOneWidget);
    expect(find.byTooltip('Đã đồng bộ'), findsOneWidget);
  });

  testWidgets('shows the pending count badge when actions are queued', (
    tester,
  ) async {
    syncEngine.soLuongChoXuLy = 3;
    syncEngine.notifyListeners();
    await tester.pumpWidget(boc());
    await tester.pump();

    expect(find.byIcon(Icons.cloud_upload), findsOneWidget);
    expect(find.text('3'), findsOneWidget);
  });

  testWidgets('shows an offline icon when there is no connectivity', (
    tester,
  ) async {
    ketNoi.ketQua = [ConnectivityResult.none];
    await tester.pumpWidget(boc());
    await tester.pump();

    expect(find.byIcon(Icons.cloud_off), findsOneWidget);
    expect(find.byTooltip('Ngoại tuyến'), findsOneWidget);
  });

  testWidgets('shows a spinner while a sync pass is running', (tester) async {
    syncEngine.dangDongBo = true;
    syncEngine.notifyListeners();
    await tester.pumpWidget(boc());
    await tester.pump();

    expect(find.byType(CircularProgressIndicator), findsOneWidget);
    expect(find.byIcon(Icons.cloud_done), findsNothing);
  });
}
