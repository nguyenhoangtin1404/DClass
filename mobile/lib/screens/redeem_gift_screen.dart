import 'package:flutter/material.dart';

import '../models/qua_tang.dart';

/// Bottom sheet to pick a gift to redeem - mirrors the reason-picker sheet
/// used by the add-points flow in students_screen.dart.
Future<QuaTang?> chonQuaTangDeDoi(BuildContext context, List<QuaTang> ds) {
  return showModalBottomSheet<QuaTang>(
    context: context,
    builder: (ctx) => SafeArea(
      child: ListView(
        shrinkWrap: true,
        children: ds
            .map(
              (qt) => ListTile(
                leading: const Icon(Icons.card_giftcard),
                title: Text(qt.ten),
                subtitle: Text('Còn ${qt.tonKho} - ${qt.giaDiem} điểm'),
                enabled: qt.tonKho != 0,
                onTap: () => Navigator.of(ctx).pop(qt),
              ),
            )
            .toList(),
      ),
    ),
  );
}
