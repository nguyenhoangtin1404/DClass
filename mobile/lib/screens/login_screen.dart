import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../api_client.dart';
import '../db/app_database.dart';
import '../main.dart';
import '../phien_dang_nhap.dart';
import '../secure_token_storage.dart';
import '../theme/dclass_colors.dart';

/// Lets a teacher point the app at their DClass server and paste the API
/// token generated from cau_hinh.php's "Tài khoản" tab (shown there as text
/// and a QR code).
class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key, this.thongBaoBanDau});

  /// Shown as the error banner immediately, e.g. "phiên đã hết hạn" after
  /// [dangXuat] redirects here - see `lib/session.dart`.
  final String? thongBaoBanDau;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _urlCtrl = TextEditingController(text: 'https://');
  final _tokenCtrl = TextEditingController();
  bool _dangKetNoi = false;
  late String? _loi = widget.thongBaoBanDau;

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
      await luuToken(token);
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

  InputDecoration _glassDecoration({
    required String label,
    required String hint,
    required IconData icon,
  }) {
    return InputDecoration(
      labelText: label,
      hintText: hint,
      prefixIcon: Icon(icon, color: Colors.white70, size: 20),
      filled: true,
      fillColor: Colors.white.withValues(alpha: .14),
      labelStyle:
          const TextStyle(color: Colors.white, fontWeight: FontWeight.w700),
      hintStyle: const TextStyle(color: Colors.white60),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: Colors.white.withValues(alpha: .28)),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: Colors.white.withValues(alpha: .28)),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: Colors.white, width: 1.6),
      ),
      errorStyle: const TextStyle(
          color: Color(0xFFFFD9DF), fontWeight: FontWeight.w700),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: DecoratedBox(
        decoration: const BoxDecoration(gradient: DClassColors.gateGradient),
        child: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 420),
                child: Form(
                  key: _formKey,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.fromLTRB(10, 8, 16, 8),
                            decoration: BoxDecoration(
                              color: const Color(0xFF4C8DFD),
                              borderRadius: BorderRadius.circular(999),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Image.asset('assets/brand/star.png',
                                    width: 20, height: 20),
                                const SizedBox(width: 7),
                                const Text(
                                  'DClass',
                                  style: TextStyle(
                                    color: Color(0xFF0B1220),
                                    fontWeight: FontWeight.w800,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 28),
                      const Text(
                        'Kết nối DClass',
                        style: TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.w800,
                          fontSize: 24,
                        ),
                      ),
                      const SizedBox(height: 4),
                      const Text(
                        'Theo dõi học sinh, cộng điểm, đổi quà',
                        style: TextStyle(color: Colors.white70),
                      ),
                      const SizedBox(height: 24),
                      TextFormField(
                        controller: _urlCtrl,
                        style: const TextStyle(color: Colors.white),
                        decoration: _glassDecoration(
                          label: 'Địa chỉ server',
                          hint: 'https://truong-cua-ban.vn',
                          icon: Icons.dns_outlined,
                        ),
                        keyboardType: TextInputType.url,
                        validator: (v) => (v == null || v.trim().length < 8)
                            ? 'Nhập địa chỉ hợp lệ'
                            : null,
                      ),
                      const SizedBox(height: 16),
                      TextFormField(
                        controller: _tokenCtrl,
                        style: const TextStyle(color: Colors.white),
                        decoration: _glassDecoration(
                          label: 'Token API',
                          hint: 'Lấy trong Cấu hình > Tài khoản',
                          icon: Icons.vpn_key_outlined,
                        ),
                        obscureText: true,
                        validator: (v) => (v == null || v.trim().isEmpty)
                            ? 'Nhập token'
                            : null,
                      ),
                      const SizedBox(height: 24),
                      if (_loi != null)
                        Padding(
                          padding: const EdgeInsets.only(bottom: 16),
                          child: Text(
                            _loi!,
                            style: const TextStyle(color: Color(0xFFFFD9DF)),
                          ),
                        ),
                      FilledButton(
                        onPressed: _dangKetNoi ? null : _dangNhap,
                        child: _dangKetNoi
                            ? const SizedBox(
                                height: 20,
                                width: 20,
                                child:
                                    CircularProgressIndicator(strokeWidth: 2),
                              )
                            : const Text('Kết nối'),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
