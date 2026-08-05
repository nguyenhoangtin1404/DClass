<?php
declare(strict_types=1);
function json_phan_hoi($ok, $du_lieu=null, $thong_bao='') {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok'=>$ok, 'du_lieu'=>$du_lieu, 'thong_bao'=>$thong_bao], JSON_UNESCAPED_UNICODE);
  exit;
}
/**
 * Xác thực bằng token API (app di động offline-first) qua header Authorization: Bearer <token>.
 * Không đụng tới cookie/session của trình duyệt - chỉ set $_SESSION['giao_vien_id'] trong bộ nhớ
 * cho đúng request hiện tại (client không dùng cookie thì Set-Cookie phản hồi cũng bị bỏ qua).
 */
function thu_xac_thuc_bang_token(PDO $pdo): bool {
  $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
  if (!preg_match('/^Bearer\s+([A-Za-z0-9]+)$/', trim($hdr), $m)) { return false; }
  $token_bam = hash('sha256', $m[1]);
  $st = $pdo->prepare('SELECT id, phai_doi_mat_khau FROM giao_vien WHERE api_token_bam = ?');
  $st->execute([$token_bam]);
  $gv = $st->fetch();
  if (!$gv) { return false; }
  $_SESSION['giao_vien_id'] = (int)$gv['id'];
  $_SESSION['phai_doi_mat_khau'] = !empty($gv['phai_doi_mat_khau']);
  return true;
}
function yeu_cau_dang_nhap() {
  global $pdo;
  if (!isset($_SESSION['giao_vien_id']) && isset($pdo)) { thu_xac_thuc_bang_token($pdo); }
  if (!isset($_SESSION['giao_vien_id'])) { http_response_code(401); json_phan_hoi(false, null, 'chua_dang_nhap'); }
  if (!empty($_SESSION['phai_doi_mat_khau'])) {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    // Chỉ cho phép qua trang quản trị tài khoản (nơi có action đổi mật khẩu) khi đang bị buộc đổi mật khẩu
    if (strpos($script, 'giao_vien_quan_tri.php') === false) {
      http_response_code(403);
      json_phan_hoi(false, null, 'phai_doi_mat_khau');
    }
  }
}
function than_json() {
  $raw = file_get_contents('php://input'); $j = json_decode($raw, true);
  return is_array($j) ? $j : [];
}
function like_mau($s) { return '%' . str_replace(['%','_'], ['\%','\_'], trim($s)) . '%'; }
if (!function_exists('ghi_log')) {
  function ghi_log(PDO $pdo, $gv_id, string $hanh_dong, string $noi_dung=''): void {
    try {
      $st = $pdo->prepare("INSERT INTO nhat_ky(giao_vien_id, hanh_dong, noi_dung) VALUES(?,?,?)");
      $st->execute([$gv_id ?: null, $hanh_dong, $noi_dung ?: null]);
    } catch (Throwable $e) { /* ignore logging failures */ }
  }
}
