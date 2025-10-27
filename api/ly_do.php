<?php
declare(strict_types=1);
require __DIR__ . '/../config/db.php'; require __DIR__ . '/../lib/tro_giup.php';
yeu_cau_dang_nhap(); $st = $pdo->query("SELECT id, tieu_de, bien_diem FROM ly_do WHERE dang_hoat_dong=1 ORDER BY bien_diem DESC, tieu_de ASC");
json_phan_hoi(true, $st->fetchAll());
