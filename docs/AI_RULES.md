# Quy tắc & quy ước mã nguồn

- **Ngôn ngữ & định danh**: tiếng Việt không dấu, snake_case cho tên file, biến, hàm, bảng/cột DB, khóa JSON, query string (`hanh_dong`, `hoc_sinh_id`, ...). Tránh viết hoa ký tự đầu biến/hàm.
- **PHP**: bắt buộc `declare(strict_types=1);`, luôn dùng PDO prepare/execute, không echo thẳng dữ liệu. Mọi API phải trả về qua `json_phan_hoi($ok, $du_lieu, $thong_bao)` với header `application/json`.
- **SQLite**: bật `PRAGMA foreign_keys = ON` ngay sau kết nối; thao tác số dư/đổi quà phải gói trong transaction và rollback nếu có exception. Không dùng `SELECT ... FOR UPDATE` (SQLite không hỗ trợ); cập nhật số dư bằng câu lệnh cập nhật nguyên tử (ví dụ `UPDATE vi_diem SET so_du = so_du - ? WHERE hoc_sinh_id=? AND so_du >= ?`).
- **Xác thực & phân quyền**: API yêu cầu session đăng nhập (`yeu_cau_dang_nhap()`); ADMIN toàn quyền, GV chỉ thấy lớp được gán (xem `lop_duoc_gan`, `kiem_tra_quyen_lop`). Đăng nhập có giới hạn 3 lần sai → khóa 10 phút, có cookie ghi nhớ (`gv_u`, `gv_t`).
- **Upload**: chỉ chấp nhận ảnh JPEG/PNG/GIF/WEBP, tối đa 2MB và 4000px; re-encode (trừ GIF) và lưu vào `upload/avatar/` hoặc `upload/qua/` với tên ngẫu nhiên. Chỉ xóa ảnh cũ khi nằm trong thư mục upload nội bộ.
- **Ghi log**: hành động quản trị phải gọi `ghi_log($pdo, $giao_vien_id, $hanh_dong, $noi_dung)` để lưu vào bảng `nhat_ky`.
- **Tài nguyên tĩnh**: giữ nguyên asset nội bộ trong `public/vendor/` (bootstrap, icons, chartjs, AOS, Nunito). Không thêm CDN nếu không bắt buộc.
- **Kiểm thử**: chạy `composer lint`, `composer phpstan`, `composer test` trước khi phát hành. Dữ liệu test nên dùng SQLite in-memory hoặc DB tạm trong `data/`.
