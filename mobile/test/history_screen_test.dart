import 'package:dclass_mobile/models/lich_su_giao_dich.dart';
import 'package:dclass_mobile/screens/history_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'fakes/fake_diem_api.dart';

void main() {
  Widget boc(Widget child) => MaterialApp(home: child);

  LichSuGiaoDich giaoDich({
    int id = 1,
    String loai = 'CONG_DIEM',
    int bienDiem = 2,
    int soDuSau = 10,
    String hoTen = 'An',
    String? lyDo = 'Phat bieu',
    String? qua,
    String? ghiChu,
  }) {
    return LichSuGiaoDich(
      id: id,
      loai: loai,
      bienDiem: bienDiem,
      soDuSau: soDuSau,
      ghiChu: ghiChu,
      taoLuc: '2026-01-01 08:00:00',
      hoTen: hoTen,
      lyDo: lyDo,
      qua: qua,
    );
  }

  testWidgets('shows an empty state when there is no history', (
    tester,
  ) async {
    final api = FakeDiemApi();
    await tester.pumpWidget(boc(HistoryScreen(api: api)));
    await tester.pumpAndSettle();

    expect(find.text('Chưa có giao dịch nào'), findsOneWidget);
  });

  testWidgets('lists a point award with the reason and the running balance',
      (tester) async {
    final api = FakeDiemApi()
      ..lichSuTraVe = [
        giaoDich(loai: 'CONG_DIEM', bienDiem: 3, soDuSau: 13, lyDo: 'Cham chi')
      ];
    await tester.pumpWidget(boc(HistoryScreen(api: api)));
    await tester.pumpAndSettle();

    expect(find.text('An - Cham chi'), findsOneWidget);
    expect(find.text('+3'), findsOneWidget);
    expect(find.text('còn 13 đ'), findsOneWidget);
  });

  testWidgets('lists a gift redemption as a deduction', (tester) async {
    final api = FakeDiemApi()
      ..lichSuTraVe = [
        giaoDich(loai: 'DOI_DIEM', bienDiem: -5, soDuSau: 8, qua: 'Sticker')
      ];
    await tester.pumpWidget(boc(HistoryScreen(api: api)));
    await tester.pumpAndSettle();

    expect(find.text('An - Sticker'), findsOneWidget);
    expect(find.text('-5'), findsOneWidget);
  });

  testWidgets('restricting to one student omits the name from the title', (
    tester,
  ) async {
    final api = FakeDiemApi()..lichSuTraVe = [giaoDich(lyDo: 'Cham chi')];
    await tester.pumpWidget(
      boc(HistoryScreen(api: api, hocSinhId: 1, tieuDe: 'Lịch sử - An')),
    );
    await tester.pumpAndSettle();

    expect(find.text('Cham chi'), findsOneWidget);
    expect(find.text('An - Cham chi'), findsNothing);
  });

  testWidgets('shows an error message when the fetch fails', (tester) async {
    final api = FakeDiemApi()..loiLichSu = Exception('mat_ket_noi');
    await tester.pumpWidget(boc(HistoryScreen(api: api)));
    await tester.pumpAndSettle();

    expect(find.textContaining('Lỗi'), findsOneWidget);
  });
}
