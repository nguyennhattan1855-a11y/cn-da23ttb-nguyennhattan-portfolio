<?php
// dashboard/education.php - Quản lý học vấn
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login();
$uid = (int) $user['id'];

$errors = [];
$input = ['id' => 0, 'school' => '', 'degree' => '', 'qualification' => '', 'start_year' => '', 'end_year' => '', 'description' => ''];
$maxYear = (int) date('Y') + 10;

// ---- Xóa ----
if (is_post() && post_str('action') === 'delete') {
    csrf_require();
    if (delete_owned('educations', (int) post_str('id'))) {
        flash('success', 'Đã xóa mục học vấn.');
    } else {
        flash('error', 'Không tìm thấy mục học vấn cần xóa.');
    }
    redirect('dashboard/education.php');
}

// ---- Mở form sửa ----
if (!is_post() && isset($_GET['edit'])) {
    $row = find_owned('educations', (int) $_GET['edit']);
    if (!$row) {
        flash('error', 'Không tìm thấy mục học vấn cần sửa.');
        redirect('dashboard/education.php');
    }
    $input = [
        'id'          => (int) $row['id'],
        'school'      => $row['school'],
        'degree'      => $row['degree'],
        'qualification' => (string) ($row['qualification'] ?? ''),
        'start_year'  => (string) $row['start_year'],
        'end_year'    => (string) $row['end_year'],
        'description' => (string) $row['description'],
    ];
}

// ---- Lưu ----
if (is_post() && post_str('action') === 'save') {
    csrf_require();

    $input = [
        'id'          => (int) post_str('id'),
        'school'      => post_str('school'),
        'degree'      => post_str('degree'),
        'qualification' => post_str('qualification') === 'Khác' ? post_str('qualification_other') : post_str('qualification'),
        'start_year'  => post_str('start_year'),
        'end_year'    => post_str('end_year'),
        'description' => post_str('description'),
    ];

    if ($input['id'] > 0 && !find_owned('educations', $input['id'])) {
        flash('error', 'Không tìm thấy mục học vấn cần sửa.');
        redirect('dashboard/education.php');
    }

    $len = text_length($input['school']);
    if ($len < 2 || $len > 150) {
        $errors['school'] = 'Tên trường phải từ 2 đến 150 ký tự.';
    }
    $len = text_length($input['degree']);
    if ($len < 2 || $len > 150) {
        $errors['degree'] = 'Ngành học phải từ 2 đến 150 ký tự.';
    }

    // Bằng cấp là thông tin riêng, không bắt buộc khi người dùng đang học.
    $allowedQualifications = ['Chưa tốt nghiệp', 'Trung cấp', 'Cao đẳng', 'Cử nhân', 'Kỹ sư', 'Thạc sĩ', 'Tiến sĩ'];
    if ($input['qualification'] !== '' && text_length($input['qualification']) > 150) {
        $errors['qualification'] = 'Bằng cấp tối đa 150 ký tự.';
    }
    // Cho phép người dùng nhập bằng cấp khác ngoài các gợi ý có sẵn.
    if (post_str('qualification') === 'Khác' && trim($input['qualification']) === '') {
        $errors['qualification'] = 'Vui lòng nhập tên bằng cấp khác.';
    }

    $startYear = null;
    if (!ctype_digit($input['start_year']) || (int) $input['start_year'] < 1950 || (int) $input['start_year'] > $maxYear) {
        $errors['start_year'] = 'Năm bắt đầu không hợp lệ.';
    } else {
        $startYear = (int) $input['start_year'];
    }

    $endYear = null; // để trống = đang học
    if ($input['end_year'] !== '') {
        if (!ctype_digit($input['end_year']) || (int) $input['end_year'] < 1950 || (int) $input['end_year'] > $maxYear) {
            $errors['end_year'] = 'Năm kết thúc không hợp lệ.';
        } elseif ($startYear !== null && (int) $input['end_year'] < $startYear) {
            $errors['end_year'] = 'Năm kết thúc không được nhỏ hơn năm bắt đầu.';
        } else {
            $endYear = (int) $input['end_year'];
        }
    }

    if (text_length($input['description']) > 1000) {
        $errors['description'] = 'Mô tả tối đa 1000 ký tự.';
    }

    if (!$errors) {
        if ($input['id'] > 0) {
            db_query(
                'UPDATE educations SET school = ?, degree = ?, qualification = ?, start_year = ?, end_year = ?, description = ?
                 WHERE id = ? AND user_id = ?',
                [$input['school'], $input['degree'], null_if_empty($input['qualification']), $startYear, $endYear,
                 null_if_empty($input['description']), $input['id'], $uid]
            );
            flash('success', 'Đã cập nhật học vấn.');
        } else {
            db_query(
                'INSERT INTO educations (user_id, school, degree, qualification, start_year, end_year, description)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$uid, $input['school'], $input['degree'], null_if_empty($input['qualification']), $startYear, $endYear,
                 null_if_empty($input['description'])]
            );
            flash('success', 'Đã thêm học vấn.');
        }
        redirect('dashboard/education.php');
    }
}

$educations = db_all(
    'SELECT * FROM educations WHERE user_id = ? ORDER BY start_year DESC, id DESC',
    [$uid]
);
$isEditing = $input['id'] > 0;

$pageTitle = 'Học vấn';
$activeNav = 'education.php';
require __DIR__ . '/../includes/dash_header.php';
?>
                <form class="card" method="post" action="<?= e(url('dashboard/education.php')) ?>" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= (int) $input['id'] ?>">
                    <h2><?= $isEditing ? 'Sửa học vấn' : 'Thêm học vấn' ?></h2>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="school">Tên trường</label>
                            <input class="form-control" type="text" id="school" name="school" maxlength="150" required
                                   value="<?= e($input['school']) ?>">
                            <?= field_error($errors, 'school') ?>
                        </div>
                        <div class="form-group">
                            <label for="degree">Ngành học</label>
                            <input class="form-control" type="text" id="degree" name="degree" maxlength="150" required
                                   placeholder="Ví dụ: Công nghệ thông tin" value="<?= e($input['degree']) ?>">
                            <?= field_error($errors, 'degree') ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="qualification">Bằng cấp</label>
                        <?php
                        $qualifications = ['Chưa tốt nghiệp', 'Trung cấp', 'Cao đẳng', 'Cử nhân', 'Kỹ sư', 'Thạc sĩ', 'Tiến sĩ'];
                        $savedQualification = trim((string) $input['qualification']);
                        $isOtherQualification = $savedQualification !== '' && !in_array($savedQualification, $qualifications, true);
                        ?>
                        <select class="form-control" id="qualification" name="qualification">
                            <option value="" <?= $savedQualification === '' ? 'selected' : '' ?>>-- Chọn bằng cấp (không bắt buộc) --</option>
                            <?php foreach ($qualifications as $option): ?>
                                <option value="<?= e($option) ?>" <?= $savedQualification === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                            <?php endforeach; ?>
                            <option value="Khác" <?= $isOtherQualification ? 'selected' : '' ?>>Khác (tự nhập)</option>
                        </select>
                        <input class="form-control" type="text" id="qualification_other" name="qualification_other" maxlength="150"
                               placeholder="Nhập bằng cấp khác..." value="<?= e($isOtherQualification ? $savedQualification : '') ?>"
                               style="margin-top:10px;<?= $isOtherQualification ? '' : 'display:none;' ?>" <?= $isOtherQualification ? 'required' : 'disabled' ?>>
                        <div class="form-hint">Nếu đang học và chưa nhận bằng, chọn “Chưa tốt nghiệp”. Không ghi bằng cấp chưa được cấp như đã tốt nghiệp.</div>
                        <?= field_error($errors, 'qualification') ?>
                    </div>
                    <script>
                    (() => {
                        const select = document.getElementById('qualification');
                        const other = document.getElementById('qualification_other');
                        if (!select || !other) return;
                        const update = () => {
                            const custom = select.value === 'Khác';
                            other.style.display = custom ? 'block' : 'none';
                            other.disabled = !custom;
                            other.required = custom;
                        };
                        select.addEventListener('change', update);
                        update();
                    })();
                    </script>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_year">Năm bắt đầu</label>
                            <input class="form-control" type="number" id="start_year" name="start_year"
                                   min="1950" max="<?= $maxYear ?>" required value="<?= e($input['start_year']) ?>">
                            <?= field_error($errors, 'start_year') ?>
                        </div>
                        <div class="form-group">
                            <label for="end_year">Năm kết thúc</label>
                            <input class="form-control" type="number" id="end_year" name="end_year"
                                   min="1950" max="<?= $maxYear ?>" value="<?= e($input['end_year']) ?>">
                            <div class="form-hint">Để trống nếu bạn đang học.</div>
                            <?= field_error($errors, 'end_year') ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Mô tả (không bắt buộc)</label>
                        <textarea class="form-control" id="description" name="description" maxlength="1000"
                                  placeholder="Thành tích, môn học nổi bật..."><?= e($input['description']) ?></textarea>
                        <?= field_error($errors, 'description') ?>
                    </div>

                    <button type="submit" class="btn"><?= $isEditing ? 'Cập nhật' : 'Thêm học vấn' ?></button>
                    <?php if ($isEditing): ?>
                        <a class="btn btn-outline" href="<?= e(url('dashboard/education.php')) ?>">Hủy</a>
                    <?php endif; ?>
                </form>

                <div class="card">
                    <h2>Danh sách học vấn</h2>
                    <?php if (!$educations): ?>
                        <p class="muted">Bạn chưa có mục học vấn nào. Hãy thêm ở form phía trên.</p>
                    <?php endif; ?>
                    <div class="item-list">
                        <?php foreach ($educations as $edu): ?>
                            <div class="item">
                                <div class="item-body">
                                    <strong><?= e($edu['school']) ?></strong>
                                    <div><strong>Ngành học:</strong> <?= e($edu['degree']) ?></div>
                                    <?php if (!empty($edu['qualification'])): ?>
                                        <div><strong>Bằng cấp:</strong> <?= e($edu['qualification']) ?></div>
                                    <?php endif; ?>
                                    <div class="muted">
                                        <?= (int) $edu['start_year'] ?> &ndash;
                                        <?= $edu['end_year'] ? (int) $edu['end_year'] : 'Đang học' ?>
                                    </div>
                                    <?php if (!empty($edu['description'])): ?>
                                        <p class="item-text"><?= nl2br(e($edu['description'])) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="item-actions">
                                    <a class="btn btn-outline btn-sm" href="<?= e(url('dashboard/education.php?edit=' . (int) $edu['id'])) ?>">Sửa</a>
                                    <form method="post" action="<?= e(url('dashboard/education.php')) ?>"
                                          data-confirm="Bạn chắc chắn muốn xóa mục học vấn này?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $edu['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
<?php require __DIR__ . '/../includes/dash_footer.php'; ?>
