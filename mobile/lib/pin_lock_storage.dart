import 'package:flutter_secure_storage/flutter_secure_storage.dart';

const _khoaPin = 'khoa_ung_dung_pin';

const _storage = FlutterSecureStorage();

/// Whether the teacher has turned on the app-level PIN lock (see
/// `screens/set_pin_screen.dart`/`screens/unlock_screen.dart` and
/// `lock/app_lock_gate.dart`). Derived from whether a PIN is saved, rather
/// than tracked as a separate on/off flag, so the two can't drift out of
/// sync.
Future<bool> khoaDaBat() async => (await _storage.read(key: _khoaPin)) != null;

/// Sets (or changes) the PIN and turns the lock on. Stored the same way as
/// the API token (`secure_token_storage.dart`) - Keychain/Keystore, not
/// plaintext prefs.
Future<void> datPin(String pin) => _storage.write(key: _khoaPin, value: pin);

/// Turns the lock off and forgets the PIN.
Future<void> tatKhoa() => _storage.delete(key: _khoaPin);

/// Whether [pin] matches the saved one. Returns false (never throws) when no
/// PIN is set.
Future<bool> kiemTraPin(String pin) async =>
    (await _storage.read(key: _khoaPin)) == pin;
