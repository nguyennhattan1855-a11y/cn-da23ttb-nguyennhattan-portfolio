<?php
// dashboard/projects.php - Danh sách dự án (xem, đánh dấu nổi bật, xóa)
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login();
$uid = (int) $user['id'];

if (is_post()) {
    csrf_require();
    $action = post_str('action');
    $id = (int) post_str('id');

    // ---- Xóa dự án (kèm toàn bộ ảnh của dự án) ----
    if ($action === 'delete') {
        $project = find_owned('projects', $id);
        if (!$project) {
            flash('error', 'Không tìm thấy dự án cần xóa.');
        } else {
            $files = [$project['thumbnail_path']];
            foreach (db_all('SELECT image_path FROM project_images WHERE project_id = ?', [$id]) as $img) {
                $files[] = $img['image_path'];
            }
            delete_owned('projects', $id); // bảng project_images tự xóa theo (ON DELETE CASCADE)
            foreach ($files as $f) {
                delete_stored_file('image', $f);
            }
            flash('success', 'Đã xóa dự án.');
        }
        redirect('dashboard/projects.php');
    }

    // ---- Bật/tắt dự án nổi bật ----
    if ($action === 'toggle_featured') {
        $project = find_owned('projects', $id);
        if (!$project) {
            flash('error', 'Không tìm thấy dự án.');
        } else {
            $new = (int) $project['is_featured'] === 1 ? 0 : 1;
            db_query('UPDATE projects SET is_featured = ? WHERE id = ? AND user_id = ?', [$new, $id, $uid]);
            flash('success', $new ? 'Đã đánh dấu dự án nổi bật.' : 'Đã bỏ đánh dấu nổi bật.');
        }
        redirect('dashboard/projects.php');
    }
}

$projects = db_all(
    'SELECT * FROM projects WHERE user_id = ? ORDER BY is_featured DESC, project_date DESC, id DESC',
    [$uid]
);

$pageTitle = 'Dự án';
$activeNav = 'projects.php';
require __DIR__ . '/../includes/dash_header.php';
?>
                <div class="card">
                    <div class="card-head">
                        <h2>Danh sách dự án</h2>
                        <a class="btn btn-sm" href="<?= e(url('dashboard/project_edit.php')) ?>">+ Thêm dự án</a>
                    </div>

                    <?php if (!$projects): ?>
                        <p class="muted">Bạn chưa có dự án nào. Hãy thêm dự án đầu tiên.</p>
                    <?php endif; ?>

                    <div class="item-list">
                        <?php foreach ($projects as $p): ?>
                            <div class="item">
                                <div class="item-thumb">
                                    <?php if (!empty($p['thumbnail_path'])): ?>
                                        <img src="<?= e(image_url($p['thumbnail_path'])) ?>" alt="">
                                    <?php else: ?>
                                        <span class="thumb-fallback"><?= e(initials($p['title'])) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="item-body">
                                    <strong><?= e($p['title']) ?></strong>
                                    <?php if ((int) $p['is_featured'] === 1): ?><span class="badge">Nổi bật</span><?php endif; ?>
                                    <?php if (!empty($p['short_description'])): ?>
                                        <div><?= e($p['short_description']) ?></div>
                                    <?php endif; ?>
                                    <div class="muted">
                                        <?= !empty($p['technologies']) ? e($p['technologies']) : '' ?>
                                        <?= !empty($p['start_date']) ? ' · ' . e(format_date_vn($p['start_date'])) : (!empty($p['project_date']) ? ' · ' . e(format_date_vn($p['project_date'])) : '') ?><?= (int) ($p['is_ongoing'] ?? 0) ? ' – Hiện tại' : (!empty($p['end_date']) ? ' – ' . e(format_date_vn($p['end_date'])) : '') ?>
                                    </div>
                                    <div class="item-links">
                                        <a href="<?= e(url('project.php?slug=' . rawurlencode($user['slug']) . '&id=' . (int) $p['id'])) ?>" target="_blank" rel="noopener">Xem trang chi tiết</a>
                                    </div>
                                </div>
                                <div class="item-actions">
                                    <a class="btn btn-outline btn-sm" href="<?= e(url('dashboard/project_edit.php?id=' . (int) $p['id'])) ?>">Sửa</a>
                                    <form method="post" action="<?= e(url('dashboard/projects.php')) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_featured">
                                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                        <button type="submit" class="btn btn-outline btn-sm"><?= (int) $p['is_featured'] === 1 ? 'Bỏ nổi bật' : 'Nổi bật' ?></button>
                                    </form>
                                    <form method="post" action="<?= e(url('dashboard/projects.php')) ?>"
                                          data-confirm="Xóa dự án này cùng toàn bộ hình ảnh của nó?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
<?php require __DIR__ . '/../includes/dash_footer.php'; ?>
