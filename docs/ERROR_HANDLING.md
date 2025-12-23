# Xử lý lỗi & an toàn

- **Định dạng chung**: mọi API trả JSON qua `json_phan_hoi($ok, $du_lieu, $thong_bao)`; không ném stacktrace ra ngoài. Content-Type `application/json; charset=utf-8`.
- **HTTP status**: 401 khi chưa đăng nhập, 403 khi thiếu quyền (ví dụ xem nhật ký, upload quà), 404 cho route không hợp lệ, 405 cho method không cho phép (upload), 500 cho lỗi kết nối DB (fail-fast trong `config/db.php`).
- **Ràng buộc đăng nhập**: `yeu_cau_dang_nhap()` kiểm tra session, trả `chua_dang_nhap`. Đăng nhập giới hạn 3 lần sai → khóa 10 phút, trả `qua_so_lan` kèm số lần/khoá đến; có bảng `reset_khoa` để skip khóa.
- **Giao dịch điểm**: `cong_diem_giao_vien` và `quy_doi_qua_tang` gói trong transaction, rollback khi exception. Lỗi nghiệp vụ trả các mã như `ly_do_khong_hop_le`, `khong_du_quyen`, `het_hang`, `khong_du_diem`, `qua_khong_hop_le`.
- **Upload file**: kiểm tra bắt buộc `is_uploaded_file`, kích thước ≤2MB, định dạng ảnh hợp lệ, giới hạn kích thước 4000px và tổng pixel; re-encode để loại payload lạ. Trả các mã: `thieu_file`, `upload_error`, `file_qua_lon`, `dinh_dang_khong_ho_tro`, `anh_khong_hop_le`, `khong_ho_tro_webp`, `khong_luu_duoc_file`.
- **CSV import**: kiểm tra file, header, delimiter; trả `file_rong`, `thieu_cot_ho_ten`, ... trước khi ghi DB. Nhập thực tế chỉ chấp nhận dữ liệu hợp lệ sau chuẩn hóa (ngày, giới tính, lớp là số).
- **Migrate nhẹ**: một số API tự thêm cột nếu thiếu (ví dụ `qua_tang.anh_url`, `lop_hoc.dang_hoat_dong`). Nếu ALTER thất bại sẽ bỏ qua yên lặng để không chặn luồng chính.
- **Nhật ký**: hành động quản trị gọi `ghi_log`; lỗi log bị nuốt để không ảnh hưởng giao dịch chính.
