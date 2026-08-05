import 'package:dclass_mobile/pin_lock_storage.dart';
import 'package:flutter_secure_storage_platform_interface/flutter_secure_storage_platform_interface.dart';
import 'package:flutter_test/flutter_test.dart';

import 'fakes/fake_secure_storage_platform.dart';

void main() {
  setUp(() {
    FlutterSecureStoragePlatform.instance = FakeSecureStoragePlatform();
  });

  test('no PIN set: lock is off and any PIN check fails', () async {
    expect(await khoaDaBat(), isFalse);
    expect(await kiemTraPin('1234'), isFalse);
  });

  test('datPin turns the lock on and kiemTraPin validates it', () async {
    await datPin('1234');

    expect(await khoaDaBat(), isTrue);
    expect(await kiemTraPin('1234'), isTrue);
    expect(await kiemTraPin('0000'), isFalse);
  });

  test('tatKhoa forgets the PIN and turns the lock off', () async {
    await datPin('1234');
    await tatKhoa();

    expect(await khoaDaBat(), isFalse);
    expect(await kiemTraPin('1234'), isFalse);
  });
}
