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

  test('fewer than 5 wrong attempts in a row does not lock out', () async {
    await datPin('1234');
    for (var i = 0; i < 4; i++) {
      expect(await kiemTraPin('0000'), isFalse);
    }

    expect(await thoiGianConLaiKhoa(), isNull);
    expect(await kiemTraPin('1234'), isTrue);
  });

  test('the 5th consecutive wrong attempt locks out further checks', () async {
    await datPin('1234');
    for (var i = 0; i < 5; i++) {
      expect(await kiemTraPin('0000'), isFalse);
    }

    final conLai = await thoiGianConLaiKhoa();
    expect(conLai, isNotNull);
    expect(conLai!.inSeconds, greaterThan(0));
    // Even the correct PIN is rejected without comparing while locked out -
    // otherwise a script could keep guessing at full speed regardless.
    expect(await kiemTraPin('1234'), isFalse);
  });

  test('a successful check resets the failed-attempt count', () async {
    await datPin('1234');
    for (var i = 0; i < 4; i++) {
      expect(await kiemTraPin('0000'), isFalse);
    }
    expect(await kiemTraPin('1234'), isTrue);

    // 4 more wrong attempts after a success should not reach the threshold
    // if the count really reset to 0.
    for (var i = 0; i < 4; i++) {
      expect(await kiemTraPin('0000'), isFalse);
    }
    expect(await thoiGianConLaiKhoa(), isNull);
  });

  test('datPin clears a previous lockout', () async {
    await datPin('1234');
    for (var i = 0; i < 5; i++) {
      await kiemTraPin('0000');
    }
    expect(await thoiGianConLaiKhoa(), isNotNull);

    await datPin('5678');

    expect(await thoiGianConLaiKhoa(), isNull);
    expect(await kiemTraPin('5678'), isTrue);
  });

  test('tatKhoa clears a previous lockout', () async {
    await datPin('1234');
    for (var i = 0; i < 5; i++) {
      await kiemTraPin('0000');
    }
    expect(await thoiGianConLaiKhoa(), isNotNull);

    await tatKhoa();

    expect(await thoiGianConLaiKhoa(), isNull);
  });
}
