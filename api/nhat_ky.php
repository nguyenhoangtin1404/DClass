<?php
declare(strict_types=1);
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/tro_giup.php';

yeu_cau_dang_nhap();
if (($_SESSION['vai_tro'] ?? '') !== 'ADMIN') { http_response_code(403); json_phan_hoi(false, null, 'khong_du_quyen'); }

$limit = isset($_GET['limit']) ? max(1, min(500, (int)$_GET['limit'])) : 200;
$st = $pdo->prepare("SELECT nk.id, nk.hanh_dong, nk.noi_dung, nk.tao_luc, gv.ten_dang_nhap
                     FROM nhat_ky nk
                     LEFT JOIN giao_vien gv ON gv.id = nk.giao_vien_id
                     ORDER BY nk.id DESC
                     LIMIT ?");
$st->execute([$limit]);
json_phan_hoi(true, $st->fetchAll());
