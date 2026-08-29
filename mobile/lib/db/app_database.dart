import 'package:path/path.dart' as p;
import 'package:sqflite/sqflite.dart';

const int schemaVersion = 2;

const String _taoBang = '''
CREATE TABLE hoc_sinh_cache (
  id INTEGER PRIMARY KEY,
  ma TEXT,
  ho_ten TEXT NOT NULL,
  lop_hoc_id INTEGER,
  ten_lop TEXT,
  so_du_may_chu INTEGER NOT NULL DEFAULT 0,
  anh_dai_dien_url TEXT,
  gioi_tinh TEXT,
  ngay_sinh TEXT,
  stt INTEGER,
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

/// Columns added to `hoc_sinh_cache` in schema v2 (see issue #99) - a device
/// that installed the app before this change has a v1 database missing
/// these, so [openAppDatabase]'s `onUpgrade` must add them without touching
/// existing rows.
const List<String> _cotV2ThemVaoHocSinhCache = [
  'ALTER TABLE hoc_sinh_cache ADD COLUMN anh_dai_dien_url TEXT',
  'ALTER TABLE hoc_sinh_cache ADD COLUMN gioi_tinh TEXT',
  'ALTER TABLE hoc_sinh_cache ADD COLUMN ngay_sinh TEXT',
  'ALTER TABLE hoc_sinh_cache ADD COLUMN stt INTEGER',
];

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
    onUpgrade: (db, oldVersion, newVersion) async {
      if (oldVersion < 2) {
        for (final cauLenh in _cotV2ThemVaoHocSinhCache) {
          await db.execute(cauLenh);
        }
      }
    },
  );
}
