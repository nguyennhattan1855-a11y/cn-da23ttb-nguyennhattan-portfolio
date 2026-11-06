<?php
// dashboard/documents.php - CV, bằng cấp, chứng chỉ: tải lên, xem, tải xuống, xóa, công khai/ẩn
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login();
$uid = (int) $user['id'];

$types = document_types();
$errors = [];
$input = ['doc_type' => 'cv', 'title' => '', 'is_public' => 0];

if (is_post()) {
    csrf_require();
    $action = post_str('action');

    // ---- Xóa tài liệu ----
    if ($action === 'delete') {
        $doc = find_owned('documents', (int) post_str('id'));
        if ($doc) {
            delete_owned('documents', (int) $doc['id']);
            delete_stored_file('doc', $doc['file_path']);
            flash('success', 'Đã xóa tài liệu.');
        } else {
            flash('error', 'Không tìm thấy tài liệu cần xóa.');
        }
        redirect('dashboard/documents.php');
    }

    // ---- Bật/tắt công khai ----
    if ($action === 'toggle_public') {
        $doc = find_owned('documents', (int) post_str('id'));
        if ($doc) {
            $new = (int) $doc['is_public'] === 1 ? 0 : 1;
            db_query('UPDATE documents SET is_public = ? WHERE id = ? AND user_id = ?', [$new, $doc['id'], $uid]);
            flash('success', $new ? 'Tài liệu đã được công khai trên hồ sơ.' : 'Tài liệu đã được ẩn khỏi hồ sơ.');
        } else {
            flash('error', 'Không tìm thấy tài liệu.');
        }
        redirect('dashboard/documents.php');
    }

    // ---- Tải lên ----
    if ($action === 'upload') {
        $input = [
            'doc_type'  => post_str('doc_type'),
            'title'     => post_str('title'),
            'is_public' => post_bool('is_public'),
        ];
        if (!array_key_exists($input['doc_type'], $types)) {
            $errors['doc_type'] = 'Loại tài liệu không hợp lệ.';
        }
        $len = text_length($input['title']);
        if ($len < 2 || $len > 150) {
            $errors['title'] = 'Tiêu đề phải từ 2 đến 150 ký tự.';
        }
        if ((int) db_value('SELECT COUNT(*) FROM documents WHERE user_id = ?', [$uid]) >= 30) {
            $errors['file'] = 'Bạn đã đạt giới hạn 30 tài liệu. Hãy xóa bớt tài liệu cũ.';
        }

        $info = null;
        if (!$errors) {
            [$info, $fileError] = save_uploaded_document($_FILES['document'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
            if ($fileError !== null) {
                $errors['file'] = $fileError;
            }
        }

        if (!$errors && $info !== null) {
            try {
                db_query(
                    'INSERT INTO documents (user_id, doc_type, title, file_path, original_name, mime_type, file_size, is_public)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                    [$uid, $input['doc_type'], $input['title'], $info['file_path'], $info['original_name'],
                     $info['mime_type'], $info['file_size'], $input['is_public']]
                );
            } catch (Throwable $ex) {
                delete_stored_file('doc', $info['file_path']);
                throw $ex;
            }
            flash('success', 'Đã tải lên tài liệu.');
            redirect('dashboard/documents.php');
        }
    }
}

$documents = db_all('SELECT * FROM documents WHERE user_id = ? ORDER BY doc_type, id DESC', [$uid]);

$pageTitle = 'CV, bằng cấp & chứng chỉ';
$activeNav = 'documents.php';
require __DIR__ . '/../includes/dash_header.php';
?>
                <form class="card" method="post" action="<?= e(url('dashboard/documents.php')) ?>"
                      enctype="multipart/form-data" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="upload">
                    <h2>Tải lên tài liệu mới</h2>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="doc_type">Loại tài liệu</label>
                            <select class="form-control" id="doc_type" name="doc_type">
                                <?php foreach ($types as $key => $label): ?>
                                    <option value="<?= e($key) ?>"<?= $input['doc_type'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= field_error($errors, 'doc_type') ?>
                        </div>
                        <div class="form-group">
                            <label for="title">Tiêu đề</label>
                            <input class="form-control" type="text" id="title" name="title" maxlength="150" required
                                   placeholder="Ví dụ: CV tháng 10/2026" value="<?= e($input['title']) ?>">
                            <?= field_error($errors, 'title') ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="document">Chọn file</label>
                        <input type="file" id="document" name="document" accept="application/pdf,image/jpeg,image/png" required>
                        <div class="form-hint">PDF, JPG hoặc PNG, tối đa 5 MB.</div>
                        <?= field_error($errors, 'file') ?>
                    </div>

                    <div class="form-group checkbox-row">
                        <input type="checkbox" id="is_public" name="is_public" value="1"<?= $input['is_public'] ? ' checked' : '' ?>>
                        <label for="is_public">Công khai tài liệu này trên hồ sơ</label>
                    </div>

                    <button type="submit" class="btn">Tải lên</button>
                </form>

                <div class="card">
                    <h2>Tài liệu của bạn</h2>
                    <?php if (!$documents): ?>
                        <p class="muted">Bạn chưa tải lên tài liệu nào.</p>
                    <?php endif; ?>
                    <div class="item-list">
                        <?php foreach ($documents as $doc): ?>
                            <div class="item">
                                <div class="item-body">
                                    <strong><?= e($doc['title']) ?></strong>
                                    <span class="badge"><?= e($types[$doc['doc_type']] ?? '') ?></span>
                                    <span class="badge <?= (int) $doc['is_public'] === 1 ? 'badge-ok' : 'badge-off' ?>">
                                        <?= (int) $doc['is_public'] === 1 ? 'Công khai' : 'Riêng tư' ?>
                                    </span>
                                    <div class="muted"><?= e($doc['original_name']) ?> · <?= e(format_bytes((int) $doc['file_size'])) ?> · <?= e(format_date_vn(substr((string) $doc['created_at'], 0, 10))) ?></div>
                                    <div class="item-links">
                                        <a href="<?= e(url('file.php?id=' . (int) $doc['id'])) ?>" target="_blank" rel="noopener">Xem</a>
                                        <a href="<?= e(url('file.php?id=' . (int) $doc['id'] . '&download=1')) ?>">Tải xuống</a>
                                    </div>
                                </div>
                                <div class="item-actions">
                                    <form method="post" action="<?= e(url('dashboard/documents.php')) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_public">
                                        <input type="hidden" name="id" value="<?= (int) $doc['id'] ?>">
                                        <button type="submit" class="btn btn-outline btn-sm"><?= (int) $doc['is_public'] === 1 ? 'Ẩn' : 'Công khai' ?></button>
                                    </form>
                                    <form method="post" action="<?= e(url('dashboard/documents.php')) ?>"
                                          data-confirm="Bạn chắc chắn muốn xóa tài liệu này? File sẽ bị xóa vĩnh viễn.">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $doc['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
<?php require __DIR__ . '/../includes/dash_footer.php'; ?>
