-- =====================================================================
-- DevPortfolio - NÂNG CẤP lên phiên bản có: giới hạn đăng nhập sai, quên mật khẩu.
-- Dành cho CSDL đã có dữ liệu (đã nhập file SQL của phiên bản trước).
-- Chỉ THÊM 2 bảng mới bằng CREATE TABLE IF NOT EXISTS: không sửa, không xóa dữ liệu cũ.
-- Chạy trong phpMyAdmin: chọn CSDL devportfolio_db -> tab Nhập -> chọn file này.
-- =====================================================================
USE devportfolio_db;

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
