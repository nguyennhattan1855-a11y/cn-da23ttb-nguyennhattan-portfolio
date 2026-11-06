<?php
// includes/auth.php
// Xác thực: đăng nhập, đăng xuất, kiểm tra người dùng hiện tại.

const SESSION_IDLE_SECONDS = 7200; // tự đăng xuất sau 2 giờ không hoạt động

// Trả về mảng thông tin người dùng đang đăng nhập, hoặc null nếu chưa đăng nhập.
function current_user(bool $refresh = false): ?array
{
    static $cache = null;
    static $loaded = false;

    if ($refresh) {
        $cache = null;
        $loaded = false;
    }
    if ($loaded) {
        return $cache;
    }
    $loaded = true;

    $id = $_SESSION['user_id'] ?? null;
    if (!$id) {
        return null;
    }
    $last = (int) ($_SESSION['last_activity'] ?? 0);
    if ($last > 0 && (time() - $last) > SESSION_IDLE_SECONDS) {
        logout_user();
        flash('warning', 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
        return null;
    }

    $user = db_one(
        'SELECT id, full_name, email, slug, is_active, created_at FROM users WHERE id = ?',
        [(int) $id]
    );
    if (!$user || (int) $user['is_active'] !== 1) {
        logout_user();
        return null;
    }
    $_SESSION['last_activity'] = time();
    $cache = $user;
    return $cache;
}

// Ghi nhớ đăng nhập. Đổi session id để chống session fixation.
function login_user(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['last_activity'] = time();
    unset($_SESSION['csrf_token']);
    current_user(true);
}

function logout_user(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
    current_user(true);
}

// Trang cần đăng nhập: chưa đăng nhập thì chuyển về trang đăng nhập.
function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash('warning', 'Vui lòng đăng nhập để tiếp tục.');
        redirect('login.php');
    }
    return $user;
}

// Trang chỉ dành cho khách (đăng nhập/đăng ký): đã đăng nhập thì vào dashboard.
function require_guest(): void
{
    if (current_user()) {
        redirect('dashboard/index.php');
    }
}
