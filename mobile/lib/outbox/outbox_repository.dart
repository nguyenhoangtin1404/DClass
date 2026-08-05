import 'package:sqflite/sqflite.dart';

import 'hanh_dong_cho.dart';

const String _bang = 'hang_doi_thao_tac';

/// Persists the queue of offline point actions and their lifecycle
/// transitions. Ordering for replay is always the local autoincrement `id`
/// (`ORDER BY id ASC`), not `tao_luc` - avoids any clock-skew concern and
/// matches insertion order exactly for a single-writer, single-device app.
class OutboxRepository {
  OutboxRepository(this.db);
  final Database db;

  Future<int> themMoi(HanhDongCho hanhDong) {
    return db.insert(_bang, hanhDong.toRow());
  }

  /// All actions still needing to be sent, oldest first. Excludes
  /// permanently-failed actions - those are surfaced separately via
  /// [layDanhSachLoiVinhVien] and never auto-retried.
  Future<List<HanhDongCho>> layDanhSachChoXuLy() async {
    final rows = await db.query(
      _bang,
      where: 'trang_thai != ?',
      whereArgs: [TrangThaiHanhDong.loiVinhVien.gtri],
      orderBy: 'id ASC',
    );
    return rows.map(HanhDongCho.fromRow).toList();
  }

  Future<List<HanhDongCho>> layDanhSachLoiVinhVien() async {
    final rows = await db.query(
      _bang,
      where: 'trang_thai = ?',
      whereArgs: [TrangThaiHanhDong.loiVinhVien.gtri],
      orderBy: 'id ASC',
    );
    return rows.map(HanhDongCho.fromRow).toList();
  }

  Future<void> danDauDangGui(int id) {
    return db.update(
      _bang,
      {
        'trang_thai': TrangThaiHanhDong.dangGui.gtri,
        'cap_nhat_luc': DateTime.now().toIso8601String(),
      },
      where: 'id = ?',
      whereArgs: [id],
    );
  }

  Future<void> danhDauLoiVinhVien(int id, String maLoi) {
    return db.update(
      _bang,
      {
        'trang_thai': TrangThaiHanhDong.loiVinhVien.gtri,
        'loi_ma': maLoi,
        'cap_nhat_luc': DateTime.now().toIso8601String(),
      },
      where: 'id = ?',
      whereArgs: [id],
    );
  }

  /// Resets an action back to pending (incrementing its attempt count) -
  /// used both for a transient-failure retry and for a user-initiated
  /// "Thử lại" on a previously permanently-failed action.
  Future<void> datLaiChoXuLy(int id) {
    return db.rawUpdate(
      'UPDATE $_bang SET trang_thai = ?, so_lan_thu = so_lan_thu + 1, '
      'cap_nhat_luc = ? WHERE id = ?',
      [TrangThaiHanhDong.choXuLy.gtri, DateTime.now().toIso8601String(), id],
    );
  }

  /// Resets any action left `dang_gui` (in-flight) back to `cho_xu_ly` -
  /// call once when the sync engine starts. Safe because the server's
  /// `client_action_id` idempotency guarantees a resend either double-checks
  /// harmlessly or applies fresh - see so_cai_diem's partial unique index.
  Future<void> quetDangGuiConLai() {
    return db.update(
      _bang,
      {'trang_thai': TrangThaiHanhDong.choXuLy.gtri},
      where: 'trang_thai = ?',
      whereArgs: [TrangThaiHanhDong.dangGui.gtri],
    );
  }

  Future<int> demSoLuongChoXuLy() async {
    final ketQua = await db.rawQuery(
      'SELECT COUNT(*) AS c FROM $_bang WHERE trang_thai != ?',
      [TrangThaiHanhDong.loiVinhVien.gtri],
    );
    return Sqflite.firstIntValue(ketQua) ?? 0;
  }

  /// Deletes a successfully-synced action - the server's own ledger
  /// (`api/diem.php?hanh_dong=lich_su`) is the durable record, no need to
  /// keep a local copy once it's confirmed applied.
  Future<void> xoaSauKhiThanhCong(int id) => xoa(id);

  /// User-initiated discard of a permanently-failed action.
  Future<void> xoa(int id) {
    return db.delete(_bang, where: 'id = ?', whereArgs: [id]);
  }
}
