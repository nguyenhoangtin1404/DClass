class LyDo {
  final int id;
  final String tieuDe;
  final int bienDiem;

  LyDo({required this.id, required this.tieuDe, required this.bienDiem});

  factory LyDo.fromJson(Map<String, dynamic> json) {
    return LyDo(
      id: json['id'] as int,
      tieuDe: json['tieu_de'] as String? ?? '',
      bienDiem: (json['bien_diem'] as num?)?.toInt() ?? 0,
    );
  }

  /// Row shape for `ly_do_cache` (see `lib/db/app_database.dart`).
  factory LyDo.fromCacheRow(Map<String, Object?> row) {
    return LyDo(
      id: row['id'] as int,
      tieuDe: row['tieu_de'] as String? ?? '',
      bienDiem: (row['bien_diem'] as num?)?.toInt() ?? 0,
    );
  }

  Map<String, Object?> toCacheRow() {
    return {
      'id': id,
      'tieu_de': tieuDe,
      'bien_diem': bienDiem,
      'cap_nhat_luc': DateTime.now().toIso8601String(),
    };
  }
}
