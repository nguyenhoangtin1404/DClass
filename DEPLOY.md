# Hướng dẫn vận hành & deploy

- **Chuẩn bị môi trường**
  - Yêu cầu: PHP 8.2+ có `pdo_sqlite`, quyền ghi thư mục `data/`, `upload/`.
  - Sao chép cấu hình: `cp config/env.php.example config/env.php`, chỉnh `db_path`, `session_name` nếu cần chạy song song nhiều môi trường.

- **Khởi tạo/migrate CSDL**
  - Chạy `php tools/cai_dat.php --seed` để tạo bảng (đọc từ `config/luoc_do.sql`), seed mẫu nếu DB trống và tự backup nếu file DB đã tồn tại.
  - Xuất/nhập CSV thủ công khi cần rollback:  
    - Xuất: `php tools/cai_dat.php --export-hoc-sinh --export-so-cai`  
    - Nhập: `php tools/cai_dat.php --import-hoc-sinh=duong_dan.csv --import-so-cai=duong_dan.csv`

- **Kiểm thử & chất lượng**
  - Cài deps: `composer install`
  - Lint cú pháp: `composer lint`
  - Static analysis: `composer phpstan`
  - Unit/feature tests (SQLite in-memory): `composer test`

- **CI/CD**
  - Workflow CI (`.github/workflows/ci.yml`) chạy trên PHP 8.2: composer install → lint → PHPStan → PHPUnit.
  - Workflow deploy FTP giữ nguyên ( `.github/workflows/deploy.yml` ).

- **Chạy ứng dụng**
  - Dev nhanh: `php -S 0.0.0.0:8000 -t public`
  - Sản xuất: trỏ vhost vào `public/`, bật HTTPS, cấp quyền ghi cho `data/` và `upload/`.

