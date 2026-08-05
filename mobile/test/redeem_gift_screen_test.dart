import 'package:dclass_mobile/models/qua_tang.dart';
import 'package:dclass_mobile/screens/redeem_gift_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  final quaConCon =
      QuaTang(id: 1, ten: 'Bút chì', giaDiem: 5, tonKho: 3, anhUrl: null);
  final quaHetHang =
      QuaTang(id: 2, ten: 'Sticker', giaDiem: 2, tonKho: 0, anhUrl: null);

  Widget boc(List<QuaTang> ds, void Function(QuaTang?) onKetQua) {
    return MaterialApp(
      home: Builder(
        builder: (context) => ElevatedButton(
          onPressed: () async {
            final ketQua = await chonQuaTangDeDoi(context, ds);
            onKetQua(ketQua);
          },
          child: const Text('Mở'),
        ),
      ),
    );
  }

  testWidgets('lists gifts with remaining stock and price', (tester) async {
    await tester.pumpWidget(boc([quaConCon, quaHetHang], (_) {}));
    await tester.tap(find.text('Mở'));
    await tester.pumpAndSettle();

    expect(find.text('Bút chì'), findsOneWidget);
    expect(find.text('Còn 3 - 5 điểm'), findsOneWidget);
    expect(find.text('Sticker'), findsOneWidget);
    expect(find.text('Còn 0 - 2 điểm'), findsOneWidget);
  });

  testWidgets('an out-of-stock gift is disabled', (tester) async {
    await tester.pumpWidget(boc([quaHetHang], (_) {}));
    await tester.tap(find.text('Mở'));
    await tester.pumpAndSettle();

    final tile = tester.widget<ListTile>(find.byType(ListTile));
    expect(tile.enabled, isFalse);
  });

  testWidgets('tapping an available gift resolves the future with it', (
    tester,
  ) async {
    QuaTang? chon;
    await tester.pumpWidget(boc([quaConCon], (kq) => chon = kq));
    await tester.tap(find.text('Mở'));
    await tester.pumpAndSettle();

    await tester.tap(find.text('Bút chì'));
    await tester.pumpAndSettle();

    expect(chon?.id, quaConCon.id);
  });
}
