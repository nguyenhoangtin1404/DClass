# Lược đồ CSDL (SQLite)

- Bật `PRAGMA foreign_keys = ON` sau khi kết nối (đã thực hiện trong `config/db.php`).
- File lược đồ gốc: `config/luoc_do.sql`; `config/db.php` còn bổ sung một số cột/bảng khi migrate.

## Bảng & cột chính
- **giao_vien**: `id` (PK, AUTOINCREMENT), `ten_dang_nhap` (UNIQUE, NOT NULL), `mat_khau_bam` (NOT NULL), `vai_tro` (`ADMIN|GV`, default `GV`), `tao_luc` (TEXT, default `datetime('now')`).
- **lop_hoc**: `id` (PK), `ten` (NOT NULL), `dang_hoat_dong` (INTEGER default 1).
- **giao_vien_lop**: `giao_vien_id` (FK giao_vien), `lop_hoc_id` (FK lop_hoc), PK (giao_vien_id, lop_hoc_id).
- **hoc_sinh**: `id` (PK), `ma` (UNIQUE), `ho_ten` (NOT NULL), `stt` (INTEGER, tùy chọn), `lop_hoc_id` (FK lop_hoc), `anh_dai_dien_url` (TEXT), `gioi_tinh` (TEXT), `ngay_sinh` (TEXT), `dang_hoat_dong` (INTEGER default 1), `tao_luc` (TEXT default now).
- **vi_diem**: `hoc_sinh_id` (PK, FK hoc_sinh), `so_du` (INTEGER NOT NULL default 0).
- **ly_do**: `id` (PK), `tieu_de` (TEXT NOT NULL), `bien_diem` (INTEGER NOT NULL), `dang_hoat_dong` (INTEGER default 1).
- **qua_tang**: `id` (PK), `ten` (TEXT NOT NULL), `gia_diem` (INTEGER NOT NULL), `ton_kho` (INTEGER default 0, `-1` = không giới hạn), `dang_hoat_dong` (INTEGER default 1), `anh_url` (TEXT).
- **so_cai_diem**: `id` (PK), `hoc_sinh_id` (FK hoc_sinh), `giao_vien_id` (FK giao_vien), `loai` (TEXT: `CONG_DIEM|DOI_DIEM|HOAN_TAC`), `ly_do_id` (FK ly_do), `qua_tang_id` (FK qua_tang), `bien_diem` (INTEGER), `so_du_sau` (INTEGER), `ghi_chu` (TEXT), `tao_luc` (TEXT default now). Chỉ mục: `idx_so_cai_hoc_sinh_thoi_gian` trên `(hoc_sinh_id, tao_luc)`.
- **nhat_ky**: `id` (PK), `giao_vien_id` (FK giao_vien), `hanh_dong` (TEXT NOT NULL), `noi_dung` (TEXT), `tao_luc` (TEXT default now).
- **reset_khoa**: `ten_dang_nhap` (PK TEXT), `het_han` (INTEGER timestamp). Dùng để bypass tạm thời khóa đăng nhập.

## Seed mặc định (khi DB trống)
- Tài khoản ADMIN: `gv1` / `123456` (phải đổi mật khẩu sau khi đăng nhập).
- Lớp mẫu: `4A`, `4B`, `4C`.
- Lý do: `Giup ban` (+2), `Hoan thanh som` (+1), `Noi chuyen rieng` (-1).
- Quà tặng: `Sticker` (3, không giới hạn), `But chi` (5, ton_kho=50), `Tui mu` (8, ton_kho=20).
