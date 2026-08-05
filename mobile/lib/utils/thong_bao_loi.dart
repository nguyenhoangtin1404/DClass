/// Friendly Vietnamese message for a queued action's stored error code
/// (`HanhDongCho.loiMa`) - mirrors the server's error strings (see
/// `lib/diem_nghiep_vu.php`) or an `ApiClient`-synthesized `loi_http_<code>`.
String thongBaoLoi(String? maLoi) {
  switch (maLoi) {
    case 'het_hang':
      return 'Quà đã hết hàng';
    case 'khong_du_diem':
      return 'Học sinh không đủ điểm để đổi quà';
    case 'ly_do_khong_hop_le':
      return 'Lý do không còn hoạt động';
    case 'qua_khong_hop_le':
      return 'Quà không còn hoạt động';
    case 'khong_du_quyen':
      return 'Không có quyền trên học sinh này';
    case 'thieu_thong_tin':
      return 'Thiếu thông tin cần thiết';
    case 'loi_http_401':
      return 'Token không hợp lệ, vui lòng đăng nhập lại';
  }
  return maLoi == null ? 'Lỗi không xác định' : 'Lỗi: $maLoi';
}
