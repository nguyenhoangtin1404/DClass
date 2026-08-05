import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:sqflite/sqflite.dart';

import 'api_client.dart';
import 'db/app_database.dart';
import 'phien_dang_nhap.dart';
import 'screens/login_screen.dart';
import 'secure_token_storage.dart';

const String prefsBaseUrl = 'base_url';

void main() {
  runApp(const DClassApp());
}

class DClassApp extends StatelessWidget {
  const DClassApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'DClass',
      theme: ThemeData(colorSchemeSeed: Colors.indigo, useMaterial3: true),
      home: const _KhoiDong(),
    );
  }
}

/// Reads any saved base URL/token and routes straight to the student list,
/// otherwise falls back to the login screen.
class _KhoiDong extends StatefulWidget {
  const _KhoiDong();

  @override
  State<_KhoiDong> createState() => _KhoiDongState();
}

class _KhoiDongState extends State<_KhoiDong> {
  ApiClient? _client;
  Database? _db;
  bool _dangTai = true;

  @override
  void initState() {
    super.initState();
    _nap();
  }

  Future<void> _nap() async {
    final prefs = await SharedPreferences.getInstance();
    final baseUrl = prefs.getString(prefsBaseUrl);
    final token = await docToken();
    if (baseUrl == null || token == null) {
      setState(() => _dangTai = false);
      return;
    }
    final client = ApiClient(baseUrl: baseUrl, token: token);
    final db = await openAppDatabase();
    setState(() {
      _client = client;
      _db = db;
      _dangTai = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    if (_dangTai) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }
    return (_client == null || _db == null)
        ? const LoginScreen()
        : PhienDangNhap(client: _client!, db: _db!);
  }
}
