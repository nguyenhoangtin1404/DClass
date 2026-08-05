class HocSinh {
  final int id;
  final String? ma;
  final String hoTen;
  final int? lopHocId;
  final String? tenLop;
  final int soDu;

  HocSinh({
    required this.id,
    required this.ma,
    required this.hoTen,
    required this.lopHocId,
    required this.tenLop,
    required this.soDu,
  });

  factory HocSinh.fromJson(Map<String, dynamic> json) {
    return HocSinh(
      id: json['id'] as int,
      ma: json['ma'] as String?,
      hoTen: json['ho_ten'] as String? ?? '',
      lopHocId: json['lop_hoc_id'] as int?,
      tenLop: json['ten_lop'] as String?,
      soDu: (json['so_du'] as num?)?.toInt() ?? 0,
    );
  }

  /// Row shape for `hoc_sinh_cache` (see `lib/db/app_database.dart`).
  factory HocSinh.fromCacheRow(Map<String, Object?> row) {
    return HocSinh(
      id: row['id'] as int,
      ma: row['ma'] as String?,
      hoTen: row['ho_ten'] as String? ?? '',
      lopHocId: row['lop_hoc_id'] as int?,
      tenLop: row['ten_lop'] as String?,
      soDu: (row['so_du_may_chu'] as num?)?.toInt() ?? 0,
    );
  }

  Map<String, Object?> toCacheRow() {
    return {
      'id': id,
      'ma': ma,
      'ho_ten': hoTen,
      'lop_hoc_id': lopHocId,
      'ten_lop': tenLop,
      'so_du_may_chu': soDu,
      'cap_nhat_luc': DateTime.now().toIso8601String(),
    };
  }
}
