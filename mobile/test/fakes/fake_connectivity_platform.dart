import 'package:connectivity_plus_platform_interface/connectivity_plus_platform_interface.dart';
import 'package:plugin_platform_interface/plugin_platform_interface.dart';

/// In-memory fake for [ConnectivityPlatform], registered the same way
/// [FakeSecureStoragePlatform] fakes flutter_secure_storage - avoids hitting
/// the real platform channel (unavailable in widget tests) whenever a
/// widget renders [SyncStatusBadge], which reads connectivity_plus directly
/// via `ConnectivityWatcher`.
class FakeConnectivityPlatform extends ConnectivityPlatform
    with MockPlatformInterfaceMixin {
  List<ConnectivityResult> ketQua = [ConnectivityResult.wifi];

  @override
  Future<List<ConnectivityResult>> checkConnectivity() async => ketQua;

  @override
  Stream<List<ConnectivityResult>> get onConnectivityChanged =>
      const Stream<List<ConnectivityResult>>.empty();
}
