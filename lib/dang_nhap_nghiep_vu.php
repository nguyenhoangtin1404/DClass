<?php
declare(strict_types=1);

function tim_giao_vien_theo_ten(PDO $pdo, string $ten_dang_nhap): ?array
{
  $st = $pdo->prepare('SELECT * FROM giao_vien WHERE ten_dang_nhap = ?');
  $st->execute([$ten_dang_nhap]);
  $gv = $st->fetch();
  return $gv ?: null;
}

function kiem_tra_dang_nhap(PDO $pdo, string $ten_dang_nhap, string $mat_khau): ?array
{
  $gv = tim_giao_vien_theo_ten($pdo, $ten_dang_nhap);
  if ($gv && password_verify($mat_khau, $gv['mat_khau_bam'])) {
    return $gv;
  }
  return null;
}

