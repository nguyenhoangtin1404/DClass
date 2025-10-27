<?php
declare(strict_types=1);
require __DIR__ . '/../config/db.php'; require __DIR__ . '/../lib/tro_giup.php';
// đảm bảo cột ảnh tồn tại (an toàn khi nâng cấp)
try { $cols = $pdo->query("PRAGMA table_info(qua_tang)")->fetchAll(); $co=false; foreach($cols as $c){ if(($c['name']??'')==='anh_url'){ $co=true; break; } } if(!$co){ $pdo->exec("ALTER TABLE qua_tang ADD COLUMN anh_url TEXT"); } } catch (Throwable $e) {}
yeu_cau_dang_nhap(); $st = $pdo->query("SELECT id, ten, gia_diem, ton_kho, anh_url FROM qua_tang WHERE dang_hoat_dong=1 ORDER BY gia_diem ASC");
json_phan_hoi(true, $st->fetchAll());
