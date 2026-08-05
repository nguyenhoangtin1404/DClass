import 'package:flutter/material.dart';

import '../models/qua_tang.dart';
import '../theme/dclass_colors.dart';
import '../widgets/pill_button.dart';

/// Bottom sheet to pick a gift to redeem - mirrors the reason-picker sheet
/// used by the add-points flow in students_screen.dart.
Future<QuaTang?> chonQuaTangDeDoi(BuildContext context, List<QuaTang> ds) {
  return showModalBottomSheet<QuaTang>(
    context: context,
    shape: const RoundedRectangleBorder(
      borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
    ),
    builder: (ctx) => SafeArea(
      child: ListView(
        shrinkWrap: true,
        padding: const EdgeInsets.fromLTRB(8, 12, 8, 12),
        children: ds
            .map(
              (qt) => Padding(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                child: ListTile(
                  leading: Container(
                    width: 40,
                    height: 40,
                    decoration: BoxDecoration(
                      color: const Color(0xFFF5ECD9),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(Icons.card_giftcard, color: DClassColors.warning),
                  ),
                  title: Text(qt.ten, style: const TextStyle(fontWeight: FontWeight.w700)),
                  subtitle: Text('Còn ${qt.tonKho} - ${qt.giaDiem} điểm'),
                  trailing: qt.tonKho == 0
                      ? const StatusBadge.danger(label: 'Hết hàng')
                      : StatusBadge.warning(label: '${qt.giaDiem} đ'),
                  enabled: qt.tonKho != 0,
                  onTap: () => Navigator.of(ctx).pop(qt),
                ),
              ),
            )
            .toList(),
      ),
    ),
  );
}
