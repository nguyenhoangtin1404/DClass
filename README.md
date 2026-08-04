# Hệ thống quản lý điểm thưởng học sinh (PHP + SQLite) – chạy offline

Ứng dụng web cho giáo viên cộng/đổi điểm, quản lý học sinh, quà tặng, lịch sử và thống kê. Toàn bộ định danh/API/CSDL dùng tiếng Việt không dấu, snake_case. Bộ asset (CSS/JS/font) đã được lưu local để chạy không cần internet.

## Tính năng
- Giáo viên tự đăng ký tài khoản, tự tạo và quản lý lớp/học sinh/lý do/quà tặng của riêng mình — mỗi tài khoản hoàn toàn độc lập, không ai thấy hay sửa được dữ liệu của giáo viên khác.
- Đăng nhập/đăng xuất, ghi nhớ đăng nhập.
- Học sinh: thêm/sửa/bật/tắt, tìm kiếm, import/export CSV, upload avatar, xem số dư.
- Điểm và quà: cộng điểm theo lý do; đổi quà (kiểm tồn kho, kiểm số dư); theo dõi sổ cái.
- Lịch sử & thống kê: lọc/sort lịch sử, dashboard xu hướng 30 ngày, top quà/lý do, histogram số dư.
- API JSON thống nhất `{ok, du_lieu, thong_bao}`.

## Chạy offline & thư viện local
- Tất cả trang dùng asset nội bộ trong `public/vendor/`:
  - `vendor/bootswatch/bootstrap.min.css` (Morph 5.3.3, đã bỏ import Google Fonts)
  - `vendor/bootstrap/bootstrap.bundle.min.js`
  - `vendor/bootstrap-icons/` (CSS + fonts)
  - `vendor/aos/` (CSS/JS), `vendor/chartjs/chart.umd.min.js`
  - Font Nunito local: `public/vendor/nunito/nunito.css` trỏ đến 3 file TTF (400/600/700)
- Nếu cần tải lại, xem `public/vendor/asset_links.txt` và giữ nguyên cấu trúc thư mục.

## Cài đặt nhanh
1) Yêu cầu: PHP 8+ với PDO SQLite.
2) Clone dự án, đảm bảo PHP có quyền ghi `data/` và `upload/`.
3) Chạy `php -S 0.0.0.0:8000` tại **thư mục gốc repo** (không dùng `-t public` vì JS trong `public/*.php` gọi API bằng đường dẫn tương đối `../api/...`, cần `public/` và `api/` là 2 thư mục anh em cùng cấp docroot), truy cập `http://localhost:8000/public/dang_nhap.php` để khởi tạo DB:
   - Không có tài khoản mặc định — bấm "Chưa có tài khoản? Đăng ký ngay" trên trang đăng nhập để tự tạo tài khoản giáo viên đầu tiên.

## Cấu trúc thư mục
```
config/        Lược đồ, kết nối DB, migrate
api/           Endpoint JSON (đăng nhập, học sinh, điểm, lý do, quà, quản trị, upload, nhật ký)
lib/           Hàm tiện ích (json_phan_hoi, than_json, ghi nhớ, ghi_log)
public/        Giao diện web + asset local trong vendor/
upload/        Lưu avatar/quà
data/          SQLite DB (`ung_dung.db`)
rule_dat_ten_tieng_viet.txt  Quy tắc đặt tên/dự án
```

## Lược đồ CSDL (tóm tắt)
- Bảng chính: `giao_vien`, `lop_hoc`, `hoc_sinh`, `ly_do`, `qua_tang`, `vi_diem`, `so_cai_diem`, `giao_vien_lop`, `nhat_ky`.
- `ly_do` và `qua_tang` có cột `nguoi_tao_id` — mỗi giáo viên chỉ thấy/sửa được lý do và quà do chính mình tạo. `lop_hoc` không có cột sở hữu trực tiếp, quyền truy cập xác định qua bảng `giao_vien_lop` (giáo viên nào được gán vào lớp nào).
- Điểm hiện thời ở `vi_diem`, lịch sử tại `so_cai_diem` (loai: `CONG_DIEM`, `DOI_DIEM`, `HOAN_TAC`).
- PRAGMA `foreign_keys = ON`.

## API (mẫu)
- Đăng ký: `POST /api/dang_nhap.php?hanh_dong=dang_ky`
- Đăng nhập: `POST /api/dang_nhap.php?hanh_dong=dang_nhap`
- Học sinh: `GET/POST /api/hoc_sinh.php` (`hanh_dong=sua|bat_tat`)
- Điểm: `POST /api/diem.php?hanh_dong=cong|quy_doi`, `GET ?hanh_dong=lich_su|thong_ke`
- CSV HS: `/api/hoc_sinh_csv.php?hanh_dong=xuat|nhap`
- Quản trị: `/api/giao_vien_quan_tri.php` (đổi mật khẩu của chính mình), `/api/lop_hoc_quan_tri.php`, `/api/ly_do_quan_tri.php`, `/api/qua_tang_quan_tri.php`

## Lưu ý bảo mật/vận hành
- Giữ `declare(strict_types=1);`, dùng HTTPS nếu triển khai Internet.
- Giao dịch điểm trên SQLite: nên khóa sớm (`BEGIN IMMEDIATE`) khi thay đổi số dư.
- Upload ảnh kiểm MIME, sinh tên ngẫu nhiên; cấp quyền ghi `upload/`.
- Khi thay đổi schema, cập nhật `config/luoc_do.sql` và migrate trong `config/db.php`.

## Phát triển/kiểm thử
- Tuân thủ quy tắc đặt tên trong `rule_dat_ten_tieng_viet.txt`.
- Kiểm thử thủ công các luồng: đăng nhập/ghi nhớ, cộng/đổi điểm, import CSV, upload ảnh, phân quyền, báo cáo.

## License
Nội bộ/Chưa khai báo. Thêm giấy phép nếu phát hành.
