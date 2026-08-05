import 'package:dclass_mobile/pin_lock_storage.dart';
import 'package:dclass_mobile/screens/unlock_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_secure_storage_platform_interface/flutter_secure_storage_platform_interface.dart';
import 'package:flutter_test/flutter_test.dart';

import 'fakes/fake_secure_storage_platform.dart';

void main() {
  setUp(() async {
    FlutterSecureStoragePlatform.instance = FakeSecureStoragePlatform();
    await datPin('1234');
  });

  Widget boc(VoidCallback onMoKhoa) =>
      MaterialApp(home: UnlockScreen(onMoKhoaThanhCong: onMoKhoa));

  testWidgets('the correct PIN unlocks', (tester) async {
    var daMoKhoa = false;
    await tester.pumpWidget(boc(() => daMoKhoa = true));
    await tester.pump();

    await tester.enterText(find.byType(TextField), '1234');
    await tester.tap(find.widgetWithText(FilledButton, 'Mở khoá'));
    await tester.pump();
    await tester.pump();

    expect(daMoKhoa, isTrue);
  });

  testWidgets('a wrong PIN shows an error and stays locked', (tester) async {
    var daMoKhoa = false;
    await tester.pumpWidget(boc(() => daMoKhoa = true));
    await tester.pump();

    await tester.enterText(find.byType(TextField), '0000');
    await tester.tap(find.widgetWithText(FilledButton, 'Mở khoá'));
    await tester.pump();
    await tester.pump();

    expect(daMoKhoa, isFalse);
    expect(find.text('Sai PIN, vui lòng thử lại.'), findsOneWidget);
  });

  testWidgets(
    'hides the biometric option when the platform has no support wired up',
    (tester) async {
      await tester.pumpWidget(boc(() {}));
      await tester.pump();
      await tester.pump();

      expect(find.text('Dùng sinh trắc học'), findsNothing);
    },
  );
}
