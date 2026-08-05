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
}
