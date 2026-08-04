<?php
declare(strict_types=1);

/**
 * Kết nối/khởi tạo registry.db — CSDL trung tâm quản lý danh sách tổ chức (tenant).
 * Tách biệt hoàn toàn khỏi config/db.php (CSDL nghiệp vụ theo từng tổ chức).
 * Không đụng tới session/header — chỉ lo phần kết nối CSDL, dùng được cả từ CLI lẫn web.
 */

function duong_dan_registry_db(): string {
  $env = [];
  $env_file = __DIR__ . '/env.php';
  if (file_exists($env_file)) {
    $env = require $env_file;
  }
  return $env['registry_db_path'] ?? (__DIR__ . '/../data/registry.db');
}

function chay_migration_registry(PDO $pdo): void {
  $ensureCols = function (string $table, array $cols) use ($pdo) {
    try {
      $info = $pdo->query("PRAGMA table_info($table)")->fetchAll();
      $exist = array_map(fn($c) => $c['name'] ?? '', $info);
      foreach ($cols as $col => $ddl) {
        if (!in_array($col, $exist, true)) {
          $pdo->exec("ALTER TABLE $table ADD COLUMN $ddl");
        }
      }
    } catch (Throwable $e) { /* bảng có thể chưa tồn tại */ }
  };
  try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS to_chuc (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      ma_to_chuc TEXT UNIQUE NOT NULL,
      ten TEXT NOT NULL,
      domain TEXT,
      db_path TEXT NOT NULL,
      dang_hoat_dong INTEGER DEFAULT 1,
      tao_luc TEXT DEFAULT (datetime('now'))
    )");
  } catch (Throwable $e) { /* ignore */ }
  try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS sieu_quan_tri (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      ten_dang_nhap TEXT UNIQUE NOT NULL,
      mat_khau_bam TEXT NOT NULL,
      tao_luc TEXT DEFAULT (datetime('now'))
    )");
  } catch (Throwable $e) { /* ignore */ }
  try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS nhat_ky_registry (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      sieu_quan_tri_id INTEGER,
      hanh_dong TEXT NOT NULL,
      noi_dung TEXT,
      tao_luc TEXT DEFAULT (datetime('now')),
      FOREIGN KEY (sieu_quan_tri_id) REFERENCES sieu_quan_tri(id)
    )");
  } catch (Throwable $e) { /* ignore */ }
  try {
    // Domain la duy nhat khi da gan (nhieu to chuc chua gan domain deu la NULL, khong xung dot).
    $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_to_chuc_domain ON to_chuc(domain) WHERE domain IS NOT NULL");
  } catch (Throwable $e) { /* ignore */ }
}

/**
 * Chuẩn hoá Host header về dạng so sánh được với cột to_chuc.domain:
 * bỏ cổng (nếu có), viết thường, bỏ khoảng trắng thừa.
 */
function chuan_hoa_host(string $host): string {
  $host = trim($host);
  // IPv6 dạng [::1]:8000 - giữ nguyên phần trong ngoặc, chỉ bỏ cổng phía sau ']'
  if ($host !== '' && $host[0] === '[') {
    $vi_tri = strpos($host, ']');
    if ($vi_tri !== false) {
      return strtolower(substr($host, 0, $vi_tri + 1));
    }
  }
  $vi_tri_hai_cham = strpos($host, ':');
  if ($vi_tri_hai_cham !== false) {
    $host = substr($host, 0, $vi_tri_hai_cham);
  }
  return strtolower($host);
}

/**
 * Xác định tổ chức hiện tại dựa trên Host header của request.
 * Trả về null nếu không tìm thấy domain khớp hoặc tổ chức đã bị khoá.
 */
function xac_dinh_to_chuc_theo_domain(PDO $registry, string $host): ?array {
  $host_chuan = chuan_hoa_host($host);
  if ($host_chuan === '') return null;
  $st = $registry->prepare('SELECT * FROM to_chuc WHERE domain = ? AND dang_hoat_dong = 1');
  $st->execute([$host_chuan]);
  $tc = $st->fetch();
  return $tc ?: null;
}

function ket_noi_registry(): PDO {
  static $pdo = null;
  if ($pdo !== null) return $pdo;
  $duong_dan = duong_dan_registry_db();
  $thu_muc = dirname($duong_dan);
  if (!is_dir($thu_muc)) {
    @mkdir($thu_muc, 0777, true);
  }
  $lan_dau = !file_exists($duong_dan);
  $pdo = new PDO('sqlite:' . $duong_dan);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  $pdo->exec('PRAGMA foreign_keys = ON');
  if ($lan_dau) {
    $luoc_do = file_get_contents(__DIR__ . '/luoc_do_registry.sql');
    $pdo->exec($luoc_do);
  }
  chay_migration_registry($pdo);
  return $pdo;
}

/**
 * Tra registry theo mã tổ chức, trả về bản ghi to_chuc hoặc null nếu không tồn tại/bị khoá.
 */
function tim_to_chuc_theo_ma(PDO $registry, string $ma_to_chuc): ?array {
  $st = $registry->prepare('SELECT * FROM to_chuc WHERE ma_to_chuc = ?');
  $st->execute([$ma_to_chuc]);
  $tc = $st->fetch();
  return $tc ?: null;
}
