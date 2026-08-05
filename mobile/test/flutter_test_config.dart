import 'dart:async';

import 'package:sqflite_common_ffi/sqflite_ffi.dart';

/// Flutter automatically wraps every test file's `main()` in this - it's the
/// one place to point `sqflite` at the FFI backend (real SQLite via native
/// bindings, no platform channel) so `flutter test` can exercise the local
/// database without a device or emulator.
Future<void> testExecutable(FutureOr<void> Function() testMain) async {
  sqfliteFfiInit();
  databaseFactory = databaseFactoryFfi;
  return testMain();
}
