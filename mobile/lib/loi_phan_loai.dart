import 'api_client.dart';

/// Server error codes (see `lib/diem_nghiep_vu.php`) that are permanent -
/// retrying the exact same request won't help unless something changes
/// server-side first (a reason gets reactivated, stock gets restocked...).
const _maLoiVinhVien = {
  'ly_do_khong_hop_le',
  'khong_du_quyen',
  'qua_khong_hop_le',
  'het_hang',
  'khong_du_diem',
  'thieu_thong_tin',
};

/// Whether [loi] is worth retrying later unchanged (network hiccup, server
/// briefly down) as opposed to a permanent/business failure that needs the
/// user - or a server-side change - to resolve. Shared by DiemRepository's
/// immediate-attempt fallback and the sync engine's replay loop so both
/// classify errors identically.
bool laLoiTamThoi(Object loi) {
  if (loi is ApiException) {
    final ma = loi.thongBao;
    if (_maLoiVinhVien.contains(ma)) return false;
    if (ma.startsWith('loi_http_')) {
      final maSo = int.tryParse(ma.substring('loi_http_'.length));
      if (maSo != null) {
        // Timeouts, rate-limiting, and any 5xx are worth retrying; other
        // 4xx (401 bad/revoked token, 404, ...) are not.
        return maSo == 408 || maSo == 429 || maSo >= 500;
      }
    }
    return false; // unrecognized ApiException message: treat as permanent
  }
  // Anything else (SocketException, TimeoutException, http.ClientException,
  // ...) is a network-level failure - always transient.
  return true;
}

/// Whether [loi] means the saved Bearer token is no longer valid (revoked
/// from cau_hinh.php's "Tài khoản" tab, or otherwise rejected) - the caller
/// should end the local session and send the user back to the login screen
/// instead of treating this like any other failure (e.g. silently falling
/// back to a stale cache, which would hide that the session is dead).
bool laLoiPhienHetHan(Object loi) {
  return loi is ApiException && loi.thongBao == 'loi_http_401';
}
