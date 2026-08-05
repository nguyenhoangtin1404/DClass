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
}
