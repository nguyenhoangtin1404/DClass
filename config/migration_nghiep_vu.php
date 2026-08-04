<?php
declare(strict_types=1);

/**
 * Migration cho CSDL nghiệp vụ (1 CSDL dùng chung cho tất cả giáo viên tự đăng ký).
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
      'anh_url' => 'anh_url TEXT',
      'nguoi_tao_id' => 'nguoi_tao_id INTEGER REFERENCES giao_vien(id) ON DELETE CASCADE'
    ]);
    // Lý do cộng/trừ điểm chuyển từ catalog dùng chung sang sở hữu theo từng giáo viên.
    $ensureCols('ly_do', [
      'nguoi_tao_id' => 'nguoi_tao_id INTEGER REFERENCES giao_vien(id) ON DELETE CASCADE'
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
    try {
      $pdo->exec("CREATE TABLE IF NOT EXISTS migrations_ap_dung (
        ten TEXT PRIMARY KEY,
        ap_dung_luc TEXT DEFAULT (datetime('now'))
      )");
    } catch (Throwable $e) { /* ignore */ }

    chay_backfill_so_huu($pdo);
  }
}

/**
 * Backfill 1 LẦN DUY NHẤT cho CSDL đã có dữ liệu thật từ trước khi tách quyền theo giáo viên
 * (bản trước: ADMIN thấy hết mọi lớp, ly_do/qua_tang là catalog dùng chung không ai sở hữu).
 * Không chạy lại nếu đã áp dụng (đánh dấu trong migrations_ap_dung) - tránh gán lại quyền cho
 * lớp/lý do/quà được tạo SAU migration này (những cái đó phải đi qua luồng sở hữu bình thường).
 *
 * - Tài khoản có vai_tro='ADMIN' (dữ liệu vai_tro cũ chưa bị đụng tới) được gán tường minh vào
 *   giao_vien_lop cho MỌI lớp đang có, để giữ nguyên đúng phạm vi truy cập họ đang có trước đây
 *   (không mở rộng thêm, không cho họ thấy lớp tạo ra sau này của giáo viên khác).
 * - Mỗi lý do/quà đang có (nguoi_tao_id NULL, tức tạo trước khi có cột sở hữu) được nhân bản cho
 *   TỪNG tài khoản giáo viên đang có, để không giáo viên nào bị mất quyền dùng lý do/quà họ đang
 *   dùng hàng ngày. Từ lúc này mỗi giáo viên có bản sao riêng, sửa độc lập với nhau.
 */
if (!function_exists('chay_backfill_so_huu')) {
  function chay_backfill_so_huu(PDO $pdo): void {
    $ten_migration = 'backfill_so_huu_theo_giao_vien_2026_08';
    try {
      $st = $pdo->prepare('SELECT 1 FROM migrations_ap_dung WHERE ten = ?');
      $st->execute([$ten_migration]);
      if ($st->fetchColumn()) { return; }
    } catch (Throwable $e) { return; }

    try {
      $pdo->beginTransaction();

      // 1) Tài khoản ADMIN cũ: gán tường minh vào mọi lớp đang có (giữ nguyên phạm vi cũ).
      $pdo->exec("INSERT OR IGNORE INTO giao_vien_lop(giao_vien_id, lop_hoc_id)
                  SELECT gv.id, l.id FROM giao_vien gv, lop_hoc l WHERE gv.vai_tro = 'ADMIN'");

      // 2) Nhân bản lý do/quà chưa có chủ (tạo trước khi có cột nguoi_tao_id) cho từng giáo viên.
      $giao_vien_ids = array_map('intval', $pdo->query('SELECT id FROM giao_vien ORDER BY id ASC')->fetchAll(PDO::FETCH_COLUMN));
      if ($giao_vien_ids) {
        foreach (['ly_do' => ['tieu_de', 'bien_diem', 'dang_hoat_dong'], 'qua_tang' => ['ten', 'gia_diem', 'ton_kho', 'dang_hoat_dong', 'anh_url']] as $bang => $cot) {
          $ds_cot = implode(',', $cot);
          $mo_ta = implode(',', array_fill(0, count($cot), '?'));
          $mo_cu = $pdo->query("SELECT id, " . $ds_cot . " FROM {$bang} WHERE nguoi_tao_id IS NULL")->fetchAll();
          $st_ins = $pdo->prepare("INSERT INTO {$bang}(" . $ds_cot . ", nguoi_tao_id) VALUES(" . $mo_ta . ", ?)");
          $st_up = $pdo->prepare("UPDATE {$bang} SET nguoi_tao_id = ? WHERE id = ?");
          foreach ($mo_cu as $dong) {
            $gia_tri = [];
            foreach ($cot as $c) { $gia_tri[] = $dong[$c]; }
            // Giáo viên đầu tiên nhận luôn bản ghi cũ (giữ nguyên id, không phát sinh id mới).
            $st_up->execute([$giao_vien_ids[0], (int)$dong['id']]);
            // Các giáo viên còn lại nhận bản sao riêng.
            for ($i = 1; $i < count($giao_vien_ids); $i++) {
              $st_ins->execute(array_merge($gia_tri, [$giao_vien_ids[$i]]));
            }
          }
        }
      }

      $pdo->prepare('INSERT INTO migrations_ap_dung(ten) VALUES(?)')->execute([$ten_migration]);
      $pdo->commit();
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) { $pdo->rollBack(); }
    }
  }
}
