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
}
