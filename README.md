# Xây dựng website hồ sơ năng lực cá nhân cho lập trình viên

## 1. Thông tin đồ án

- **Sinh viên thực hiện:** Nguyễn Nhật Tân
- **MSSV:** 110123243
- **Lớp:** DA23TTB
- **Giảng viên hướng dẫn:** ThS. Dương Ngọc Vân Khanh
- **Đơn vị:** Khoa Công nghệ Thông tin – Trường Kỹ thuật và Công nghệ, Trường Đại học Trà Vinh
- **Thời gian thực hiện:** 19/10/2026 – 27/12/2026

## 2. Giới thiệu đề tài

DevPortfolio là website hỗ trợ sinh viên Công nghệ thông tin và lập trình viên xây dựng, quản lý và trình bày hồ sơ năng lực cá nhân trên một nền tảng thống nhất.

Website giúp người dùng giới thiệu thông tin cá nhân, kỹ năng, học vấn, kinh nghiệm, dự án và tài liệu liên quan. Hồ sơ có thể được chia sẻ với nhà tuyển dụng thông qua đường dẫn hoặc mã QR.

## 3. Các chức năng đã xây dựng

### 3.1. Tài khoản người dùng
- Đăng ký, đăng nhập và đăng xuất.
- Quản lý thông tin tài khoản.
- Hỗ trợ quy trình quên và đặt lại mật khẩu.

### 3.2. Quản lý hồ sơ
- Cập nhật thông tin cá nhân và ảnh đại diện.
- Quản lý kỹ năng theo nhóm và mức độ.
- Quản lý học vấn, bằng cấp và kinh nghiệm.
- Quản lý dự án, công nghệ sử dụng, liên kết GitHub và bản demo.
- Tải ảnh đại diện và nhiều ảnh minh họa cho dự án.

### 3.3. Quản lý tài liệu
- Tải lên và quản lý CV.
- Quản lý tài liệu bằng cấp và chứng chỉ.

### 3.4. Trình bày và chia sẻ hồ sơ
- Hiển thị trang hồ sơ cá nhân công khai.
- Hiển thị trang chi tiết dự án.
- Tùy chỉnh mẫu giao diện và màu sắc chủ đạo.
- Liên kết GitHub và LinkedIn.
- Tạo mã QR để chia sẻ hồ sơ.
- Xuất hồ sơ dưới dạng PDF hoặc hình ảnh.

## 4. Công nghệ sử dụng

| Thành phần | Công nghệ |
|---|---|
| Giao diện | HTML5, CSS3, JavaScript |
| Xử lý phía máy chủ | PHP |
| Cơ sở dữ liệu | MySQL / MariaDB |
| Môi trường phát triển | XAMPP |
| Trình soạn thảo | Visual Studio Code |
| Quản lý phiên bản | Git và GitHub |

## 5. Cấu trúc mã nguồn

- `assets/`: CSS, JavaScript và tài nguyên giao diện.
- `config/`: Cấu hình ứng dụng.
- `dashboard/`: Các chức năng quản lý hồ sơ.
- `includes/`: Các hàm PHP dùng chung.
- `setup/`: Tập tin SQL, tài liệu hướng dẫn cài đặt và kiểm tra.
- `progress-report/`: Báo cáo tiến độ thực hiện đồ án.
- `index.php`: Trang chủ.
- `u.php`: Trang hồ sơ cá nhân công khai.
- `project.php`: Trang chi tiết dự án.

## 6. Hướng dẫn cài đặt trên máy cá nhân

1. Cài đặt XAMPP với Apache, PHP và MariaDB.
2. Tải mã nguồn repository về máy tính.
3. Đặt mã nguồn trong thư mục được Apache phục vụ, với đường dẫn website là `/portfolio`.
4. Tạo cơ sở dữ liệu `devportfolio_db` bằng phpMyAdmin.
5. Sử dụng tập tin SQL phù hợp trong thư mục `setup/` để tạo cấu trúc cơ sở dữ liệu và áp dụng các bản nâng cấp cần thiết.
6. Kiểm tra cấu hình kết nối cơ sở dữ liệu trong `config/`.
7. Khởi động Apache và MariaDB trong XAMPP.
8. Mở `http://localhost/portfolio/` trên trình duyệt.

**Lưu ý:** Cấu hình cơ sở dữ liệu cần điều chỉnh theo từng máy. Không đưa mật khẩu thật, dữ liệu tài khoản hoặc tài liệu riêng tư lên GitHub công khai.

## 7. Báo cáo tiến độ

Các báo cáo được lưu trong thư mục `progress-report/`.

- `tuan-01.md`: Báo cáo tiến độ tuần 1.
- `tuan-02.md`: Báo cáo tiến độ tuần 2.

Những báo cáo tiếp theo được bổ sung trong quá trình thực hiện đồ án.

## 8. Tình trạng phát triển

Các chức năng chính của website đã được xây dựng và chạy thử trên môi trường XAMPP.

Các công việc đang tiếp tục hoàn thiện gồm:

- Thiết kế và chuẩn hóa giao diện bằng Figma.
- Hoàn thiện sơ đồ Use Case, ERD và sơ đồ hoạt động.
- Kiểm thử chức năng, ghi nhận kết quả và khắc phục các lỗi phát sinh.
- Hoàn thiện tài liệu kỹ thuật và báo cáo đồ án.
- Chuẩn bị nội dung trình bày, demo và bảo vệ.

## 9. Thông tin liên hệ

**Sinh viên:** Nguyễn Nhật Tân

**Email:** nguyennhattan1855@gmail.com

---

*Repository được sử dụng để quản lý mã nguồn, tài liệu và theo dõi tiến độ thực hiện đồ án chuyên ngành.*
