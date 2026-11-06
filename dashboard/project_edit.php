<?php
// dashboard/project_edit.php - Thêm / sửa một dự án, kèm ảnh đại diện và ảnh minh họa
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login();
$uid = (int) $user['id'];

$projectId = (int) ($_GET['id'] ?? post_str('id'));
$project = null;
if ($projectId > 0) {
    $project = find_owned('projects', $projectId);
    if (!$project) {
        flash('error', 'Không tìm thấy dự án.');
        redirect('dashboard/projects.php');
    }
}
$isEditing = $project !== null;

$errors = [];
$input = [
    'title' => $project['title'] ?? '',
    'short_description' => $project['short_description'] ?? '',
    'description' => $project['description'] ?? '',
    'technologies' => $project['technologies'] ?? '',
    'role' => $project['role'] ?? '',
    'start_date' => $project['start_date'] ?? $project['project_date'] ?? '',
    'end_date' => $project['end_date'] ?? '',
    'is_ongoing' => (int) ($project['is_ongoing'] ?? 0),
    'github_url' => $project['github_url'] ?? '',
    'demo_url' => $project['demo_url'] ?? '',
    'is_featured' => (int) ($project['is_featured'] ?? 0),
];

// Số ảnh minh họa hiện có
$existingImages = [];
if ($isEditing) {
    $existingImages = db_all('SELECT * FROM project_images WHERE project_id = ? ORDER BY sort_order, id', [$projectId]);
}

if (is_post()) {
    csrf_require();
    $action = post_str('action');

    // ---- Xóa một ảnh minh họa ----
    if ($action === 'delete_image' && $isEditing) {
        $imageId = (int) post_str('image_id');
        $img = db_one(
            'SELECT pi.* FROM project_images pi JOIN projects p ON p.id = pi.project_id
             WHERE pi.id = ? AND p.id = ? AND p.user_id = ?',
            [$imageId, $projectId, $uid]
        );
        if ($img) {
            db_query('DELETE FROM project_images WHERE id = ?', [$imageId]);
            delete_stored_file('image', $img['image_path']);
            flash('success', 'Đã xóa ảnh.');
        } else {
            flash('error', 'Không tìm thấy ảnh cần xóa.');
        }
        redirect('dashboard/project_edit.php?id=' . $projectId);
    }

    // ---- Lưu dự án ----
    if ($action === 'save') {
        $input = [
            'title' => post_str('title'),
            'short_description' => post_str('short_description'),
            'description' => post_str('description'),
            'technologies' => post_str('technologies'),
            'role' => post_str('role'),
            'start_date' => post_str('start_date'),
            'end_date' => post_str('end_date'),
            'is_ongoing' => post_bool('is_ongoing'),
            'github_url' => post_str('github_url'),
            'demo_url' => post_str('demo_url'),
            'is_featured' => post_bool('is_featured'),
        ];

        $len = text_length($input['title']);
        if ($len < 2 || $len > 150) {
            $errors['title'] = 'Tên dự án phải từ 2 đến 150 ký tự.';
        }
        if (text_length($input['short_description']) > 300) {
            $errors['short_description'] = 'Mô tả ngắn tối đa 300 ký tự.';
        }
        if (text_length($input['description']) > 5000) {
            $errors['description'] = 'Mô tả chi tiết tối đa 5000 ký tự.';
        }
        if (text_length($input['technologies']) > 255) {
            $errors['technologies'] = 'Công nghệ sử dụng tối đa 255 ký tự.';
        }
        if (text_length($input['role']) > 150) {
            $errors['role'] = 'Vai trò tối đa 150 ký tự.';
        }

        $startDate = null;
        $endDate = null;
        if ($input['start_date'] !== '') {
            $startDate = parse_date($input['start_date']);
            if ($startDate === null) {
                $errors['start_date'] = 'Ngày bắt đầu không hợp lệ.';
            }
        } else {
            $errors['start_date'] = 'Vui lòng chọn ngày bắt đầu.';
        }
        if (!$input['is_ongoing']) {
            if ($input['end_date'] === '') {
                $errors['end_date'] = 'Chọn ngày kết thúc hoặc đánh dấu Đang thực hiện.';
            } else {
                $endDate = parse_date($input['end_date']);
                if ($endDate === null) {
                    $errors['end_date'] = 'Ngày kết thúc không hợp lệ.';
                } elseif ($startDate !== null && $endDate < $startDate) {
                    $errors['end_date'] = 'Ngày kết thúc phải bằng hoặc sau ngày bắt đầu.';
                }
            }
        }

        [$githubUrl, $githubError] = normalize_url($input['github_url'], 'github.com');
        if ($githubError !== null) {
            $errors['github_url'] = $githubError;
        } else {
            $input['github_url'] = $githubUrl ?? '';
        }
        [$demoUrl, $demoError] = normalize_url($input['demo_url']);
        if ($demoError !== null) {
            $errors['demo_url'] = $demoError;
        } else {
            $input['demo_url'] = $demoUrl ?? '';
        }

        // Ảnh minh họa mới
        $newImageFiles = [];
        foreach (normalize_files('images') as $f) {
            if (!no_file_chosen($f)) {
                $newImageFiles[] = $f;
            }
        }
        if (count($existingImages) + count($newImageFiles) > MAX_PROJECT_IMAGES) {
            $errors['images'] = 'Mỗi dự án tối đa ' . MAX_PROJECT_IMAGES . ' ảnh minh họa.';
        }

        // ---- Lưu file (chỉ khi dữ liệu văn bản đã hợp lệ) ----
        $savedFiles = [];      // để dọn dẹp nếu có lỗi
        $newThumb = null;
        $newImageNames = [];
        if (!$errors) {
            if (isset($_FILES['thumbnail'])) {
                [$newThumb, $thumbError] = save_uploaded_image($_FILES['thumbnail']);
                if ($thumbError !== null) {
                    $errors['thumbnail'] = $thumbError;
                } elseif ($newThumb !== null) {
                    $savedFiles[] = $newThumb;
                }
            }
            foreach ($newImageFiles as $f) {
                [$name, $imgError] = save_uploaded_image($f);
                if ($imgError !== null) {
                    $errors['images'] = $imgError;
                    break;
                }
                if ($name !== null) {
                    $newImageNames[] = $name;
                    $savedFiles[] = $name;
                }
            }
        }

        if (!$errors) {
            $removeThumb = post_bool('remove_thumbnail') === 1;
            $oldThumb = $project['thumbnail_path'] ?? null;
            $thumbValue = $newThumb ?? ($removeThumb ? null : $oldThumb);

            $pdo = db();
            try {
                $pdo->beginTransaction();
                $values = [
                    $input['title'],
                    null_if_empty($input['short_description']),
                    null_if_empty($input['description']),
                    null_if_empty($input['technologies']),
                    null_if_empty($input['role']),
                    $startDate, // project_date cũ: giữ tương thích với danh sách cũ
                    $startDate,
                    $endDate,
                    $input['is_ongoing'],
                    $thumbValue,
                    $input['github_url'] === '' ? null : $input['github_url'],
                    $input['demo_url'] === '' ? null : $input['demo_url'],
                    $input['is_featured'],
                ];
                if ($isEditing) {
                    $values[] = $projectId;
                    $values[] = $uid;
                    db_query(
                        'UPDATE projects SET title = ?, short_description = ?, description = ?, technologies = ?,
                                role = ?, project_date = ?, start_date = ?, end_date = ?, is_ongoing = ?, thumbnail_path = ?, github_url = ?, demo_url = ?,
                                is_featured = ?
                         WHERE id = ? AND user_id = ?',
                        $values
                    );
                    $savedId = $projectId;
                } else {
                    array_unshift($values, $uid);
                    db_query(
                        'INSERT INTO projects (user_id, title, short_description, description, technologies, role,
                                project_date, start_date, end_date, is_ongoing, thumbnail_path, github_url, demo_url, is_featured)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        $values
                    );
                    $savedId = (int) $pdo->lastInsertId();
                }

                $order = (int) db_value('SELECT COALESCE(MAX(sort_order), 0) FROM project_images WHERE project_id = ?', [$savedId]);
                foreach ($newImageNames as $name) {
                    $order++;
                    db_query('INSERT INTO project_images (project_id, image_path, sort_order) VALUES (?, ?, ?)',
                        [$savedId, $name, $order]);
                }
                $pdo->commit();
            } catch (Throwable $ex) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                foreach ($savedFiles as $f) {
                    delete_stored_file('image', $f);
                }
                throw $ex;
            }

            if ($thumbValue !== $oldThumb) {
                delete_stored_file('image', $oldThumb);
            }
            flash('success', $isEditing ? 'Đã cập nhật dự án.' : 'Đã thêm dự án.');
            redirect('dashboard/projects.php');
        }

        // Có lỗi: xóa các file vừa lưu để không để lại file mồ côi
        foreach ($savedFiles as $f) {
            delete_stored_file('image', $f);
        }
    }
}

$pageTitle = $isEditing ? 'Sửa dự án' : 'Thêm dự án';
$activeNav = 'projects.php';
require __DIR__ . '/../includes/dash_header.php';
?>
                <form class="card" method="post" action="<?= e(url('dashboard/project_edit.php' . ($isEditing ? '?id=' . $projectId : ''))) ?>"
                      enctype="multipart/form-data" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= (int) $projectId ?>">

                    <div class="form-group">
                        <label for="title">Tên dự án</label>
                        <input class="form-control" type="text" id="title" name="title" maxlength="150" required
                               value="<?= e($input['title']) ?>">
                        <?= field_error($errors, 'title') ?>
                    </div>

                    <div class="form-group">
                        <label for="short_description">Mô tả ngắn</label>
                        <input class="form-control" type="text" id="short_description" name="short_description" maxlength="300"
                               value="<?= e($input['short_description']) ?>">
                        <?= field_error($errors, 'short_description') ?>
                    </div>

                    <div class="form-group">
                        <label for="description">Mô tả chi tiết</label>
                        <textarea class="form-control" id="description" name="description" maxlength="5000"
                                  placeholder="Chức năng chính, khó khăn đã giải quyết, kết quả..."><?= e($input['description']) ?></textarea>
                        <?= field_error($errors, 'description') ?>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="technologies">Công nghệ sử dụng</label>
                            <input class="form-control" type="text" id="technologies" name="technologies" maxlength="255"
                                   placeholder="PHP, MySQL, Bootstrap" value="<?= e($input['technologies']) ?>">
                            <div class="form-hint">Ngăn cách bằng dấu phẩy.</div>
                            <?= field_error($errors, 'technologies') ?>
                        </div>
                        <div class="form-group">
                            <label for="role">Vai trò trong dự án</label>
                            <input class="form-control" type="text" id="role" name="role" maxlength="150"
                                   value="<?= e($input['role']) ?>">
                            <?= field_error($errors, 'role') ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_date">Ngày bắt đầu</label>
                            <input class="form-control" type="date" id="start_date" name="start_date" required
                                   value="<?= e($input['start_date']) ?>">
                            <?= field_error($errors, 'start_date') ?>
                        </div>
                        <div class="form-group">
                            <label for="end_date">Ngày kết thúc</label>
                            <input class="form-control" type="date" id="end_date" name="end_date"
                                   value="<?= e($input['end_date']) ?>"<?= $input['is_ongoing'] ? ' disabled' : '' ?>>
                            <?= field_error($errors, 'end_date') ?>
                        </div>
                    </div>
                    <div class="form-group checkbox-row">
                        <input type="checkbox" id="is_ongoing" name="is_ongoing" value="1"
                               data-disables="end_date"<?= $input['is_ongoing'] ? ' checked' : '' ?>>
                        <label for="is_ongoing">Đang thực hiện (chưa có ngày kết thúc)</label>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="github_url">Liên kết GitHub của dự án</label>
                            <input class="form-control" type="text" id="github_url" name="github_url" maxlength="255"
                                   placeholder="https://github.com/ten/du-an" value="<?= e($input['github_url']) ?>">
                            <?= field_error($errors, 'github_url') ?>
                        </div>
                        <div class="form-group">
                            <label for="demo_url">Liên kết bản demo</label>
                            <input class="form-control" type="text" id="demo_url" name="demo_url" maxlength="255"
                                   placeholder="https://..." value="<?= e($input['demo_url']) ?>">
                            <?= field_error($errors, 'demo_url') ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="thumbnail">Ảnh đại diện dự án</label>
                        <?php if ($isEditing && !empty($project['thumbnail_path'])): ?>
                            <img class="thumb-current" src="<?= e(image_url($project['thumbnail_path'])) ?>" alt="Ảnh đại diện hiện tại">
                            <div class="checkbox-row">
                                <input type="checkbox" id="remove_thumbnail" name="remove_thumbnail" value="1">
                                <label for="remove_thumbnail">Xóa ảnh đại diện hiện tại</label>
                            </div>
                        <?php endif; ?>
                        <input type="file" id="thumbnail" name="thumbnail" accept="image/jpeg,image/png,image/webp">
                        <div class="form-hint">JPG, PNG hoặc WEBP, tối đa 3 MB. Chọn ảnh mới sẽ thay ảnh cũ.</div>
                        <?= field_error($errors, 'thumbnail') ?>
                    </div>

                    <div class="form-group project-image-picker">
                        <label for="images">Thêm ảnh minh họa</label>
                        <input class="project-multi-input" type="file" id="images" name="images[]" multiple accept="image/jpeg,image/png,image/webp"
                               data-max-images="<?= (int) MAX_PROJECT_IMAGES ?>" data-existing-count="<?= count($existingImages) ?>">
                        <div class="form-hint">Giữ Ctrl để chọn nhiều ảnh trong một lần. Ảnh đã chọn sẽ hiện bên dưới, có thể bỏ riêng từng ảnh trước khi lưu.</div>
                        <div class="form-hint">Mỗi ảnh tối đa 3 MB; đã lưu <?= count($existingImages) ?>/<?= (int) MAX_PROJECT_IMAGES ?> ảnh minh họa.</div>
                        <p id="project-images-selection-status" class="form-hint" role="status" aria-live="polite">Chưa chọn ảnh mới.</p>
                        <div id="project-images-preview" class="project-images-preview" aria-label="Ảnh mới chờ lưu"></div>
                        <p id="project-images-client-error" class="project-images-client-error" role="alert" hidden></p>
                        <?= field_error($errors, 'images') ?>
                    </div>

                    <div class="form-group checkbox-row">
                        <input type="checkbox" id="is_featured" name="is_featured" value="1"<?= $input['is_featured'] ? ' checked' : '' ?>>
                        <label for="is_featured">Đánh dấu là dự án nổi bật</label>
                    </div>

                    <button type="submit" class="btn"><?= $isEditing ? 'Cập nhật dự án' : 'Thêm dự án' ?></button>
                    <a class="btn btn-outline" href="<?= e(url('dashboard/projects.php')) ?>">Quay lại danh sách</a>
                </form>

                <?php if ($isEditing && $existingImages): ?>
                    <div class="card">
                        <h2>Ảnh minh họa hiện có</h2>
                        <p class="muted">Chỉ gồm các ảnh minh họa. Ảnh đại diện dự án được quản lý riêng phía trên.</p>
                        <div class="gallery-edit">
                            <?php foreach ($existingImages as $img): ?>
                                <div class="gallery-item">
                                    <img src="<?= e(image_url($img['image_path'])) ?>" alt="Ảnh minh họa">
                                    <form method="post" action="<?= e(url('dashboard/project_edit.php?id=' . $projectId)) ?>"
                                          data-confirm="Xóa ảnh này?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_image">
                                        <input type="hidden" name="image_id" value="<?= (int) $img['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Xóa ảnh</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
<link rel="stylesheet" href="<?= e(asset('css/project_images_picker.css') . '?v=20261101') ?>">
<?php
$extraScripts = array_merge($extraScripts ?? [], ['js/project_images_picker.js']);
require __DIR__ . '/../includes/dash_footer.php';
?>
