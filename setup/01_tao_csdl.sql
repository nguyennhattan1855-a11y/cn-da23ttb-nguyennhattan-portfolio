-- =====================================================================
-- DevPortfolio - Tạo cơ sở dữ liệu và các bảng
-- Chạy trong phpMyAdmin: tab "Nhập" (Import) -> chọn file này -> Thực hiện.
-- An toàn khi chạy lại: dùng CREATE ... IF NOT EXISTS, không xóa dữ liệu.
-- Tương thích MariaDB 10.4 / MySQL 5.7+.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS devportfolio_db
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE devportfolio_db;

-- 1. Tài khoản đăng nhập
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,            -- kết quả password_hash(), KHÔNG lưu mật khẩu thật
    slug          VARCHAR(120) NOT NULL,            -- đường dẫn hồ sơ công khai
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Thông tin hồ sơ cá nhân (quan hệ 1-1 với users)
CREATE TABLE IF NOT EXISTS profiles (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id       INT UNSIGNED NOT NULL,
    headline      VARCHAR(150) NULL,
    bio           TEXT         NULL,
    contact_email VARCHAR(190) NULL,
    phone         VARCHAR(20)  NULL,
    location      VARCHAR(150) NULL,
    avatar_path   VARCHAR(255) NULL,                -- dùng ở giai đoạn 2 (tải ảnh)
    website_url   VARCHAR(255) NULL,
    github_url    VARCHAR(255) NULL,
    linkedin_url  VARCHAR(255) NULL,
    is_public     TINYINT(1)   NOT NULL DEFAULT 1,  -- 1 = công khai, 0 = ẩn
    updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_profiles_user (user_id),
    CONSTRAINT fk_profiles_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Kỹ năng
CREATE TABLE IF NOT EXISTS skills (
    id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id  INT UNSIGNED NOT NULL,
    name     VARCHAR(60)  NOT NULL,
    category VARCHAR(20)  NOT NULL DEFAULT 'other', -- language, framework, database, tool, other
    level    TINYINT UNSIGNED NOT NULL DEFAULT 3,   -- 1..5
    PRIMARY KEY (id),
    UNIQUE KEY uq_skills_user_name (user_id, name),
    CONSTRAINT fk_skills_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Kinh nghiệm làm việc / thực tập
CREATE TABLE IF NOT EXISTS experiences (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    position    VARCHAR(150) NOT NULL,
    company     VARCHAR(150) NOT NULL,
    start_date  DATE         NOT NULL,
    end_date    DATE         NULL,
    is_current  TINYINT(1)   NOT NULL DEFAULT 0,
    description TEXT         NULL,
    PRIMARY KEY (id),
    KEY idx_experiences_user_date (user_id, start_date),
    CONSTRAINT fk_experiences_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Học vấn
CREATE TABLE IF NOT EXISTS educations (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    school      VARCHAR(150) NOT NULL,
    degree      VARCHAR(150) NOT NULL,
    start_year  SMALLINT UNSIGNED NOT NULL,
    end_year    SMALLINT UNSIGNED NULL,              -- NULL = đang học
    description TEXT         NULL,
    PRIMARY KEY (id),
    KEY idx_educations_user_year (user_id, start_year),
    CONSTRAINT fk_educations_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Dự án lập trình (giao diện quản lý làm ở giai đoạn 2)
CREATE TABLE IF NOT EXISTS projects (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id           INT UNSIGNED NOT NULL,
    title             VARCHAR(150) NOT NULL,
    short_description VARCHAR(300) NULL,
    description       TEXT         NULL,
    technologies      VARCHAR(255) NULL,
    role              VARCHAR(150) NULL,
    project_date      DATE         NULL,
    thumbnail_path    VARCHAR(255) NULL,
    github_url        VARCHAR(255) NULL,
    demo_url          VARCHAR(255) NULL,
    is_featured       TINYINT(1)   NOT NULL DEFAULT 0,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_projects_user (user_id),
    CONSTRAINT fk_projects_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Nhiều hình ảnh minh họa cho một dự án
CREATE TABLE IF NOT EXISTS project_images (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id INT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    sort_order INT          NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_project_images_project (project_id),
    CONSTRAINT fk_project_images_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. CV, bằng cấp, chứng chỉ (tải lên ở giai đoạn 2)
CREATE TABLE IF NOT EXISTS documents (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id       INT UNSIGNED NOT NULL,
    doc_type      ENUM('cv','degree','certificate') NOT NULL,
    title         VARCHAR(150) NOT NULL,
    file_path     VARCHAR(255) NOT NULL,             -- tên file đã đổi ngẫu nhiên trên máy chủ
    original_name VARCHAR(255) NOT NULL,
    mime_type     VARCHAR(100) NOT NULL,
    file_size     INT UNSIGNED NOT NULL,
    is_public     TINYINT(1)   NOT NULL DEFAULT 0,   -- người dùng chọn tài liệu nào công khai
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_documents_user (user_id),
    CONSTRAINT fk_documents_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Cấu hình giao diện hồ sơ (mẫu giao diện, màu chủ đạo) - dùng ở giai đoạn 3
CREATE TABLE IF NOT EXISTS profile_settings (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id       INT UNSIGNED NOT NULL,
    template      VARCHAR(30)  NOT NULL DEFAULT 'classic',
    primary_color VARCHAR(7)   NOT NULL DEFAULT '#2563eb',
    updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_profile_settings_user (user_id),
    CONSTRAINT fk_profile_settings_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Nhật ký đăng nhập sai (giới hạn số lần thử, chống dò mật khẩu)
CREATE TABLE IF NOT EXISTS login_attempts (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email        VARCHAR(190) NOT NULL,
    ip           VARCHAR(45)  NOT NULL,
    attempted_at DATETIME     NOT NULL,
    PRIMARY KEY (id),
    KEY idx_login_attempts_email (email, attempted_at),
    KEY idx_login_attempts_ip (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Mã đặt lại mật khẩu (chỉ lưu bản băm SHA-256 của mã, không lưu mã thật)
CREATE TABLE IF NOT EXISTS password_resets (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NOT NULL,
    token_hash CHAR(64)     NOT NULL,
    ip         VARCHAR(45)  NOT NULL,
    expires_at DATETIME     NOT NULL,
    used_at    DATETIME     NULL,
    created_at DATETIME     NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_password_resets_token (token_hash),
    KEY idx_password_resets_user (user_id, created_at),
    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
