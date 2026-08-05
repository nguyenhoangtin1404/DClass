import 'package:dclass_mobile/api_client.dart';
import 'package:dclass_mobile/db/app_database.dart';
import 'package:dclass_mobile/models/hoc_sinh.dart';
import 'package:dclass_mobile/models/ly_do.dart';
import 'package:dclass_mobile/models/qua_tang.dart';
import 'package:dclass_mobile/repositories/danh_muc_repository.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:sqflite/sqflite.dart';
import 'package:sqflite_common_ffi/sqflite_ffi.dart';

import 'fakes/fake_diem_api.dart';

void main() {
  late FakeDiemApi api;
  late Database db;
  late DanhMucRepository repo;

  setUp(() async {
    api = FakeDiemApi();
    db = await openAppDatabase(path: inMemoryDatabasePath);
    repo = DanhMucRepository(api: api, db: db);
  });

  test('online: returns fresh data and upserts the cache', () async {
    api.hocSinhTraVe = [
      HocSinh(
        id: 1,
        ma: 'HS1',
        hoTen: 'An',
        lopHocId: 1,
        tenLop: '1A',
        soDu: 5,
      ),
    ];

    final ds = await repo.danhSachHocSinh();

    expect(ds, hasLength(1));
    expect(ds.single.hoTen, 'An');

    // Cache was upserted - the next offline call should still see it.
    api.loiHocSinh = Exception('mat_ket_noi');
    final tuCache = await repo.danhSachHocSinh();
    expect(tuCache, hasLength(1));
    expect(tuCache.single.hoTen, 'An');
  });

  test('offline: falls back to the last-synced cache', () async {
    api.hocSinhTraVe = [
      HocSinh(
        id: 1,
        ma: null,
        hoTen: 'Binh',
        lopHocId: null,
        tenLop: null,
        soDu: 10,
      ),
    ];
    await repo.danhSachHocSinh(); // seeds the cache while "online"

    api.loiHocSinh = Exception('mat_ket_noi');
    final ds = await repo.danhSachHocSinh();

    expect(ds, hasLength(1));
    expect(ds.single.hoTen, 'Binh');
    expect(ds.single.soDu, 10);
  });

  test(
    'offline with an empty cache returns an empty list, not a throw',
    () async {
      api.loiHocSinh = Exception('mat_ket_noi');
      final ds = await repo.danhSachHocSinh();
      expect(ds, isEmpty);
    },
  );

  test('a fresh fetch fully replaces the cache (no stale leftovers)', () async {
    api.hocSinhTraVe = [
      HocSinh(
        id: 1,
        ma: null,
        hoTen: 'Cu',
        lopHocId: null,
        tenLop: null,
        soDu: 0,
      ),
    ];
    await repo.danhSachHocSinh();

    api.hocSinhTraVe = [
      HocSinh(
        id: 2,
        ma: null,
        hoTen: 'Danh',
        lopHocId: null,
        tenLop: null,
        soDu: 0,
      ),
    ];
    await repo.danhSachHocSinh();

    api.loiHocSinh = Exception('mat_ket_noi');
    final ds = await repo.danhSachHocSinh();
    expect(ds.map((e) => e.hoTen), ['Danh']);
  });

  test('ly do: caches and falls back the same way', () async {
    api.lyDoTraVe = [LyDo(id: 1, tieuDe: 'Phat bieu', bienDiem: 5)];
    await repo.danhSachLyDo();

    api.loiLyDo = Exception('mat_ket_noi');
    final ds = await repo.danhSachLyDo();
    expect(ds.single.tieuDe, 'Phat bieu');
  });

  test('a 401 (dead token) is rethrown, not masked by the cache fallback',
      () async {
    // Seed the cache while "online" so there's something a naive fallback
    // could have wrongly served instead of surfacing the real problem.
    api.hocSinhTraVe = [
      HocSinh(
          id: 1, ma: null, hoTen: 'An', lopHocId: null, tenLop: null, soDu: 0),
    ];
    await repo.danhSachHocSinh();

    api.loiHocSinh = ApiException('loi_http_401');
    await expectLater(
      () => repo.danhSachHocSinh(),
      throwsA(isA<ApiException>()),
    );
  });

  test(
    'online search/filter passes through to the API without touching the cache',
    () async {
      api.hocSinhTraVe = [
        HocSinh(
            id: 1, ma: null, hoTen: 'An', lopHocId: 1, tenLop: '10A', soDu: 0),
        HocSinh(
            id: 2,
            ma: null,
            hoTen: 'Binh',
            lopHocId: 2,
            tenLop: '10B',
            soDu: 0),
      ];
      await repo
          .danhSachHocSinh(); // unfiltered fetch seeds the cache with both

      final ketQua = await repo.danhSachHocSinh(tuKhoa: 'an');
      expect(ketQua.map((e) => e.hoTen), ['An']);

      // The filtered fetch must not have wiped Binh out of the cache.
      api.loiHocSinh = Exception('mat_ket_noi');
      final tuCache = await repo.danhSachHocSinh();
      expect(tuCache.map((e) => e.hoTen), containsAll(['An', 'Binh']));
    },
  );

  test('offline search filters the cache directly', () async {
    api.hocSinhTraVe = [
      HocSinh(
          id: 1, ma: 'MA1', hoTen: 'An', lopHocId: 1, tenLop: '10A', soDu: 0),
      HocSinh(
          id: 2, ma: 'MA2', hoTen: 'Binh', lopHocId: 2, tenLop: '10B', soDu: 0),
    ];
    await repo.danhSachHocSinh();

    api.loiHocSinh = Exception('mat_ket_noi');
    final theoTen = await repo.danhSachHocSinh(tuKhoa: 'binh');
    expect(theoTen.map((e) => e.hoTen), ['Binh']);

    final theoLop = await repo.danhSachHocSinh(lopHocId: 1);
    expect(theoLop.map((e) => e.hoTen), ['An']);
  });

  test('danhSachLop lists distinct classes from the cache', () async {
    api.hocSinhTraVe = [
      HocSinh(
          id: 1, ma: null, hoTen: 'An', lopHocId: 1, tenLop: '10A', soDu: 0),
      HocSinh(
          id: 2, ma: null, hoTen: 'Binh', lopHocId: 1, tenLop: '10A', soDu: 0),
      HocSinh(
          id: 3, ma: null, hoTen: 'Chi', lopHocId: 2, tenLop: '10B', soDu: 0),
    ];
    await repo.danhSachHocSinh();

    final lop = await repo.danhSachLop();
    expect(lop, [(1, '10A'), (2, '10B')]);
  });

  test('giamTonKhoQuaTang decrements a positive stock by one', () async {
    final qt = QuaTang(id: 1, ten: 'Keo', giaDiem: 3, tonKho: 5, anhUrl: null);
    await db.insert('qua_tang_cache', qt.toCacheRow());

    await repo.giamTonKhoQuaTang(1);

    final rows = await db.query('qua_tang_cache', where: 'id = 1');
    expect(rows.single['ton_kho_may_chu'], 4);
  });

  test(
    'giamTonKhoQuaTang leaves unlimited stock (negative) untouched',
    () async {
      final qt = QuaTang(
        id: 1,
        ten: 'Keo',
        giaDiem: 3,
        tonKho: -1,
        anhUrl: null,
      );
      await db.insert('qua_tang_cache', qt.toCacheRow());

      await repo.giamTonKhoQuaTang(1);

      final rows = await db.query('qua_tang_cache', where: 'id = 1');
      expect(rows.single['ton_kho_may_chu'], -1);
    },
  );
}
