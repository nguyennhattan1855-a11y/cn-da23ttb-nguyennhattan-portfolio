<?php
// dashboard/account.php - Cài đặt tài khoản: đổi email, đổi mật khẩu
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login();
$uid = (int) $user['id'];

$emailErrors = [];
$passErrors = [];
$deleteErrors = [];
$emailInput = $user['email'];

if (is_post()) {
    csrf_require();
    $form = post_str('form');

    // ---------- Đổi email ----------
    if ($form === 'email') {
        $emailInput = strtolower(post_str('email'));
        $currentPassword = post_raw('current_password');

        if (!filter_var($emailInput, FILTER_VALIDATE_EMAIL) || strlen($emailInput) > 190) {
            $emailErrors['email'] = 'Email không hợp lệ.';
        } elseif ((int) db_value('SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?', [$emailInput, $uid]) > 0) {
            $emailErrors['email'] = 'Email này đã được sử dụng.';
        }

        $hash = (string) db_value('SELECT password_hash FROM users WHERE id = ?', [$uid]);
        if ($currentPassword === '' || !password_verify($currentPassword, $hash)) {
            $emailErrors['current_password'] = 'Mật khẩu hiện tại không đúng.';
        }

        if (!$emailErrors) {
            try {
                db_query('UPDATE users SET email = ? WHERE id = ?', [$emailInput, $uid]);
                flash('success', 'Đã cập nhật email đăng nhập.');
                redirect('dashboard/account.php');
            } catch (PDOException $ex) {
                if ($ex->getCode() === '23000') {
                    $emailErrors['email'] = 'Email này đã được sử dụng.';
                } else {
                    throw $ex;
                }
            }
        }
    }

    // ---------- Đổi mật khẩu ----------
    if ($form === 'password') {
        $currentPassword = post_raw('current_password');
        $newPassword = post_raw('new_password');
        $confirm = post_raw('new_password_confirm');

        $hash = (string) db_value('SELECT password_hash FROM users WHERE id = ?', [$uid]);
        if ($currentPassword === '' || !password_verify($currentPassword, $hash)) {
            $passErrors['current_password'] = 'Mật khẩu hiện tại không đúng.';
        }
        $pwError = password_policy_error($newPassword);
        if ($pwError !== null) {
            $passErrors['new_password'] = $pwError;
        } elseif ($newPassword !== $confirm) {
            $passErrors['new_password_confirm'] = 'Mật khẩu nhập lại không khớp.';
        }

        if (!$passErrors) {
            db_query('UPDATE users SET password_hash = ? WHERE id = ?', [
                password_hash($newPassword, PASSWORD_DEFAULT),
                $uid,
            ]);
            session_regenerate_id(true); // đổi session id sau khi đổi mật khẩu
            flash('success', 'Đã đổi mật khẩu.');
            redirect('dashboard/account.php');
        }
    }

    // ---------- Xóa tài khoản ----------
    if ($form === 'delete_account') {
        $currentPassword = post_raw('current_password');
        $confirmText = post_str('confirm_text');

        $hash = (string) db_value('SELECT password_hash FROM users WHERE id = ?', [$uid]);
        if ($currentPassword === '' || !password_verify($currentPassword, $hash)) {
            $deleteErrors['current_password'] = 'Mật khẩu hiện tại không đúng.';
        }
        if (strtoupper($confirmText) !== 'XOA') {
            $deleteErrors['confirm_text'] = 'Hãy gõ đúng chữ XOA để xác nhận.';
        }

        if (!$deleteErrors) {
            // Gom danh sách file của người dùng để xóa khỏi ổ đĩa sau khi xóa dữ liệu
            $imageFiles = [];
            $docFiles = [];
            $avatar = db_value('SELECT avatar_path FROM profiles WHERE user_id = ?', [$uid]);
            if ($avatar) {
                $imageFiles[] = $avatar;
            }
            foreach (db_all('SELECT thumbnail_path FROM projects WHERE user_id = ? AND thumbnail_path IS NOT NULL', [$uid]) as $r) {
                $imageFiles[] = $r['thumbnail_path'];
            }
            foreach (db_all(
                'SELECT pi.image_path FROM project_images pi JOIN projects p ON p.id = pi.project_id WHERE p.user_id = ?',
                [$uid]
            ) as $r) {
                $imageFiles[] = $r['image_path'];
            }
            foreach (db_all('SELECT file_path FROM documents WHERE user_id = ?', [$uid]) as $r) {
                $docFiles[] = $r['file_path'];
            }

            // Xóa người dùng: các bảng liên quan tự xóa theo (ON DELETE CASCADE)
            db_query('DELETE FROM users WHERE id = ?', [$uid]);

            foreach ($imageFiles as $f) {
                delete_stored_file('image', $f);
            }
            foreach ($docFiles as $f) {
                delete_stored_file('doc', $f);
            }
            logout_user();
            flash('success', 'Tài khoản và toàn bộ dữ liệu của bạn đã được xóa.');
            redirect('index.php');
        }
    }
}

$pageTitle = 'Cài đặt tài khoản';
$activeNav = 'account.php';
require __DIR__ . '/../includes/dash_header.php';
?>
                <div class="card">
                    <h2>Thông tin tài khoản</h2>
                    <p><strong>Họ tên:</strong> <?= e($user['full_name']) ?>
                        <span class="muted">(đổi tại mục "Thông tin cá nhân")</span></p>
                    <p><strong>Ngày tạo tài khoản:</strong> <?= e(format_date_vn(substr((string) $user['created_at'], 0, 10))) ?></p>
                </div>

                <form class="card" method="post" action="<?= e(url('dashboard/account.php')) ?>" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="form" value="email">
                    <h2>Đổi email đăng nhập</h2>

                    <div class="form-group">
                        <label for="email">Email mới</label>
                        <input class="form-control" type="email" id="email" name="email" maxlength="190"
                               autocomplete="email" required value="<?= e($emailInput) ?>">
                        <?= field_error($emailErrors, 'email') ?>
                    </div>
                    <div class="form-group">
                        <label for="email_current_password">Mật khẩu hiện tại</label>
                        <input class="form-control" type="password" id="email_current_password" name="current_password"
                               maxlength="72" autocomplete="current-password" required>
                        <?= field_error($emailErrors, 'current_password') ?>
                    </div>
                    <button type="submit" class="btn">Cập nhật email</button>
                </form>

                <form class="card" method="post" action="<?= e(url('dashboard/account.php')) ?>" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="form" value="password">
                    <h2>Đổi mật khẩu</h2>

                    <div class="form-group">
                        <label for="pw_current_password">Mật khẩu hiện tại</label>
                        <input class="form-control" type="password" id="pw_current_password" name="current_password"
                               maxlength="72" autocomplete="current-password" required>
                        <?= field_error($passErrors, 'current_password') ?>
                    </div>
                    <div class="form-group">
                        <label for="new_password">Mật khẩu mới</label>
                        <input class="form-control" type="password" id="new_password" name="new_password"
                               maxlength="72" autocomplete="new-password" required>
                        <div class="form-hint">Ít nhất 8 ký tự, gồm cả chữ và số.</div>
                        <?= field_error($passErrors, 'new_password') ?>
                    </div>
                    <div class="form-group">
                        <label for="new_password_confirm">Nhập lại mật khẩu mới</label>
                        <input class="form-control" type="password" id="new_password_confirm" name="new_password_confirm"
                               maxlength="72" autocomplete="new-password" required>
                        <?= field_error($passErrors, 'new_password_confirm') ?>
                    </div>
                    <button type="submit" class="btn">Đổi mật khẩu</button>
                </form>

                <form class="card card-danger" method="post" action="<?= e(url('dashboard/account.php')) ?>"
                      data-confirm="Xóa vĩnh viễn tài khoản và toàn bộ dữ liệu? Không thể khôi phục." novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="form" value="delete_account">
                    <h2>Xóa tài khoản</h2>
                    <p class="muted">
                        Toàn bộ hồ sơ, kỹ năng, kinh nghiệm, dự án, ảnh và tài liệu của bạn sẽ bị xóa vĩnh viễn.
                        Thao tác này không thể hoàn tác.
                    </p>
                    <div class="form-group">
                        <label for="del_current_password">Mật khẩu hiện tại</label>
                        <input class="form-control" type="password" id="del_current_password" name="current_password"
                               maxlength="72" autocomplete="current-password" required>
                        <?= field_error($deleteErrors, 'current_password') ?>
                    </div>
                    <div class="form-group">
                        <label for="confirm_text">Gõ chữ <strong>XOA</strong> để xác nhận</label>
                        <input class="form-control" type="text" id="confirm_text" name="confirm_text" maxlength="10"
                               autocomplete="off" required>
                        <?= field_error($deleteErrors, 'confirm_text') ?>
                    </div>
                    <button type="submit" class="btn btn-danger">Xóa tài khoản vĩnh viễn</button>
                </form>
<?php require __DIR__ . '/../includes/dash_footer.php'; ?>
