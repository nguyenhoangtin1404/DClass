import 'package:flutter_secure_storage_platform_interface/flutter_secure_storage_platform_interface.dart';

/// In-memory fake for [FlutterSecureStoragePlatform], registered in widget
/// tests the same way `SharedPreferences.setMockInitialValues({})` fakes
/// shared_preferences - there's no bundled mock equivalent for
/// flutter_secure_storage, so this is hand-written against the small
/// platform-interface contract.
class FakeSecureStoragePlatform extends FlutterSecureStoragePlatform {
  final Map<String, String> _luuTru = {};

  @override
  Future<void> write({
    required String key,
    required String value,
    required Map<String, String> options,
  }) async {
    _luuTru[key] = value;
  }

  @override
  Future<String?> read({
    required String key,
    required Map<String, String> options,
  }) async {
    return _luuTru[key];
  }

  @override
  Future<bool> containsKey({
    required String key,
    required Map<String, String> options,
  }) async {
    return _luuTru.containsKey(key);
  }

  @override
  Future<void> delete({
    required String key,
    required Map<String, String> options,
  }) async {
    _luuTru.remove(key);
  }

  @override
  Future<Map<String, String>> readAll({
    required Map<String, String> options,
  }) async {
    return Map.of(_luuTru);
  }

  @override
  Future<void> deleteAll({required Map<String, String> options}) async {
    _luuTru.clear();
  }
}
