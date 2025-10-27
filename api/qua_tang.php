<?php
declare(strict_types=1);
require __DIR__ . '/../config/db.php'; require __DIR__ . '/../lib/tro_giup.php';
yeu_cau_dang_nhap(); $st = $pdo->query("SELECT id, ten, gia_diem, ton_kho FROM qua_tang WHERE dang_hoat_dong=1 ORDER BY gia_diem ASC");
json_phan_hoi(true, $st->fetchAll());
