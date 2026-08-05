import 'package:flutter/material.dart';

import '../api_client.dart';
import '../models/lich_su_giao_dich.dart';

/// Point-transaction history - a thin read-only view onto
/// `api/diem.php?hanh_dong=lich_su`. Network-only for now (no offline
/// cache): it's a "look back", not something a teacher needs mid-class with
/// no signal the way the student list/add-points flow is.
class HistoryScreen extends StatefulWidget {
  const HistoryScreen(
      {super.key, required this.api, this.hocSinhId, this.tieuDe});

  final DiemApi api;

  /// Restricts to one student's history; `null` shows every student's
  /// recent transactions (across the teacher's classes).
  final int? hocSinhId;
  final String? tieuDe;

  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  late Future<List<LichSuGiaoDich>> _lichSu;

  @override
  void initState() {
    super.initState();
    _lichSu = widget.api.layLichSu(hocSinhId: widget.hocSinhId);
  }

  Future<void> _lamMoi() async {
    setState(() {
      _lichSu = widget.api.layLichSu(hocSinhId: widget.hocSinhId);
    });
    await _lichSu;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.tieuDe ?? 'Lịch sử điểm')),
      body: RefreshIndicator(
        onRefresh: _lamMoi,
        child: FutureBuilder<List<LichSuGiaoDich>>(
          future: _lichSu,
          builder: (context, snap) {
            if (snap.connectionState != ConnectionState.done) {
              return const Center(child: CircularProgressIndicator());
            }
            if (snap.hasError) {
              return ListView(
                children: [
                  const SizedBox(height: 64),
                  Center(child: Text('Lỗi: ${snap.error}')),
                ],
              );
            }
            final ds = snap.data ?? [];
            if (ds.isEmpty) {
              return ListView(
                children: const [
                  SizedBox(height: 64),
                  Center(child: Text('Chưa có giao dịch nào')),
                ],
              );
            }
            return ListView.separated(
              itemCount: ds.length,
              separatorBuilder: (_, __) => const Divider(height: 1),
              itemBuilder: (context, i) {
                final gd = ds[i];
                final laCong = gd.loai == 'CONG_DIEM';
                final moTa =
                    laCong ? (gd.lyDo ?? 'Cộng điểm') : (gd.qua ?? 'Đổi quà');
                final tieuDe =
                    widget.hocSinhId == null ? '${gd.hoTen} - $moTa' : moTa;
                final phuDe = [
                  gd.taoLuc,
                  if ((gd.ghiChu ?? '').isNotEmpty) gd.ghiChu,
                ].join(' - ');
                return ListTile(
                  leading: Icon(
                    laCong ? Icons.add_circle : Icons.card_giftcard,
                    color: laCong ? Colors.green : Colors.orange,
                  ),
                  title: Text(tieuDe),
                  subtitle: Text(phuDe),
                  trailing: Text(
                    '${gd.bienDiem > 0 ? '+' : ''}${gd.bienDiem}\ncòn ${gd.soDuSau}',
                    textAlign: TextAlign.right,
                  ),
                  isThreeLine: false,
                );
              },
            );
          },
        ),
      ),
    );
  }
}
