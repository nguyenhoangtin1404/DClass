import 'package:flutter_secure_storage/flutter_secure_storage.dart';

const _khoaPin = 'khoa_ung_dung_pin';
const _khoaSoLanSai = 'khoa_ung_dung_so_lan_sai';
const _khoaKhoaDenLuc = 'khoa_ung_dung_khoa_den_luc';

const _storage = FlutterSecureStorage();

/// Số lần nhập sai liên tiếp trước khi khoá tạm thời - cùng ngưỡng với
/// đăng nhập trên web (`lib/gioi_han_toc_do.php`).
const _nguongKhoa = 5;

/// Thời gian khoá (giây) leo thang theo số lần vượt ngưỡng liên tiếp, để mỗi
/// lần thử vét cạn PIN 4-8 số bằng tay đều chậm hơn lần trước, nhưng một
/// giáo viên quên PIN thật sự vẫn vào lại được sau một khoảng thời gian hữu
/// hạn (không khoá vĩnh viễn như tài khoản trên server).
const _ttlLeoThang = [30, 60, 120, 300];

/// Whether the teacher has turned on the app-level PIN lock (see
/// `screens/set_pin_screen.dart`/`screens/unlock_screen.dart` and
/// `lock/app_lock_gate.dart`). Derived from whether a PIN is saved, rather
/// than tracked as a separate on/off flag, so the two can't drift out of
/// sync.
Future<bool> khoaDaBat() async => (await _storage.read(key: _khoaPin)) != null;

/// Sets (or changes) the PIN and turns the lock on. Stored the same way as
/// the API token (`secure_token_storage.dart`) - Keychain/Keystore, not
/// plaintext prefs. Also clears any past brute-force lockout state, so
/// setting a new PIN starts clean.
Future<void> datPin(String pin) async {
  await _storage.write(key: _khoaPin, value: pin);
  await _xoaDemSaiPin();
}

/// Turns the lock off and forgets the PIN (and any lockout state).
Future<void> tatKhoa() async {
  await _storage.delete(key: _khoaPin);
  await _xoaDemSaiPin();
}

/// Thời gian còn lại đang bị khoá do nhập sai PIN quá nhiều lần liên tiếp,
/// hoặc `null` nếu không bị khoá. Không có PIN nào là "vô hạn" thử được -
/// PIN 4 số chỉ có 10.000 khả năng, ai cầm được điện thoại đã mở khoá hệ
/// điều hành có thể dò tay nếu không có gì chặn lại (xem issue #101 - trước
/// đây không có giới hạn số lần thử).
Future<Duration?> thoiGianConLaiKhoa() async {
  final raw = await _storage.read(key: _khoaKhoaDenLuc);
  if (raw == null) return null;
  final denLuc = DateTime.fromMillisecondsSinceEpoch(int.parse(raw));
  final conLai = denLuc.difference(DateTime.now());
  return conLai.isNegative ? null : conLai;
}

Future<void> _xoaDemSaiPin() async {
  await _storage.delete(key: _khoaSoLanSai);
  await _storage.delete(key: _khoaKhoaDenLuc);
}

Future<void> _ghiNhanSaiPin() async {
  final raw = await _storage.read(key: _khoaSoLanSai);
  final soLan = (int.tryParse(raw ?? '0') ?? 0) + 1;
  await _storage.write(key: _khoaSoLanSai, value: '$soLan');
  if (soLan < _nguongKhoa) return;
  final vuotNguong = soLan - _nguongKhoa;
  final chiSo = vuotNguong.clamp(0, _ttlLeoThang.length - 1);
  final denLuc = DateTime.now().add(Duration(seconds: _ttlLeoThang[chiSo]));
  await _storage.write(
    key: _khoaKhoaDenLuc,
    value: '${denLuc.millisecondsSinceEpoch}',
  );
}

/// Whether [pin] matches the saved one. Returns false (never throws) when no
/// PIN is set, or while a brute-force lockout ([thoiGianConLaiKhoa]) is in
/// effect - the caller should check that first to show a countdown instead
/// of silently rejecting a correct PIN.
Future<bool> kiemTraPin(String pin) async {
  if (await thoiGianConLaiKhoa() != null) return false;
  final dung = (await _storage.read(key: _khoaPin)) == pin;
  if (dung) {
    await _xoaDemSaiPin();
  } else {
    await _ghiNhanSaiPin();
  }
  return dung;
}
