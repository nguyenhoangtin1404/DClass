<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=utf-8');
require __DIR__ . '/../config/db.php';

$TITLE = 'Tool Reset Khóa Đăng Nhập';
$PASS = 'Kduy@1911';
$ok = isset($_SESSION['tool_reset_ok']) && $_SESSION['tool_reset_ok'] === true;
$thong_bao = '';

// Đăng xuất tool
if (isset($_GET['logout'])) { unset($_SESSION['tool_reset_ok']); header('Location: /public/tool_reset.php'); exit; }

// Xử lý đăng nhập tool
if (!$ok && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['hanh_dong'] ?? '') === 'dang_nhap_tool') {
  $p = (string)($_POST['mat_khau'] ?? '');
  if (hash_equals($PASS, $p)) { $_SESSION['tool_reset_ok'] = true; header('Location: /public/tool_reset.php'); exit; }
  else { $thong_bao = 'Sai mật khẩu tool'; }
}

// Xử lý reset
if ($ok && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['hanh_dong'] ?? '') === 'reset_user') {
  $ten = strtolower(trim((string)($_POST['ten_dang_nhap'] ?? '')));
  if ($ten === '') { $thong_bao = 'Vui lòng nhập tên đăng nhập'; }
  else {
    $het_han = time() + 10*60; // whitelist 10 phút
    try {
      $st = $pdo->prepare("INSERT INTO reset_khoa(ten_dang_nhap, het_han) VALUES(?,?) ON CONFLICT(ten_dang_nhap) DO UPDATE SET het_han=excluded.het_han");
      $st->execute([$ten, $het_han]);
      $thong_bao = 'Đã cấp quyền reset cho tài khoản: ' . htmlspecialchars($ten, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
    } catch (Throwable $e) {
      $thong_bao = 'Lỗi khi ghi CSDL';
    }
  }
}

// Reset khóa cho phiên (trình duyệt) hiện tại ngay lập tức
if ($ok && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['hanh_dong'] ?? '') === 'reset_session') {
  if (isset($_SESSION['dn_sai'])) unset($_SESSION['dn_sai']);
  $thong_bao = 'Đã reset khóa đăng nhập cho trình duyệt này (phiên hiện tại).';
}

?><!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($TITLE, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/morph/bootstrap.min.css">
  <link rel="stylesheet" href="/public/theme.css">
  <style>.container{max-width:720px}</style>
  <meta name="robots" content="noindex,nofollow">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="card shadow-sm">
    <div class="card-body">
      <h5 class="mb-3"><?= htmlspecialchars($TITLE, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') ?></h5>
      <?php if (!$ok): ?>
        <form method="post">
          <input type="hidden" name="hanh_dong" value="dang_nhap_tool">
          <div class="mb-3">
            <label class="form-label">Mật khẩu</label>
            <input type="password" name="mat_khau" class="form-control" autofocus>
          </div>
          <button class="btn btn-primary">Đăng nhập</button>
          <?php if ($thong_bao): ?><div class="text-danger small mt-2"><?= htmlspecialchars($thong_bao, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') ?></div><?php endif; ?>
        </form>
      <?php else: ?>
        <div class="mb-3">
          <form method="post" class="d-inline">
            <input type="hidden" name="hanh_dong" value="reset_session">
            <button class="btn btn-success">Reset khóa của trình duyệt này</button>
          </form>
        </div>
        <form method="post" class="row gy-2 gx-2 align-items-end">
          <input type="hidden" name="hanh_dong" value="reset_user">
          <div class="col-sm-8">
            <label class="form-label">Tên đăng nhập cần reset</label>
            <input type="text" name="ten_dang_nhap" class="form-control" placeholder="vd: gv1">
          </div>
          <div class="col-sm-4">
            <button class="btn btn-warning w-100">Cấp quyền reset 10 phút</button>
          </div>
        </form>
        <?php if ($thong_bao): ?><div class="text-success small mt-2"><?= htmlspecialchars($thong_bao, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') ?></div><?php endif; ?>
        <hr>
        <div class="d-flex justify-content-between">
          <div class="text-muted small">Sau khi người dùng đăng nhập thành công, whitelist sẽ tự xoá.</div>
          <a class="btn btn-outline-secondary btn-sm" href="/public/tool_reset.php?logout=1">Đăng xuất tool</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
