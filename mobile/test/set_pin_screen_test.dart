import 'package:dclass_mobile/pin_lock_storage.dart';
import 'package:dclass_mobile/screens/set_pin_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_secure_storage_platform_interface/flutter_secure_storage_platform_interface.dart';
import 'package:flutter_test/flutter_test.dart';

import 'fakes/fake_secure_storage_platform.dart';

void main() {
  setUp(() {
    FlutterSecureStoragePlatform.instance = FakeSecureStoragePlatform();
  });

  Widget boc(void Function(bool?) onKetQua) {
    return MaterialApp(
      home: Builder(
        builder: (context) => ElevatedButton(
          onPressed: () async {
            final ketQua = await Navigator.of(context).push<bool>(
              MaterialPageRoute(builder: (_) => const SetPinScreen()),
            );
            onKetQua(ketQua);
          },
          child: const Text('Mở'),
        ),
      ),
    );
  }

  Future<void> moManHinh(WidgetTester tester) async {
    await tester.tap(find.text('Mở'));
    await tester.pumpAndSettle();
  }

  testWidgets('rejects a too-short PIN', (tester) async {
    await tester.pumpWidget(boc((_) {}));
    await moManHinh(tester);

    final oTextField = find.byType(TextField);
    await tester.enterText(oTextField.at(0), '12');
    await tester.enterText(oTextField.at(1), '12');
    await tester.tap(find.widgetWithText(FilledButton, 'Lưu'));
    await tester.pump();

    expect(find.text('PIN cần tối thiểu 4 số.'), findsOneWidget);
    expect(await khoaDaBat(), isFalse);
  });

  testWidgets('rejects a mismatched confirmation', (tester) async {
    await tester.pumpWidget(boc((_) {}));
    await moManHinh(tester);

    final oTextField = find.byType(TextField);
    await tester.enterText(oTextField.at(0), '1234');
    await tester.enterText(oTextField.at(1), '4321');
    await tester.tap(find.widgetWithText(FilledButton, 'Lưu'));
    await tester.pump();

    expect(find.text('Hai lần nhập PIN không khớp.'), findsOneWidget);
    expect(await khoaDaBat(), isFalse);
  });

  testWidgets('saves a valid matching PIN, turns the lock on, and pops true', (
    tester,
  ) async {
    bool? ketQua;
    await tester.pumpWidget(boc((kq) => ketQua = kq));
    await moManHinh(tester);

    final oTextField = find.byType(TextField);
    await tester.enterText(oTextField.at(0), '1234');
    await tester.enterText(oTextField.at(1), '1234');
    await tester.tap(find.widgetWithText(FilledButton, 'Lưu'));
    await tester.pumpAndSettle();

    expect(ketQua, isTrue);
    expect(await khoaDaBat(), isTrue);
    expect(await kiemTraPin('1234'), isTrue);
  });
}
