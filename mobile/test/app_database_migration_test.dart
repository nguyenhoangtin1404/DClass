import 'dart:io';

import 'package:dclass_mobile/db/app_database.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:path/path.dart' as p;
import 'package:sqflite/sqflite.dart';

/// `hoc_sinh_cache`'s schema before v2 (see issue #99) - a stand-in for
/// what's already on disk for anyone who installed the app prior to that
/// change.
const String _taoBangV1 = '''
CREATE TABLE hoc_sinh_cache (
  id INTEGER PRIMARY KEY,
  ma TEXT,
  ho_ten TEXT NOT NULL,
  lop_hoc_id INTEGER,
  ten_lop TEXT,
  so_du_may_chu INTEGER NOT NULL DEFAULT 0,
  cap_nhat_luc TEXT
);
''';

void main() {
  test(
    'openAppDatabase upgrades a v1 database in place, keeping old rows',
    () async {
      final thuMuc = await Directory.systemTemp.createTemp('dclass_db_test');
      final duongDan = p.join(thuMuc.path, 'v1.db');
      addTearDown(() => thuMuc.delete(recursive: true));

      final dbCu = await openDatabase(
        duongDan,
        version: 1,
        onCreate: (db, _) async {
          await db.execute(_taoBangV1);
        },
      );
      await dbCu.insert('hoc_sinh_cache', {
        'id': 1,
        'ho_ten': 'An',
        'so_du_may_chu': 5,
      });
      await dbCu.close();

      final db = await openAppDatabase(path: duongDan);

      final rows = await db.query('hoc_sinh_cache');
      expect(rows, hasLength(1));
      expect(rows.single['ho_ten'], 'An');
      expect(rows.single['so_du_may_chu'], 5);
      // New v2 columns exist and are readable (would throw "no such column"
      // before the fix, since onUpgrade never ran).
      expect(rows.single['anh_dai_dien_url'], isNull);
      expect(rows.single['gioi_tinh'], isNull);
      expect(rows.single['ngay_sinh'], isNull);
      expect(rows.single['stt'], isNull);

      await db.close();
    },
  );
}
