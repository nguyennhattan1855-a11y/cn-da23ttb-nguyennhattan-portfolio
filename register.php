<?php
// register.php - Đăng ký tài khoản
require __DIR__ . '/includes/bootstrap.php';
require_guest();

$errors = [];
$input = ['full_name' => '', 'email' => ''];

if (is_post()) {
    csrf_require();

    $input['full_name'] = post_str('full_name');
    $input['email'] = strtolower(post_str('email'));
    $password = post_raw('password');
    $confirm = post_raw('password_confirm');

    // --- Kiểm tra dữ liệu ở phía máy chủ ---
    $nameLen = text_length($input['full_name']);
    if ($nameLen < 2 || $nameLen > 100) {
        $errors['full_name'] = 'Họ tên phải từ 2 đến 100 ký tự.';
    }

    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL) || strlen($input['email']) > 190) {
        $errors['email'] = 'Email không hợp lệ.';
    } elseif ((int) db_value('SELECT COUNT(*) FROM users WHERE email = ?', [$input['email']]) > 0) {
        $errors['email'] = 'Email này đã được sử dụng.';
    }

    $pwError = password_policy_error($password);
    if ($pwError !== null) {
        $errors['password'] = $pwError;
    } elseif ($password !== $confirm) {
        $errors['password_confirm'] = 'Mật khẩu nhập lại không khớp.';
    }

    // --- Lưu vào CSDL ---
    if (!$errors) {
        $pdo = db();
        $newUserId = 0;
        try {
            $pdo->beginTransaction();
            db_query(
                'INSERT INTO users (full_name, email, password_hash, slug) VALUES (?, ?, ?, ?)',
                [
                    $input['full_name'],
                    $input['email'],
                    password_hash($password, PASSWORD_DEFAULT),
                    unique_slug($input['full_name']),
                ]
            );
            $newUserId = (int) $pdo->lastInsertId();
            db_query('INSERT INTO profiles (user_id, contact_email) VALUES (?, ?)', [$newUserId, $input['email']]);
            db_query('INSERT INTO profile_settings (user_id) VALUES (?)', [$newUserId]);
            $pdo->commit();
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($ex->getCode() === '23000') { // trùng khóa duy nhất (email)
                $errors['email'] = 'Email này đã được sử dụng.';
            } else {
                throw $ex;
            }
        }

        if (!$errors) {
            login_user($newUserId);
            flash('success', 'Đăng ký thành công! Hãy hoàn thiện hồ sơ của bạn.');
            redirect('dashboard/index.php');
        }
    }
}

$pageTitle = 'Đăng ký';
require __DIR__ . '/includes/header.php';
?>
    <div class="auth-wrap">
        <div class="auth-card">
            <h1>Tạo tài khoản</h1>
            <p class="muted">Đăng ký để bắt đầu xây dựng hồ sơ năng lực của bạn.</p>

            <form method="post" action="<?= e(url('register.php')) ?>" novalidate>
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="full_name">Họ và tên</label>
                    <input class="form-control" type="text" id="full_name" name="full_name" maxlength="100"
                           autocomplete="name" required value="<?= e($input['full_name']) ?>">
                    <?= field_error($errors, 'full_name') ?>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input class="form-control" type="email" id="email" name="email" maxlength="190"
                           autocomplete="email" required value="<?= e($input['email']) ?>">
                    <?= field_error($errors, 'email') ?>
                </div>

                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input class="form-control" type="password" id="password" name="password" maxlength="72"
                           autocomplete="new-password" required>
                    <div class="form-hint">Ít nhất 8 ký tự, gồm cả chữ và số.</div>
                    <?= field_error($errors, 'password') ?>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Nhập lại mật khẩu</label>
                    <input class="form-control" type="password" id="password_confirm" name="password_confirm"
                           maxlength="72" autocomplete="new-password" required>
                    <?= field_error($errors, 'password_confirm') ?>
                </div>

                <button type="submit" class="btn btn-block">Đăng ký</button>
            </form>

            <p class="auth-switch">Đã có tài khoản? <a href="<?= e(url('login.php')) ?>">Đăng nhập</a></p>
        </div>
    </div>
<?php require __DIR__ . '/includes/footer.php'; ?>
