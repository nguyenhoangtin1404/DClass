import 'package:flutter/material.dart';

import '../pin_lock_storage.dart';
import '../screens/unlock_screen.dart';

/// Wraps the authenticated shell (`phien_dang_nhap.dart`) and shows
/// `UnlockScreen` on top of it - on cold start if the lock is on, and again
/// whenever the app returns to the foreground after being backgrounded for
/// at least [nguongThoiGian] (default 30s: long enough that a quick
/// app-switch to check a notification doesn't re-lock, short enough that
/// actually setting the phone down does). Mirrors how
/// `sync/app_lifecycle_sync_trigger.dart` watches [AppLifecycleState] -
/// the two observers are independent and coexist fine.
class AppLockGate extends StatefulWidget {
  const AppLockGate({
    super.key,
    required this.child,
    this.nguongThoiGian = const Duration(seconds: 30),
  });

  final Widget child;
  final Duration nguongThoiGian;

  @override
  State<AppLockGate> createState() => _AppLockGateState();
}

class _AppLockGateState extends State<AppLockGate> with WidgetsBindingObserver {
  DateTime? _tamDungLuc;
  bool _dangKhoa = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _kiemTraKhoaLucKhoiDong();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  Future<void> _kiemTraKhoaLucKhoiDong() async {
    if (await khoaDaBat() && mounted) setState(() => _dangKhoa = true);
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.paused ||
        state == AppLifecycleState.inactive) {
      _tamDungLuc ??= DateTime.now();
      return;
    }
    if (state != AppLifecycleState.resumed) return;
    final tamDungLuc = _tamDungLuc;
    _tamDungLuc = null;
    if (tamDungLuc == null) return;
    if (DateTime.now().difference(tamDungLuc) < widget.nguongThoiGian) return;
    khoaDaBat().then((bat) {
      if (bat && mounted) setState(() => _dangKhoa = true);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        widget.child,
        if (_dangKhoa)
          UnlockScreen(
            onMoKhoaThanhCong: () => setState(() => _dangKhoa = false),
          ),
      ],
    );
  }
}
