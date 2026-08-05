import 'package:flutter/material.dart';
import 'package:local_auth/local_auth.dart';

import '../pin_lock_storage.dart' as pin_lock_storage;

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

  @override
  void initState() {
    super.initState();
    _kiemTraHoTroSinhTracHoc();
  }

  @override
  void dispose() {
    _pinCtrl.dispose();
    super.dispose();
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
    if (_pinCtrl.text.isEmpty) return;
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
    setState(() {
      _dangXuLy = false;
      _loi = 'Sai PIN, vui lòng thử lại.';
      _pinCtrl.clear();
    });
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      child: Scaffold(
        body: SafeArea(
          child: Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.lock_outline, size: 48),
                  const SizedBox(height: 16),
                  const Text(
                    'Nhập PIN để mở khoá',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 24),
                  TextField(
                    controller: _pinCtrl,
                    autofocus: true,
                    obscureText: true,
                    keyboardType: TextInputType.number,
                    textAlign: TextAlign.center,
                    maxLength: 8,
                    decoration: InputDecoration(
                      labelText: 'PIN',
                      errorText: _loi,
                      counterText: '',
                    ),
                    onSubmitted: (_) => _kiemTraPin(),
                  ),
                  const SizedBox(height: 16),
                  FilledButton(
                    onPressed: _dangXuLy ? null : _kiemTraPin,
                    child: const Text('Mở khoá'),
                  ),
                  if (_coTheSinhTracHoc) ...[
                    const SizedBox(height: 8),
                    TextButton.icon(
                      onPressed: _thuSinhTracHoc,
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
    );
  }
}
