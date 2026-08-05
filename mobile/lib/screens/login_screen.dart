import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../api_client.dart';
import '../db/app_database.dart';
import '../main.dart';
import '../phien_dang_nhap.dart';

/// Lets a teacher point the app at their DClass server and paste the API
/// token generated from cau_hinh.php's "Tài khoản" tab (shown there as text
/// and a QR code).
class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _urlCtrl = TextEditingController(text: 'https://');
  final _tokenCtrl = TextEditingController();
  bool _dangKetNoi = false;
  String? _loi;

  @override
  void dispose() {
    _urlCtrl.dispose();
    _tokenCtrl.dispose();
    super.dispose();
  }

  Future<void> _dangNhap() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _dangKetNoi = true;
      _loi = null;
    });
    final baseUrl = _urlCtrl.text.trim().replaceAll(RegExp(r'/+$'), '');
    final token = _tokenCtrl.text.trim();
    final client = ApiClient(baseUrl: baseUrl, token: token);
    try {
      await client.danhSachHocSinh();
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(prefsBaseUrl, baseUrl);
      await prefs.setString(prefsToken, token);
      final db = await openAppDatabase();
      if (!mounted) return;
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(
          builder: (_) => PhienDangNhap(client: client, db: db),
        ),
      );
    } catch (e) {
      setState(() => _loi = 'Không kết nối được: $e');
    } finally {
      if (mounted) setState(() => _dangKetNoi = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Kết nối DClass')),
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              TextFormField(
                controller: _urlCtrl,
                decoration: const InputDecoration(
                  labelText: 'Địa chỉ server',
                  hintText: 'https://truong-cua-ban.vn',
                ),
                keyboardType: TextInputType.url,
                validator: (v) => (v == null || v.trim().length < 8)
                    ? 'Nhập địa chỉ hợp lệ'
                    : null,
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _tokenCtrl,
                decoration: const InputDecoration(
                  labelText: 'Token API',
                  hintText: 'Lấy trong Cấu hình > Tài khoản',
                ),
                obscureText: true,
                validator: (v) =>
                    (v == null || v.trim().isEmpty) ? 'Nhập token' : null,
              ),
              const SizedBox(height: 24),
              if (_loi != null)
                Padding(
                  padding: const EdgeInsets.only(bottom: 16),
                  child: Text(
                    _loi!,
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.error,
                    ),
                  ),
                ),
              FilledButton(
                onPressed: _dangKetNoi ? null : _dangNhap,
                child: _dangKetNoi
                    ? const SizedBox(
                        height: 20,
                        width: 20,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Kết nối'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
