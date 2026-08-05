import 'dart:math';

import 'package:flutter/material.dart';

import '../api_client.dart';
import '../models/hoc_sinh.dart';
import '../models/ly_do.dart';
import '../repositories/danh_muc_repository.dart';

/// Student list with a quick "add points" action per row - the core
/// in-class flow the mobile app exists for (web stays the tool for
/// admin/reports/setup).
class StudentsScreen extends StatefulWidget {
  final ApiClient client;
  final DanhMucRepository danhMuc;
  const StudentsScreen({
    super.key,
    required this.client,
    required this.danhMuc,
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
    setState(() => _hocSinh = widget.danhMuc.danhSachHocSinh());
    await _hocSinh;
  }

  String _taoClientActionId() {
    final r = Random();
    return '${DateTime.now().microsecondsSinceEpoch}-${r.nextInt(1 << 32)}';
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
      await widget.client.congDiem(
        hocSinhId: hs.id,
        lyDoId: lyDo.id,
        clientActionId: _taoClientActionId(),
      );
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Đã cộng điểm cho ${hs.hoTen}')));
      await _lamMoi();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Lỗi: $e')));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Học sinh')),
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
                  onTap: () => _chonLyDoVaCongDiem(hs),
                );
              },
            );
          },
        ),
      ),
    );
  }
}
