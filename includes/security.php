<?php
// includes/security.php
// Giới hạn đăng nhập sai (chống dò mật khẩu) và các hàm hỗ trợ bảo mật khác.

// Địa chỉ IP của người truy cập. Không tin X-Forwarded-For vì người dùng có thể giả mạo.
function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function now_string(int $offsetSeconds = 0): string
{
    return date('Y-m-d H:i:s', time() + $offsetSeconds);
}

// Chạy một thao tác với bảng login_attempts; nếu bảng chưa tồn tại (chưa nâng cấp CSDL)
// thì ghi log và bỏ qua để việc đăng nhập vẫn hoạt động. setup/check.php sẽ báo thiếu bảng.
function with_attempts_table(callable $fn, $fallback = null)
{
    try {
        return $fn();
    } catch (PDOException $ex) {
        if ($ex->getCode() === '42S02') {
            error_log('Thiếu bảng login_attempts: hãy nhập setup/nang_cap_v2.sql');
            return $fallback;
        }
        throw $ex;
    }
}

// Email hoặc địa chỉ IP này đã nhập sai quá nhiều lần chưa?
function login_is_locked(string $email): bool
{
    $max = max(1, (int) config('login_max_attempts', 5));
    $minutes = max(1, (int) config('login_lock_minutes', 15));
    $since = now_string(-$minutes * 60);
    $email = substr($email, 0, 190);

    return (bool) with_attempts_table(function () use ($email, $since, $max) {
        $byEmail = (int) db_value(
            'SELECT COUNT(*) FROM login_attempts WHERE email = ? AND attempted_at >= ?',
            [$email, $since]
        );
        $byIp = (int) db_value(
            'SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at >= ?',
            [client_ip(), $since]
        );
        return $byEmail >= $max || $byIp >= $max * 4;
    }, false);
}

function login_record_failure(string $email): void
{
    $email = substr($email, 0, 190);
    with_attempts_table(function () use ($email) {
        db_query('INSERT INTO login_attempts (email, ip, attempted_at) VALUES (?, ?, ?)',
            [$email, client_ip(), now_string()]);
        // Thỉnh thoảng dọn các bản ghi cũ hơn 1 ngày
        if (random_int(1, 50) === 1) {
            db_query('DELETE FROM login_attempts WHERE attempted_at < ?', [now_string(-86400)]);
        }
    });
}

// Đăng nhập thành công hoặc đã đặt lại mật khẩu: xóa các lần sai của email đó
function login_clear_failures(string $email): void
{
    $email = substr($email, 0, 190);
    with_attempts_table(function () use ($email) {
        db_query('DELETE FROM login_attempts WHERE email = ?', [$email]);
    });
}
