<?php
// dashboard/profile.php - Quản lý thông tin cá nhân
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login();
$uid = (int) $user['id'];

$profile = db_one('SELECT * FROM profiles WHERE user_id = ?', [$uid]);
if (!$profile) {
    db_query('INSERT INTO profiles (user_id, contact_email) VALUES (?, ?)', [$uid, $user['email']]);
    $profile = db_one('SELECT * FROM profiles WHERE user_id = ?', [$uid]);
}

$errors = [];
$input = [
    'full_name'     => $user['full_name'],
    'headline'      => (string) $profile['headline'],
    'bio'           => (string) $profile['bio'],
    'contact_email' => (string) $profile['contact_email'],
    'phone'         => (string) $profile['phone'],
    'location'      => (string) $profile['location'],
    'website_url'   => (string) $profile['website_url'],
    'github_url'    => (string) $profile['github_url'],
    'linkedin_url'  => (string) $profile['linkedin_url'],
    'is_public'     => (int) $profile['is_public'],
];

if (is_post()) {
    csrf_require();

    $input = [
        'full_name'     => post_str('full_name'),
        'headline'      => post_str('headline'),
        'bio'           => post_str('bio'),
        'contact_email' => strtolower(post_str('contact_email')),
        'phone'         => post_str('phone'),
        'location'      => post_str('location'),
        'website_url'   => post_str('website_url'),
        'github_url'    => post_str('github_url'),
        'linkedin_url'  => post_str('linkedin_url'),
        'is_public'     => post_bool('is_public'),
    ];

    $len = text_length($input['full_name']);
    if ($len < 2 || $len > 100) {
        $errors['full_name'] = 'Họ tên phải từ 2 đến 100 ký tự.';
    }
    if (text_length($input['headline']) > 150) {
        $errors['headline'] = 'Vị trí mong muốn tối đa 150 ký tự.';
    }
    if (text_length($input['bio']) > 2000) {
        $errors['bio'] = 'Giới thiệu tối đa 2000 ký tự.';
    }
    if ($input['contact_email'] !== '' &&
        (!filter_var($input['contact_email'], FILTER_VALIDATE_EMAIL) || strlen($input['contact_email']) > 190)) {
        $errors['contact_email'] = 'Email liên hệ không hợp lệ.';
    }
    if ($input['phone'] !== '' && !preg_match('/^[0-9+\-\s().]{8,20}$/', $input['phone'])) {
        $errors['phone'] = 'Số điện thoại không hợp lệ (8–20 ký tự gồm số, khoảng trắng, + - ( ) .).';
    }
    if (text_length($input['location']) > 150) {
        $errors['location'] = 'Khu vực tối đa 150 ký tự.';
    }

    // Kiểm tra và chuẩn hóa các đường dẫn (chỉ http/https)
    $urlRules = [
        'website_url'  => null,
        'github_url'   => 'github.com',
        'linkedin_url' => 'linkedin.com',
    ];
    foreach ($urlRules as $field => $domain) {
        [$normalized, $urlError] = normalize_url($input[$field], $domain);
        if ($urlError !== null) {
            $errors[$field] = $urlError;
        } else {
            $input[$field] = $normalized ?? '';
        }
    }

    // Ảnh đại diện (chỉ xử lý khi các trường khác đã hợp lệ)
    $newAvatar = null;
    $removeAvatar = post_bool('remove_avatar') === 1;
    if (!$errors && isset($_FILES['avatar'])) {
        [$newAvatar, $avatarError] = save_uploaded_image($_FILES['avatar']);
        if ($avatarError !== null) {
            $errors['avatar'] = $avatarError;
        }
    }

    if (!$errors) {
        $oldAvatar = $profile['avatar_path'];
        $avatarValue = $newAvatar ?? ($removeAvatar ? null : $oldAvatar);

        $pdo = db();
        try {
            $pdo->beginTransaction();
            db_query('UPDATE users SET full_name = ? WHERE id = ?', [$input['full_name'], $uid]);
            db_query(
                'UPDATE profiles SET headline = ?, bio = ?, contact_email = ?, phone = ?, location = ?,
                        avatar_path = ?, website_url = ?, github_url = ?, linkedin_url = ?, is_public = ?
                 WHERE user_id = ?',
                [
                    null_if_empty($input['headline']),
                    null_if_empty($input['bio']),
                    null_if_empty($input['contact_email']),
                    null_if_empty($input['phone']),
                    null_if_empty($input['location']),
                    $avatarValue,
                    null_if_empty($input['website_url']),
                    null_if_empty($input['github_url']),
                    null_if_empty($input['linkedin_url']),
                    $input['is_public'],
                    $uid,
                ]
            );
            $pdo->commit();
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            delete_stored_file('image', $newAvatar); // không để lại file mồ côi
            throw $ex;
        }
        if ($avatarValue !== $oldAvatar) {
            delete_stored_file('image', $oldAvatar);
        }
        flash('success', 'Đã lưu thông tin cá nhân.');
        redirect('dashboard/profile.php');
    }
}

$pageTitle = 'Thông tin cá nhân';
$activeNav = 'profile.php';
require __DIR__ . '/../includes/dash_header.php';
?>
<link rel="stylesheet" href="<?= e(asset('css/position_suggestions.css') . '?v=20261101-1') ?>">
                <form class="card" method="post" action="<?= e(url('dashboard/profile.php')) ?>" enctype="multipart/form-data" novalidate>
                    <?= csrf_field() ?>

                    <div class="form-group avatar-edit">
                        <?php if (!empty($profile['avatar_path'])): ?>
                            <img class="avatar-preview" src="<?= e(image_url($profile['avatar_path'])) ?>" alt="Ảnh đại diện hiện tại">
                        <?php else: ?>
                            <span class="avatar-preview avatar-fallback"><?= e(initials($input['full_name'])) ?></span>
                        <?php endif; ?>
                        <div>
                            <label for="avatar">Ảnh đại diện</label>
                            <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp">
                            <div class="form-hint">JPG, PNG hoặc WEBP, tối đa 3 MB.</div>
                            <?php if (!empty($profile['avatar_path'])): ?>
                                <div class="checkbox-row">
                                    <input type="checkbox" id="remove_avatar" name="remove_avatar" value="1">
                                    <label for="remove_avatar">Xóa ảnh đại diện hiện tại</label>
                                </div>
                            <?php endif; ?>
                            <?= field_error($errors, 'avatar') ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="full_name">Họ và tên</label>
                        <input class="form-control" type="text" id="full_name" name="full_name" maxlength="100"
                               required value="<?= e($input['full_name']) ?>">
                        <?= field_error($errors, 'full_name') ?>
                    </div>

                    <div class="form-group">
                        <label for="headline">Vị trí mong muốn</label>
                        <div class="position-combo" data-position-combo>
                            <input class="form-control" type="text" id="headline" name="headline" maxlength="150"
                                   autocomplete="off" role="combobox" aria-autocomplete="list" aria-haspopup="listbox"
                                   aria-expanded="false" aria-controls="position-options"
                                   placeholder="Nhập hoặc chọn vị trí mong muốn..."
                                   value="<?= e($input['headline']) ?>">
                            <div id="position-options" class="position-dropdown" role="listbox"
                                 aria-label="Các vị trí công việc gợi ý" hidden></div>
                        </div>
                        <div class="form-hint">Chọn trong danh sách theo nhóm nghề hoặc tự nhập vị trí khác.
                            Bạn có thể thêm cấp độ như Intern, Fresher, Junior hoặc Senior.</div>
                        <?= field_error($errors, 'headline') ?>
                    </div>

                    <div class="form-group">
                        <label for="bio">Giới thiệu bản thân</label>
                        <textarea class="form-control" id="bio" name="bio" maxlength="2000"
                                  placeholder="Vài dòng về bản thân, định hướng nghề nghiệp..."><?= e($input['bio']) ?></textarea>
                        <?= field_error($errors, 'bio') ?>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="contact_email">Email liên hệ</label>
                            <input class="form-control" type="email" id="contact_email" name="contact_email"
                                   maxlength="190" value="<?= e($input['contact_email']) ?>">
                            <?= field_error($errors, 'contact_email') ?>
                        </div>
                        <div class="form-group">
                            <label for="phone">Số điện thoại</label>
                            <input class="form-control" type="tel" id="phone" name="phone" maxlength="20"
                                   value="<?= e($input['phone']) ?>">
                            <?= field_error($errors, 'phone') ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="location">Địa chỉ / khu vực sinh sống</label>
                        <input class="form-control" type="text" id="location" name="location" maxlength="150"
                               placeholder="Ví dụ: Vĩnh Long" value="<?= e($input['location']) ?>">
                        <?= field_error($errors, 'location') ?>
                    </div>

                    <div class="form-group">
                        <label for="website_url">Website cá nhân</label>
                        <input class="form-control" type="text" id="website_url" name="website_url" maxlength="255"
                               placeholder="https://..." value="<?= e($input['website_url']) ?>">
                        <?= field_error($errors, 'website_url') ?>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="github_url">Liên kết GitHub</label>
                            <input class="form-control" type="text" id="github_url" name="github_url" maxlength="255"
                                   placeholder="https://github.com/ten-cua-ban" value="<?= e($input['github_url']) ?>">
                            <?= field_error($errors, 'github_url') ?>
                        </div>
                        <div class="form-group">
                            <label for="linkedin_url">Liên kết LinkedIn</label>
                            <input class="form-control" type="text" id="linkedin_url" name="linkedin_url" maxlength="255"
                                   placeholder="https://www.linkedin.com/in/ten-cua-ban" value="<?= e($input['linkedin_url']) ?>">
                            <?= field_error($errors, 'linkedin_url') ?>
                        </div>
                    </div>

                    <div class="form-group checkbox-row">
                        <input type="checkbox" id="is_public" name="is_public" value="1"<?= $input['is_public'] ? ' checked' : '' ?>>
                        <label for="is_public">Cho phép công khai hồ sơ của tôi</label>
                    </div>
                    <div class="form-hint">Tắt tùy chọn này nếu bạn chưa muốn người khác xem hồ sơ.</div>

                    <button type="submit" class="btn">Lưu thông tin</button>
                </form>
<?php
$extraScripts = ['js/position_suggestions.js?v=20261101-1'];
require __DIR__ . '/../includes/dash_footer.php';
?>
