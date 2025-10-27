<?php
declare(strict_types=1);
function json_phan_hoi($ok, $du_lieu=null, $thong_bao='') {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok'=>$ok, 'du_lieu'=>$du_lieu, 'thong_bao'=>$thong_bao], JSON_UNESCAPED_UNICODE);
  exit;
}
function yeu_cau_dang_nhap() {
  if (!isset($_SESSION['giao_vien_id'])) { http_response_code(401); json_phan_hoi(false, null, 'chua_dang_nhap'); }
}
function than_json() {
  $raw = file_get_contents('php://input'); $j = json_decode($raw, true);
  return is_array($j) ? $j : [];
}
function like_mau($s) { return '%' . str_replace(['%','_'], ['\%','\_'], trim($s)) . '%'; }
