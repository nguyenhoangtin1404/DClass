import 'package:sqflite/sqflite.dart';

import '../api_client.dart';
import '../loi_phan_loai.dart';
import '../models/hoc_sinh.dart';
import '../models/ly_do.dart';
import '../models/qua_tang.dart';

/// Caches the reference data a teacher needs (students, reasons, gifts) so
/// the app keeps working with no connection. Strategy: try the network
/// first - it's the freshest data, and refreshes the cache for next time -
/// and fall back to whatever was last synced when the network call fails
/// for a reason other than the session being dead (see [laLoiPhienHetHan] -
/// that's rethrown instead, since silently serving stale cache would hide
/// that the teacher needs to log in again).
class DanhMucRepository {
  DanhMucRepository({required this.api, required this.db});
  final DiemApi api;
  final Database db;

  /// [tuKhoa]/[lopHocId] search/filter a live (online) fetch. A filtered
  /// fetch never touches the cache table - deleting-then-reinserting only
  /// the matching rows would wrongly wipe out cached students that simply
  /// weren't part of this filtered result. Only a full (unfiltered) fetch
  /// is safe to use as a cache replacement.
  Future<List<HocSinh>> danhSachHocSinh({
    String tuKhoa = '',
    int? lopHocId,
  }) async {
    final coLoc = tuKhoa.isNotEmpty || lopHocId != null;
    try {
      final ds = await api.danhSachHocSinh(tuKhoa: tuKhoa, lopHocId: lopHocId);
      if (!coLoc) {
        await db.transaction((txn) async {
          await txn.delete('hoc_sinh_cache');
          for (final hs in ds) {
            await txn.insert('hoc_sinh_cache', hs.toCacheRow());
          }
        });
      }
      return ds;
    } catch (loi) {
      if (laLoiPhienHetHan(loi)) rethrow;
      return _timHocSinhTrongCache(tuKhoa: tuKhoa, lopHocId: lopHocId);
    }
  }

  Future<List<HocSinh>> _timHocSinhTrongCache({
    String tuKhoa = '',
    int? lopHocId,
  }) async {
    final dieuKien = <String>[];
    final doiSo = <Object?>[];
    if (tuKhoa.isNotEmpty) {
      dieuKien.add('(ho_ten LIKE ? OR ma LIKE ?)');
      final mau = '%$tuKhoa%';
      doiSo.addAll([mau, mau]);
    }
    if (lopHocId != null) {
      dieuKien.add('lop_hoc_id = ?');
      doiSo.add(lopHocId);
    }
    final rows = await db.query(
      'hoc_sinh_cache',
      where: dieuKien.isEmpty ? null : dieuKien.join(' AND '),
      whereArgs: doiSo.isEmpty ? null : doiSo,
      orderBy: 'ho_ten ASC',
    );
    return rows.map(HocSinh.fromCacheRow).toList();
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
    } catch (loi) {
      if (laLoiPhienHetHan(loi)) rethrow;
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
    } catch (loi) {
      if (laLoiPhienHetHan(loi)) rethrow;
      final rows = await db.query('qua_tang_cache', orderBy: 'gia_diem ASC');
      return rows.map(QuaTang.fromCacheRow).toList();
    }
  }

  /// Distinct class ids/names among cached students - used to populate a
  /// class filter without a dedicated "list my classes" endpoint.
  Future<List<(int, String)>> danhSachLop() async {
    final rows = await db.rawQuery(
      'SELECT DISTINCT lop_hoc_id, ten_lop FROM hoc_sinh_cache '
      'WHERE lop_hoc_id IS NOT NULL ORDER BY ten_lop ASC',
    );
    return rows
        .map(
          (r) => (r['lop_hoc_id'] as int, (r['ten_lop'] as String?) ?? ''),
        )
        .toList();
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
