# Hệ thống quản lý điểm thưởng học sinh (PHP + SQLite)

Ứng dụng web giúp giáo viên cộng/trừ điểm, đổi quà, xem lịch sử và thống kê nhanh cho học sinh. Toàn bộ định danh, API và CSDL tuân theo quy ước tiếng Việt không dấu, snake_case.

## Tính năng chính
- Đăng nhập/đăng xuất, ghi nhớ đăng nhập; phân quyền `ADMIN` (toàn quyền) và `GV` (theo lớp được gán).
- Quản lý học sinh: thêm/sửa/bật/tắt, tìm kiếm, import/export CSV (map linh hoạt header, chuẩn hóa ngày sinh/giới tính), upload avatar.
- Cộng điểm, đổi quà, xem lịch sử giao dịch, thống kê top; giới hạn dữ liệu theo lớp giáo viên được gán (trừ admin).
- Quản trị: quản lý giáo viên, lớp học (gán nhiều giáo viên), lý do cộng/trừ, quà tặng (tồn kho, ảnh), phân quyền vai trò.
- Audit log: ghi lại thao tác quản trị (thêm/sửa/xóa lớp, lý do, quà, phân quyền, đổi mật khẩu, thêm GV); admin có thể xem log.
- API JSON thống nhất `{ok, du_lieu, thong_bao}`.

## Mô tả chức năng
- **Đăng nhập & phiên**: xác thực giáo viên, chống brute-force cơ bản, hỗ trợ ghi nhớ đăng nhập, logout xóa session/cookie, lưu vai trò vào session.
- **Học sinh**: CRUD, bật/tắt hoạt động, tìm kiếm theo tên/mã, import CSV (upsert theo mã, lọc theo lớp GV), export CSV (lọc theo lớp GV), xem số dư điểm, upload avatar.
- **Lý do điểm**: tạo/sửa/bật/tắt/xóa lý do cộng/trừ.
- **Quà tặng**: tạo/sửa/bật/tắt/xóa quà, quản lý giá điểm và tồn kho, upload ảnh.
- **Cộng/đổi điểm**: cộng điểm theo lý do; đổi điểm lấy quà có kiểm tồn kho và số dư; lưu lịch sử vào sổ cái.
- **Lịch sử & thống kê**: tra lịch sử giao dịch, thống kê top số dư/cộng điểm/đổi điểm, tồn kho quà, quà được yêu thích; giáo viên chỉ xem lớp được gán.
- **Quản trị & phân quyền**: thêm giáo viên mới, đổi mật khẩu; gán lớp cho giáo viên; phân vai trò ADMIN/GV; xem nhật ký thao tác (admin).

## Chức năng mở rộng & nâng cấp đề xuất
- **Bảo mật**: bắt buộc HTTPS, CSRF token cho POST, rate-limit API cộng/đổi, 2FA cho admin, log audit chi tiết hơn (IP/user-agent).
- **Hiệu năng/đồng bộ**: khóa giao dịch sớm (`BEGIN IMMEDIATE`), cập nhật số dư nguyên tử, cache danh mục lớp/lý do/quà (etag/if-none-match).
- **Trải nghiệm**: phím tắt trang chấm điểm, hiển thị ảnh học sinh/quà, thông báo realtime (WebSocket/EventSource), tăng cỡ chữ/contrast theo môi trường sáng mạnh.
- **Quy đổi nâng cao**: hoàn tác giao dịch, đổi nhiều quà/lần, phiếu giảm giá/sự kiện, giới hạn số lần đổi theo ngày.
- **Báo cáo**: xuất Excel/PDF, biểu đồ theo lớp/thời gian, dashboard tổng quan, cảnh báo tồn kho thấp.
- **Triển khai/vận hành**: docker-compose (php-fpm + nginx), backup/restore DB, script migrate thay vì ALTER rải rác, kiểm thử tự động cho API chính.

## Cấu trúc thư mục
```
config/        Kết nối DB SQLite, lược đồ
api/           Endpoint JSON (đăng nhập, học sinh, điểm, lý do, quà, quản trị, upload, nhật ký)
lib/           Hàm tiện ích (json_phan_hoi, than_json, ghi nhớ cookie, ghi_log)
public/        Giao diện web (đăng nhập, trang chính chấm điểm, quản lý, lịch sử, báo cáo, cấu hình)
upload/        Lưu file upload avatar/quà
data/          CSDL SQLite (`ung_dung.db`)
rule_dat_ten_tieng_viet.txt  Quy tắc đặt tên và chuẩn dự án
```

## Cài đặt nhanh
1) Yêu cầu: PHP 8+, extension PDO SQLite.
2) Clone dự án, đảm bảo PHP có quyền ghi vào `data/` và `upload/`.
3) Lần chạy đầu: truy cập `public/dang_nhap.php` (hoặc gốc `/` sẽ redirect) để tự tạo DB và seed:
   - Tài khoản admin: `gv1` / `123456` (vai trò `ADMIN`)
4) Đăng nhập và đổi mật khẩu ngay tại mục quản trị giáo viên.

## Lược đồ CSDL (tóm tắt)
- Bảng chính: `giao_vien (vai_tro)`, `lop_hoc`, `hoc_sinh (stt, gioi_tinh, ngay_sinh)`, `ly_do`, `qua_tang (anh_url)`, `vi_diem`, `so_cai_diem`, `giao_vien_lop` (gán GV-lớp), `nhat_ky` (audit).
- Khóa ngoại bật `PRAGMA foreign_keys = ON`.
- Điểm số lưu tại `vi_diem`, lịch sử tại `so_cai_diem` (loai: `CONG_DIEM`, `DOI_DIEM`, `HOAN_TAC`).

## API chính
- Đăng nhập/đăng xuất: `POST /api/dang_nhap.php?hanh_dong=dang_nhap|dang_xuat`
- Học sinh: `GET/POST /api/hoc_sinh.php` (`hanh_dong`: `sua`, `bat_tat`)
- Lý do: `GET /api/ly_do.php`
- Quà tặng: `GET /api/qua_tang.php`
- Điểm: `POST /api/diem.php?hanh_dong=cong|quy_doi`, `GET ?hanh_dong=lich_su|thong_ke`
- CSV học sinh: `/api/hoc_sinh_csv.php?hanh_dong=xuat|nhap` (lọc theo lớp GV trừ admin)
- Quản trị: `/api/giao_vien_quan_tri.php`, `/api/lop_hoc_quan_tri.php`, `/api/ly_do_quan_tri.php`, `/api/qua_tang_quan_tri.php`, upload ảnh `/api/upload_avatar.php`, `/api/upload_qua.php`.
- Nhật ký: `GET /api/nhat_ky.php` (chỉ admin).

Tất cả phản hồi theo schema JSON: `{ "ok": true|false, "du_lieu": ..., "thong_bao": "..." }`.

## Bảo mật & lưu ý
- Duy trì `declare(strict_types=1);`.
- Giao dịch điểm trên SQLite nên khóa sớm (`BEGIN IMMEDIATE`) để tránh race khi cộng/đổi.
- Upload ảnh kiểm tra MIME, sinh tên ngẫu nhiên; `upload/` cần quyền ghi.
- Sử dụng HTTPS để cookie ghi nhớ an toàn hơn.

## Phát triển
- Tuân thủ quy tắc trong `rule_dat_ten_tieng_viet.txt` (định danh không dấu, snake_case, tên bảng số nhiều).
- Nếu thay đổi schema, cập nhật `config/luoc_do.sql` và logic migrate trong `config/db.php`.
- Kiểm thử thủ công các luồng: đăng nhập/ghi nhớ, cộng điểm, đổi quà với tồn kho, import CSV, upload ảnh, phân quyền.

## License
Nội bộ/Chưa khai báo. Thêm giấy phép nếu phát hành.
