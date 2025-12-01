# Hệ thống quản lý điểm thưởng học sinh (PHP + SQLite)

Ứng dụng web giúp giáo viên cộng/trừ điểm, đổi quà, xem lịch sử và thống kê nhanh cho học sinh. Toàn bộ định danh, API và CSDL tuân theo quy ước tiếng Việt không dấu, snake_case.

## Tính năng chính
- Đăng nhập/đăng xuất, ghi nhớ đăng nhập bằng cookie ký HMAC.
- Quản lý học sinh: thêm/sửa/bật/tắt, tìm kiếm, import/export CSV, upload avatar.
- Cộng điểm, đổi điểm lấy quà, xem lịch sử giao dịch, thống kê top.
- Quản trị: quản lý giáo viên, lớp học, lý do cộng/trừ, quà tặng (kèm tồn kho, ảnh).
- API JSON thống nhất `{ok, du_lieu, thong_bao}`.

## Mô tả chức năng
- **Đăng nhập & phiên**: xác thực giáo viên, chống brute-force cơ bản, hỗ trợ ghi nhớ đăng nhập qua cookie ký HMAC, logout xóa session/cookie.
- **Học sinh**: CRUD, bật/tắt hoạt động, tìm kiếm theo tên/mã, import CSV (upsert theo mã), export CSV, xem số dư điểm, upload avatar.
- **Lý do điểm**: tạo/sửa/bật/tắt/xóa lý do cộng/trừ, phục vụ cho thao tác cộng điểm nhanh.
- **Quà tặng**: tạo/sửa/bật/tắt/xóa quà, quản lý giá điểm và tồn kho (có thể không giới hạn), upload ảnh minh họa.
- **Cộng/đổi điểm**: cộng điểm theo lý do; đổi điểm lấy quà có kiểm tồn kho và số dư; lưu lịch sử vào sổ cái với loại giao dịch, ghi chú, số dư sau.
- **Lịch sử & thống kê**: tra lịch sử giao dịch (lọc theo học sinh), thống kê top số dư, top cộng điểm, top đổi điểm, tồn kho quà, quà được yêu thích.
- **Quản trị**: thêm giáo viên mới, đổi mật khẩu; quản lý lớp học; quản lý danh mục lý do và quà tặng.

## Chức năng mở rộng & nâng cấp đề xuất
- **Bảo mật**: bắt buộc HTTPS, hạn chế IP/2FA cho quản trị, CSRF token cho POST, rate limit API cộng/đổi điểm, log audit đầy đủ (ai làm gì, khi nào).
- **Hiệu năng/đồng bộ**: khóa giao dịch `BEGIN IMMEDIATE`, cập nhật số dư nguyên tử (UPDATE ... RETURNING), cache danh mục lớp/lý do/quà (etag/if-none-match).
- **Trải nghiệm**: phím tắt/bàn phím cho trang chấm điểm nhanh, hiển thị ảnh học sinh/quà, thông báo realtime (WebSocket/EventSource) khi cập nhật điểm.
- **Quy đổi nâng cao**: hỗ trợ hoàn tác giao dịch, đổi nhiều quà một lần, phiếu giảm giá/khuyến mãi theo sự kiện, giới hạn số lần đổi trong ngày.
- **Báo cáo**: xuất Excel/PDF, biểu đồ theo lớp/thời gian, dashboard quản trị tổng quan, cảnh báo tồn kho thấp.
- **Triển khai/vận hành**: docker-compose (php-fpm + nginx), backup/restore DB, script migrate riêng thay vì ALTER rải rác, kiểm thử tự động cho API chính.

## Cấu trúc thư mục
```
config/        Kết nối DB SQLite, lược đồ
api/           Endpoint JSON (dang_nhap, hoc_sinh, diem, ly_do, qua_tang, quản trị, upload)
lib/           Hàm tiện ích (json_phan_hoi, than_json, ghi nhớ cookie)
public/        Giao diện web (đăng nhập, trang chính chấm điểm, quản lý, lịch sử, báo cáo, cấu hình)
upload/        Lưu file upload avatar/quà
data/          CSDL SQLite (`ung_dung.db`)
rule_dat_ten_tieng_viet.txt  Quy tắc đặt tên và chuẩn dự án
```

## Cài đặt nhanh
1) Yêu cầu: PHP 8+, extension PDO SQLite.
2) Clone dự án, đảm bảo PHP có quyền ghi vào `data/` và `upload/`.
3) Lần chạy đầu: truy cập `public/dang_nhap.php` (hoặc gốc `/` sẽ redirect) để tự tạo DB và seed tài khoản mặc định:
   - Tài khoản: `gv1`
   - Mật khẩu: `123456`
4) Đăng nhập và đổi mật khẩu ngay tại mục quản trị giáo viên.

## Lược đồ CSDL (tóm tắt)
- Bảng chính: `giao_vien`, `lop_hoc`, `hoc_sinh`, `ly_do`, `qua_tang`, `vi_diem`, `so_cai_diem`.
- Khóa ngoại bật `PRAGMA foreign_keys = ON`.
- Điểm số lưu tại `vi_diem`, lịch sử tại `so_cai_diem` (loai: `CONG_DIEM`, `DOI_DIEM`, `HOAN_TAC`).

## API chính
- Đăng nhập/đăng xuất: `POST /api/dang_nhap.php?hanh_dong=dang_nhap|dang_xuat`
- Học sinh: `GET/POST /api/hoc_sinh.php` (`hanh_dong`: `sua`, `bat_tat`)
- Lý do: `GET /api/ly_do.php`
- Quà tặng: `GET /api/qua_tang.php`
- Điểm: `POST /api/diem.php?hanh_dong=cong|quy_doi`, `GET ?hanh_dong=lich_su|thong_ke`
- CSV học sinh: `/api/hoc_sinh_csv.php?hanh_dong=xuat|nhap`
- Quản trị: `/api/giao_vien_quan_tri.php`, `/api/lop_hoc_quan_tri.php`, `/api/ly_do_quan_tri.php`, `/api/qua_tang_quan_tri.php`, upload ảnh `/api/upload_avatar.php`, `/api/upload_qua.php`.

Mọi phản hồi đều theo schema JSON: `{ "ok": true|false, "du_lieu": ..., "thong_bao": "..." }`.

## Bảo mật & lưu ý
- Bật `declare(strict_types=1);` trong mọi file PHP.
- Giao dịch điểm dùng SQLite, cần khóa sớm (`BEGIN IMMEDIATE`) để tránh race khi cộng/đổi.
- Upload ảnh kiểm tra MIME, sinh tên ngẫu nhiên, thư mục `upload/` cần quyền ghi.
- Đặt HTTPS để cookie ghi nhớ an toàn hơn (`secure`).

## Phát triển
- Tuân thủ quy tắc trong `rule_dat_ten_tieng_viet.txt` (định danh không dấu, snake_case, tên bảng số nhiều).
- Chạy format thủ công nếu cần; không phụ thuộc composer.
- Nếu thay đổi schema, cập nhật `config/luoc_do.sql` và logic migrate trong `config/db.php`.
- Kiểm thử thủ công các luồng: đăng nhập/ghi nhớ, cộng điểm, đổi quà với tồn kho, import CSV, upload ảnh.

## License
Nội bộ/Chưa khai báo. Thêm giấy phép nếu phát hành.
