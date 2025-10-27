PRAGMA foreign_keys = ON;
CREATE TABLE giao_vien (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  ten_dang_nhap TEXT UNIQUE NOT NULL,
  mat_khau_bam TEXT NOT NULL,
  tao_luc TEXT DEFAULT (datetime('now'))
);
CREATE TABLE lop_hoc (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  ten TEXT NOT NULL,
  dang_hoat_dong INTEGER DEFAULT 1
);
CREATE TABLE hoc_sinh (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  ma TEXT UNIQUE,
  ho_ten TEXT NOT NULL,
  lop_hoc_id INTEGER,
  anh_dai_dien_url TEXT,
  dang_hoat_dong INTEGER DEFAULT 1,
  tao_luc TEXT DEFAULT (datetime('now')),
  FOREIGN KEY (lop_hoc_id) REFERENCES lop_hoc(id)
);
CREATE TABLE ly_do (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  tieu_de TEXT NOT NULL,
  bien_diem INTEGER NOT NULL,
  dang_hoat_dong INTEGER DEFAULT 1
);
CREATE TABLE qua_tang (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  ten TEXT NOT NULL,
  gia_diem INTEGER NOT NULL,
  ton_kho INTEGER DEFAULT 0,
  dang_hoat_dong INTEGER DEFAULT 1
);
CREATE TABLE vi_diem (
  hoc_sinh_id INTEGER PRIMARY KEY,
  so_du INTEGER NOT NULL DEFAULT 0,
  FOREIGN KEY (hoc_sinh_id) REFERENCES hoc_sinh(id)
);
CREATE TABLE so_cai_diem (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  hoc_sinh_id INTEGER NOT NULL,
  giao_vien_id INTEGER NOT NULL,
  loai TEXT NOT NULL,
  ly_do_id INTEGER,
  qua_tang_id INTEGER,
  bien_diem INTEGER NOT NULL,
  so_du_sau INTEGER NOT NULL,
  ghi_chu TEXT,
  tao_luc TEXT DEFAULT (datetime('now')),
  FOREIGN KEY (hoc_sinh_id) REFERENCES hoc_sinh(id),
  FOREIGN KEY (giao_vien_id) REFERENCES giao_vien(id)
);
CREATE INDEX idx_so_cai_hoc_sinh_thoi_gian ON so_cai_diem(hoc_sinh_id, tao_luc);
