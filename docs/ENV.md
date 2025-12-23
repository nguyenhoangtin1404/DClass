# Cấu hình môi trường

- **Yêu cầu runtime**: PHP 8.1+ (CI dùng 8.2) với extension `pdo_sqlite`, quyền ghi thư mục `data/` và `upload/`.
- **Biến cấu hình** (`config/env.php`, copy từ `config/env.php.example`):
  ```php
  return [
    'db_path' => __DIR__ . '/../data/ung_dung.db', // đường dẫn tuyệt đối tới file SQLite
    'session_name' => 'dclass_sid',                // tên session (tùy chỉnh nếu deploy nhiều môi trường trên cùng domain)
  ];
  ```
- **Kết nối DB**: nếu `db_path` không khai báo, mặc định `data/ung_dung.db`; thư mục chứa file DB sẽ được tự tạo nếu thiếu.
- **Server**: dev nhanh bằng `php -S 0.0.0.0:8000 -t public`; production trỏ web server vào `public/`, bắt buộc HTTPS nếu công khai.
- **Thư mục ghi file**:
  - `data/` chứa DB SQLite (tự tạo file nếu chưa có).
  - `upload/avatar/`, `upload/qua/` chứa ảnh upload; script tự tạo nếu thiếu.
- **Phụ thuộc dev**: `composer install` để có PHPUnit và PHPStan; script lint nằm trong `scripts/lint.php`.
- **Backup/khởi tạo**: `php tools/cai_dat.php --seed` tạo bảng, seed admin/lớp/lý do/quà; có flag import/export CSV & sổ cái để backup thủ công.
