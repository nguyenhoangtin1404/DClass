<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Test end-to-end qua HTTP thật: bật `php -S` trên một CSDL SQLite tạm
 * (biến môi trường DCLASS_DB_PATH, xem config/db.php) rồi gọi các endpoint bằng cURL.
 *
 * Phủ các thay đổi gần đây:
 *  - api/*_quan_tri.php action 'them' trả về đúng 'id' (bug lastInsertId sau ghi_log)
 *  - api/hoc_sinh.php: học sinh CÓ lớp hiện trong danh sách, KHÔNG lớp thì bị ẩn
 *  - api/upload_anh.php: upload chung trả { url } và ảnh tải về được
 *
 * Tự bỏ qua nếu môi trường không có ext curl / proc_open hoặc không bật được server.
 */
final class ApiHttpTest extends TestCase
{
  /** @var resource|null */
  private static $server = null;
  private static string $base = '';
  private static string $dbPath = '';
  private static string $cookieJar = '';
  /** @var string[] URL ảnh test đã upload, cần xoá khi kết thúc (dùng chung thư mục upload/ thật) */
  private static array $anhDaUpload = [];

  public static function setUpBeforeClass(): void
  {
    if (!function_exists('curl_init') || !function_exists('proc_open')) {
      self::markTestSkipped('cần ext curl và proc_open');
    }
    $root = dirname(__DIR__);
    $tmp = sys_get_temp_dir();
    $tag = getmypid() . '_' . bin2hex(random_bytes(4));
    self::$dbPath = $tmp . DIRECTORY_SEPARATOR . "dclass_e2e_$tag.db";
    self::$cookieJar = $tmp . DIRECTORY_SEPARATOR . "dclass_e2e_cookies_$tag.txt";

    $port = self::timFreePort();
    self::$base = "http://127.0.0.1:$port";

    $env = getenv();
    $env['DCLASS_DB_PATH'] = self::$dbPath;

    $desc = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    self::$server = proc_open(
      [PHP_BINARY, '-S', "127.0.0.1:$port", '-t', $root],
      $desc,
      $pipes,
      $root,
      $env
    );
    if (!is_resource(self::$server)) {
      self::markTestSkipped('không khởi động được php -S');
    }

    $len = false;
    for ($i = 0; $i < 60; $i++) {
      usleep(100000); // 100ms
      $ch = curl_init(self::$base . '/api/dang_nhap.php');
      curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 2, CURLOPT_NOBODY => true]);
      curl_exec($ch);
      $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);
      if ($code > 0) { $len = true; break; }
    }
    if (!$len) {
      self::tearDownAfterClass();
      self::markTestSkipped('php -S không phản hồi trong thời gian chờ');
    }
  }

  public static function tearDownAfterClass(): void
  {
    if (is_resource(self::$server)) {
      $st = proc_get_status(self::$server);
      if ($st && $st['running']) {
        proc_terminate(self::$server, 9);
      }
      proc_close(self::$server);
      self::$server = null;
    }
    foreach ([self::$dbPath, self::$dbPath . '-wal', self::$dbPath . '-shm', self::$cookieJar] as $f) {
      if ($f && is_file($f)) { @unlink($f); }
    }
    // upload_anh.php ghi vào thư mục upload/ THẬT của repo -> dọn file test đã tạo.
    $root = dirname(__DIR__);
    foreach (self::$anhDaUpload as $url) {
      $p = $root . str_replace('/', DIRECTORY_SEPARATOR, $url);
      if (is_file($p) && strpos($url, '/upload/') === 0) { @unlink($p); }
    }
    self::$anhDaUpload = [];
  }

  private static function timFreePort(): int
  {
    $s = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
    if (!$s) { self::markTestSkipped('không cấp phát được cổng trống'); }
    $name = stream_socket_get_name($s, false);
    fclose($s);
    return (int) substr($name, strrpos($name, ':') + 1);
  }

  /**
   * @param mixed $body  mảng -> JSON; CURLFile/mảng multipart nếu $multipart=true
   * @return array{code:int, json:?array, raw:string}
   */
  private function goi(string $method, string $path, $body = null, bool $multipart = false): array
  {
    $ch = curl_init(self::$base . $path);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CUSTOMREQUEST => $method,
      CURLOPT_COOKIEJAR => self::$cookieJar,
      CURLOPT_COOKIEFILE => self::$cookieJar,
      CURLOPT_TIMEOUT => 15,
    ]);
    if ($body !== null) {
      if ($multipart) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
      } else {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
      }
    }
    $raw = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $j = json_decode($raw, true);
    return ['code' => $code, 'json' => is_array($j) ? $j : null, 'raw' => $raw];
  }

  public function testLuongDayDu(): void
  {
    // 1) Đăng ký -> tạo phiên
    $r = $this->goi('POST', '/api/dang_nhap.php?hanh_dong=dang_ky', [
      'ten_dang_nhap' => 'gve2e', 'mat_khau' => 'matkhau123',
    ]);
    $this->assertSame(200, $r['code'], $r['raw']);
    $this->assertTrue($r['json']['ok'] ?? false, $r['raw']);

    // 2) Chưa có lớp -> danh sách học sinh rỗng
    $r = $this->goi('GET', '/api/hoc_sinh.php');
    $this->assertTrue($r['json']['ok']);
    $this->assertSame([], $r['json']['du_lieu']);

    // 3) Tạo lớp
    $r = $this->goi('POST', '/api/lop_hoc_quan_tri.php?hanh_dong=them', ['ten' => 'Lớp E2E']);
    $this->assertTrue($r['json']['ok'] ?? false, $r['raw']);
    $lopId = (int) ($r['json']['du_lieu']['id'] ?? 0);
    $this->assertGreaterThan(0, $lopId);
    $ds = $this->goi('GET', '/api/lop_hoc_quan_tri.php')['json']['du_lieu'];
    $this->assertSame([$lopId], array_map(static fn($l) => (int) $l['id'], $ds), 'id lớp trả về khớp bản ghi thật');

    // 4) Thêm lý do -> 'id' trả về phải là id THẬT của ly_do (bug lastInsertId sau ghi_log)
    $r = $this->goi('POST', '/api/ly_do_quan_tri.php?hanh_dong=them', ['tieu_de' => 'Chăm chỉ', 'bien_diem' => 2]);
    $this->assertTrue($r['json']['ok'] ?? false, $r['raw']);
    $lyDoId = (int) ($r['json']['du_lieu']['id'] ?? 0);
    $dsLd = $this->goi('GET', '/api/ly_do_quan_tri.php')['json']['du_lieu'];
    $this->assertContains($lyDoId, array_map(static fn($x) => (int) $x['id'], $dsLd), 'id lý do trả về phải tồn tại thật');

    // 5) Thêm quà -> 'id' trả về phải là id THẬT của qua_tang, dùng được để upload ảnh
    $r = $this->goi('POST', '/api/qua_tang_quan_tri.php?hanh_dong=them', ['ten' => 'Bút bi', 'gia_diem' => 5, 'ton_kho' => 3]);
    $this->assertTrue($r['json']['ok'] ?? false, $r['raw']);
    $quaId = (int) ($r['json']['du_lieu']['id'] ?? 0);
    $dsQ = $this->goi('GET', '/api/qua_tang_quan_tri.php')['json']['du_lieu'];
    $quaKhop = null;
    foreach ($dsQ as $q) { if ((int) $q['id'] === $quaId) { $quaKhop = $q; } }
    $this->assertNotNull($quaKhop, 'id quà trả về phải trỏ đúng bản ghi vừa tạo');
    $this->assertSame('Bút bi', $quaKhop['ten']);

    // 6) Thêm học sinh CÓ lớp
    $r = $this->goi('POST', '/api/hoc_sinh.php', ['ho_ten' => 'Học Sinh Có Lớp', 'lop_hoc_id' => $lopId]);
    $this->assertTrue($r['json']['ok'] ?? false, $r['raw']);
    $hsCoLop = (int) ($r['json']['du_lieu']['id'] ?? 0);

    // 7) ...và học sinh KHÔNG lớp
    $r = $this->goi('POST', '/api/hoc_sinh.php', ['ho_ten' => 'Học Sinh Không Lớp']);
    $this->assertTrue($r['json']['ok'] ?? false, $r['raw']);
    $hsKhongLop = (int) ($r['json']['du_lieu']['id'] ?? 0);

    // 8) Danh sách: có HS thuộc lớp (kèm ten_lop), KHÔNG có HS không lớp
    $ds = $this->goi('GET', '/api/hoc_sinh.php')['json']['du_lieu'];
    $theoId = [];
    foreach ($ds as $hs) { $theoId[(int) $hs['id']] = $hs; }
    $this->assertArrayHasKey($hsCoLop, $theoId, 'học sinh có lớp phải hiện trong danh sách');
    $this->assertSame('Lớp E2E', $theoId[$hsCoLop]['ten_lop']);
    $this->assertSame($lopId, (int) $theoId[$hsCoLop]['lop_hoc_id']);
    $this->assertArrayNotHasKey($hsKhongLop, $theoId, 'học sinh không lớp bị ẩn khỏi danh sách');

    // 9) upload_anh.php: trả { url } dưới /upload/avatar/ và tải về được
    $png = self::taoPngTam();
    $r = $this->goi('POST', '/api/upload_anh.php', ['file' => new CURLFile($png, 'image/png', 'a.png')], true);
    @unlink($png);
    if ($r['json'] === null && stripos($r['raw'], 'exif_imagetype') !== false) {
      // Runner thiếu ext exif -> endpoint upload không chạy được; bỏ qua phần này.
      $this->markTestSkipped('môi trường thiếu ext exif cho api/upload_anh.php');
    }
    $this->assertTrue($r['json']['ok'] ?? false, $r['raw']);
    $url = (string) ($r['json']['du_lieu']['url'] ?? '');
    if ($url !== '') { self::$anhDaUpload[] = $url; }
    $this->assertStringStartsWith('/upload/avatar/', $url);
    $anh = $this->goi('GET', $url);
    $this->assertSame(200, $anh['code'], 'ảnh vừa upload phải tải về được');
    $this->assertNotSame('', $anh['raw']);

    // 10) upload_anh.php từ chối khi chưa đăng nhập
    $ch = curl_init(self::$base . '/api/upload_anh.php');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => ['x' => '1'], CURLOPT_TIMEOUT => 10]);
    $raw = (string) curl_exec($ch);
    curl_close($ch);
    $this->assertStringContainsString('chua_dang_nhap', $raw);
  }

  private static function taoPngTam(): string
  {
    $f = tempnam(sys_get_temp_dir(), 'dce2e') . '.png';
    if (function_exists('imagecreatetruecolor')) {
      $im = imagecreatetruecolor(48, 48);
      imagefill($im, 0, 0, imagecolorallocate($im, 200, 120, 60));
      imagepng($im, $f);
      imagedestroy($im);
    } else {
      // PNG 1x1 hợp lệ nếu không có GD
      file_put_contents($f, base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8Xw8AAoMBgDTD2qgAAAAASUVORK5CYII='
      ));
    }
    return $f;
  }
}
