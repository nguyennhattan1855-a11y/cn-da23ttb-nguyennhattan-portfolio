<?php
// dashboard/experiences.php - Quản lý kinh nghiệm làm việc / thực tập
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login();
$uid = (int) $user['id'];

$errors = [];
$input = [
    'id' => 0, 'position' => '', 'company' => '',
    'start_date' => '', 'end_date' => '', 'is_current' => 0, 'description' => '',
];

// ---- Xóa ----
if (is_post() && post_str('action') === 'delete') {
    csrf_require();
    if (delete_owned('experiences', (int) post_str('id'))) {
        flash('success', 'Đã xóa kinh nghiệm.');
    } else {
        flash('error', 'Không tìm thấy kinh nghiệm cần xóa.');
    }
    redirect('dashboard/experiences.php');
}

// ---- Mở form sửa ----
if (!is_post() && isset($_GET['edit'])) {
    $row = find_owned('experiences', (int) $_GET['edit']);
    if (!$row) {
        flash('error', 'Không tìm thấy kinh nghiệm cần sửa.');
        redirect('dashboard/experiences.php');
    }
    $input = [
        'id'          => (int) $row['id'],
        'position'    => $row['position'],
        'company'     => $row['company'],
        'start_date'  => (string) $row['start_date'],
        'end_date'    => (string) $row['end_date'],
        'is_current'  => (int) $row['is_current'],
        'description' => (string) $row['description'],
    ];
}

// ---- Lưu ----
if (is_post() && post_str('action') === 'save') {
    csrf_require();

    $input = [
        'id'          => (int) post_str('id'),
        'position'    => post_str('position'),
        'company'     => post_str('company'),
        'start_date'  => post_str('start_date'),
        'end_date'    => post_str('end_date'),
        'is_current'  => post_bool('is_current'),
        'description' => post_str('description'),
    ];

    if ($input['id'] > 0 && !find_owned('experiences', $input['id'])) {
        flash('error', 'Không tìm thấy kinh nghiệm cần sửa.');
        redirect('dashboard/experiences.php');
    }

    $len = text_length($input['position']);
    if ($len < 2 || $len > 150) {
        $errors['position'] = 'Vị trí phải từ 2 đến 150 ký tự.';
    }
    $len = text_length($input['company']);
    if ($len < 2 || $len > 150) {
        $errors['company'] = 'Tên đơn vị phải từ 2 đến 150 ký tự.';
    }

    $start = parse_date($input['start_date']);
    if ($start === null) {
        $errors['start_date'] = 'Vui lòng chọn ngày bắt đầu hợp lệ.';
    }

    $end = null;
    if (!$input['is_current']) {
        $end = parse_date($input['end_date']);
        if ($end === null) {
            $errors['end_date'] = 'Vui lòng chọn ngày kết thúc, hoặc tích "Đang làm việc".';
        } elseif ($start !== null && $end < $start) {
            $errors['end_date'] = 'Ngày kết thúc không được trước ngày bắt đầu.';
        }
    }

    if (text_length($input['description']) > 3000) {
        $errors['description'] = 'Mô tả tối đa 3000 ký tự.';
    }

    if (!$errors) {
        if ($input['id'] > 0) {
            db_query(
                'UPDATE experiences SET position = ?, company = ?, start_date = ?, end_date = ?,
                        is_current = ?, description = ? WHERE id = ? AND user_id = ?',
                [$input['position'], $input['company'], $start, $end, $input['is_current'],
                 null_if_empty($input['description']), $input['id'], $uid]
            );
            flash('success', 'Đã cập nhật kinh nghiệm.');
        } else {
            db_query(
                'INSERT INTO experiences (user_id, position, company, start_date, end_date, is_current, description)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$uid, $input['position'], $input['company'], $start, $end, $input['is_current'],
                 null_if_empty($input['description'])]
            );
            flash('success', 'Đã thêm kinh nghiệm.');
        }
        redirect('dashboard/experiences.php');
    }
}

// Mới nhất lên trước (theo trình tự thời gian)
$experiences = db_all(
    'SELECT * FROM experiences WHERE user_id = ? ORDER BY start_date DESC, id DESC',
    [$uid]
);
$isEditing = $input['id'] > 0;

$pageTitle = 'Kinh nghiệm';
$activeNav = 'experiences.php';
require __DIR__ . '/../includes/dash_header.php';
?>
                <form class="card" method="post" action="<?= e(url('dashboard/experiences.php')) ?>" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= (int) $input['id'] ?>">
                    <h2><?= $isEditing ? 'Sửa kinh nghiệm' : 'Thêm kinh nghiệm làm việc / thực tập' ?></h2>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="position">Vị trí</label>
                            <input class="form-control" type="text" id="position" name="position" maxlength="150"
                                   required placeholder="Ví dụ: Thực tập sinh lập trình web" value="<?= e($input['position']) ?>">
                            <?= field_error($errors, 'position') ?>
                        </div>
                        <div class="form-group">
                            <label for="company">Tên đơn vị</label>
                            <input class="form-control" type="text" id="company" name="company" maxlength="150"
                                   required value="<?= e($input['company']) ?>">
                            <?= field_error($errors, 'company') ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_date">Thời gian bắt đầu</label>
                            <input class="form-control" type="date" id="start_date" name="start_date" required
                                   value="<?= e($input['start_date']) ?>">
                            <?= field_error($errors, 'start_date') ?>
                        </div>
                        <div class="form-group">
                            <label for="end_date">Thời gian kết thúc</label>
                            <input class="form-control" type="date" id="end_date" name="end_date"
                                   value="<?= e($input['end_date']) ?>"<?= $input['is_current'] ? ' disabled' : '' ?>>
                            <?= field_error($errors, 'end_date') ?>
                        </div>
                    </div>

                    <div class="form-group checkbox-row">
                        <input type="checkbox" id="is_current" name="is_current" value="1" data-disables="end_date"<?= $input['is_current'] ? ' checked' : '' ?>>
                        <label for="is_current">Tôi đang làm việc tại đây</label>
                    </div>

                    <div class="form-group">
                        <label for="description">Mô tả công việc</label>
                        <textarea class="form-control" id="description" name="description" maxlength="3000"
                                  placeholder="Nhiệm vụ, công nghệ sử dụng, kết quả đạt được..."><?= e($input['description']) ?></textarea>
                        <?= field_error($errors, 'description') ?>
                    </div>

                    <button type="submit" class="btn"><?= $isEditing ? 'Cập nhật' : 'Thêm kinh nghiệm' ?></button>
                    <?php if ($isEditing): ?>
                        <a class="btn btn-outline" href="<?= e(url('dashboard/experiences.php')) ?>">Hủy</a>
                    <?php endif; ?>
                </form>

                <div class="card">
                    <h2>Danh sách kinh nghiệm</h2>
                    <?php if (!$experiences): ?>
                        <p class="muted">Bạn chưa có kinh nghiệm nào. Hãy thêm ở form phía trên.</p>
                    <?php endif; ?>
                    <div class="item-list">
                        <?php foreach ($experiences as $exp): ?>
                            <div class="item">
                                <div class="item-body">
                                    <strong><?= e($exp['position']) ?></strong>
                                    <div><?= e($exp['company']) ?></div>
                                    <div class="muted">
                                        <?= e(format_date_vn($exp['start_date'])) ?> &ndash;
                                        <?= (int) $exp['is_current'] ? 'Hiện tại' : e(format_date_vn($exp['end_date'])) ?>
                                    </div>
                                    <?php if (!empty($exp['description'])): ?>
                                        <p class="item-text"><?= nl2br(e($exp['description'])) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="item-actions">
                                    <a class="btn btn-outline btn-sm" href="<?= e(url('dashboard/experiences.php?edit=' . (int) $exp['id'])) ?>">Sửa</a>
                                    <form method="post" action="<?= e(url('dashboard/experiences.php')) ?>"
                                          data-confirm="Bạn chắc chắn muốn xóa kinh nghiệm này?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $exp['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
<?php require __DIR__ . '/../includes/dash_footer.php'; ?>
