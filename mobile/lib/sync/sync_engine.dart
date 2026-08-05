import 'package:flutter/foundation.dart';

import '../api_client.dart';
import '../loi_phan_loai.dart';
import '../outbox/hanh_dong_cho.dart';
import '../outbox/outbox_repository.dart';
import '../repositories/danh_muc_repository.dart';

/// Replays the queued outbox against the server, strictly in order. The
/// server has no notion of "client's expected prior balance" - every write
/// recomputes from live state - so an out-of-order replay can change
/// business outcome even though every individual call is safely retryable
/// (e.g. a queued "redeem gift" replayed before a queued "earn points" can
/// fail for insufficient balance, even though the original order would have
/// succeeded). Foreground-only by design: triggered by connectivity regained
/// ([ConnectivityWatcher]), app resume ([AppLifecycleSyncTrigger]), manual
/// pull-to-refresh, or an explicit "sync now" action - never a background
/// service.
class SyncEngine extends ChangeNotifier {
  SyncEngine({required this.api, required this.outbox, required this.danhMuc});

  final DiemApi api;
  final OutboxRepository outbox;
  final DanhMucRepository danhMuc;

  bool dangDongBo = false;
  int soLuongChoXuLy = 0;
  String? loiGanNhat;

  Future<void>? _dangChay;

  /// Runs a sync pass. Concurrent callers while one is already in flight
  /// await that same pass rather than starting a parallel one.
  Future<void> chaySync() {
    final dangChay = _dangChay;
    if (dangChay != null) return dangChay;
    final future = _chayThuc();
    _dangChay = future;
    return future.whenComplete(() => _dangChay = null);
  }

  Future<void> _chayThuc() async {
    dangDongBo = true;
    notifyListeners();
    try {
      // An app kill mid-send can leave a row stuck "dang_gui" - safe to
      // reset because the server's client_action_id idempotency guarantees
      // a resend either double-checks harmlessly or applies fresh.
      await outbox.quetDangGuiConLai();
      final danhSach = await outbox.layDanhSachChoXuLy();
      for (final hanhDong in danhSach) {
        final tiepTuc = await _xuLyMot(hanhDong);
        if (!tiepTuc) break;
      }
    } finally {
      soLuongChoXuLy = await outbox.demSoLuongChoXuLy();
      dangDongBo = false;
      notifyListeners();
    }
  }

  /// Sends one queued action. Returns whether the batch should continue to
  /// the next item.
  Future<bool> _xuLyMot(HanhDongCho hanhDong) async {
    final id = hanhDong.id!;
    await outbox.danDauDangGui(id);
    try {
      final Map<String, dynamic> ketQua;
      if (hanhDong.loai == LoaiHanhDong.congDiem) {
        ketQua = await api.congDiem(
          hocSinhId: hanhDong.hocSinhId,
          lyDoId: hanhDong.lyDoId!,
          ghiChu: hanhDong.ghiChu,
          clientActionId: hanhDong.clientActionId,
        );
      } else {
        ketQua = await api.quyDoiQuaTang(
          hocSinhId: hanhDong.hocSinhId,
          quaTangId: hanhDong.quaTangId!,
          ghiChu: hanhDong.ghiChu,
          clientActionId: hanhDong.clientActionId,
        );
      }
      final soDu = (ketQua['so_du'] as num?)?.toInt();
      if (soDu != null) {
        await danhMuc.capNhatSoDuHocSinh(hanhDong.hocSinhId, soDu);
      }
      if (hanhDong.loai == LoaiHanhDong.doiQua && hanhDong.quaTangId != null) {
        await danhMuc.giamTonKhoQuaTang(hanhDong.quaTangId!);
      }
      await outbox.xoaSauKhiThanhCong(id);
      loiGanNhat = null;
      return true;
    } catch (loi) {
      if (laLoiTamThoi(loi)) {
        // Order must not be skipped over here: a later item running before
        // this unresolved one would break the replay-in-order guarantee.
        await outbox.datLaiChoXuLy(id);
        loiGanNhat = loi.toString();
        return false;
      }
      // A failed call makes no server-side state change (the PHP either
      // throws before any write or rolls back its transaction), so later
      // queued items still see real, unperturbed server state - safe to
      // skip this one and continue.
      final maLoi = loi is ApiException ? loi.thongBao : loi.toString();
      await outbox.danhDauLoiVinhVien(id, maLoi);
      return true;
    }
  }
}
