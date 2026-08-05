/// One row of `so_cai_diem`, as returned by
/// `api/diem.php?hanh_dong=lich_su`.
class LichSuGiaoDich {
  final int id;
  final String loai; // 'CONG_DIEM' | 'DOI_DIEM'
  final int bienDiem;
  final int soDuSau;
  final String? ghiChu;
  final String taoLuc;
  final String hoTen;
  final String? lyDo;
  final String? qua;

  LichSuGiaoDich({
    required this.id,
    required this.loai,
    required this.bienDiem,
    required this.soDuSau,
    required this.ghiChu,
    required this.taoLuc,
    required this.hoTen,
    required this.lyDo,
    required this.qua,
  });

  factory LichSuGiaoDich.fromJson(Map<String, dynamic> json) {
    return LichSuGiaoDich(
      id: json['id'] as int,
      loai: json['loai'] as String? ?? '',
      bienDiem: (json['bien_diem'] as num?)?.toInt() ?? 0,
      soDuSau: (json['so_du_sau'] as num?)?.toInt() ?? 0,
      ghiChu: json['ghi_chu'] as String?,
      taoLuc: json['tao_luc'] as String? ?? '',
      hoTen: json['ho_ten'] as String? ?? '',
      lyDo: json['ly_do'] as String?,
      qua: json['qua'] as String?,
    );
  }
}
