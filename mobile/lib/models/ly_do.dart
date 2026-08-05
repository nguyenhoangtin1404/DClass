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
}
