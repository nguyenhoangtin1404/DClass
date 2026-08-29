import 'dart:async';

import 'package:flutter/material.dart';
import 'package:local_auth/local_auth.dart';

import '../pin_lock_storage.dart' as pin_lock_storage;
import '../theme/dclass_colors.dart';

/// Full-screen PIN gate shown by `AppLockGate` on cold start and after the
/// app returns from the background past its threshold - see issue #101:
/// the token is already Keychain/Keystore-protected
/// (`secure_token_storage.dart`), but the app itself had no lock screen of
/// its own, so anyone holding an unlocked phone could open it straight to
/// the student list. Not part of the normal navigation stack: blocks the
/// system back button (`PopScope`) rather than being dismissible, and is
/// only removed by calling [onMoKhoaThanhCong].
class UnlockScreen extends StatefulWidget {
  const UnlockScreen({
    super.key,
    required this.onMoKhoaThanhCong,
    LocalAuthentication? sinhTracHoc,
  }) : _sinhTracHoc = sinhTracHoc;

  final VoidCallback onMoKhoaThanhCong;
  final LocalAuthentication? _sinhTracHoc;

  @override
  State<UnlockScreen> createState() => _UnlockScreenState();
}

class _UnlockScreenState extends State<UnlockScreen> {
  final _pinCtrl = TextEditingController();
  late final LocalAuthentication _sinhTracHoc =
      widget._sinhTracHoc ?? LocalAuthentication();
  String? _loi;
  bool _dangXuLy = false;
  bool _coTheSinhTracHoc = false;
  Duration? _khoaConLai;
  Timer? _dongHoKhoa;

  @override
  void initState() {
    super.initState();
    _kiemTraHoTroSinhTracHoc();
    _kiemTraKhoa();
  }

  @override
  void dispose() {
    _pinCtrl.dispose();
    _dongHoKhoa?.cancel();
    super.dispose();
  }

  /// Reads any brute-force lockout already in effect (e.g. the app was
  /// closed and reopened mid-lockout) and, if locked, starts a 1s countdown
  /// that re-enables PIN entry the moment it expires.
  Future<void> _kiemTraKhoa() async {
    final conLai = await pin_lock_storage.thoiGianConLaiKhoa();
    if (!mounted) return;
    setState(() => _khoaConLai = conLai);
    if (conLai == null) return;
    _dongHoKhoa?.cancel();
    _dongHoKhoa = Timer.periodic(const Duration(seconds: 1), (timer) async {
      final conLai = await pin_lock_storage.thoiGianConLaiKhoa();
      if (!mounted) {
        timer.cancel();
        return;
      }
      setState(() => _khoaConLai = conLai);
      if (conLai == null) {
        timer.cancel();
        setState(() => _loi = null);
      }
    });
  }

  /// Biometrics may be unsupported (emulator, no fingerprint/face enrolled,
  /// missing platform setup) - probed once so the button only shows when it
  /// stands a chance of working; PIN entry always works regardless.
  Future<void> _kiemTraHoTroSinhTracHoc() async {
    try {
      final hoTroThietBi = await _sinhTracHoc.isDeviceSupported();
      final coSinhTracHoc =
          hoTroThietBi && await _sinhTracHoc.canCheckBiometrics;
      if (mounted) setState(() => _coTheSinhTracHoc = coSinhTracHoc);
    } catch (_) {
      // Giữ nguyên _coTheSinhTracHoc = false, không chặn việc mở khoá bằng PIN.
    }
  }

  Future<void> _thuSinhTracHoc() async {
    setState(() => _loi = null);
    try {
      final thanhCong = await _sinhTracHoc.authenticate(
        localizedReason: 'Xác thực để mở khoá DClass',
        biometricOnly: true,
      );
      if (thanhCong) widget.onMoKhoaThanhCong();
    } catch (_) {
      if (mounted) {
        setState(() => _loi = 'Không thể xác thực sinh trắc học.');
      }
    }
  }

  Future<void> _kiemTraPin() async {
    if (_pinCtrl.text.isEmpty || _dangBiKhoa) return;
    setState(() {
      _dangXuLy = true;
      _loi = null;
    });
    final dung = await pin_lock_storage.kiemTraPin(_pinCtrl.text);
    if (!mounted) return;
    if (dung) {
      widget.onMoKhoaThanhCong();
      return;
    }
    _pinCtrl.clear();
    // A wrong attempt may have just crossed the lockout threshold - check
    // right away instead of waiting for the next attempt to find out. When
    // it has, the lockout countdown (not _loi) drives the error text.
    await _kiemTraKhoa();
    if (!mounted) return;
    setState(() {
      _dangXuLy = false;
      if (_khoaConLai == null) _loi = 'Sai PIN, vui lòng thử lại.';
    });
  }

  bool get _dangBiKhoa => _khoaConLai != null;

  String _moTaKhoa(Duration conLai) {
    final giay = conLai.inSeconds + 1; // làm tròn lên, không hiện "0 giây"
    return 'Nhập sai PIN quá nhiều lần. Thử lại sau $giay giây.';
  }

  Widget _pinDots(int filled, int total) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: List.generate(total, (i) {
        final on = i < filled;
        return Container(
          margin: const EdgeInsets.symmetric(horizontal: 5),
          width: 13,
          height: 13,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: on ? Colors.white : Colors.transparent,
            border: Border.all(
                color: Colors.white.withValues(alpha: on ? 1 : .55),
                width: 1.8),
          ),
        );
      }),
    );
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      child: Scaffold(
        body: DecoratedBox(
          decoration: const BoxDecoration(gradient: DClassColors.gateGradient),
          child: SafeArea(
            child: Center(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(24),
                child: ConstrainedBox(
                  constraints: const BoxConstraints(maxWidth: 380),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
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
                                width: 18, height: 18),
                            const SizedBox(width: 6),
                            const Text(
                              'DClass',
                              style: TextStyle(
                                  color: Color(0xFF0B1220),
                                  fontWeight: FontWeight.w800),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 22),
                      Container(
                        width: 56,
                        height: 56,
                        decoration: const BoxDecoration(
                          shape: BoxShape.circle,
                          gradient: LinearGradient(
                              colors: [Color(0xFFC7D2FE), Color(0xFF93C5FD)]),
                        ),
                        child: const Icon(Icons.lock_outline,
                            color: Color(0xFF0B1220)),
                      ),
                      const SizedBox(height: 16),
                      const Text(
                        'Nhập PIN để mở khoá',
                        style: TextStyle(
                            fontSize: 19,
                            fontWeight: FontWeight.w800,
                            color: Colors.white),
                      ),
                      const SizedBox(height: 18),
                      ValueListenableBuilder<TextEditingValue>(
                        valueListenable: _pinCtrl,
                        builder: (context, value, _) =>
                            _pinDots(value.text.length.clamp(0, 8), 8),
                      ),
                      const SizedBox(height: 18),
                      TextField(
                        controller: _pinCtrl,
                        enabled: !_dangBiKhoa,
                        autofocus: true,
                        obscureText: true,
                        keyboardType: TextInputType.number,
                        textAlign: TextAlign.center,
                        maxLength: 8,
                        style: const TextStyle(
                            color: Colors.white, letterSpacing: 6),
                        decoration: InputDecoration(
                          labelText: 'PIN',
                          labelStyle: const TextStyle(color: Colors.white70),
                          errorText: _khoaConLai != null
                              ? _moTaKhoa(_khoaConLai!)
                              : _loi,
                          errorStyle: const TextStyle(color: Color(0xFFFFD9DF)),
                          counterText: '',
                          filled: true,
                          fillColor: Colors.white.withValues(alpha: .14),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: BorderSide(
                                color: Colors.white.withValues(alpha: .28)),
                          ),
                          enabledBorder: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: BorderSide(
                                color: Colors.white.withValues(alpha: .28)),
                          ),
                          focusedBorder: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: const BorderSide(
                                color: Colors.white, width: 1.6),
                          ),
                        ),
                        onSubmitted: (_) => _kiemTraPin(),
                      ),
                      const SizedBox(height: 18),
                      FilledButton(
                        onPressed:
                            (_dangXuLy || _dangBiKhoa) ? null : _kiemTraPin,
                        child: const Text('Mở khoá'),
                      ),
                      if (_coTheSinhTracHoc) ...[
                        const SizedBox(height: 10),
                        TextButton.icon(
                          onPressed: _thuSinhTracHoc,
                          style: TextButton.styleFrom(
                              foregroundColor: Colors.white),
                          icon: const Icon(Icons.fingerprint),
                          label: const Text('Dùng sinh trắc học'),
                        ),
                      ],
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
