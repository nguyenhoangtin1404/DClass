import 'package:flutter/material.dart';

import '../pin_lock_storage.dart';

/// Lets a teacher choose (or change) the PIN `UnlockScreen` will ask for -
/// opened from `bao_mat_screen.dart` when turning the lock on, or to replace
/// an existing PIN. Pops `true` once the PIN is saved, `null`/`false`
/// otherwise (back button, cancelled).
class SetPinScreen extends StatefulWidget {
  const SetPinScreen({super.key});

  @override
  State<SetPinScreen> createState() => _SetPinScreenState();
}

class _SetPinScreenState extends State<SetPinScreen> {
  final _pinCtrl = TextEditingController();
  final _xacNhanCtrl = TextEditingController();
  String? _loi;

  @override
  void dispose() {
    _pinCtrl.dispose();
    _xacNhanCtrl.dispose();
    super.dispose();
  }

  Future<void> _luu() async {
    final pin = _pinCtrl.text;
    if (pin.length < 4) {
      setState(() => _loi = 'PIN cần tối thiểu 4 số.');
      return;
    }
    if (pin != _xacNhanCtrl.text) {
      setState(() => _loi = 'Hai lần nhập PIN không khớp.');
      return;
    }
    await datPin(pin);
    if (mounted) Navigator.of(context).pop(true);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Đặt PIN khoá ứng dụng')),
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            TextField(
              controller: _pinCtrl,
              obscureText: true,
              keyboardType: TextInputType.number,
              maxLength: 8,
              decoration: const InputDecoration(
                labelText: 'PIN mới',
                counterText: '',
              ),
            ),
            const SizedBox(height: 8),
            TextField(
              controller: _xacNhanCtrl,
              obscureText: true,
              keyboardType: TextInputType.number,
              maxLength: 8,
              decoration: const InputDecoration(
                labelText: 'Nhập lại PIN',
                counterText: '',
              ),
              onSubmitted: (_) => _luu(),
            ),
            if (_loi != null)
              Padding(
                padding: const EdgeInsets.only(top: 8),
                child: Text(
                  _loi!,
                  style: TextStyle(color: Theme.of(context).colorScheme.error),
                ),
              ),
            const SizedBox(height: 16),
            FilledButton(onPressed: _luu, child: const Text('Lưu')),
          ],
        ),
      ),
    );
  }
}
