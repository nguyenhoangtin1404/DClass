import 'package:path/path.dart' as p;
import 'package:sqflite/sqflite.dart';

const int schemaVersion = 1;

const String _taoBang = '''
CREATE TABLE hoc_sinh_cache (
  id INTEGER PRIMARY KEY,
  ma TEXT,
  ho_ten TEXT NOT NULL,
  lop_hoc_id INTEGER,
  ten_lop TEXT,
  so_du_may_chu INTEGER NOT NULL DEFAULT 0,
  cap_nhat_luc TEXT
);

CREATE TABLE ly_do_cache (
  id INTEGER PRIMARY KEY,
  tieu_de TEXT NOT NULL,
  bien_diem INTEGER NOT NULL,
  cap_nhat_luc TEXT
);

CREATE TABLE qua_tang_cache (
  id INTEGER PRIMARY KEY,
  ten TEXT NOT NULL,
  gia_diem INTEGER NOT NULL,
  ton_kho_may_chu INTEGER NOT NULL DEFAULT 0,
  anh_url TEXT,
  cap_nhat_luc TEXT
);

CREATE TABLE hang_doi_thao_tac (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  client_action_id TEXT NOT NULL UNIQUE,
  loai TEXT NOT NULL CHECK(loai IN ('CONG_DIEM','DOI_DIEM')),
  hoc_sinh_id INTEGER NOT NULL,
  ly_do_id INTEGER,
  qua_tang_id INTEGER,
  ghi_chu TEXT,
  trang_thai TEXT NOT NULL DEFAULT 'cho_xu_ly'
    CHECK(trang_thai IN ('cho_xu_ly','dang_gui','loi_vinh_vien')),
  loi_ma TEXT,
  so_lan_thu INTEGER NOT NULL DEFAULT 0,
  tao_luc TEXT NOT NULL,
  cap_nhat_luc TEXT
);
CREATE INDEX idx_hang_doi_trang_thai ON hang_doi_thao_tac(trang_thai);
''';

/// Opens (creating if needed) the app's local cache + outbox database.
///
/// [path] overrides the on-device storage location - tests pass
/// `inMemoryDatabasePath` (from `sqflite_common_ffi`) to get an isolated
/// in-memory database per test instead of touching disk. `singleInstance` is
/// forced off whenever [path] is given: sqflite otherwise caches connections
/// by path, and every test using the same literal `:memory:` constant would
/// share one leftover database instead of each getting a fresh one.
Future<Database> openAppDatabase({String? path}) async {
  final laDuongDanTuyChinh = path != null;
  final duongDan = path ?? p.join(await getDatabasesPath(), 'dclass_mobile.db');
  return openDatabase(
    duongDan,
    version: schemaVersion,
    singleInstance: !laDuongDanTuyChinh,
    onCreate: (db, version) async {
      for (final cauLenh in _taoBang.split(';')) {
        final sql = cauLenh.trim();
        if (sql.isNotEmpty) {
          await db.execute(sql);
        }
      }
    },
  );
}
