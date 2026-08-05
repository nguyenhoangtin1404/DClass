import 'package:dclass_mobile/secure_token_storage.dart';
import 'package:flutter_secure_storage_platform_interface/flutter_secure_storage_platform_interface.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'fakes/fake_secure_storage_platform.dart';

void main() {
  setUp(() {
    FlutterSecureStoragePlatform.instance = FakeSecureStoragePlatform();
  });

  test('no token saved anywhere: docToken returns null', () async {
    SharedPreferences.setMockInitialValues({});
    expect(await docToken(), isNull);
  });

  test('luuToken/docToken round-trip through secure storage', () async {
    SharedPreferences.setMockInitialValues({});
    await luuToken('token-moi');
    expect(await docToken(), 'token-moi');
  });

  test(
    'migrates a legacy plaintext token from shared_preferences once',
    () async {
      SharedPreferences.setMockInitialValues({'api_token': 'token-cu'});

      final token = await docToken();
      expect(token, 'token-cu');

      // Migrated into secure storage and removed from shared_preferences.
      final prefs = await SharedPreferences.getInstance();
      expect(prefs.getString('api_token'), isNull);
      expect(await docToken(), 'token-cu'); // now served from secure storage
    },
  );

  test('xoaToken removes the saved token', () async {
    SharedPreferences.setMockInitialValues({});
    await luuToken('token-moi');
    await xoaToken();
    expect(await docToken(), isNull);
  });
}
