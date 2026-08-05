import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

const _khoaToken = 'api_token';

const _storage = FlutterSecureStorage();

/// Reads the saved Bearer token from secure storage (Keychain/Keystore).
///
/// The token is a single-active-credential secret equivalent to a password
/// for a teacher's real student roster - `shared_preferences` (plain,
/// unencrypted storage on both platforms) is the wrong place to keep it.
/// On first read, migrates a legacy plaintext copy left over from before
/// this file existed, then deletes it, so an existing install isn't
/// silently logged out.
Future<String?> docToken() async {
  final tuAnToan = await _storage.read(key: _khoaToken);
  if (tuAnToan != null) return tuAnToan;

  final prefs = await SharedPreferences.getInstance();
  final cu = prefs.getString(_khoaToken);
  if (cu != null) {
    await _storage.write(key: _khoaToken, value: cu);
    await prefs.remove(_khoaToken);
    return cu;
  }
  return null;
}

Future<void> luuToken(String token) {
  return _storage.write(key: _khoaToken, value: token);
}

Future<void> xoaToken() {
  return _storage.delete(key: _khoaToken);
}
