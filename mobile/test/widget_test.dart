import 'package:flutter_secure_storage_platform_interface/flutter_secure_storage_platform_interface.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:dclass_mobile/main.dart';

import 'fakes/fake_secure_storage_platform.dart';

void main() {
  testWidgets('shows the login screen when no token is saved', (tester) async {
    SharedPreferences.setMockInitialValues({});
    FlutterSecureStoragePlatform.instance = FakeSecureStoragePlatform();
    await tester.pumpWidget(const DClassApp());
    await tester.pump();
    expect(find.text('Kết nối DClass'), findsOneWidget);
  });
}
