# Báo cáo kiểm thử (trung thực về những gì đã và chưa kiểm chứng)

## 1. Đã thực hiện thật (có chạy mã)
| Nội dung | Cách làm | Kết quả |
|---|---|---|
| Bộ tạo mã QR (`assets/js/qrcode.js`) | Sinh QR cho mọi độ dài 1–213 byte (phiên bản 1–10, có tiếng Việt UTF-8) rồi giải mã bằng OpenCV | 213/213 giải mã đúng bằng bộ giải mã Aruco; bộ giải mã mặc định của OpenCV đúng 212/213 (1 mẫu 153 byte nó không đọc được nhưng bộ thứ hai đọc được → lỗi của bộ giải mã, không phải của mã QR) |
| Giao diện hồ sơ công khai | Dựng trang HTML mẫu có cấu trúc giống `u.php`, chụp bằng Chromium với 4 tổ hợp (2 mẫu × màu đậm/nhạt) | Bố cục đúng, chữ đọc được cả khi chọn màu nhạt (tự đổi màu chữ) |
| Responsive | Chụp ở khổ 390 px | Không tràn ngang |
| Xuất PNG (`export.js`) | Bấm nút "Tải ảnh PNG" trong Chromium | Tải được file PNG 2160 px, đúng bố cục, ảnh được nhúng, ẩn thanh công cụ và mục Tài liệu; đã sửa lỗi sai phông trong lúc thử |
| Xuất PDF | In ra PDF khổ A4 bằng Chromium | 2 trang, trích được chữ tiếng Việt, không có thanh công cụ và mục Tài liệu |
| Rà soát tĩnh PHP | Script tự viết kiểm tra cho 37 file PHP: cân bằng ngoặc, cặp `if/endif`, `foreach/endforeach`, thẻ `?>` trong chú thích, tên hàm gọi tới có tồn tại không, biến chưa gán | Phát hiện và đã sửa 1 lỗi thật (thẻ `?>` nằm trong chú thích làm vỡ file); hiện không còn vấn đề |

## 2. CHƯA thực hiện được (không có PHP/MariaDB/Apache trong môi trường xây dựng)
- Chạy bất kỳ trang PHP nào; thực thi các câu SQL; nhập file SQL vào MariaDB.
- Phiên đăng nhập, CSRF, tải file lên, tải tài liệu qua `file.php`, các file `.htaccess`.
- Việc PHP trên Windows có đọc được đường dẫn thư mục chứa chữ "Đồ Án" qua junction hay không (xem `setup/ping.php`).
- Giới hạn đăng nhập sai, quên mật khẩu, xóa tài khoản: mới chỉ rà soát tĩnh, chưa chạy; việc gửi email thật chưa thể thử.
- Trang hồ sơ thật do `u.php` sinh ra (mới thử bằng trang mẫu có cùng cấu trúc HTML/CSS).

→ Vì vậy **cần bạn chạy bảng kiểm thử 35 mục ở `HUONG_DAN_CAI_DAT_VA_SU_DUNG.md` (mục F)** và gửi lại kết quả hoặc nội dung `logs/error.log` nếu có lỗi.
Chỉ sau khi bảng đó đạt mới được ghi "đã kiểm thử" trong báo cáo đồ án.
