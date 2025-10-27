<?php
declare(strict_types=1);
require __DIR__ . '/../config/db.php'; require __DIR__ . '/../lib/tro_giup.php';
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  yeu_cau_dang_nhap(); $tu_khoa = trim($_GET['tu_khoa'] ?? ''); $lop_hoc_id = $_GET['lop_hoc_id'] ?? '';
  $sql = "SELECT s.id, s.ma, s.ho_ten, s.lop_hoc_id, l.ten AS ten_lop, IFNULL(v.so_du,0) AS so_du
          FROM hoc_sinh s
          LEFT JOIN lop_hoc l ON l.id = s.lop_hoc_id
          LEFT JOIN vi_diem v ON v.hoc_sinh_id = s.id
          WHERE s.dang_hoat_dong=1";
  $pr = [];
  if ($tu_khoa !== '') { $sql .= " AND (s.ho_ten LIKE ? OR s.ma LIKE ?)"; $like = like_mau($tu_khoa); $pr += [ $like, $like ]; }
  if ($lop_hoc_id !== '') { $sql .= " AND s.lop_hoc_id = ?"; $pr[] =((int)$lop_hoc_id); }
  $sql .= " ORDER BY s.ho_ten ASC LIMIT 200";
  $st = $pdo->prepare($sql); $st->execute(is_array($pr)?$pr:[]); json_phan_hoi(true, $st->fetchAll());
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  yeu_cau_dang_nhap(); $b = than_json(); $ho_ten = trim($b['ho_ten'] ?? ''); if ($ho_ten === '') json_phan_hoi(false, null, 'thieu_ho_ten');
  $ma = trim($b['ma'] ?? ''); $lop = isset($b['lop_hoc_id']) ? (int)$b['lop_hoc_id'] : null;
  $st = $pdo->prepare("INSERT INTO hoc_sinh(ma, ho_ten, lop_hoc_id) VALUES(?,?,?)"); $st->execute([$ma ?: null, $ho_ten, $lop]);
  $id = (int)$pdo->lastInsertId(); $pdo->prepare("INSERT OR IGNORE INTO vi_diem(hoc_sinh_id, so_du) VALUES(?,0)")->execute([$id]);
  json_phan_hoi(true, ['id'=>$id]);
}
http_response_code(404); json_phan_hoi(false, null, 'khong_tim_thay');
