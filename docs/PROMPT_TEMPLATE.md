# Prompt template cho tác vụ với DClass

Dùng mẫu dưới khi nhờ AI/hỗ trợ tự động để đảm bảo nắm đúng bối cảnh và quy tắc dự án.

```
Mục tiêu: <mô tả ngắn bài cần làm>
Bối cảnh: PHP 8 + SQLite, session auth; API JSON {ok, du_lieu, thong_bao}; code tiếng Việt không dấu, snake_case. Tệp liên quan: <liệt kê đường dẫn chính>.
Ràng buộc: giữ strict_types, dùng json_phan_hoi, tuân thủ quyền sở hữu theo giáo viên (lớp qua giao_vien_lop, ly_do/qua_tang qua nguoi_tao_id) - không có vai trò quản trị toàn cục; cập nhật DB qua PDO prepare; asset offline trong public/vendor.
Đầu ra mong muốn: <mô tả file/thay đổi cần có, format (code, diff, tài liệu, ...)>.
Kiểm thử/đánh giá: <cách kiểm: composer lint/phpstan/test, hoặc bước manual cụ thể>.
Ghi chú an toàn: không lộ mật khẩu tài khoản mẫu (--seed), không thêm CDN, kiểm file upload ≤2MB/ảnh hợp lệ.
```
