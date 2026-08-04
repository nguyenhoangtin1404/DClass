# Hướng dẫn vận hành & deploy

- **Chuẩn bị môi trường**
  - Yêu cầu: PHP 8.2+ có `pdo_sqlite`, quyền ghi thư mục `data/`, `upload/`.
  - Sao chép cấu hình: `cp config/env.php.example config/env.php`, chỉnh `db_path`, `session_name` nếu cần chạy song song nhiều môi trường.

- **Khởi tạo/migrate CSDL**
  - Chạy `php tools/cai_dat.php` để tạo bảng (đọc từ `config/luoc_do.sql`) và tự backup nếu file DB đã tồn tại. Không seed tài khoản nào — giáo viên tự đăng ký qua trang đăng nhập. Thêm `--seed` chỉ khi cần 1 tài khoản mẫu cho dev/demo cục bộ (`gv1`/`123456`, bắt buộc đổi mật khẩu ngay lần đăng nhập đầu).
  - Xuất/nhập CSV thủ công khi cần rollback:  
    - Xuất: `php tools/cai_dat.php --export-hoc-sinh --export-so-cai`  
    - Nhập: `php tools/cai_dat.php --import-hoc-sinh=duong_dan.csv --import-so-cai=duong_dan.csv`
  - Hỗ trợ vận hành (CLI, chỉ người có quyền chạy trên server, không lộ qua web):
    - Liệt kê tài khoản: `php tools/cai_dat.php --liet-ke-tai-khoan`
    - Đặt lại mật khẩu cho 1 giáo viên khi được yêu cầu hỗ trợ: `php tools/cai_dat.php --dat-lai-mat-khau=ten_dang_nhap --mat-khau-moi=xxxx`

- **Kiểm thử & chất lượng**
  - Cài deps: `composer install`
  - Lint cú pháp: `composer lint`
  - Static analysis: `composer phpstan`
  - Unit/feature tests (SQLite in-memory): `composer test`

- **CI/CD**
  - Workflow CI (`.github/workflows/ci.yml`) chạy trên PHP 8.2: composer install → lint → PHPStan → PHPUnit.
  - Workflow deploy FTP giữ nguyên ( `.github/workflows/deploy.yml` ).

- **Chạy ứng dụng**
  - Dev nhanh: `php -S 0.0.0.0:8000` chạy tại **thư mục gốc repo** (không `-t public` — xem lý do trong README.md), truy cập `http://localhost:8000/public/...`.
  - Sản xuất: DocumentRoot của vhost trỏ vào **gốc repo** (không phải `public/`), vì JS gọi API qua đường dẫn tương đối `../api/...` cần `public/` và `api/` là 2 thư mục anh em. Vì docroot là gốc repo, **bắt buộc** phải chặn truy cập trực tiếp các thư mục/file nhạy cảm (`config/`, `lib/`, `data/`, `tests/`, `scripts/`, `tools/`, `vendor/`, `composer.json`, `composer.lock`, `phpunit.xml`, `phpstan.neon.dist`) — repo đã có sẵn `.htaccess` gốc chặn mặc định (deny-by-default) và `.htaccess` riêng trong `public/`, `api/`, `upload/` để mở lại quyền truy cập cho 3 thư mục này. Chỉ hoạt động nếu Apache bật `AllowOverride All` (hoặc tối thiểu `AllowOverride Limit AuthConfig`) cho vhost.

- **Checklist bảo mật trước khi deploy online (Apache)**
  - `AllowOverride All` (hoặc tương đương) phải bật cho vhost để các file `.htaccess` trong repo có hiệu lực — nếu không, `config/`, `data/`... sẽ lộ trực tiếp qua URL.
  - Tắt `display_errors`, bật `log_errors` trong `php.ini` production (không để lộ stack trace/đường dẫn server khi có lỗi).
  - Bắt buộc HTTPS: dùng Let's Encrypt/mod_ssl trực tiếp trên Apache, hoặc đặt sau reverse proxy (Nginx/Cloudflare) có TLS termination — code đã tự nhận diện HTTPS qua `X-Forwarded-Proto` khi chạy sau proxy (xem `dang_https()` trong `config/db.php`).
  - Không có tài khoản mặc định trên môi trường online — mỗi giáo viên tự đăng ký tài khoản riêng qua trang đăng nhập, chỉ thấy/sửa được dữ liệu do chính mình tạo (lớp, học sinh, lý do, quà tặng).
  - Sinh `remember_secret` ngẫu nhiên trong `config/env.php` (`php -r "echo bin2hex(random_bytes(32));"`) trước khi bật tính năng "ghi nhớ đăng nhập".
  - Cấp quyền ghi cho `data/` và `upload/` (user chạy PHP-FPM/mod_php), nhưng đảm bảo cả 2 không lộ qua URL ngoài các file ảnh hợp lệ trong `upload/`.

