import 'package:flutter/material.dart';

import '../models/qua_tang.dart';
import '../theme/dclass_colors.dart';
import '../widgets/pill_button.dart';

/// Bottom sheet to pick a gift to redeem - mirrors the reason-picker sheet
/// used by the add-points flow in students_screen.dart.
///
/// [soDuHienTai] (the student's current balance) disables - same as an
/// out-of-stock gift - any gift they can't afford. The server is still the
/// real authority (a stale cached balance could be wrong), but this avoids
/// the common case of a teacher redeeming a gift offline that's certain to
/// fail once synced, after already telling the student it worked.
Future<QuaTang?> chonQuaTangDeDoi(
  BuildContext context,
  List<QuaTang> ds, {
  required int soDuHienTai,
}) {
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
              (qt) {
                final hetHang = qt.tonKho == 0;
                final khongDuDiem = qt.giaDiem > soDuHienTai;
                final coTheDoi = !hetHang && !khongDuDiem;
                return Padding(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  child: ListTile(
                    leading: Container(
                      width: 40,
                      height: 40,
                      decoration: BoxDecoration(
                        color: const Color(0xFFF5ECD9),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: const Icon(Icons.card_giftcard,
                          color: DClassColors.warning),
                    ),
                    title: Text(qt.ten,
                        style: const TextStyle(fontWeight: FontWeight.w700)),
                    subtitle: Text('Còn ${qt.tonKho} - ${qt.giaDiem} điểm'),
                    trailing: hetHang
                        ? const StatusBadge.danger(label: 'Hết hàng')
                        : khongDuDiem
                            ? const StatusBadge.danger(label: 'Không đủ điểm')
                            : StatusBadge.warning(label: '${qt.giaDiem} đ'),
                    enabled: coTheDoi,
                    onTap: () => Navigator.of(ctx).pop(qt),
                  ),
                );
              },
            )
            .toList(),
      ),
    ),
  );
}
