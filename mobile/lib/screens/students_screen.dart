import 'dart:async';

import 'package:flutter/material.dart';

import '../loi_phan_loai.dart';
import '../models/hoc_sinh.dart';
import '../models/ly_do.dart';
import '../models/qua_tang.dart';
import '../repositories/danh_muc_repository.dart';
import '../repositories/diem_repository.dart';
import '../session.dart';
import '../sync/sync_engine.dart';
import '../theme/dclass_colors.dart';
import '../widgets/pill_button.dart';
import '../widgets/ribbon_header.dart';
import '../widgets/sync_status_badge.dart';
import 'bao_mat_screen.dart';
import 'failed_actions_screen.dart';
import 'history_screen.dart';
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
  final _timKiemCtrl = TextEditingController();
  Timer? _hoanTimKiem;
  String _tuKhoa = '';
  int? _lopLoc;
  List<(int, String)> _dsLop = [];

  @override
  void initState() {
    super.initState();
    _hocSinh = _taiHocSinh();
    _timKiemCtrl.addListener(_onThayDoiTimKiem);
  }

  @override
  void dispose() {
    _hoanTimKiem?.cancel();
    _timKiemCtrl.dispose();
    super.dispose();
  }

  void _onThayDoiTimKiem() {
    _hoanTimKiem?.cancel();
    _hoanTimKiem = Timer(const Duration(milliseconds: 300), () {
      setState(() {
        _tuKhoa = _timKiemCtrl.text.trim();
        _hocSinh = _taiHocSinh();
      });
    });
  }

  /// "Nam · 12/05/2016" kiểu hiển thị - cùng cách đọc `gioi_tinh`/`ngay_sinh`
  /// như `hienThongTinDep()` bên web (`public/trang_chinh.php`).
  String _moTaGioiTinhNgaySinh(HocSinh hs) {
    final gioi = (hs.gioiTinh ?? '').toUpperCase();
    final gioiLbl = gioi == 'NAM'
        ? 'Nam'
        : gioi == 'NU'
            ? 'Nữ'
            : gioi == 'KHAC'
                ? 'Khác'
                : '';
    final raw = (hs.ngaySinh ?? '');
    final ngay = raw.length >= 10 ? raw.substring(0, 10) : raw;
    String ngayLbl = '';
    if (RegExp(r'^\d{4}-\d{2}-\d{2}$').hasMatch(ngay)) {
      final p = ngay.split('-');
      ngayLbl = '${p[2]}/${p[1]}/${p[0]}';
    }
    return [gioiLbl, ngayLbl].where((s) => s.isNotEmpty).join(' · ');
  }

  /// Wraps a repository read: a dead/revoked token ([laLoiPhienHetHan])
  /// ends the local session and sends the user back to login instead of
  /// letting the caller's normal error-handling silently mask it (e.g. a
  /// read falling back to stale cache, or a snackbar the teacher might not
  /// connect to "I need to log in again").
  Future<T?> _voiKiemTraPhien<T>(Future<T> Function() hanhDong) async {
    try {
      return await hanhDong();
    } catch (loi) {
      if (laLoiPhienHetHan(loi) && mounted) {
        await dangXuat(
          context,
          thongBao: 'Phiên đăng nhập đã hết hạn hoặc bị thu hồi. '
              'Vui lòng đăng nhập lại.',
        );
        return null;
      }
      rethrow;
    }
  }

  Future<List<HocSinh>> _taiHocSinh() async {
    final ds = await _voiKiemTraPhien(
      () => widget.danhMuc.danhSachHocSinh(tuKhoa: _tuKhoa, lopHocId: _lopLoc),
    );
    if (ds == null) return [];
    final dsLop = await widget.danhMuc.danhSachLop();
    if (mounted) setState(() => _dsLop = dsLop);
    return ds;
  }

  Future<void> _lamMoi() async {
    await widget.syncEngine.chaySync();
    setState(() => _hocSinh = _taiHocSinh());
    await _hocSinh;
  }

  Future<void> _dangXuat() async {
    final xacNhan = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Đăng xuất'),
        content: const Text('Đăng xuất khỏi tài khoản này trên thiết bị?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: const Text('Huỷ'),
          ),
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            child: const Text('Đăng xuất'),
          ),
        ],
      ),
    );
    if (xacNhan == true && mounted) await dangXuat(context);
  }

  Future<void> _chonHanhDong(HocSinh hs) async {
    final hanhDong = await showModalBottomSheet<String>(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const SizedBox(height: 8),
            _sheetGrabber(),
            _sheetHeader(hs),
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
            ListTile(
              leading: const Icon(Icons.history),
              title: const Text('Xem lịch sử...'),
              onTap: () => Navigator.of(ctx).pop('lich_su'),
            ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
    if (!mounted || hanhDong == null) return;
    if (hanhDong == 'cong_diem') {
      await _chonLyDoVaCongDiem(hs);
    } else if (hanhDong == 'doi_qua') {
      await _chonQuaVaDoiDiem(hs);
    } else if (hanhDong == 'lich_su') {
      _moLichSu(hocSinhId: hs.id, tieuDe: 'Lịch sử - ${hs.hoTen}');
    }
  }

  Widget _sheetGrabber() {
    return Container(
      width: 36,
      height: 4,
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: DClassColors.listBorder,
        borderRadius: BorderRadius.circular(4),
      ),
    );
  }

  Widget _sheetHeader(HocSinh hs) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
      child: Row(
        children: [
          CircleAvatar(
            backgroundColor: DClassColors.listBg,
            foregroundColor: DClassColors.primary,
            backgroundImage: (hs.anhDaiDienUrl ?? '').isEmpty
                ? null
                : NetworkImage(hs.anhDaiDienUrl!),
            child: (hs.anhDaiDienUrl ?? '').isEmpty
                ? Text(hs.hoTen.isEmpty ? '?' : hs.hoTen[0].toUpperCase())
                : null,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(hs.hoTen,
                    style: const TextStyle(fontWeight: FontWeight.w800)),
                Text(
                  'Số dư hiện tại · ${hs.soDu} điểm',
                  style: const TextStyle(
                      color: DClassColors.muted, fontSize: 12.5),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _chonLyDoVaCongDiem(HocSinh hs) async {
    List<LyDo>? lyDoList;
    try {
      lyDoList = await _voiKiemTraPhien(() => widget.danhMuc.danhSachLyDo());
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Lỗi tải lý do: $e')));
      return;
    }
    if (lyDoList == null || !mounted) return;
    final lyDo = await showModalBottomSheet<LyDo>(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(child: _sheetGrabber()),
              _sheetHeader(hs),
              const SizedBox(height: 4),
              const Text(
                'CHỌN LÝ DO CỘNG / TRỪ ĐIỂM',
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w800,
                  letterSpacing: .4,
                  color: DClassColors.muted,
                ),
              ),
              const SizedBox(height: 10),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: lyDoList!.map((ld) {
                  final duong = ld.bienDiem > 0;
                  final mau =
                      duong ? DClassColors.success : DClassColors.danger;
                  final nhan =
                      '${ld.tieuDe} ${ld.bienDiem > 0 ? '+' : ''}${ld.bienDiem}';
                  return PillButton(
                    label: nhan,
                    color: mau,
                    onTap: () => Navigator.of(ctx).pop(ld),
                  );
                }).toList(),
              ),
            ],
          ),
        ),
      ),
    );
    if (lyDo == null) return;
    try {
      final ketQua = await _voiKiemTraPhien(
        () => widget.diem.themCongDiem(hocSinhId: hs.id, lyDoId: lyDo.id),
      );
      if (ketQua == null || !mounted) return;
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
    List<QuaTang>? quaTangList;
    try {
      quaTangList = await _voiKiemTraPhien(
        () => widget.danhMuc.danhSachQuaTang(),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Lỗi tải quà: $e')));
      return;
    }
    if (quaTangList == null || !mounted) return;
    final quaTang = await chonQuaTangDeDoi(context, quaTangList);
    if (quaTang == null) return;
    try {
      final ketQua = await _voiKiemTraPhien(
        () => widget.diem.themDoiQua(hocSinhId: hs.id, quaTangId: quaTang.id),
      );
      if (ketQua == null || !mounted) return;
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

  void _moBaoMat() {
    Navigator.of(
      context,
    ).push(MaterialPageRoute(builder: (_) => const BaoMatScreen()));
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

  void _moLichSu({int? hocSinhId, String? tieuDe}) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => HistoryScreen(
          api: widget.danhMuc.api,
          hocSinhId: hocSinhId,
          tieuDe: tieuDe,
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
            icon: const Icon(Icons.history),
            tooltip: 'Lịch sử điểm',
            onPressed: () => _moLichSu(tieuDe: 'Lịch sử điểm'),
          ),
          IconButton(
            icon: const Icon(Icons.error_outline),
            tooltip: 'Thao tác lỗi',
            onPressed: _moThaoTacLoi,
          ),
          SyncStatusBadge(syncEngine: widget.syncEngine),
          IconButton(
            icon: const Icon(Icons.security),
            tooltip: 'Bảo mật',
            onPressed: _moBaoMat,
          ),
          IconButton(
            icon: const Icon(Icons.logout),
            tooltip: 'Đăng xuất',
            onPressed: _dangXuat,
          ),
        ],
      ),
      body: Column(
        children: [
          const Padding(
            padding: EdgeInsets.fromLTRB(12, 12, 12, 0),
            child: Align(
              alignment: Alignment.centerLeft,
              child: RibbonHeader(label: 'Học sinh'),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(12, 12, 12, 0),
            child: TextField(
              controller: _timKiemCtrl,
              decoration: const InputDecoration(
                prefixIcon: Icon(Icons.search),
                hintText: 'Tìm theo tên hoặc mã học sinh...',
                isDense: true,
              ),
            ),
          ),
          if (_dsLop.length > 1)
            SizedBox(
              height: 52,
              child: ListView(
                scrollDirection: Axis.horizontal,
                padding:
                    const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                children: [
                  Padding(
                    padding: const EdgeInsets.only(right: 8),
                    child: PillButton(
                      label: 'Tất cả lớp',
                      color: DClassColors.primary,
                      selected: _lopLoc == null,
                      onTap: () => setState(() {
                        _lopLoc = null;
                        _hocSinh = _taiHocSinh();
                      }),
                    ),
                  ),
                  for (final (id, ten) in _dsLop)
                    Padding(
                      padding: const EdgeInsets.only(right: 8),
                      child: PillButton(
                        label: ten,
                        color: DClassColors.primary,
                        selected: _lopLoc == id,
                        onTap: () => setState(() {
                          _lopLoc = id;
                          _hocSinh = _taiHocSinh();
                        }),
                      ),
                    ),
                ],
              ),
            ),
          Expanded(
            child: RefreshIndicator(
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
                  return ListView.builder(
                    padding: const EdgeInsets.fromLTRB(12, 10, 12, 12),
                    itemCount: list.length,
                    itemBuilder: (context, i) {
                      final hs = list[i];
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 8),
                        child: ListTile(
                          leading: CircleAvatar(
                            backgroundColor: DClassColors.listBg,
                            foregroundColor: DClassColors.primary,
                            backgroundImage: (hs.anhDaiDienUrl ?? '').isEmpty
                                ? null
                                : NetworkImage(hs.anhDaiDienUrl!),
                            child: (hs.anhDaiDienUrl ?? '').isEmpty
                                ? Text(
                                    hs.hoTen.isEmpty
                                        ? '?'
                                        : hs.hoTen[0].toUpperCase(),
                                  )
                                : null,
                          ),
                          title: Text(
                            hs.hoTen,
                            style: const TextStyle(fontWeight: FontWeight.w700),
                          ),
                          subtitle: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Text(
                                [
                                  if (hs.stt != null) 'STT ${hs.stt}',
                                  if ((hs.ma ?? '').isNotEmpty) hs.ma,
                                  hs.tenLop ?? '',
                                ]
                                    .where((s) => (s ?? '').isNotEmpty)
                                    .join(' · '),
                              ),
                              if (_moTaGioiTinhNgaySinh(hs).isNotEmpty)
                                Text(
                                  _moTaGioiTinhNgaySinh(hs),
                                  style: const TextStyle(
                                      fontSize: 12, color: Colors.grey),
                                ),
                            ],
                          ),
                          trailing: StatusBadge.warning(label: '${hs.soDu} đ'),
                          onTap: () => _chonHanhDong(hs),
                        ),
                      );
                    },
                  );
                },
              ),
            ),
          ),
        ],
      ),
    );
  }
}
