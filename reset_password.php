<?php
// reset_password.php?token=... - Đặt mật khẩu mới từ liên kết trong email
require __DIR__ . '/includes/bootstrap.php';
require_guest();

// Không gửi địa chỉ trang (có chứa mã) sang trang khác qua header Referer
header('Referrer-Policy: no-referrer');

$token = '';
if (isset($_POST['token']) && is_string($_POST['token'])) {
    $token = $_POST['token'];
} elseif (isset($_GET['token']) && is_string($_GET['token'])) {
    $token = $_GET['token'];
}

// Tìm mã còn hiệu lực (chỉ so sánh bản băm SHA-256)
$reset = null;
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $reset = db_one(
        'SELECT id, user_id, expires_at, used_at FROM password_resets WHERE token_hash = ?',
        [hash('sha256', $token)]
    );
    if ($reset && ($reset['used_at'] !== null || $reset['expires_at'] < now_string())) {
        $reset = null;
    }
}

$errors = [];
if ($reset !== null && is_post()) {
    csrf_require();
    $newPassword = post_raw('new_password');
    $confirm = post_raw('new_password_confirm');

    $pwError = password_policy_error($newPassword);
    if ($pwError !== null) {
        $errors['new_password'] = $pwError;
    } elseif ($newPassword !== $confirm) {
        $errors['new_password_confirm'] = 'Mật khẩu nhập lại không khớp.';
    }

    if (!$errors) {
        $pdo = db();
        $claimed = false;
        try {
            $pdo->beginTransaction();
            // Đánh dấu đã dùng; nếu không cập nhật được dòng nào nghĩa là mã vừa bị dùng ở nơi khác
            $stmt = db_query('UPDATE password_resets SET used_at = ? WHERE id = ? AND used_at IS NULL',
                [now_string(), $reset['id']]);
            if ($stmt->rowCount() === 1) {
                db_query('UPDATE users SET password_hash = ? WHERE id = ?',
                    [password_hash($newPassword, PASSWORD_DEFAULT), $reset['user_id']]);
                // Các mã khác còn hiệu lực của tài khoản này cũng vô hiệu
                db_query('UPDATE password_resets SET used_at = ? WHERE user_id = ? AND used_at IS NULL',
                    [now_string(), $reset['user_id']]);
                $claimed = true;
            }
            $pdo->commit();
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $ex;
        }

        if ($claimed) {
            $emailOfUser = (string) db_value('SELECT email FROM users WHERE id = ?', [$reset['user_id']]);
            login_clear_failures($emailOfUser); // mở khóa đăng nhập nếu đang bị khóa
            flash('success', 'Đã đặt lại mật khẩu. Bạn có thể đăng nhập bằng mật khẩu mới.');
            redirect('login.php');
        }
        $reset = null; // mã không còn hợp lệ
    }
}

$pageTitle = 'Đặt lại mật khẩu';
require __DIR__ . '/includes/header.php';
?>
    <div class="auth-wrap">
        <div class="auth-card">
            <h1>Đặt lại mật khẩu</h1>

            <?php if ($reset === null): ?>
                <div class="alert alert-error" role="alert">
                    Liên kết không hợp lệ, đã hết hạn hoặc đã được sử dụng.
                </div>
                <p><a class="btn btn-block" href="<?= e(url('forgot_password.php')) ?>">Yêu cầu liên kết mới</a></p>
            <?php else: ?>
                <p class="muted">Nhập mật khẩu mới cho tài khoản của bạn.</p>
                <form method="post" action="<?= e(url('reset_password.php')) ?>" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= e($token) ?>">

                    <div class="form-group">
                        <label for="new_password">Mật khẩu mới</label>
                        <input class="form-control" type="password" id="new_password" name="new_password"
                               maxlength="72" autocomplete="new-password" required>
                        <div class="form-hint">Ít nhất 8 ký tự, gồm cả chữ và số.</div>
                        <?= field_error($errors, 'new_password') ?>
                    </div>
                    <div class="form-group">
                        <label for="new_password_confirm">Nhập lại mật khẩu mới</label>
                        <input class="form-control" type="password" id="new_password_confirm" name="new_password_confirm"
                               maxlength="72" autocomplete="new-password" required>
                        <?= field_error($errors, 'new_password_confirm') ?>
                    </div>
                    <button type="submit" class="btn btn-block">Đặt mật khẩu mới</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php require __DIR__ . '/includes/footer.php'; ?>
