# Hợp đồng API JSON

## Chung
- Base: các file PHP trong `api/`, trả `application/json` với schema chuẩn: `{ "ok": true|false, "du_lieu": any, "thong_bao": string }`.
- Xác thực: session PHP. Gọi `POST /api/dang_nhap.php?hanh_dong=dang_nhap` trước, dùng cookie để giữ session; một số API yêu cầu vai trò ADMIN.
- Mã lỗi thường gặp (ở trường `thong_bao`): `chua_dang_nhap`, `khong_du_quyen`, `thieu_truong|thieu_id|thieu_hoc_sinh_id|thieu_file...`, `dang_nhap_that_bai`, `qua_so_lan`, `ly_do_khong_hop_le`, `qua_khong_hop_le`, `khong_du_diem`, `het_hang`, `file_qua_lon`, `dinh_dang_khong_ho_tro`...

## Xác thực
- `POST /api/dang_nhap.php?hanh_dong=dang_nhap` body JSON `{ten_dang_nhap, mat_khau, ghi_nho?bool}` → `du_lieu.ten_dang_nhap`. Giới hạn 3 lần sai → khóa 10 phút (kèm số lần/khoá đến nếu lỗi `qua_so_lan`).
- `POST /api/dang_nhap.php?hanh_dong=dang_xuat` → xóa session + cookie.

## Học sinh
- `GET /api/hoc_sinh.php?tu_khoa=&lop_hoc_id=&tat_ca=0|1` → danh sách học sinh (lọc theo lớp GV được gán nếu không phải ADMIN). Trường trả về: `id, ma, ho_ten, stt, lop_hoc_id, ten_lop, so_du, anh_dai_dien_url, gioi_tinh, ngay_sinh, dang_hoat_dong`.
- `POST /api/hoc_sinh.php` body `{ho_ten, ma?, lop_hoc_id?, anh_dai_dien_url?, gioi_tinh?, ngay_sinh?, stt?}` → `du_lieu.id` mới, tạo kèm ví điểm.
- `POST /api/hoc_sinh.php?hanh_dong=sua` body chứa `id` và các trường cần cập nhật (như trên, có thể bật/tắt `dang_hoat_dong`).
- `POST /api/hoc_sinh.php?hanh_dong=bat_tat` body `{id, dang_hoat_dong}`.
- CSV: `/api/hoc_sinh_csv.php`
  - `GET ?hanh_dong=xuat&lop_hoc_id=&tu_khoa=` → tải file CSV BOM UTF-8 (lọc quyền lớp).
  - `POST ?hanh_dong=xem_truoc` upload file (field `file`) → trả mẫu 5 dòng sau khi chuẩn hóa.
  - `POST ?hanh_dong=nhap` upload file (field `file`) → ghi DB, tự nhận delimiter, chuẩn hóa ngày sinh/giới tính/lớp; trả thống kê import.

## Điểm & quà
- `POST /api/diem.php?hanh_dong=cong` body `{hoc_sinh_id, ly_do_id, ghi_chu?}` → cập nhật số dư, trả `{so_du}`. Kiểm quyền lớp.
- `POST /api/diem.php?hanh_dong=quy_doi` body `{hoc_sinh_id, qua_tang_id, ghi_chu?}` → trừ điểm theo `gia_diem` của quà, kiểm tồn kho (>0 hoặc -1 nghĩa vô hạn), trả `{so_du}`.
- Luồng thẻ cào ở `trang_chinh.php`: yêu cầu học sinh có tối thiểu `5` điểm để bắt đầu cào; quà được chọn ngẫu nhiên trong nhóm có `gia_diem <= so_du` hiện tại, khi quy đổi trừ theo `gia_diem` của quà.
- `GET /api/diem.php?hanh_dong=lich_su&hoc_sinh_id=` → tối đa 200 bản ghi, GV chỉ thấy lịch sử cộng điểm lớp mình; trường: `id, loai, bien_diem, so_du_sau, ghi_chu, tao_luc, ho_ten, ly_do, qua`.
- `GET /api/diem.php?hanh_dong=thong_ke` → top số dư, top cộng điểm, top đổi điểm, tồn kho quà, quà ưa thích (lọc theo lớp GV nếu không phải ADMIN).
- Lý do: `GET /api/ly_do.php` → danh sách đang hoạt động.
- Quà: `GET /api/qua_tang.php` → danh sách đang hoạt động (đảm bảo có cột `anh_url`).

## Quản trị (ADMIN trừ khi ghi chú)
- `GET /api/giao_vien_quan_tri.php?hanh_dong=ds` → `{giao_vien:[{id,ten_dang_nhap}]}` để gán lớp.
- `GET /api/giao_vien_quan_tri.php?hanh_dong=ds_tk` (ADMIN) → `{tai_khoan:[{id,ten_dang_nhap,vai_tro}]}`.
- `GET /api/giao_vien_quan_tri.php` (mặc định) với `giao_vien_id?` → `{lop_hoc_ids:[...]}` lớp được gán cho GV.
- `POST /api/giao_vien_quan_tri.php?hanh_dong=doi_mat_khau` body `{mat_khau_cu, mat_khau_moi}`.
- `POST /api/giao_vien_quan_tri.php?hanh_dong=reset_mat_khau` (ADMIN) body `{id, mat_khau_moi}`.
- `POST /api/giao_vien_quan_tri.php?hanh_dong=them` (ADMIN) body `{ten_dang_nhap, mat_khau, vai_tro?}`.
- `POST /api/giao_vien_quan_tri.php?hanh_dong=cap_nhat_lop` body `{giao_vien_id?, lop_hoc_ids:[...]}` (GV có thể tự cập nhật lớp của mình).
- `POST /api/giao_vien_quan_tri.php?hanh_dong=cap_nhat_vai_tro` (ADMIN) body `{id, vai_tro}`.
- `GET /api/lop_hoc_quan_tri.php?cua_toi=0|1` → danh sách lớp (có `giao_vien_ids`).
- `POST /api/lop_hoc_quan_tri.php?hanh_dong=them` body `{ten, giao_vien_ids?[]}`; `hanh_dong=sua` body `{id, ten?, giao_vien_ids?[]}`; `hanh_dong=bat_tat` body `{id, dang_hoat_dong}`; `hanh_dong=xoa` body `{id}`.
- `GET /api/ly_do_quan_tri.php` → tất cả lý do; `POST` với `hanh_dong=them|sua|bat_tat|xoa` tương ứng.
- `GET /api/qua_tang_quan_tri.php` → tất cả quà; `POST` với `hanh_dong=them|sua|bat_tat|xoa`, hỗ trợ trường `anh_url`.
- `GET /api/nhat_ky.php?limit=&offset=&hanh_dong=&giao_vien_id=&tu_luc=&den_luc=&tu_khoa=` (ADMIN) → log thao tác.

## Upload
- `POST /api/upload_avatar.php` (multipart) field `hoc_sinh_id`, `file` ảnh → trả `{url}`; kiểm quyền lớp trừ ADMIN.
- `POST /api/upload_qua.php` (multipart) field `qua_tang_id`, `file` ảnh → `{url}`; chỉ ADMIN; re-encode ảnh (trừ GIF) và cập nhật `qua_tang.anh_url`.
