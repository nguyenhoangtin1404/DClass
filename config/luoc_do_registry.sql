PRAGMA foreign_keys = ON;
-- registry.db: CSDL trung tâm quản lý danh sách tổ chức (tenant), tách biệt
-- hoàn toàn khỏi CSDL nghiệp vụ từng trường (data/{ma_to_chuc}.db).
CREATE TABLE to_chuc (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  ma_to_chuc TEXT UNIQUE NOT NULL,
  ten TEXT NOT NULL,
  domain TEXT,
  db_path TEXT NOT NULL,
  dang_hoat_dong INTEGER DEFAULT 1,
  tao_luc TEXT DEFAULT (datetime('now'))
);
-- Tài khoản vận hành nền tảng, tách biệt hoàn toàn với giao_vien của từng tổ chức.
-- Không có quyền truy cập dữ liệu điểm/học sinh của bất kỳ trường nào.
CREATE TABLE sieu_quan_tri (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  ten_dang_nhap TEXT UNIQUE NOT NULL,
  mat_khau_bam TEXT NOT NULL,
  tao_luc TEXT DEFAULT (datetime('now'))
);
CREATE TABLE nhat_ky_registry (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  sieu_quan_tri_id INTEGER,
  hanh_dong TEXT NOT NULL,
  noi_dung TEXT,
  tao_luc TEXT DEFAULT (datetime('now')),
  FOREIGN KEY (sieu_quan_tri_id) REFERENCES sieu_quan_tri(id)
);
