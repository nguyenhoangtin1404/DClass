<?php
declare(strict_types=1);
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/tro_giup.php';
/** @var \PDO $pdo Global PDO instance from config/db.php */

yeu_cau_dang_nhap();
if (($_SESSION['vai_tro'] ?? '') !== 'ADMIN') { http_response_code(403); json_phan_hoi(false, null, 'khong_du_quyen'); }

$limit = isset($_GET['limit']) ? max(1, min(500, (int)$_GET['limit'])) : 200;
$offset = isset($_GET['offset']) ? max(0, min(50000, (int)$_GET['offset'])) : 0;
$hanh_dong = trim($_GET['hanh_dong'] ?? '');
$giao_vien_id = isset($_GET['giao_vien_id']) ? (int)$_GET['giao_vien_id'] : 0;
$tu_luc = trim($_GET['tu_luc'] ?? '');
$den_luc = trim($_GET['den_luc'] ?? '');
$tu_khoa = trim($_GET['tu_khoa'] ?? '');

$sql = "SELECT nk.id, nk.hanh_dong, nk.noi_dung, nk.tao_luc, gv.ten_dang_nhap
        FROM nhat_ky nk
        LEFT JOIN giao_vien gv ON gv.id = nk.giao_vien_id
        WHERE 1=1";
$pr = [];
if ($hanh_dong !== '') { $sql .= " AND nk.hanh_dong = ?"; $pr[] = $hanh_dong; }
if ($giao_vien_id > 0) { $sql .= " AND nk.giao_vien_id = ?"; $pr[] = $giao_vien_id; }
if ($tu_luc !== '') { $sql .= " AND nk.tao_luc >= ?"; $pr[] = $tu_luc; }
if ($den_luc !== '') { $sql .= " AND nk.tao_luc <= ?"; $pr[] = $den_luc; }
if ($tu_khoa !== '') { $sql .= " AND (nk.noi_dung LIKE ? OR nk.hanh_dong LIKE ?)"; $like = like_mau($tu_khoa); $pr[] = $like; $pr[] = $like; }
$sql .= " ORDER BY nk.id DESC LIMIT ? OFFSET ?";
$pr[] = $limit; $pr[] = $offset;
$st = $pdo->prepare($sql);
$st->execute($pr);
json_phan_hoi(true, $st->fetchAll());
