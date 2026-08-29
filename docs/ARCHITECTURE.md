# Kiến trúc hệ thống

## Tổng quan
- Ứng dụng web đơn (server-rendered PHP) cho giáo viên quản lý điểm thưởng, chạy offline với SQLite.
- Backend thuần PHP 8.x + PDO SQLite, xác thực bằng session; API trả JSON thống nhất `{ok, du_lieu, thong_bao}`.
- Frontend là các trang PHP trong `public/` sử dụng asset nội bộ (Bootstrap 5 Morph, Bootstrap Icons, Chart.js, AOS, Nunito) từ `public/vendor/`.

## Các lớp thành phần
- **config/**: `db.php` thiết lập session, nạp `config/env.php` (nếu có), kết nối SQLite, chạy migrate nhẹ (`chay_migration`) và tạo schema từ `config/luoc_do.sql` khi CSDL chưa tồn tại (không seed tài khoản nào - giáo viên tự đăng ký). `env.php.example` làm mẫu cấu hình.
- **lib/**: tiện ích dùng chung (`tro_giup.php` cho JSON/validate/login guard, `ghi_nho.php` cho cookie remember-me, `dang_nhap_nghiep_vu.php` cho kiểm tra đăng nhập, `diem_nghiep_vu.php` cho nghiệp vụ cộng/trừ điểm, kiểm quyền sở hữu lớp, đảm bảo ví).
- **api/**: endpoint JSON cho đăng ký/đăng nhập, học sinh, điểm, lý do, quà, upload, quản lý lớp/lý do/quà tặng của chính giáo viên. Mỗi file include `config/db.php` + lib tương ứng.
- **public/**: các trang giao diện (`dang_nhap.php`, `trang_chinh.php`, `hoc_sinh_quan_ly.php`, `lich_su.php`, `bao_cao.php`, `cau_hinh.php`), partial `_nav.php`, favicon, thư mục upload và vendor.
- **data/**: chứa file SQLite `ung_dung.db` (tự tạo khi chạy lần đầu).
- **upload/**: lưu avatar học sinh và ảnh quà, chia thư mục con `avatar/`, `qua/`.
- **tools/**: script CLI `tools/cai_dat.php` tạo/migrate DB, seed dữ liệu, import/export CSV/so_cai để backup/khôi phục.
- **tests/**, **scripts/**: phục vụ lint, PHPUnit, PHPStan.

## Luồng chính
1) Giáo viên mới tự đăng ký qua `POST /api/dang_nhap.php?hanh_dong=dang_ky` (kích hoạt ngay, không cần xác thực email), hoặc đăng nhập qua `POST /api/dang_nhap.php?hanh_dong=dang_nhap` để nhận session (có tùy chọn ghi nhớ cookie).
2) Các trang giao diện gọi API JSON, gửi/nhận dữ liệu qua fetch/AJAX. Không có vai trò quản trị toàn cục — mọi quyền hạn dựa trên sở hữu: lớp qua bảng `giao_vien_lop`, lý do/quà qua cột `nguoi_tao_id`.
3) Nghiệp vụ cộng/đổi điểm dùng transaction trong `diem_nghiep_vu.php` để cập nhật `vi_diem` và ghi sổ cái `so_cai_diem` nhất quán.
4) Hành động của giáo viên (tạo/sửa/xóa lớp, lý do, quà, upload ảnh) ghi nhật ký vào bảng `nhat_ky`, gắn với `giao_vien_id` của người thực hiện.

## Triển khai & vận hành
- Chạy bằng PHP built-in **tại thư mục gốc repo, không dùng `-t public`**: `php -S 0.0.0.0:8000`, truy cập `http://localhost:8000/public/...` (JS trong `public/*.php` gọi API bằng đường dẫn tương đối `../api/...`, cần `public/` và `api/` là 2 thư mục anh em cùng cấp docroot - xem README.md). Sản xuất: DocumentRoot vhost cũng trỏ vào gốc repo, với `.htaccess` chặn truy cập trực tiếp các thư mục nhạy cảm (xem DEPLOY.md).
- Thư mục cần quyền ghi: `data/` (DB), `upload/` (ảnh), có thể cần `public/vendor/` nếu cập nhật asset.
- CI: `.github/workflows/ci.yml` chạy composer install → lint → PHPStan → PHPUnit; deploy FTP có workflow riêng (`deploy.yml`).
