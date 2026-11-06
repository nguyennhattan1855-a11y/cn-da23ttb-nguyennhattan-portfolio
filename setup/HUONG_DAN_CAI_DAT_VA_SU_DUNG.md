# DevPortfolio – Hướng dẫn cài đặt và sử dụng (XAMPP, Windows)

## A. Đưa mã nguồn vào repository
1. Đóng VS Code. Giải nén `DevPortfolio_HoanChinh.zip` ra Desktop.
2. Sao chép TOÀN BỘ nội dung trong thư mục `cn-da23ttb-nguyennhattan-portfolio` (trong ZIP) vào
   `D:\Đồ Án Chuyên Ngành_Vân Khanh\04_Ma_nguon\cn-da23ttb-nguyennhattan-portfolio`, chọn **Replace/Ghi đè**.
   ZIP không chứa `.git` nên lịch sử Git không bị ảnh hưởng. Không tạo thêm bản sao trong `htdocs`.

## B. Cài đặt lần đầu
0. Bật **Apache** và **MySQL** trong XAMPP Control Panel.
1. Mở `http://localhost/portfolio/setup/ping.php` → dòng "Tìm thấy includes/bootstrap.php" phải là **CÓ - OK**.
2. Mở `http://localhost/phpmyadmin` → tab **Nhập (Import)** → chọn `setup/devportfolio_full.sql` → **Thực hiện**.
   (File này tạo CSDL `devportfolio_db`, 9 bảng và tài khoản mẫu.)
3. Mở `http://localhost/portfolio/setup/check.php` → mọi dòng phải có dấu ✔.
4. Mở `http://localhost/portfolio/`.

**Tài khoản mẫu:** `demo@example.com` / `Demo@12345` (hồ sơ: `http://localhost/portfolio/u.php?slug=tran-minh-demo`).
Hoặc tự đăng ký tài khoản mới. Trước khi nộp/bảo vệ nên xóa hoặc đổi mật khẩu tài khoản mẫu.

## C. Nâng cấp từ phiên bản trước (đã có dữ liệu)
Phiên bản này thêm **2 bảng mới** (`login_attempts`, `password_resets`) cho chức năng giới hạn đăng nhập sai và quên mật khẩu.
Các bảng cũ **không đổi** nên không mất dữ liệu:
1. (Khuyên dùng) Sao lưu: phpMyAdmin → chọn `devportfolio_db` → tab **Xuất** → Thực hiện.
2. Chép mã nguồn mới đè lên repo (bước A).
3. phpMyAdmin → chọn `devportfolio_db` → tab **Nhập** → chọn `setup/nang_cap_v2.sql` → Thực hiện.
4. Mở `setup/check.php`: dòng "Đủ 11 bảng dữ liệu" phải đạt.
Không nhập `devportfolio_full.sql` vào CSDL đang có dữ liệu thật (file này dành cho cài mới; nó chỉ xóa và tạo lại tài khoản mẫu
`demo@example.com`, nhưng vẫn nên sao lưu trước).
Nếu quên bước 3, đăng nhập vẫn dùng được nhưng chưa có giới hạn số lần sai; trang quên mật khẩu sẽ báo lỗi.

## D. Cấu hình (khi cần)
- MariaDB có đặt mật khẩu cho `root`: sao chép `config/config.local.example.php` thành `config/config.local.php`
  và sửa mật khẩu (file này không được đưa lên GitHub).
- Muốn xem lỗi chi tiết khi cài đặt: trong `config.local.php` đặt `'debug' => true` (xong việc đặt lại `false`).
- Nhật ký lỗi: `logs/error.log`.
- Quên mật khẩu: mặc định `mail.mode = 'log'` (không gửi email thật; nội dung email, trong đó có liên kết đặt lại mật khẩu,
  được ghi vào `logs/mail.log`). Khi đưa lên hosting có máy chủ thư, đặt `'mail' => ['mode' => 'mail', 'from' => '...']` trong `config.local.php`.
- Giới hạn đăng nhập sai: `login_max_attempts` (mặc định 5 lần) và `login_lock_minutes` (mặc định 15 phút) trong `config/config.php`.
- Triển khai thật (không phải localhost): không dùng `root` không mật khẩu; tạo tài khoản MySQL riêng có mật khẩu mạnh,
  bật HTTPS và đặt `'site_url' => 'https://ten-mien-cua-ban'` trong `config.local.php`.

## E. Cách sử dụng
| Việc cần làm | Vào đâu |
|---|---|
| Nhập thông tin, ảnh đại diện, GitHub/LinkedIn, ẩn/hiện hồ sơ | Bảng điều khiển → Thông tin cá nhân |
| Kỹ năng, kinh nghiệm, học vấn | Các mục cùng tên |
| Dự án (nhiều ảnh, nổi bật, liên kết) | Dự án |
| CV, bằng cấp, chứng chỉ; chọn tài liệu công khai | CV & chứng chỉ |
| Chọn mẫu giao diện, màu chủ đạo, xem trước | Giao diện hồ sơ |
| Sao chép đường dẫn, mã QR, xuất PDF/PNG | Chia sẻ & xuất hồ sơ |
| Đổi mật khẩu, email, xóa tài khoản | Cài đặt tài khoản |
| Quên mật khẩu | Trang đăng nhập → "Quên mật khẩu?" |

Xuất PDF: trang hồ sơ mở hộp thoại in của trình duyệt → chọn "Lưu dưới dạng PDF". Xuất ảnh: tự tải file PNG.

## F. Bảng kiểm thử dành cho bạn (điền cột cuối)
| # | Việc làm | Kết quả đúng | Kết quả của bạn |
|---|---|---|---|
| 1 | `setup/ping.php`, `setup/check.php` | Tất cả đạt | |
| 2 | Đăng ký email mới, mật khẩu `Abc12345` | Vào bảng điều khiển | |
| 3 | Đăng ký lại cùng email / mật khẩu `abc` | Báo lỗi email trùng / mật khẩu yếu | |
| 4 | Đăng nhập sai / đúng; đăng xuất | Đúng thông báo; đăng xuất về trang chủ | |
| 5 | Chưa đăng nhập, mở `/portfolio/dashboard/index.php` | Bị chuyển về đăng nhập | |
| 6 | Thông tin cá nhân: nhập đủ; GitHub `github.com/abc`; LinkedIn sai tên miền; `javascript:alert(1)` | GitHub lưu thành `https://github.com/abc`; hai trường sai bị từ chối | |
| 7 | Tải ảnh đại diện JPG/PNG ≤ 3 MB | Ảnh hiện ở form và hồ sơ công khai | |
| 8 | Tải file `.txt` đổi đuôi thành `.jpg` / ảnh > 3 MB | Bị từ chối, có thông báo | |
| 9 | Kỹ năng: thêm, sửa, trùng tên, xóa | Đúng; trùng tên báo lỗi; xóa có xác nhận | |
| 10 | Kinh nghiệm: ngày kết thúc trước ngày bắt đầu; tích "đang làm việc" | Báo lỗi; ô ngày kết thúc bị khóa | |
| 11 | Học vấn: thêm, sửa, xóa; để trống năm kết thúc | Hiện "Đang học" | |
| 12 | Dự án: thêm có ảnh đại diện + 3 ảnh minh họa, đánh dấu nổi bật | Hiện trong danh sách và hồ sơ công khai | |
| 13 | Sửa dự án: xóa 1 ảnh, thay ảnh đại diện | Ảnh cũ biến mất (kiểm tra cả thư mục `uploads/images`) | |
| 14 | Mở trang chi tiết dự án | Có thông tin, công nghệ, thư viện ảnh, liên kết | |
| 15 | Xóa dự án | Có xác nhận; ảnh bị xóa khỏi `uploads/images` | |
| 16 | Tải CV PDF (công khai) và 1 chứng chỉ (riêng tư) | Cả hai trong danh sách; chỉ CV hiện ở hồ sơ công khai | |
| 17 | Xem / Tải xuống tài liệu; Ẩn/Công khai; Xóa | Đúng từng nút; xóa có xác nhận | |
| 18 | Mở `http://localhost/portfolio/storage/documents/` hoặc `/uploads/` trực tiếp | Bị chặn (403) | |
| 19 | Mở hồ sơ công khai bằng cửa sổ ẩn danh | Xem được khi hồ sơ công khai | |
| 20 | Tắt "công khai hồ sơ", mở lại bằng ẩn danh | Báo không tìm thấy; chính chủ vẫn xem được | |
| 21 | Ẩn danh mở `file.php?id=` của tài liệu riêng tư | Báo không tìm thấy | |
| 22 | Giao diện hồ sơ: đổi mẫu và màu, xem trước, Lưu | Hồ sơ công khai đổi theo | |
| 23 | Chia sẻ: nút Sao chép; quét mã QR bằng điện thoại | Dán được đường dẫn; mở đúng hồ sơ | |
| 24 | Tải mã QR PNG | Có file ảnh, quét được | |
| 25 | Xuất PDF; xuất PNG | PDF A4 đủ tiếng Việt; PNG đúng bố cục | |
| 26 | Đăng ký tài khoản thứ hai; thử `project_edit.php?id=` của dự án tài khoản thứ nhất | Báo không tìm thấy | |
| 27 | Đổi mật khẩu, đăng xuất, đăng nhập lại | Mật khẩu mới dùng được | |
| 29 | Đăng nhập sai mật khẩu 5 lần liên tiếp rồi đăng nhập lại (kể cả mật khẩu đúng) | Báo "đã nhập sai quá nhiều lần", chờ 15 phút mới thử lại được | |
| 30 | Quên mật khẩu: nhập email đã đăng ký rồi mở `logs/mail.log` | Có email chứa liên kết `reset_password.php?token=...` | |
| 31 | Quên mật khẩu với email không tồn tại | Vẫn hiện cùng thông báo thành công, không có email trong `mail.log` | |
| 32 | Mở liên kết, đặt mật khẩu mới, đăng nhập | Đăng nhập được bằng mật khẩu mới; bị khóa do sai nhiều lần cũng được mở khóa | |
| 33 | Mở lại chính liên kết đó lần hai | Báo "không hợp lệ, đã hết hạn hoặc đã được sử dụng" | |
| 34 | Xóa tài khoản: nhập sai mật khẩu / không gõ XOA | Bị từ chối, có thông báo | |
| 35 | Xóa tài khoản đúng mật khẩu và gõ XOA (dùng tài khoản thử) | Về trang chủ; đăng nhập lại không được; ảnh/tài liệu của tài khoản biến mất khỏi `uploads/images`, `storage/documents`; hồ sơ công khai báo không tìm thấy | |
| 28 | Thu nhỏ trình duyệt / mở trên điện thoại (dashboard và hồ sơ) | Bố cục xếp dọc, không vỡ | |

## G. Đưa lên GitHub (Terminal trong VS Code, ở thư mục dự án)
```
git status
git add .
git commit -m "Hoan thien DevPortfolio: du an, tai lieu, ho so cong khai, QR, xuat PDF/PNG"
git push
```
`uploads/images/*`, `storage/documents/*`, `config/config.local.php`, `logs/*` đã được `.gitignore` bỏ qua nên dữ liệu thật không bị đưa lên GitHub.
