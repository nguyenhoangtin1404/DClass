# Kiến trúc hệ thống

## Tổng quan
- Ứng dụng web đơn (server-rendered PHP) cho giáo viên quản lý điểm thưởng, chạy offline với SQLite.
- Backend thuần PHP 8.x + PDO SQLite, xác thực bằng session; API trả JSON thống nhất `{ok, du_lieu, thong_bao}`.
- Frontend là các trang PHP trong `public/` sử dụng asset nội bộ (Bootstrap 5 Morph, Bootstrap Icons, Chart.js, AOS, Nunito) từ `public/vendor/`.

## Các lớp thành phần
- **config/**: `db.php` thiết lập session, nạp `config/env.php` (nếu có), kết nối SQLite, chạy migrate nhẹ (`chay_migration`) và seed dữ liệu lần đầu từ `config/luoc_do.sql`. `env.php.example` làm mẫu cấu hình.
- **lib/**: tiện ích dùng chung (`tro_giup.php` cho JSON/validate/login guard, `ghi_nho.php` cho cookie remember-me, `dang_nhap_nghiep_vu.php` cho kiểm tra đăng nhập, `diem_nghiep_vu.php` cho nghiệp vụ cộng/trừ điểm, kiểm quyền lớp, đảm bảo ví).
- **api/**: endpoint JSON cho đăng nhập, học sinh, điểm, lý do, quà, upload, quản trị (tài khoản, lớp, lý do, quà, nhật ký). Mỗi file include `config/db.php` + lib tương ứng.
- **public/**: các trang giao diện (`dang_nhap.php`, `trang_chinh.php`, `hoc_sinh_quan_ly.php`, `lich_su.php`, `bao_cao.php`, `cau_hinh.php`), partial `_nav.php`, favicon, thư mục upload và vendor.
- **data/**: chứa file SQLite `ung_dung.db` (tự tạo khi chạy lần đầu).
- **upload/**: lưu avatar học sinh và ảnh quà, chia thư mục con `avatar/`, `qua/`.
- **tools/**: script CLI `tools/cai_dat.php` tạo/migrate DB, seed dữ liệu, import/export CSV/so_cai để backup/khôi phục.
- **tests/**, **scripts/**: phục vụ lint, PHPUnit, PHPStan.

## Luồng chính
1) Người dùng truy cập `public/dang_nhap.php`, gửi `POST /api/dang_nhap.php?hanh_dong=dang_nhap` để nhận session (có tùy chọn ghi nhớ cookie).
2) Các trang giao diện gọi API JSON, gửi/nhận dữ liệu qua fetch/AJAX. Quyền hạn dựa trên session (`ADMIN` vs `GV` + lớp được gán trong `giao_vien_lop`).
3) Nghiệp vụ cộng/đổi điểm dùng transaction trong `diem_nghiep_vu.php` để cập nhật `vi_diem` và ghi sổ cái `so_cai_diem` nhất quán.
4) Hành động quản trị (tạo tài khoản, lớp, lý do, quà, upload ảnh) ghi nhật ký vào bảng `nhat_ky` để truy vết.

## Triển khai & vận hành
- Chạy bằng PHP built-in: `php -S 0.0.0.0:8000 -t public` (hoặc web server khác trỏ document root tới `public/`).
- Thư mục cần quyền ghi: `data/` (DB), `upload/` (ảnh), có thể cần `public/vendor/` nếu cập nhật asset.
- CI: `.github/workflows/ci.yml` chạy composer install → lint → PHPStan → PHPUnit; deploy FTP có workflow riêng (`deploy.yml`).
