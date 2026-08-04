# Multi-tenant: trạng thái & việc còn lại

Ghi lại để không bị mất giữa các phiên làm việc (lần trước danh sách "P2.x-P5.x" chỉ nằm
trong commit message, không có ở đâu tra lại được).

## Đã xong

- **P0/P1 — Nền tảng registry**: `registry.db` (`to_chuc`, `sieu_quan_tri`, `nhat_ky_registry`),
  `scripts/tao_to_chuc.php` (tạo tổ chức + CSDL + admin đầu tiên), `scripts/tao_sieu_quan_tri.php`
  (tạo tài khoản vận hành nền tảng), `scripts/migrate_all.php` (migrate schema mọi tổ chức).
- **P2 — Tenant resolution theo domain**: `config/registry.php` có `chuan_hoa_host()` và
  `xac_dinh_to_chuc_theo_domain()`. `config/db.php` xác định tổ chức theo `Host` header trước
  khi mở session/kết nối CSDL nghiệp vụ; domain không khớp tổ chức nào đang hoạt động → 404.
  Chế độ single-tenant cũ (registry rỗng) giữ nguyên hành vi, không phải migrate ép buộc.
- **P3 — Cô lập session giữa các tổ chức**: đăng nhập (mật khẩu hoặc cookie "ghi nhớ") ghi
  `$_SESSION['to_chuc_id']`; mỗi request so khớp với tổ chức vừa xác định theo domain, lệch thì
  huỷ session ngay (phòng session id của tổ chức A bị dùng lại trên domain tổ chức B — session
  lưu trên server không tự tách theo tenant, chỉ cookie mới bị trình duyệt cô lập theo domain).
- **P3.1 — Quản lý domain**: `scripts/tao_to_chuc.php --domain=...` gán domain lúc tạo,
  `scripts/dat_domain_to_chuc.php` gán/đổi/gỡ domain sau. Domain là duy nhất trong registry
  (unique partial index, và kiểm tra ở tầng ứng dụng khi tạo/đổi).
- Test: `tests/ToChucTest.php` (chuẩn hoá host, khớp domain, tổ chức bị khoá, không khớp).
  Đã kiểm thử tay end-to-end qua `php -S` với 2 tổ chức + domain giả lập (xem lịch sử session
  này để biết kịch bản cụ thể nếu cần lặp lại).

## Còn lại (chưa làm — không có trong lần này)

- **P4 — Trang quản trị nền tảng cho `sieu_quan_tri`**: hiện chỉ có CLI
  (`tao_to_chuc.php`, `tao_sieu_quan_tri.php`, `dat_domain_to_chuc.php`). Cần 1 UI web riêng
  (route/domain riêng, tách hoàn toàn khỏi `public/*.php` của từng trường) để: liệt kê tổ chức,
  khoá/mở tổ chức (`dang_hoat_dong`), xem `nhat_ky_registry`, tạo tổ chức mới không cần SSH.
- **P4.1 — Đăng nhập cho `sieu_quan_tri`**: bảng `sieu_quan_tri` đã có, nhưng chưa có endpoint
  đăng nhập/route nào dùng tới nó. Cần quyết định domain/path cố định cho trang này (ví dụ
  `admin.<domain-chinh>` hoặc 1 domain riêng không trùng bất kỳ tổ chức nào) và đăng nhập phải
  đi qua registry.db, không phải CSDL nghiệp vụ của tổ chức nào.
- **P5 — Tự đăng ký tổ chức (self-service signup)**: hiện bắt buộc admin nền tảng chạy CLI thủ
  công. Nếu muốn mở cho khách tự tạo trường mới, cần form đăng ký + xác thực email + rate limit
  (khác hẳn threat model so với CLI chỉ người có quyền SSH mới chạy được).
- **P5.1 — Giới hạn/billing theo tổ chức**: chưa có khái niệm gói/hạn mức (số học sinh, dung
  lượng upload...) theo từng `to_chuc`. Nếu cần mô hình SaaS trả phí thì đây là việc riêng.
- **Vận hành**: chưa có hướng dẫn backup/restore theo từng tổ chức riêng lẻ (hiện
  `tools/cai_dat.php` chỉ thao tác trên 1 `db_path` cố định đọc từ `env.php`, chưa nhận
  `--ma-to-chuc` để backup/restore đúng 1 tổ chức trong môi trường multi-tenant).

## Ghi chú thiết kế

- Chọn cô lập vật lý (1 file SQLite/tổ chức) thay vì 1 CSDL dùng chung + cột `tenant_id`, để
  không có đường nào 1 câu truy vấn thiếu `WHERE tenant_id=?` lại lộ dữ liệu tổ chức khác.
- Domain là khoá định tuyến duy nhất hiện tại — chưa hỗ trợ nhiều domain cho cùng 1 tổ chức,
  cũng chưa hỗ trợ path-based routing (`/truong_abc/...`) cho trường hợp không có DNS riêng.
