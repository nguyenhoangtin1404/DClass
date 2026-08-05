import 'package:flutter/material.dart';

import '../models/hoc_sinh.dart';
import '../models/ly_do.dart';
import '../models/qua_tang.dart';
import '../repositories/danh_muc_repository.dart';
import '../repositories/diem_repository.dart';
import '../sync/sync_engine.dart';
import '../widgets/sync_status_badge.dart';
import 'failed_actions_screen.dart';
import 'redeem_gift_screen.dart';

/// Student list with a quick "add points" action per row - the core
/// in-class flow the mobile app exists for (web stays the tool for
/// admin/reports/setup).
class StudentsScreen extends StatefulWidget {
  final DanhMucRepository danhMuc;
  final DiemRepository diem;
  final SyncEngine syncEngine;
  const StudentsScreen({
    super.key,
    required this.danhMuc,
    required this.diem,
    required this.syncEngine,
  });

  @override
  State<StudentsScreen> createState() => _StudentsScreenState();
}

class _StudentsScreenState extends State<StudentsScreen> {
  late Future<List<HocSinh>> _hocSinh;

  @override
  void initState() {
    super.initState();
    _hocSinh = widget.danhMuc.danhSachHocSinh();
  }

  Future<void> _lamMoi() async {
    await widget.syncEngine.chaySync();
    setState(() => _hocSinh = widget.danhMuc.danhSachHocSinh());
    await _hocSinh;
  }

  Future<void> _chonHanhDong(HocSinh hs) async {
    final hanhDong = await showModalBottomSheet<String>(
      context: context,
      builder: (ctx) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.add_circle_outline),
              title: const Text('Cộng điểm...'),
              onTap: () => Navigator.of(ctx).pop('cong_diem'),
            ),
            ListTile(
              leading: const Icon(Icons.card_giftcard),
              title: const Text('Đổi quà...'),
              onTap: () => Navigator.of(ctx).pop('doi_qua'),
            ),
          ],
        ),
      ),
    );
    if (!mounted || hanhDong == null) return;
    if (hanhDong == 'cong_diem') {
      await _chonLyDoVaCongDiem(hs);
    } else {
      await _chonQuaVaDoiDiem(hs);
    }
  }

  Future<void> _chonLyDoVaCongDiem(HocSinh hs) async {
    List<LyDo> lyDoList;
    try {
      lyDoList = await widget.danhMuc.danhSachLyDo();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Lỗi tải lý do: $e')));
      return;
    }
    if (!mounted) return;
    final lyDo = await showModalBottomSheet<LyDo>(
      context: context,
      builder: (ctx) => SafeArea(
        child: ListView(
          shrinkWrap: true,
          children: lyDoList
              .map(
                (ld) => ListTile(
                  title: Text(ld.tieuDe),
                  trailing: Text(
                    ld.bienDiem > 0 ? '+${ld.bienDiem}' : '${ld.bienDiem}',
                  ),
                  onTap: () => Navigator.of(ctx).pop(ld),
                ),
              )
              .toList(),
        ),
      ),
    );
    if (lyDo == null) return;
    try {
      final ketQua = await widget.diem.themCongDiem(
        hocSinhId: hs.id,
        lyDoId: lyDo.id,
      );
      if (!mounted) return;
      final thongBao = ketQua == KetQuaGhiDiem.daApDungNgay
          ? 'Đã cộng điểm cho ${hs.hoTen}'
          : 'Đã ghi nhận cho ${hs.hoTen} - sẽ đồng bộ khi có mạng';
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(thongBao)));
      await _lamMoi();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Lỗi: $e')));
    }
  }

  Future<void> _chonQuaVaDoiDiem(HocSinh hs) async {
    List<QuaTang> quaTangList;
    try {
      quaTangList = await widget.danhMuc.danhSachQuaTang();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Lỗi tải quà: $e')));
      return;
    }
    if (!mounted) return;
    final quaTang = await chonQuaTangDeDoi(context, quaTangList);
    if (quaTang == null) return;
    try {
      final ketQua = await widget.diem.themDoiQua(
        hocSinhId: hs.id,
        quaTangId: quaTang.id,
      );
      if (!mounted) return;
      final thongBao = ketQua == KetQuaGhiDiem.daApDungNgay
          ? 'Đã đổi ${quaTang.ten} cho ${hs.hoTen}'
          : 'Đã ghi nhận cho ${hs.hoTen} - sẽ đồng bộ khi có mạng';
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(thongBao)));
      await _lamMoi();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Lỗi: $e')));
    }
  }

  void _moThaoTacLoi() {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => FailedActionsScreen(
          outbox: widget.diem.outbox,
          syncEngine: widget.syncEngine,
          danhMuc: widget.danhMuc,
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Học sinh'),
        actions: [
          IconButton(
            icon: const Icon(Icons.error_outline),
            tooltip: 'Thao tác lỗi',
            onPressed: _moThaoTacLoi,
          ),
          SyncStatusBadge(syncEngine: widget.syncEngine),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _lamMoi,
        child: FutureBuilder<List<HocSinh>>(
          future: _hocSinh,
          builder: (context, snap) {
            if (snap.connectionState != ConnectionState.done) {
              return const Center(child: CircularProgressIndicator());
            }
            if (snap.hasError) {
              return Center(child: Text('Lỗi: ${snap.error}'));
            }
            final list = snap.data ?? [];
            if (list.isEmpty) {
              return const Center(child: Text('Chưa có học sinh nào'));
            }
            return ListView.separated(
              itemCount: list.length,
              separatorBuilder: (_, __) => const Divider(height: 1),
              itemBuilder: (context, i) {
                final hs = list[i];
                return ListTile(
                  title: Text(hs.hoTen),
                  subtitle: Text(hs.tenLop ?? ''),
                  trailing: Text('${hs.soDu} điểm'),
                  onTap: () => _chonHanhDong(hs),
                );
              },
            );
          },
        ),
      ),
    );
  }
}
