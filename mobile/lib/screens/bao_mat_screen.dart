import 'package:flutter/material.dart';

import '../pin_lock_storage.dart';
import 'set_pin_screen.dart';

/// Lets a teacher turn the app-level PIN lock on/off, and change the PIN
/// once it's on - see issue #101. A deliberate choice, not mandatory: some
/// teachers share a personal, always-on-them phone and don't need it.
class BaoMatScreen extends StatefulWidget {
  const BaoMatScreen({super.key});

  @override
  State<BaoMatScreen> createState() => _BaoMatScreenState();
}

class _BaoMatScreenState extends State<BaoMatScreen> {
  late Future<bool> _daBat;

  @override
  void initState() {
    super.initState();
    _daBat = khoaDaBat();
  }

  Future<void> _batKhoa(bool bat) async {
    if (bat) {
      final daDat = await Navigator.of(
        context,
      ).push<bool>(MaterialPageRoute(builder: (_) => const SetPinScreen()));
      if (daDat != true) return;
    } else {
      await tatKhoa();
    }
    if (mounted) setState(() => _daBat = khoaDaBat());
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Bảo mật')),
      body: FutureBuilder<bool>(
        future: _daBat,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }
          final bat = snap.data ?? false;
          return ListView(
            children: [
              SwitchListTile(
                title: const Text('Khoá bằng PIN khi mở lại ứng dụng'),
                subtitle: const Text(
                  'Yêu cầu nhập PIN (hoặc vân tay/khuôn mặt nếu thiết bị hỗ trợ) '
                  'khi ứng dụng được mở lại sau một thời gian không dùng.',
                ),
                value: bat,
                onChanged: _batKhoa,
              ),
              if (bat)
                ListTile(
                  leading: const Icon(Icons.pin_outlined),
                  title: const Text('Đổi PIN'),
                  onTap: () => _batKhoa(true),
                ),
            ],
          );
        },
      ),
    );
  }
}
