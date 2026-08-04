<?php
declare(strict_types=1);
function json_phan_hoi($ok, $du_lieu=null, $thong_bao='') {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok'=>$ok, 'du_lieu'=>$du_lieu, 'thong_bao'=>$thong_bao], JSON_UNESCAPED_UNICODE);
  exit;
}
function yeu_cau_dang_nhap() {
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
