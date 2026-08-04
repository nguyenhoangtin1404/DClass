<?php
declare(strict_types=1);

/**
 * Migration cho CSDL nghiệp vụ (mỗi tổ chức/tenant 1 file). Tách riêng khỏi config/db.php
 * để dùng lại được từ scripts/migrate_all.php (áp dụng cho mọi tenant, không chỉ 1 kết nối hiện tại).
 */
if (!function_exists('chay_migration')) {
  function chay_migration(PDO $pdo): void {
    // Chuẩn hóa mã loại lịch sử cũ
    try {
      $pdo->exec("UPDATE so_cai_diem SET loai='CONG_DIEM' WHERE loai='CONG'");
      $pdo->exec("UPDATE so_cai_diem SET loai='DOI_DIEM' WHERE loai='QUY_DOI'");
    } catch (Throwable $e) { /* bảng có thể chưa tồn tại */ }
    // Đảm bảo cột trong bảng
    $ensureCols = function(string $table, array $cols) use ($pdo) {
      try {
        $info = $pdo->query("PRAGMA table_info($table)")->fetchAll();
        $exist = array_map(fn($c) => $c['name'] ?? '', $info);
        foreach ($cols as $col => $ddl) {
          if (!in_array($col, $exist, true)) { $pdo->exec("ALTER TABLE $table ADD COLUMN $ddl"); }
        }
      } catch (Throwable $e) { /* ignore */ }
    };
    $ensureCols('hoc_sinh', [
      'stt' => 'stt INTEGER',
      'gioi_tinh' => 'gioi_tinh TEXT',
      'ngay_sinh' => 'ngay_sinh TEXT',
      'anh_dai_dien_url' => 'anh_dai_dien_url TEXT'
    ]);
    $ensureCols('giao_vien', [
      'vai_tro' => "vai_tro TEXT DEFAULT 'GV'",
      'phai_doi_mat_khau' => 'phai_doi_mat_khau INTEGER DEFAULT 0'
    ]);
    $ensureCols('qua_tang', [
      'anh_url' => 'anh_url TEXT'
    ]);
    // Đảm bảo bảng tồn tại
    try {
      $pdo->exec("CREATE TABLE IF NOT EXISTS giao_vien_lop (
        giao_vien_id INTEGER NOT NULL,
        lop_hoc_id INTEGER NOT NULL,
        PRIMARY KEY (giao_vien_id, lop_hoc_id),
        FOREIGN KEY (giao_vien_id) REFERENCES giao_vien(id) ON DELETE CASCADE,
        FOREIGN KEY (lop_hoc_id) REFERENCES lop_hoc(id) ON DELETE CASCADE
      )");
    } catch (Throwable $e) { /* ignore */ }
    try {
      $pdo->exec("CREATE TABLE IF NOT EXISTS reset_khoa (ten_dang_nhap TEXT PRIMARY KEY, het_han INTEGER)");
    } catch (Throwable $e) { /* ignore */ }
    try {
      $pdo->exec("CREATE TABLE IF NOT EXISTS nhat_ky (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        giao_vien_id INTEGER,
        hanh_dong TEXT NOT NULL,
        noi_dung TEXT,
        tao_luc TEXT DEFAULT (datetime('now')),
        FOREIGN KEY (giao_vien_id) REFERENCES giao_vien(id)
      )");
    } catch (Throwable $e) { /* ignore */ }
  }
}
