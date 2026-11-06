<?php
// project.php?slug=...&id=N - Trang chi tiết một dự án (công khai nếu hồ sơ công khai)
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/profile_layout.php';

$slug = isset($_GET['slug']) && is_string($_GET['slug']) ? strtolower(trim($_GET['slug'])) : '';
$projectId = (int) ($_GET['id'] ?? 0);
$owner = $slug !== '' ? find_user_by_slug($slug) : null;
if ($owner === null || $projectId <= 0) {
    render_profile_not_found();
}

$isOwner = viewer_is_owner($owner);
$settings = db_one('SELECT template, primary_color FROM profile_settings WHERE user_id = ?', [(int) $owner['id']]);
if (!$settings || !isset(profile_templates()[$settings['template']]) || !valid_hex_color($settings['primary_color'])) {
    $settings = ['template' => 'classic', 'primary_color' => '#2563eb'];
}
$isPublic = (int) db_value('SELECT is_public FROM profiles WHERE user_id = ?', [(int) $owner['id']]) === 1;
if (!$isPublic && !$isOwner) {
    render_profile_not_found();
}

$project = db_one('SELECT * FROM projects WHERE id = ? AND user_id = ?', [$projectId, (int) $owner['id']]);
if (!$project) {
    render_profile_not_found();
}
$images = db_all('SELECT * FROM project_images WHERE project_id = ? ORDER BY sort_order, id', [$projectId]);

$github = safe_url($project['github_url']);
$demo = safe_url($project['demo_url']);
$profileLink = profile_url($owner['slug'], false);
$title = $project['title'] . ' – ' . $owner['full_name'];
$description = !empty($project['short_description']) ? $project['short_description'] : 'Dự án của ' . $owner['full_name'];

profile_page_open($title, $description, $settings['template'], $settings['primary_color'], !$isPublic);
?>
<link rel="stylesheet" href="<?= e(asset('css/project_cover.css') . '?v=20261101') ?>">
<div class="pf-toolbar">
    <a class="pf-toolbar-brand" href="<?= e(url('index.php')) ?>"><?= e(config('app_name')) ?></a>
    <span class="pf-toolbar-actions">
        <a class="pf-btn pf-btn-ghost" href="<?= e($profileLink) ?>">&larr; Hồ sơ của <?= e($owner['full_name']) ?></a>
    </span>
</div>

<div class="pf-page tpl-<?= e($settings['template']) ?>" style="<?= e(profile_root_style($settings['primary_color'])) ?>">
    <header class="pf-hero pf-hero-project">
        <div class="pf-hero-text">
            <h1><?= e($project['title']) ?></h1>
            <?php if (!empty($project['short_description'])): ?>
                <p class="pf-headline"><?= e($project['short_description']) ?></p>
            <?php endif; ?>
        </div>
    </header>

    <div class="pf-layout pf-layout-single">
        <div class="pf-main">
            <section class="pf-card">
                <h2>Thông tin dự án</h2>
                <ul class="pf-list">
                    <?php if (!empty($project['role'])): ?>
                        <li><span class="pf-label">Vai trò</span> <?= e($project['role']) ?></li>
                    <?php endif; ?>
                    <?php if (!empty($project['start_date']) || !empty($project['project_date'])): ?>
                        <li><span class="pf-label">Thời gian</span>
                            <?= e(format_date_vn($project['start_date'] ?: $project['project_date'])) ?>
                            <?= (int) ($project['is_ongoing'] ?? 0) ? ' – Hiện tại' : (!empty($project['end_date']) ? ' – ' . e(format_date_vn($project['end_date'])) : '') ?>
                        </li>
                    <?php endif; ?>
                    <?php if ($github !== ''): ?>
                        <li><span class="pf-label">Mã nguồn</span> <a href="<?= e($github) ?>" target="_blank" rel="noopener noreferrer">GitHub</a></li>
                    <?php endif; ?>
                    <?php if ($demo !== ''): ?>
                        <li><span class="pf-label">Bản demo</span> <a href="<?= e($demo) ?>" target="_blank" rel="noopener noreferrer">Mở bản demo</a></li>
                    <?php endif; ?>
                </ul>
                <?php if (split_tags($project['technologies'])): ?>
                    <h3 class="pf-sub">Công nghệ sử dụng</h3>
                    <ul class="pf-chips">
                        <?php foreach (split_tags($project['technologies']) as $tag): ?><li><?= e($tag) ?></li><?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <?php if (!empty($project['description'])): ?>
                <section class="pf-card">
                    <h2>Mô tả</h2>
                    <p class="pf-text"><?= nl2br(e($project['description'])) ?></p>
                </section>
            <?php endif; ?>

            <?php if (!empty($project['thumbnail_path'])): ?>
                <section class="pf-card">
                    <h2>Ảnh đại diện dự án</h2>
                    <a class="project-cover-link" href="<?= e(image_url($project['thumbnail_path'])) ?>" target="_blank" rel="noopener noreferrer">
                        <img class="project-cover-image" src="<?= e(image_url($project['thumbnail_path'])) ?>" alt="Ảnh đại diện dự án <?= e($project['title']) ?>">
                    </a>
                </section>
            <?php endif; ?>

            <?php if ($images): ?>
                <section class="pf-card">
                    <h2>Hình ảnh minh họa</h2>
                    <div class="pf-gallery">
                        <?php foreach ($images as $img): ?>
                            <a href="<?= e(image_url($img['image_path'])) ?>" target="_blank" rel="noopener noreferrer"><img src="<?= e(image_url($img['image_path'])) ?>" alt="Ảnh minh họa dự án <?= e($project['title']) ?>"></a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </div>

    <footer class="pf-foot">Hồ sơ được tạo bằng <?= e(config('app_name')) ?></footer>
</div>
<?php profile_page_close(); ?>
