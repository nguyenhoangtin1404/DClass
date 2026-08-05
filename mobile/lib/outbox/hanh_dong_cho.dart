/// What kind of point transaction a queued action represents - matches the
/// server's `so_cai_diem.loai` values ('CONG_DIEM' / 'DOI_DIEM').
enum LoaiHanhDong {
  congDiem,
  doiQua;

  String get gtri => this == LoaiHanhDong.congDiem ? 'CONG_DIEM' : 'DOI_DIEM';

  static LoaiHanhDong tuChuoi(String s) {
    return s == 'DOI_DIEM' ? LoaiHanhDong.doiQua : LoaiHanhDong.congDiem;
  }
}

/// Lifecycle state of a queued action. See `mobile/lib/db/app_database.dart`
/// for the `hang_doi_thao_tac.trang_thai` CHECK constraint this mirrors.
enum TrangThaiHanhDong {
  choXuLy,
  dangGui,
  loiVinhVien;

  String get gtri {
    switch (this) {
      case TrangThaiHanhDong.choXuLy:
        return 'cho_xu_ly';
      case TrangThaiHanhDong.dangGui:
        return 'dang_gui';
      case TrangThaiHanhDong.loiVinhVien:
        return 'loi_vinh_vien';
    }
  }

  static TrangThaiHanhDong tuChuoi(String s) {
    switch (s) {
      case 'dang_gui':
        return TrangThaiHanhDong.dangGui;
      case 'loi_vinh_vien':
        return TrangThaiHanhDong.loiVinhVien;
      default:
        return TrangThaiHanhDong.choXuLy;
    }
  }
}

/// A queued offline point action, one row of `hang_doi_thao_tac`. [id] is
/// null until the row has been inserted (sqlite assigns it, and it's the
/// strict FIFO ordering key - see the sync engine).
class HanhDongCho {
  final int? id;
  final String clientActionId;
  final LoaiHanhDong loai;
  final int hocSinhId;
  final int? lyDoId;
  final int? quaTangId;
  final String ghiChu;
  final TrangThaiHanhDong trangThai;
  final String? loiMa;
  final int soLanThu;
  final String taoLuc;
  final String? capNhatLuc;

  HanhDongCho({
    this.id,
    required this.clientActionId,
    required this.loai,
    required this.hocSinhId,
    this.lyDoId,
    this.quaTangId,
    this.ghiChu = '',
    this.trangThai = TrangThaiHanhDong.choXuLy,
    this.loiMa,
    this.soLanThu = 0,
    required this.taoLuc,
    this.capNhatLuc,
  });

  factory HanhDongCho.fromRow(Map<String, Object?> row) {
    return HanhDongCho(
      id: row['id'] as int?,
      clientActionId: row['client_action_id'] as String,
      loai: LoaiHanhDong.tuChuoi(row['loai'] as String),
      hocSinhId: row['hoc_sinh_id'] as int,
      lyDoId: row['ly_do_id'] as int?,
      quaTangId: row['qua_tang_id'] as int?,
      ghiChu: row['ghi_chu'] as String? ?? '',
      trangThai: TrangThaiHanhDong.tuChuoi(
        row['trang_thai'] as String? ?? 'cho_xu_ly',
      ),
      loiMa: row['loi_ma'] as String?,
      soLanThu: (row['so_lan_thu'] as num?)?.toInt() ?? 0,
      taoLuc: row['tao_luc'] as String,
      capNhatLuc: row['cap_nhat_luc'] as String?,
    );
  }

  Map<String, Object?> toRow() {
    final row = <String, Object?>{
      'client_action_id': clientActionId,
      'loai': loai.gtri,
      'hoc_sinh_id': hocSinhId,
      'ly_do_id': lyDoId,
      'qua_tang_id': quaTangId,
      'ghi_chu': ghiChu,
      'trang_thai': trangThai.gtri,
      'loi_ma': loiMa,
      'so_lan_thu': soLanThu,
      'tao_luc': taoLuc,
      'cap_nhat_luc': capNhatLuc,
    };
    if (id != null) row['id'] = id;
    return row;
  }
}
