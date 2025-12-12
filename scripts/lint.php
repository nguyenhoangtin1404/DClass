<?php
declare(strict_types=1);

$iterator = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator(__DIR__ . '/..', FilesystemIterator::SKIP_DOTS)
);
$co_loi = false;

foreach ($iterator as $file) {
  if ($file->isDir()) {
    continue;
  }
  $path = $file->getPathname();
  if (str_ends_with($path, '.php') && !str_contains($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)) {
    $out = [];
    $status = 0;
    exec('php -l ' . escapeshellarg($path), $out, $status);
    foreach ($out as $line) {
      echo $line, PHP_EOL;
    }
    if ($status !== 0) {
      $co_loi = true;
    }
  }
}

if ($co_loi) {
  exit(1);
}

