import 'package:flutter/material.dart';

import '../outbox/hanh_dong_cho.dart';
import '../outbox/outbox_repository.dart';
import '../repositories/danh_muc_repository.dart';
import '../sync/sync_engine.dart';
import '../utils/thong_bao_loi.dart';

class _DuLieuManHinh {
  _DuLieuManHinh(this.danhSach, this.tenTheoId);
  final List<HanhDongCho> danhSach;
  final Map<int, String> tenTheoId;
}

/// Lets a teacher review point actions that failed permanently (an inactive
/// reason, insufficient balance, an out-of-stock gift, ...) - see
/// SyncEngine's error classification. These are never auto-retried; a
/// teacher discards them or retries manually once whatever caused the
/// failure might have changed.
class FailedActionsScreen extends StatefulWidget {
  const FailedActionsScreen({
    super.key,
    required this.outbox,
    required this.syncEngine,
    required this.danhMuc,
  });

  final OutboxRepository outbox;
  final SyncEngine syncEngine;
  final DanhMucRepository danhMuc;

  @override
  State<FailedActionsScreen> createState() => _FailedActionsScreenState();
}

class _FailedActionsScreenState extends State<FailedActionsScreen> {
  late Future<_DuLieuManHinh> _duLieu;

  @override
  void initState() {
    super.initState();
    _duLieu = _nap();
  }

  Future<_DuLieuManHinh> _nap() async {
    final danhSach = await widget.outbox.layDanhSachLoiVinhVien();
    final hocSinh = await widget.danhMuc.danhSachHocSinh();
    final tenTheoId = {for (final hs in hocSinh) hs.id: hs.hoTen};
    return _DuLieuManHinh(danhSach, tenTheoId);
  }

  void _lamMoi() => setState(() => _duLieu = _nap());

  Future<void> _thuLai(int id) async {
    await widget.outbox.datLaiChoXuLy(id);
    await widget.syncEngine.chaySync();
    _lamMoi();
  }

  Future<void> _xoa(int id) async {
    await widget.outbox.xoa(id);
    _lamMoi();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Thao tác lỗi')),
      body: FutureBuilder<_DuLieuManHinh>(
        future: _duLieu,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }
          final duLieu = snap.data!;
          if (duLieu.danhSach.isEmpty) {
            return const Center(child: Text('Không có thao tác lỗi nào'));
          }
          return ListView.separated(
            itemCount: duLieu.danhSach.length,
            separatorBuilder: (_, __) => const Divider(height: 1),
            itemBuilder: (context, i) {
              final hd = duLieu.danhSach[i];
              final ten =
                  duLieu.tenTheoId[hd.hocSinhId] ?? 'Học sinh #${hd.hocSinhId}';
              return ListTile(
                title: Text(ten),
                subtitle: Text(thongBaoLoi(hd.loiMa)),
                trailing: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    IconButton(
                      icon: const Icon(Icons.refresh),
                      tooltip: 'Thử lại',
                      onPressed: () => _thuLai(hd.id!),
                    ),
                    IconButton(
                      icon: const Icon(Icons.delete_outline),
                      tooltip: 'Xóa',
                      onPressed: () => _xoa(hd.id!),
                    ),
                  ],
                ),
              );
            },
          );
        },
      ),
    );
  }
}
