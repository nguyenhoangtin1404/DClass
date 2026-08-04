<?php
declare(strict_types=1);

/**
 * CLI: gán hoặc đổi domain của 1 tổ chức đã có trong registry.db.
 * Dùng khi tạo tổ chức trước, gán DNS/vhost sau, hoặc khi domain của tổ chức đổi.
 *
 * Cách dùng:
 *   php scripts/dat_domain_to_chuc.php --ma=truong_abc --domain=truong-abc.example.com
 *   php scripts/dat_domain_to_chuc.php --ma=truong_abc --xoa   (bỏ gán domain hiện tại)
 */

require __DIR__ . '/../config/registry.php';

$options = getopt('', ['ma:', 'domain::', 'xoa']);

function loi_thoat_domain(string $msg): never {
  fwrite(STDERR, $msg . PHP_EOL);
  exit(1);
}

$ma_to_chuc = trim((string)($options['ma'] ?? ''));
$xoa = isset($options['xoa']);
$domain_raw = isset($options['domain']) ? (string)$options['domain'] : null;

if ($ma_to_chuc === '') {
  loi_thoat_domain('Thieu --ma. Vi du: php scripts/dat_domain_to_chuc.php --ma=truong_abc --domain=truong-abc.example.com');
}
if (!$xoa && ($domain_raw === null || trim($domain_raw) === '')) {
  loi_thoat_domain('Thieu --domain (hoac dung --xoa de bo gan domain).');
}

$registry = ket_noi_registry();
$to_chuc = tim_to_chuc_theo_ma($registry, $ma_to_chuc);
if ($to_chuc === null) {
  loi_thoat_domain("Khong tim thay to chuc '{$ma_to_chuc}' trong registry.");
}

if ($xoa) {
  $registry->prepare('UPDATE to_chuc SET domain = NULL WHERE id = ?')->execute([$to_chuc['id']]);
  echo "Da bo gan domain cho to chuc '{$ma_to_chuc}'." . PHP_EOL;
  exit(0);
}

$domain = chuan_hoa_host((string)$domain_raw);
if (!preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$/', $domain)) {
  loi_thoat_domain("Domain '{$domain}' khong hop le. Vi du hop le: truong-abc.example.com");
}
$st_domain = $registry->prepare('SELECT ma_to_chuc FROM to_chuc WHERE domain = ? AND id != ?');
$st_domain->execute([$domain, $to_chuc['id']]);
$trung = $st_domain->fetch();
if ($trung !== false) {
  loi_thoat_domain("Domain '{$domain}' da duoc dung boi to chuc '{$trung['ma_to_chuc']}'.");
}

$registry->prepare('UPDATE to_chuc SET domain = ? WHERE id = ?')->execute([$domain, $to_chuc['id']]);
echo "Da gan domain '{$domain}' cho to chuc '{$ma_to_chuc}'." . PHP_EOL;
