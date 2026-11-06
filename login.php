<?php
// login.php - Đăng nhập
require __DIR__ . '/includes/bootstrap.php';
require_guest();

$error = '';
$email = '';

// Hash giả để thời gian xử lý giống nhau dù email có tồn tại hay không
const DUMMY_HASH = '$2y$12$WgDixDxvXKm3ROKfCoMb8OYo9yXfm6NHwP1l7t8/CmJE.lvpxnDoK';

if (is_post()) {
    csrf_require();

    $email = strtolower(post_str('email'));
    $password = post_raw('password');

    if ($email === '' || $password === '') {
        $error = 'Vui lòng nhập email và mật khẩu.';
    } elseif (login_is_locked($email)) {
        // Nhập sai quá nhiều lần: tạm khóa, không kiểm tra mật khẩu nữa
        $error = 'Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau '
            . (int) config('login_lock_minutes', 15) . ' phút hoặc dùng chức năng "Quên mật khẩu".';
    } else {
        $row = db_one('SELECT id, password_hash, is_active FROM users WHERE email = ?', [$email]);
        $verified = password_verify($password, $row['password_hash'] ?? DUMMY_HASH);

        if ($row && $verified && (int) $row['is_active'] === 1) {
            // Nâng cấp hash nếu thuật toán mặc định của PHP đã thay đổi
            if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
                db_query('UPDATE users SET password_hash = ? WHERE id = ?', [
                    password_hash($password, PASSWORD_DEFAULT),
                    $row['id'],
                ]);
            }
            login_clear_failures($email);
            login_user((int) $row['id']);
            flash('success', 'Đăng nhập thành công.');
            redirect('dashboard/index.php');
        }
        // Ghi nhận lần sai (kể cả khi email không tồn tại, để không lộ email nào có trong hệ thống)
        login_record_failure($email);
        $error = 'Email hoặc mật khẩu không đúng.';
    }
}

$pageTitle = 'Đăng nhập';
require __DIR__ . '/includes/header.php';
?>
    <div class="auth-wrap">
        <div class="auth-card">
            <h1>Đăng nhập</h1>
            <p class="muted">Chào mừng bạn quay lại DevPortfolio.</p>

            <?php if ($error !== ''): ?>
                <div class="alert alert-error" role="alert"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('login.php')) ?>" novalidate>
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input class="form-control" type="email" id="email" name="email" maxlength="190"
                           autocomplete="email" required value="<?= e($email) ?>">
                </div>

                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input class="form-control" type="password" id="password" name="password" maxlength="72"
                           autocomplete="current-password" required>
                    <div class="form-hint"><a href="<?= e(url('forgot_password.php')) ?>">Quên mật khẩu?</a></div>
                </div>

                <button type="submit" class="btn btn-block">Đăng nhập</button>
            </form>

            <p class="auth-switch">Chưa có tài khoản? <a href="<?= e(url('register.php')) ?>">Đăng ký ngay</a></p>
        </div>
    </div>
<?php require __DIR__ . '/includes/footer.php'; ?>
