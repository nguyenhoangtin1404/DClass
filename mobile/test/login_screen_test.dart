import 'package:dclass_mobile/screens/login_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  Widget boc(Widget child) => MaterialApp(home: child);

  testWidgets('shows the initial error banner when provided', (tester) async {
    await tester.pumpWidget(
      boc(const LoginScreen(thongBaoBanDau: 'Phiên đăng nhập đã hết hạn.')),
    );

    expect(find.text('Phiên đăng nhập đã hết hạn.'), findsOneWidget);
  });

  testWidgets('validates required fields before attempting to connect', (
    tester,
  ) async {
    await tester.pumpWidget(boc(const LoginScreen()));

    await tester.enterText(find.byType(TextFormField).first, '');
    await tester.tap(find.widgetWithText(FilledButton, 'Kết nối'));
    await tester.pump();

    expect(find.text('Nhập địa chỉ hợp lệ'), findsOneWidget);
    expect(find.text('Nhập token'), findsOneWidget);
  });

  testWidgets('token field is obscured by default', (tester) async {
    await tester.pumpWidget(boc(const LoginScreen()));

    final tokenField =
        tester.widgetList<TextField>(find.byType(TextField)).last;

    expect(tokenField.obscureText, isTrue);
  });
}
