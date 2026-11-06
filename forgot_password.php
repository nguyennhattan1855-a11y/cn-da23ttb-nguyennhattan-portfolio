<?php
// forgot_password.php - Yêu cầu đặt lại mật khẩu (gửi liên kết có thời hạn qua email)
require __DIR__ . '/includes/bootstrap.php';
require_guest();

$error = '';
$sent = false;
$email = '';

if (is_post()) {
    csrf_require();
    $email = strtolower(post_str('email'));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $error = 'Email không hợp lệ.';
    } else {
        $account = db_one('SELECT id, full_name, email FROM users WHERE email = ? AND is_active = 1', [$email]);
        if ($account) {
            // Tối đa 3 yêu cầu mỗi giờ cho một tài khoản (chống gửi thư rác)
            $recent = (int) db_value(
                'SELECT COUNT(*) FROM password_resets WHERE user_id = ? AND created_at >= ?',
                [$account['id'], now_string(-3600)]
            );
            if ($recent < 3) {
                $token = bin2hex(random_bytes(32));
                db_query(
                    'INSERT INTO password_resets (user_id, token_hash, ip, expires_at, created_at)
                     VALUES (?, ?, ?, ?, ?)',
                    [$account['id'], hash('sha256', $token), client_ip(), now_string(3600), now_string()]
                );
                $link = site_origin() . url('reset_password.php?token=' . $token);
                $body = "Xin chào " . $account['full_name'] . ",\n\n"
                    . "Bạn (hoặc ai đó) đã yêu cầu đặt lại mật khẩu tài khoản DevPortfolio.\n"
                    . "Bấm vào liên kết dưới đây để đặt mật khẩu mới (có hiệu lực trong 60 phút, dùng được một lần):\n\n"
                    . $link . "\n\n"
                    . "Nếu bạn không yêu cầu, hãy bỏ qua email này; mật khẩu hiện tại vẫn được giữ nguyên.\n";
                send_app_mail($account['email'], 'Đặt lại mật khẩu DevPortfolio', $body);
            }
        }
        // Luôn hiện cùng một thông báo, không tiết lộ email có tồn tại hay không
        $sent = true;
    }
}

$pageTitle = 'Quên mật khẩu';
require __DIR__ . '/includes/header.php';
?>
    <div class="auth-wrap">
        <div class="auth-card">
            <h1>Quên mật khẩu</h1>

            <?php if ($sent): ?>
                <div class="alert alert-success" role="alert">
                    Nếu email này có trong hệ thống, chúng tôi đã gửi hướng dẫn đặt lại mật khẩu.
                    Liên kết có hiệu lực trong 60 phút.
                </div>
                <?php if (mail_mode() === 'log'): ?>
                    <div class="alert alert-info" role="alert">
                        Chế độ thử nghiệm: email chưa được gửi thật mà được ghi vào file
                        <code>logs/mail.log</code> trong thư mục dự án. Mở file đó để lấy liên kết.
                    </div>
                <?php endif; ?>
                <p class="auth-switch"><a href="<?= e(url('login.php')) ?>">&larr; Quay lại đăng nhập</a></p>
            <?php else: ?>
                <p class="muted">Nhập email đã đăng ký, chúng tôi sẽ gửi liên kết để bạn đặt mật khẩu mới.</p>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-error" role="alert"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="post" action="<?= e(url('forgot_password.php')) ?>" novalidate>
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input class="form-control" type="email" id="email" name="email" maxlength="190"
                               autocomplete="email" required value="<?= e($email) ?>">
                    </div>
                    <button type="submit" class="btn btn-block">Gửi liên kết đặt lại</button>
                </form>
                <p class="auth-switch"><a href="<?= e(url('login.php')) ?>">&larr; Quay lại đăng nhập</a></p>
            <?php endif; ?>
        </div>
    </div>
<?php require __DIR__ . '/includes/footer.php'; ?>
