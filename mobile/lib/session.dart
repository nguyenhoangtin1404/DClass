import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'main.dart';
import 'screens/login_screen.dart';
import 'secure_token_storage.dart';

/// Clears the saved session (token + server URL) and sends the user back
/// to the login screen, replacing the whole navigation stack so there's no
/// "back" into a screen that assumes a valid session.
///
/// [thongBao], if given, is shown on the login screen itself rather than as
/// a SnackBar on the screen being left - a SnackBar's ScaffoldMessenger
/// context wouldn't survive the navigation away from it.
Future<void> dangXuat(BuildContext context, {String? thongBao}) async {
  await xoaToken();
  final prefs = await SharedPreferences.getInstance();
  await prefs.remove(prefsBaseUrl);
  if (!context.mounted) return;
  Navigator.of(context).pushAndRemoveUntil(
    MaterialPageRoute(builder: (_) => LoginScreen(thongBaoBanDau: thongBao)),
    (route) => false,
  );
}
