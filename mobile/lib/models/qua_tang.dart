class QuaTang {
  final int id;
  final String ten;
  final int giaDiem;
  final int tonKho;
  final String? anhUrl;

  QuaTang({
    required this.id,
    required this.ten,
    required this.giaDiem,
    required this.tonKho,
    required this.anhUrl,
  });

  factory QuaTang.fromJson(Map<String, dynamic> json) {
    return QuaTang(
      id: json['id'] as int,
      ten: json['ten'] as String? ?? '',
      giaDiem: (json['gia_diem'] as num?)?.toInt() ?? 0,
      tonKho: (json['ton_kho'] as num?)?.toInt() ?? 0,
      anhUrl: json['anh_url'] as String?,
    );
  }

  /// Row shape for `qua_tang_cache` (see `lib/db/app_database.dart`).
  factory QuaTang.fromCacheRow(Map<String, Object?> row) {
    return QuaTang(
      id: row['id'] as int,
      ten: row['ten'] as String? ?? '',
      giaDiem: (row['gia_diem'] as num?)?.toInt() ?? 0,
      tonKho: (row['ton_kho_may_chu'] as num?)?.toInt() ?? 0,
      anhUrl: row['anh_url'] as String?,
    );
  }

  Map<String, Object?> toCacheRow() {
    return {
      'id': id,
      'ten': ten,
      'gia_diem': giaDiem,
      'ton_kho_may_chu': tonKho,
      'anh_url': anhUrl,
      'cap_nhat_luc': DateTime.now().toIso8601String(),
    };
  }
}
