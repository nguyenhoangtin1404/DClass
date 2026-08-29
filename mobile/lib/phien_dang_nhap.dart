import 'dart:async';

import 'package:flutter/material.dart';
import 'package:sqflite/sqflite.dart';

import 'api_client.dart';
import 'lock/app_lock_gate.dart';
import 'outbox/outbox_repository.dart';
import 'repositories/danh_muc_repository.dart';
import 'repositories/diem_repository.dart';
import 'screens/students_screen.dart';
import 'session.dart';
import 'sync/app_lifecycle_sync_trigger.dart';
import 'sync/connectivity_watcher.dart';
import 'sync/sync_engine.dart';

/// Everything a logged-in session needs: repositories, the sync engine, its
/// connectivity + app-resume triggers, and the student list. Built once
/// [client]/[db] are ready - both entry points (cold start via `main.dart`,
/// and a fresh login via `login_screen.dart`) already have both in hand by
/// the time they reach this widget.
class PhienDangNhap extends StatefulWidget {
  const PhienDangNhap({super.key, required this.client, required this.db});

  final ApiClient client;
  final Database db;

  @override
  State<PhienDangNhap> createState() => _PhienDangNhapState();
}

class _PhienDangNhapState extends State<PhienDangNhap> {
  late final DanhMucRepository _danhMuc;
  late final DiemRepository _diem;
  late final SyncEngine _syncEngine;
  StreamSubscription<bool>? _dangKyKetNoi;

  @override
  void initState() {
    super.initState();
    final outbox = OutboxRepository(widget.db);
    _danhMuc = DanhMucRepository(api: widget.client, db: widget.db);
    _diem = DiemRepository(api: widget.client, outbox: outbox);
    _syncEngine = SyncEngine(
      api: widget.client,
      outbox: outbox,
      danhMuc: _danhMuc,
    );
    _syncEngine.addListener(_kiemTraPhienHetHan);
    _dangKyKetNoi = ConnectivityWatcher().thayDoi.listen((coKetNoi) {
      if (coKetNoi) _syncEngine.chaySync();
    });
    // Catch up on anything queued from a previous session on startup too.
    _syncEngine.chaySync();
  }

  /// A sync pass (possibly triggered in the background - connectivity
  /// regained, app resumed - not just a manual refresh) can discover the
  /// saved token is dead. Force the same logout the read/write paths use so
  /// the teacher isn't left staring at an app that looks fine but can no
  /// longer actually sync.
  void _kiemTraPhienHetHan() {
    if (_syncEngine.phienHetHan && mounted) {
      dangXuat(
        context,
        thongBao: 'Phiên đăng nhập đã hết hạn hoặc bị thu hồi. '
            'Vui lòng đăng nhập lại.',
      );
    }
  }

  @override
  void dispose() {
    _syncEngine.removeListener(_kiemTraPhienHetHan);
    _dangKyKetNoi?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AppLifecycleSyncTrigger(
      syncEngine: _syncEngine,
      child: AppLockGate(
        child: StudentsScreen(
          danhMuc: _danhMuc,
          diem: _diem,
          syncEngine: _syncEngine,
        ),
      ),
    );
  }
}
