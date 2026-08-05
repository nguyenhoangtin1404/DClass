import 'dart:async';

import 'package:flutter/material.dart';

import '../sync/connectivity_watcher.dart';
import '../sync/sync_engine.dart';

/// AppBar action showing offline/syncing/pending-count status; tapping it
/// triggers a manual sync.
class SyncStatusBadge extends StatefulWidget {
  const SyncStatusBadge({super.key, required this.syncEngine});

  final SyncEngine syncEngine;

  @override
  State<SyncStatusBadge> createState() => _SyncStatusBadgeState();
}

class _SyncStatusBadgeState extends State<SyncStatusBadge> {
  final _ketNoi = ConnectivityWatcher();
  bool _coKetNoi = true;
  StreamSubscription<bool>? _dangKy;

  @override
  void initState() {
    super.initState();
    _ketNoi.coKetNoiHienTai().then((gt) {
      if (mounted) setState(() => _coKetNoi = gt);
    });
    _dangKy = _ketNoi.thayDoi.listen((gt) {
      if (mounted) setState(() => _coKetNoi = gt);
    });
  }

  @override
  void dispose() {
    _dangKy?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: widget.syncEngine,
      builder: (context, _) {
        if (widget.syncEngine.dangDongBo) {
          return const Padding(
            padding: EdgeInsets.all(16),
            child: SizedBox(
              width: 20,
              height: 20,
              child: CircularProgressIndicator(strokeWidth: 2),
            ),
          );
        }
        final soLuong = widget.syncEngine.soLuongChoXuLy;
        final Widget icon;
        final String nhan;
        if (!_coKetNoi) {
          icon = const Icon(Icons.cloud_off);
          nhan = 'Ngoại tuyến';
        } else if (soLuong > 0) {
          icon = Badge(
            label: Text('$soLuong'),
            child: const Icon(Icons.cloud_upload),
          );
          nhan = '$soLuong thao tác chưa đồng bộ - nhấn để đồng bộ ngay';
        } else {
          icon = const Icon(Icons.cloud_done);
          nhan = 'Đã đồng bộ';
        }
        return IconButton(
          icon: icon,
          tooltip: nhan,
          onPressed: () => widget.syncEngine.chaySync(),
        );
      },
    );
  }
}
