# Lược đồ CSDL (SQLite)

- Bật `PRAGMA foreign_keys = ON` sau khi kết nối (đã thực hiện trong `config/db.php`).
- File lược đồ gốc: `config/luoc_do.sql`; `config/db.php` còn bổ sung một số cột/bảng khi migrate.

## Bảng & cột chính
- **giao_vien**: `id` (PK, AUTOINCREMENT), `ten_dang_nhap` (UNIQUE, NOT NULL), `mat_khau_bam` (NOT NULL), `vai_tro` (mặc định `GV`, không còn cấp quyền đặc biệt — mọi giáo viên bình đẳng), `tao_luc` (TEXT, default `datetime('now')`).
- **lop_hoc**: `id` (PK), `ten` (NOT NULL), `dang_hoat_dong` (INTEGER default 1). Không có cột sở hữu trực tiếp — quyền truy cập xác định qua `giao_vien_lop`.
- **giao_vien_lop**: `giao_vien_id` (FK giao_vien), `lop_hoc_id` (FK lop_hoc), PK (giao_vien_id, lop_hoc_id). Giáo viên tạo lớp tự động được thêm vào đây.
- **hoc_sinh**: `id` (PK), `ma` (UNIQUE **toàn CSDL**, kể cả giữa các giáo viên khác nhau — xem lưu ý bên dưới), `ho_ten` (NOT NULL), `stt` (INTEGER, tùy chọn), `lop_hoc_id` (FK lop_hoc), `anh_dai_dien_url` (TEXT), `gioi_tinh` (TEXT), `ngay_sinh` (TEXT), `dang_hoat_dong` (INTEGER default 1), `tao_luc` (TEXT default now).
- **vi_diem**: `hoc_sinh_id` (PK, FK hoc_sinh), `so_du` (INTEGER NOT NULL default 0).
- **ly_do**: `id` (PK), `tieu_de` (TEXT NOT NULL), `bien_diem` (INTEGER NOT NULL), `dang_hoat_dong` (INTEGER default 1), `nguoi_tao_id` (FK giao_vien, ON DELETE CASCADE — chủ sở hữu, lọc mọi truy vấn theo cột này).
- **qua_tang**: `id` (PK), `ten` (TEXT NOT NULL), `gia_diem` (INTEGER NOT NULL), `ton_kho` (INTEGER default 0, `-1` = không giới hạn), `dang_hoat_dong` (INTEGER default 1), `anh_url` (TEXT), `nguoi_tao_id` (FK giao_vien, ON DELETE CASCADE — chủ sở hữu).
- **so_cai_diem**: `id` (PK), `hoc_sinh_id` (FK hoc_sinh), `giao_vien_id` (FK giao_vien), `loai` (TEXT: `CONG_DIEM|DOI_DIEM|HOAN_TAC`), `ly_do_id` (FK ly_do), `qua_tang_id` (FK qua_tang), `bien_diem` (INTEGER), `so_du_sau` (INTEGER), `ghi_chu` (TEXT), `tao_luc` (TEXT default now). Chỉ mục: `idx_so_cai_hoc_sinh_thoi_gian` trên `(hoc_sinh_id, tao_luc)`.
- **nhat_ky**: `id` (PK), `giao_vien_id` (FK giao_vien), `hanh_dong` (TEXT NOT NULL), `noi_dung` (TEXT), `tao_luc` (TEXT default now). Không có API xem tổng hợp toàn hệ thống.
- **reset_khoa**: `ten_dang_nhap` (PK TEXT), `het_han` (INTEGER timestamp). Dùng để bypass tạm thời khóa đăng nhập.

**Lưu ý về `hoc_sinh.ma`**: cột này UNIQUE trên toàn CSDL dùng chung, không tách theo giáo viên. `api/hoc_sinh_csv.php` tự kiểm tra `lop_hoc_id` hiện tại của bản ghi trùng `ma` trước khi cho phép cập nhật qua import CSV, để 1 giáo viên không thể ghi đè học sinh của giáo viên khác chỉ bằng cách trùng mã.

## Khởi tạo CSDL trống
- `config/db.php`/`config/luoc_do.sql` chỉ tạo schema, **không seed tài khoản hay dữ liệu mẫu nào**. Giáo viên tự đăng ký qua `POST /api/dang_nhap.php?hanh_dong=dang_ky`, khi đó hệ thống tự seed vài `ly_do`/`qua_tang` mặc định cho riêng tài khoản đó (xem `api/dang_nhap.php`).
- `php tools/cai_dat.php --seed` (chỉ chạy khi truyền cờ này) tạo 1 tài khoản mẫu cho dev/demo cục bộ: `gv1` / `123456` (bắt buộc đổi mật khẩu ngay lần đăng nhập đầu), kèm lớp `4A/4B/4C` và vài lý do/quà mẫu gắn với tài khoản đó.

## Nâng cấp CSDL đã có dữ liệu thật (từ bản trước khi có `nguoi_tao_id`)
- `chay_migration()` trong `config/migration_nghiep_vu.php` tự chạy `chay_backfill_so_huu()` **đúng 1 lần** (đánh dấu trong bảng `migrations_ap_dung`, không lặp lại ở các lần sau, không cần thao tác tay) khi nâng cấp code lên bản này trên CSDL đã có dữ liệu cũ:
  - Tài khoản có `vai_tro='ADMIN'` được gán tường minh vào `giao_vien_lop` cho **mọi lớp đang có tại thời điểm nâng cấp** — giữ nguyên đúng phạm vi truy cập họ đang có (trước đây ADMIN bỏ qua mọi ràng buộc lớp), không tự động mở rộng thêm cho lớp tạo ra sau này.
  - Mỗi `ly_do`/`qua_tang` đang có (tạo trước khi có cột `nguoi_tao_id`, tức thuộc catalog dùng chung cũ) được **nhân bản cho từng tài khoản giáo viên đang tồn tại**: giáo viên có `id` nhỏ nhất nhận lại đúng bản ghi gốc (giữ nguyên `id`, lịch sử `so_cai_diem` tham chiếu tới vẫn đúng), các giáo viên còn lại nhận bản sao riêng — từ đó mỗi người tự sửa độc lập.
  - Không xoá, không sửa `hoc_sinh`/`so_cai_diem`/lịch sử điểm nào.
