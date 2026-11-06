# Danh sách chức năng và trạng thái

Quy ước: **Đã lập trình** = mã đã viết xong; **Đã kiểm thử** = đã chạy thật và xác nhận đúng.
Môi trường xây dựng không có PHP/MariaDB nên **chưa chức năng PHP nào ở mức "Đã kiểm thử"**.
Cột cuối cho biết phần nào đã được chạy thử thật (chỉ phần JavaScript/CSS chạy được trong trình duyệt).

| # | Chức năng | File chính | Đã lập trình | Ghi chú kiểm chứng |
|---|---|---|---|---|
| 1 | Đăng ký (kiểm tra email trùng), đăng nhập, đăng xuất | register.php, login.php, logout.php | Có | Chưa chạy |
| 1 | Session an toàn, tự hết hạn sau 2 giờ, đổi session id khi đăng nhập | includes/bootstrap.php, auth.php | Có | Chưa chạy |
| 1 | Đổi mật khẩu, đổi email, thông tin tài khoản | dashboard/account.php | Có | Chưa chạy |
| 1 | Giới hạn đăng nhập sai (5 lần/15 phút theo email, 20 lần theo IP), tự mở khóa khi đặt lại mật khẩu | login.php, includes/security.php | Có | Chưa chạy |
| 1 | Quên mật khẩu: liên kết dùng một lần, hết hạn sau 60 phút, chỉ lưu bản băm của mã, tối đa 3 yêu cầu/giờ | forgot_password.php, reset_password.php, includes/mail.php | Có | Chưa chạy; email mặc định ghi vào logs/mail.log, gửi email thật chưa thể kiểm chứng |
| 1 | Xóa tài khoản (cần mật khẩu + gõ XOA), xóa luôn ảnh và tài liệu | dashboard/account.php | Có | Chưa chạy |
| 2 | Thông tin cá nhân, ảnh đại diện, liên kết, ẩn/hiện hồ sơ | dashboard/profile.php | Có | Chưa chạy |
| 3 | Kỹ năng: thêm/sửa/xóa, nhóm, mức độ | dashboard/skills.php | Có | Chưa chạy |
| 4 | Kinh nghiệm: thêm/sửa/xóa, sắp xếp theo thời gian | dashboard/experiences.php | Có | Chưa chạy |
| 2 | Học vấn: thêm/sửa/xóa | dashboard/education.php | Có | Chưa chạy |
| 5 | Dự án: thêm/sửa/xóa, ảnh đại diện, nhiều ảnh, nổi bật | dashboard/projects.php, project_edit.php | Có | Chưa chạy |
| 5 | Trang chi tiết dự án | project.php | Có | Chưa chạy |
| 6 | CV/bằng cấp/chứng chỉ: tải lên, xem, tải xuống, xóa, công khai/ẩn | dashboard/documents.php, file.php | Có | Chưa chạy |
| 7 | Kiểm tra URL GitHub/LinkedIn/demo (chỉ http/https, đúng tên miền) | includes/helpers.php (normalize_url) | Có | Chưa chạy |
| 8 | Hai mẫu giao diện, đổi màu chủ đạo, xem trước, lưu | dashboard/appearance.php, assets/css/profile.css | Có | CSS hai mẫu đã xem trên trình duyệt với trang mẫu tĩnh; phần lưu CSDL chưa chạy |
| 9 | Hồ sơ công khai theo đường dẫn riêng, ẩn/hiện | u.php | Có | Bố cục đã xem trên trang mẫu tĩnh; truy vấn CSDL chưa chạy |
| 10 | Sao chép đường dẫn, mã QR, tải mã QR | dashboard/share.php, assets/js/qrcode.js, share.js | Có | **Bộ tạo QR đã kiểm thử thật** (xem báo cáo) |
| 11 | Xuất PDF (in → Lưu PDF) và PNG | assets/js/export.js, profile.css | Có | **Đã chạy thật trên Chromium** với trang mẫu tĩnh |
| 12 | Dashboard: tổng quan, menu, thông báo, xác nhận khi xóa | dashboard/*, includes/dash_*.php | Có | Chưa chạy |
| — | Cơ sở dữ liệu 9 bảng + dữ liệu mẫu | setup/*.sql | Có | Đã kiểm tra cú pháp bằng mắt/đếm ngoặc, chưa nhập vào MariaDB |
| — | Bảo mật: PDO, escape HTML, CSRF, quyền sở hữu, chặn chạy PHP trong thư mục tải lên | toàn hệ thống | Có | Chưa chạy |

## Hạn chế đã biết
- Chưa có xác thực email khi đăng ký và chưa có trang quản trị viên (admin).
- Giới hạn đăng nhập theo email có thể bị lợi dụng để khóa tạm tài khoản của người khác (đánh đổi thường gặp); tài khoản tự mở khóa sau 15 phút hoặc khi đặt lại mật khẩu.
- Gửi email thật phụ thuộc máy chủ thư của hosting (hàm `mail()` của PHP); trên XAMPP dùng chế độ ghi vào `logs/mail.log`.
- PDF được tạo bằng chức năng in của trình duyệt (không phải thư viện PDF phía máy chủ), nên bố cục phụ thuộc trình duyệt.
- Xuất PNG hoạt động trên trình duyệt hiện đại (Chrome/Edge đã thử trên Chromium); Safari cũ có thể không hỗ trợ.
- Mã QR hỗ trợ đường dẫn tối đa 213 byte.
- Ảnh tải lên được lưu nguyên bản (không nén/đổi kích thước); ảnh có địa chỉ ngẫu nhiên nên người biết địa chỉ vẫn xem được ảnh dù hồ sơ đang ẩn.
- Đường dẫn hồ sơ dạng `u.php?slug=...` (không dùng URL đẹp `/u/ten`).
