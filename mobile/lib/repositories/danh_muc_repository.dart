import 'package:sqflite/sqflite.dart';

import '../api_client.dart';
import '../models/hoc_sinh.dart';
import '../models/ly_do.dart';
import '../models/qua_tang.dart';

/// Caches the reference data a teacher needs (students, reasons, gifts) so
/// the app keeps working with no connection. Strategy: try the network
/// first - it's the freshest data, and refreshes the cache for next time -
/// and fall back to whatever was last synced when the network call fails.
class DanhMucRepository {
  DanhMucRepository({required this.api, required this.db});
  final DiemApi api;
  final Database db;

  Future<List<HocSinh>> danhSachHocSinh() async {
    try {
      final ds = await api.danhSachHocSinh();
      await db.transaction((txn) async {
        await txn.delete('hoc_sinh_cache');
        for (final hs in ds) {
          await txn.insert('hoc_sinh_cache', hs.toCacheRow());
        }
      });
      return ds;
    } catch (_) {
      final rows = await db.query('hoc_sinh_cache', orderBy: 'ho_ten ASC');
      return rows.map(HocSinh.fromCacheRow).toList();
    }
  }

  Future<List<LyDo>> danhSachLyDo() async {
    try {
      final ds = await api.danhSachLyDo();
      await db.transaction((txn) async {
        await txn.delete('ly_do_cache');
        for (final ld in ds) {
          await txn.insert('ly_do_cache', ld.toCacheRow());
        }
      });
      return ds;
    } catch (_) {
      final rows = await db.query('ly_do_cache', orderBy: 'bien_diem DESC');
      return rows.map(LyDo.fromCacheRow).toList();
    }
  }

  Future<List<QuaTang>> danhSachQuaTang() async {
    try {
      final ds = await api.danhSachQuaTang();
      await db.transaction((txn) async {
        await txn.delete('qua_tang_cache');
        for (final qt in ds) {
          await txn.insert('qua_tang_cache', qt.toCacheRow());
        }
      });
      return ds;
    } catch (_) {
      final rows = await db.query('qua_tang_cache', orderBy: 'gia_diem ASC');
      return rows.map(QuaTang.fromCacheRow).toList();
    }
  }

  /// Reconciles a cached balance after the sync engine confirms an action
  /// applied - the server's response is authoritative (it may differ from
  /// what was assumed offline, e.g. a gift's price changed in the meantime).
  Future<void> capNhatSoDuHocSinh(int hocSinhId, int soDuMoi) {
    return db.update(
      'hoc_sinh_cache',
      {
        'so_du_may_chu': soDuMoi,
        'cap_nhat_luc': DateTime.now().toIso8601String(),
      },
      where: 'id = ?',
      whereArgs: [hocSinhId],
    );
  }

  /// Best-effort local decrement after a redeem syncs (the API response
  /// doesn't echo remaining stock) - corrected for real on the next
  /// [danhSachQuaTang] refresh. Mirrors the server's own rule (see
  /// quy_doi_qua_tang in lib/diem_nghiep_vu.php): only decrement when stock
  /// is a real positive count. A negative ton_kho means "unlimited" and is
  /// never touched - `MAX(x - 1, 0)` would wrongly turn -1 into 0 (looking
  /// out of stock) after a single redeem.
  Future<void> giamTonKhoQuaTang(int quaTangId) {
    return db.rawUpdate(
      'UPDATE qua_tang_cache SET '
      'ton_kho_may_chu = CASE WHEN ton_kho_may_chu > 0 '
      'THEN ton_kho_may_chu - 1 ELSE ton_kho_may_chu END, '
      'cap_nhat_luc = ? WHERE id = ?',
      [DateTime.now().toIso8601String(), quaTangId],
    );
  }
}
