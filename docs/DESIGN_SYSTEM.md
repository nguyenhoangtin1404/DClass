# Chuẩn thiết kế (Design System)

Tài liệu này là **nguồn tham chiếu chuẩn** cho giao diện DClass trên cả web
và mobile, để hai nền tảng đọc như cùng một sản phẩm thay vì hai bản skin
khác nhau. Token gốc (màu, font, thành phần) được định nghĩa trước trên web
(`public/vendor/theme.css`), sau đó mobile port lại y hệt trong
`mobile/lib/theme/` — xem [ARCHITECTURE.md](ARCHITECTURE.md) cho bối cảnh hệ
thống chung.

**Mockup trực quan:** [`design/mobile-ui-mockup.html`](design/mobile-ui-mockup.html)
— mở trực tiếp bằng trình duyệt (file tĩnh, không cần server) để xem 7 màn
hình mobile mẫu kèm bảng màu/kiểu chữ/thành phần. Đây là bản mockup đã dùng
làm chuẩn khi hiện thực hoá vào `mobile/lib` ở PR
[#114](https://github.com/nguyenhoangtin1404/DClass/pull/114).

## Font

Nunito (400/600/700) — duy nhất, không dùng thêm serif/mono. Cả hai nền
tảng dùng chung file font:

- Web: `public/vendor/nunito/*.ttf` qua `public/vendor/nunito/nunito.css`.
- Mobile: cùng file `.ttf` được copy vào `mobile/assets/fonts/`, khai báo ở
  `mobile/pubspec.yaml`.

## Bảng màu

Nguồn gốc: `public/vendor/theme.css`. Phiên bản mobile: `mobile/lib/theme/dclass_colors.dart`.

| Token | Hex | Dùng cho |
|---|---|---|
| `primary` | `#2151D1` | Nút chính, liên kết, icon AppBar |
| `secondary` | `#D62872` | Nút phụ |
| `success` | `#0D9A4A` | Viền/chữ nút lý do cộng điểm |
| `success` (badge đặc) | `#16A34A` | Badge `+điểm`, "Đã đồng bộ" |
| `danger` | `#D6254F` | Viền/chữ nút lý do trừ điểm, nút xoá |
| `warning` | `#D08700` | Nút thử lại, cảnh báo |
| `warning` (badge đặc) | `#FFC107` | Badge số dư điểm |
| `background` | `#F9F6F1` | Nền màn hình (giấy kem) |
| `list-bg` | `#F5F7FB` | Nền dòng danh sách |

Ribbon gradient (tiêu đề khu vực): `linear-gradient(135deg, #FFE4F3, #FFF3C9)`.
Gate gradient (màn Kết nối/Mở khoá): `radial-gradient(#3B82F6 → #2563EB → #111827 → #0B1220)`.

## Thành phần dùng chung

- **Nút pill viền đứt** — mọi nút trên web đều là pill bo tròn hoàn toàn,
  viền đứt 2px, nền trắng, chữ đậm cùng màu viền (kể cả nút hành động chính,
  không tô đặc). Web: `.btn` trong `theme.css`. Mobile: `DashedPillBorder`
  (`mobile/lib/theme/dashed_pill_border.dart`, tự vẽ nét đứt, không cần
  thêm dependency) + `PillButton` (`mobile/lib/widgets/pill_button.dart`).
- **Ribbon tiêu đề** — pill gradient hồng-vàng kèm ngôi sao `star.png` góc
  phải, dùng cho tiêu đề khu vực nội dung (không phải tiêu đề AppBar/nav —
  AppBar có nhiều action icon thì ribbon bị chèn ép, xem bài học ở
  `students_screen.dart`). Web: `.ribbon-title-modern`. Mobile:
  `RibbonHeader` (`mobile/lib/widgets/ribbon_header.dart`).
- **Badge đặc màu** — badge điểm `+2`/`-1`, trạng thái đồng bộ. Web:
  `.badge.bg-success` / `.bg-warning`. Mobile: `StatusBadge` (cùng file
  `pill_button.dart`).

## Trạng thái áp dụng

Đã hiện thực hoá đầy đủ vào `mobile/lib` (PR #114, đã merge). Một số điểm
mockup có nhưng chưa đưa vào code thật, vì cần cân nhắc thêm hoặc đụng tới
logic ngoài phạm vi style thuần tuý:

- Màn Mở khoá: mockup có bàn phím số tuỳ chỉnh; bản thật dùng bàn phím hệ
  thống + chấm PIN để giữ tương thích với test `find.byType(TextField)`
  sẵn có (`mobile/test/unlock_screen_test.dart`).
- Màn Kết nối: chưa có icon-bubble trang trí và nút "Quét mã QR" (chưa có
  tính năng quét mã thật, tránh thêm nút không hoạt động).
- Màn Lịch sử: mockup nhóm theo "Hôm nay/Hôm qua"; bản thật là danh sách
  phẳng (nhóm theo ngày là thay đổi hành vi/IA, không chỉ style).
- Avatar học sinh: mockup dùng 2 chữ cái đầu; bản thật giữ nguyên logic có
  sẵn (1 chữ cái đầu tên đầy đủ).

Khi cần chỉnh sửa màu/font/thành phần dùng chung, sửa ở
`public/vendor/theme.css` (web) và `mobile/lib/theme/` (mobile) cùng lúc để
hai bản không lệch nhau trở lại.
