<?php
declare(strict_types=1);
require __DIR__ . '/../config/db.php'; require __DIR__ . '/../lib/tro_giup.php';
/** @var \PDO $pdo Global PDO instance from config/db.php */
yeu_cau_dang_nhap();
$st = $pdo->prepare("SELECT id, tieu_de, bien_diem FROM ly_do WHERE dang_hoat_dong=1 AND nguoi_tao_id=? ORDER BY bien_diem DESC, tieu_de ASC");
$st->execute([(int)$_SESSION['giao_vien_id']]);
json_phan_hoi(true, $st->fetchAll());
