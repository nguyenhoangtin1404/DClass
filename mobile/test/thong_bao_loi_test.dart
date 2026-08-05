import 'package:dclass_mobile/utils/thong_bao_loi.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('maps known server error codes to Vietnamese messages', () {
    expect(thongBaoLoi('het_hang'), contains('hết hàng'));
    expect(thongBaoLoi('khong_du_diem'), contains('không đủ điểm'));
    expect(thongBaoLoi('ly_do_khong_hop_le'), contains('Lý do'));
    expect(thongBaoLoi('loi_http_401'), contains('đăng nhập lại'));
  });

  test('falls back to a generic message for an unrecognized code', () {
    expect(thongBaoLoi('loi_la'), 'Lỗi: loi_la');
  });

  test('handles a null error code', () {
    expect(thongBaoLoi(null), 'Lỗi không xác định');
  });
}
