-- =====================================================================
-- DevPortfolio - Dữ liệu mẫu để thử nghiệm (toàn bộ là dữ liệu giả)
-- Tài khoản mẫu:  demo@example.com   /   mật khẩu: Demo@12345
-- Chạy SAU file 01_tao_csdl.sql. Có thể chạy lại: tài khoản mẫu sẽ được tạo mới.
-- Khi nộp/bảo vệ nên xóa tài khoản mẫu hoặc đổi mật khẩu.
-- =====================================================================

USE devportfolio_db;
SET NAMES utf8mb4;

-- Xóa tài khoản mẫu cũ (các bảng con tự xóa theo nhờ ON DELETE CASCADE)
DELETE FROM users WHERE email = 'demo@example.com';

INSERT INTO users (full_name, email, password_hash, slug) VALUES
('Trần Minh Demo', 'demo@example.com',
 '$2y$12$WgDixDxvXKm3ROKfCoMb8OYo9yXfm6NHwP1l7t8/CmJE.lvpxnDoK', 'tran-minh-demo');
SET @uid = LAST_INSERT_ID();

INSERT INTO profiles (user_id, headline, bio, contact_email, phone, location, website_url, github_url, linkedin_url, is_public) VALUES
(@uid, 'Lập trình viên Web PHP',
 'Sinh viên Công nghệ thông tin yêu thích xây dựng các ứng dụng web. Đây là hồ sơ mẫu dùng để thử nghiệm hệ thống DevPortfolio.',
 'demo@example.com', '0900000000', 'Vĩnh Long',
 'https://example.com', 'https://github.com/example-user', 'https://www.linkedin.com/in/example-user', 1);

INSERT INTO profile_settings (user_id, template, primary_color) VALUES (@uid, 'classic', '#2563eb');

INSERT INTO skills (user_id, name, category, level) VALUES
(@uid, 'PHP',        'language',  4),
(@uid, 'JavaScript', 'language',  3),
(@uid, 'HTML/CSS',   'language',  4),
(@uid, 'Bootstrap',  'framework', 3),
(@uid, 'MySQL',      'database',  4),
(@uid, 'Git',        'tool',      3),
(@uid, 'VS Code',    'tool',      5),
(@uid, 'Làm việc nhóm', 'other',  4);

INSERT INTO experiences (user_id, position, company, start_date, end_date, is_current, description) VALUES
(@uid, 'Thực tập sinh lập trình web', 'Công ty TNHH Phần mềm Ví Dụ', '2025-06-01', '2025-09-01', 0,
 'Tham gia xây dựng chức năng quản lý đơn hàng bằng PHP và MySQL.'),
(@uid, 'Cộng tác viên phát triển website', 'Câu lạc bộ Lập trình (ví dụ)', '2026-01-10', NULL, 1,
 'Thiết kế giao diện và hỗ trợ bảo trì website của câu lạc bộ.');

INSERT INTO educations (user_id, school, degree, start_year, end_year, description) VALUES
(@uid, 'Trường Đại học (ví dụ)', 'Công nghệ thông tin', 2023, NULL, 'Đang theo học chuyên ngành Công nghệ thông tin.');

INSERT INTO projects (user_id, title, short_description, description, technologies, role, project_date, github_url, demo_url, is_featured) VALUES
(@uid, 'Website bán hàng mini', 'Trang web bán hàng cơ bản bằng PHP.',
 'Có giỏ hàng, quản lý sản phẩm và đơn hàng.', 'PHP, MySQL, Bootstrap', 'Lập trình toàn bộ', '2025-12-01',
 'https://github.com/example-user/mini-shop', NULL, 1),
(@uid, 'Ứng dụng quản lý công việc', 'Danh sách việc cần làm có đăng nhập.',
 'Cho phép tạo, sửa, xóa và đánh dấu hoàn thành công việc.', 'PHP, JavaScript', 'Lập trình viên', '2026-03-15',
 'https://github.com/example-user/todo-app', NULL, 0);
