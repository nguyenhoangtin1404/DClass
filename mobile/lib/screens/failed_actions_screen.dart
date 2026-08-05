import 'package:flutter/material.dart';

import '../outbox/hanh_dong_cho.dart';
import '../outbox/outbox_repository.dart';
import '../repositories/danh_muc_repository.dart';
import '../sync/sync_engine.dart';
import '../theme/dclass_colors.dart';
import '../utils/thong_bao_loi.dart';
import '../widgets/pill_button.dart';
import '../widgets/ribbon_header.dart';

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
      appBar: AppBar(
        title: const RibbonHeader(
          label: 'Thao tác lỗi',
          gradient: LinearGradient(colors: [Color(0xFFFFE0DF), Color(0xFFFFE9C9)]),
        ),
      ),
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
          return Column(
            children: [
              Expanded(
                child: ListView.builder(
                  padding: const EdgeInsets.fromLTRB(12, 10, 12, 4),
                  itemCount: duLieu.danhSach.length,
                  itemBuilder: (context, i) {
                    final hd = duLieu.danhSach[i];
                    final ten = duLieu.tenTheoId[hd.hocSinhId] ??
                        'Học sinh #${hd.hocSinhId}';
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: ListTile(
                        title: Text(ten, style: const TextStyle(fontWeight: FontWeight.w700)),
                        subtitle: Text(thongBaoLoi(hd.loiMa)),
                        isThreeLine: true,
                        trailing: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            PillButton.icon(
                              icon: const Icon(Icons.refresh),
                              color: DClassColors.primary,
                              onTap: () => _thuLai(hd.id!),
                            ),
                            const SizedBox(width: 6),
                            PillButton.icon(
                              icon: const Icon(Icons.delete_outline),
                              color: DClassColors.danger,
                              onTap: () => _xoa(hd.id!),
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
              ),
              const Padding(
                padding: EdgeInsets.fromLTRB(20, 0, 20, 16),
                child: Text(
                  'Các thao tác này sẽ không tự đồng bộ lại - chọn thử lại hoặc xoá.',
                  textAlign: TextAlign.center,
                  style: TextStyle(color: DClassColors.muted, fontSize: 12.5),
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}
