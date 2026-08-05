import 'package:uuid/uuid.dart';

import '../api_client.dart';
import '../loi_phan_loai.dart';
import '../outbox/hanh_dong_cho.dart';
import '../outbox/outbox_repository.dart';

const _uuid = Uuid();

enum KetQuaGhiDiem { daApDungNgay, dangCho }

/// Write-side entry point for point actions. Tries the network immediately,
/// so the common case (online) still gets synchronous success/failure
/// feedback; only queues the action (via [OutboxRepository], to be replayed
/// later by the sync engine) when the immediate attempt fails for a
/// transient reason. A permanent/business failure (e.g. an inactive reason,
/// insufficient balance) is surfaced to the caller right away instead of
/// silently queued to fail again identically later.
class DiemRepository {
  DiemRepository({required this.api, required this.outbox});
  final DiemApi api;
  final OutboxRepository outbox;

  Future<KetQuaGhiDiem> themCongDiem({
    required int hocSinhId,
    required int lyDoId,
    String ghiChu = '',
  }) async {
    final clientActionId = _uuid.v4();
    try {
      await api.congDiem(
        hocSinhId: hocSinhId,
        lyDoId: lyDoId,
        ghiChu: ghiChu,
        clientActionId: clientActionId,
      );
      return KetQuaGhiDiem.daApDungNgay;
    } catch (loi) {
      if (!laLoiTamThoi(loi)) rethrow;
      await outbox.themMoi(
        HanhDongCho(
          clientActionId: clientActionId,
          loai: LoaiHanhDong.congDiem,
          hocSinhId: hocSinhId,
          lyDoId: lyDoId,
          ghiChu: ghiChu,
          taoLuc: DateTime.now().toIso8601String(),
        ),
      );
      return KetQuaGhiDiem.dangCho;
    }
  }

  Future<KetQuaGhiDiem> themDoiQua({
    required int hocSinhId,
    required int quaTangId,
    String ghiChu = '',
  }) async {
    final clientActionId = _uuid.v4();
    try {
      await api.quyDoiQuaTang(
        hocSinhId: hocSinhId,
        quaTangId: quaTangId,
        ghiChu: ghiChu,
        clientActionId: clientActionId,
      );
      return KetQuaGhiDiem.daApDungNgay;
    } catch (loi) {
      if (!laLoiTamThoi(loi)) rethrow;
      await outbox.themMoi(
        HanhDongCho(
          clientActionId: clientActionId,
          loai: LoaiHanhDong.doiQua,
          hocSinhId: hocSinhId,
          quaTangId: quaTangId,
          ghiChu: ghiChu,
          taoLuc: DateTime.now().toIso8601String(),
        ),
      );
      return KetQuaGhiDiem.dangCho;
    }
  }
}
