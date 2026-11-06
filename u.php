<?php
// u.php?slug=ten-nguoi-dung - Trang hồ sơ công khai (không cần đăng nhập)
//   &export=pdf|png  : tự mở hộp thoại in (PDF) hoặc tự tải ảnh PNG
//   &tpl=...&color=... : xem trước giao diện (chỉ chủ hồ sơ mới dùng được)
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/profile_layout.php';

$slug = isset($_GET['slug']) && is_string($_GET['slug']) ? strtolower(trim($_GET['slug'])) : '';
$owner = $slug !== '' ? find_user_by_slug($slug) : null;
if ($owner === null) {
    render_profile_not_found();
}

$isOwner = viewer_is_owner($owner);
// Chỉ chủ hồ sơ mới được phép mở chế độ xem trước từ trang quản lý.
$isPreview = $isOwner && (($_GET['preview'] ?? '') === '1');
$data = load_profile_bundle($owner);
$profile = $data['profile'];
if ((int) $profile['is_public'] !== 1 && !$isOwner) {
    render_profile_not_found();
}

// Giao diện: lấy từ CSDL; chủ hồ sơ được phép xem thử mẫu/màu khác qua tham số
$template = $data['settings']['template'];
$color = $data['settings']['primary_color'];
if ($isOwner) {
    $tplParam = isset($_GET['tpl']) && is_string($_GET['tpl']) ? $_GET['tpl'] : '';
    $colorParam = isset($_GET['color']) && is_string($_GET['color']) ? strtolower($_GET['color']) : '';
    if ($tplParam !== '' && isset(profile_templates()[$tplParam])) {
        $template = $tplParam;
    }
    if ($colorParam !== '' && valid_hex_color($colorParam)) {
        $color = $colorParam;
    }
}

$exportMode = isset($_GET['export']) && in_array($_GET['export'], ['pdf', 'png'], true) ? $_GET['export'] : '';
$name = $owner['full_name'];
$levels = skill_levels();
$categories = skill_categories();
$docTypes = document_types();
$hidden = (int) $profile['is_public'] !== 1;

$title = $name . (!empty($profile['headline']) ? ' – Vị trí hướng đến: ' . $profile['headline'] : ' – Hồ sơ năng lực');
$description = !empty($profile['bio']) ? limit_text(preg_replace('/\s+/u', ' ', (string) $profile['bio']) ?? '', 160) : 'Hồ sơ năng lực của ' . $name;

profile_page_open($title, $description, $template, $color, $hidden || $exportMode !== '' || isset($_GET['tpl']) || $isPreview);
?>
<?php if (!$isPreview): ?>
<div class="pf-toolbar" data-export-hide>
    <a class="pf-toolbar-brand" href="<?= e(url('index.php')) ?>"><?= e(config('app_name')) ?></a>
    <?php if ($isOwner && $hidden): ?>
        <span class="pf-toolbar-note">Hồ sơ đang ẩn – chỉ bạn xem được</span>
    <?php endif; ?>
    <span class="pf-toolbar-actions">
        <?php if ($isOwner): ?>
            <a class="pf-btn pf-btn-ghost" href="<?= e(url('dashboard/index.php')) ?>">&larr; Quay lại quản lý</a>
        <?php endif; ?>
        <button type="button" class="pf-btn" id="btn-pdf">Lưu PDF</button>
        <button type="button" class="pf-btn" id="btn-png">Tải ảnh PNG</button>
    </span>
</div>

<?php endif; ?>

<div id="profile-root" <?= $isPreview ? 'inert aria-label="Bản xem trước chỉ đọc"' : '' ?> class="pf-page tpl-<?= e($template) ?>" style="<?= e(profile_root_style($color)) ?>"
     data-filename="ho-so-<?= e($owner['slug']) ?>" data-export="<?= e($exportMode) ?>">

    <header class="pf-hero">
        <?= render_avatar($profile['avatar_path'], $name) ?>
        <div class="pf-hero-text">
            <h1><?= e($name) ?></h1>
            <?php if (!empty($profile['headline'])): ?>
                <p class="pf-location">Vị trí hướng đến</p>
                <p class="pf-headline"><?= e($profile['headline']) ?></p>
            <?php endif; ?>
            <?php if (!empty($profile['location'])): ?>
                <p class="pf-location"><?= e($profile['location']) ?></p>
            <?php endif; ?>
        </div>
    </header>

    <div class="pf-layout">
        <aside class="pf-side">
            <?php
            $email = (string) $profile['contact_email'];
            $phone = (string) $profile['phone'];
            $website = safe_url($profile['website_url']);
            $github = safe_url($profile['github_url']);
            $linkedin = safe_url($profile['linkedin_url']);
            ?>
            <?php if ($email !== '' || $phone !== ''): ?>
                <section class="pf-card">
                    <h2>Liên hệ</h2>
                    <ul class="pf-list">
                        <?php if ($email !== ''): ?>
                            <li><span class="pf-label">Email</span> <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></li>
                        <?php endif; ?>
                        <?php if ($phone !== ''): ?>
                            <li><span class="pf-label">Điện thoại</span> <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><?= e($phone) ?></a></li>
                        <?php endif; ?>
                    </ul>
                </section>
            <?php endif; ?>

            <?php if ($website !== '' || $github !== '' || $linkedin !== ''): ?>
                <section class="pf-card">
                    <h2>Liên kết</h2>
                    <ul class="pf-list">
                        <?php if ($github !== ''): ?>
                            <li><span class="pf-label">GitHub</span> <a href="<?= e($github) ?>" target="_blank" rel="noopener noreferrer"><?= e(limit_text(preg_replace('#^https?://(www\.)?#i', '', $github) ?? $github, 40)) ?></a></li>
                        <?php endif; ?>
                        <?php if ($linkedin !== ''): ?>
                            <li><span class="pf-label">LinkedIn</span> <a href="<?= e($linkedin) ?>" target="_blank" rel="noopener noreferrer"><?= e(limit_text(preg_replace('#^https?://(www\.)?#i', '', $linkedin) ?? $linkedin, 40)) ?></a></li>
                        <?php endif; ?>
                        <?php if ($website !== ''): ?>
                            <li><span class="pf-label">Website</span> <a href="<?= e($website) ?>" target="_blank" rel="noopener noreferrer"><?= e(limit_text(preg_replace('#^https?://(www\.)?#i', '', $website) ?? $website, 40)) ?></a></li>
                        <?php endif; ?>
                    </ul>
                </section>
            <?php endif; ?>

            <?php if ($data['skills']): ?>
                <section class="pf-card">
                    <h2>Kỹ năng</h2>
                    <?php foreach ($categories as $catKey => $catLabel): ?>
                        <?php if (empty($data['skills'][$catKey])) { continue; } ?>
                        <h3 class="pf-sub"><?= e($catLabel) ?></h3>
                        <ul class="pf-chips">
                            <?php foreach ($data['skills'][$catKey] as $skill): ?>
                                <li title="<?= e($levels[(int) $skill['level']] ?? '') ?>">
                                    <?= e($skill['name']) ?>
                                    <span class="pf-dots" aria-label="Mức độ <?= (int) $skill['level'] ?>/5"><?php for ($i = 1; $i <= 5; $i++): ?><i class="<?= $i <= (int) $skill['level'] ? 'on' : '' ?>"></i><?php endfor; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>

            <?php if ($data['documents']): ?>
                <section class="pf-card" data-export-hide>
                    <h2>Tài liệu</h2>
                    <ul class="pf-list">
                        <?php foreach ($data['documents'] as $doc): ?>
                            <li>
                                <span class="pf-label"><?= e($docTypes[$doc['doc_type']] ?? '') ?></span>
                                <a href="<?= e(url('file.php?id=' . (int) $doc['id'])) ?>" target="_blank" rel="noopener"><?= e($doc['title']) ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
        </aside>

        <div class="pf-main">
            <?php if (!empty($profile['bio'])): ?>
                <section class="pf-card">
                    <h2>Giới thiệu</h2>
                    <p class="pf-text"><?= nl2br(e($profile['bio'])) ?></p>
                </section>
            <?php endif; ?>

            <?php if ($data['experiences']): ?>
                <section class="pf-card">
                    <h2>Kinh nghiệm</h2>
                    <?php foreach ($data['experiences'] as $exp): ?>
                        <article class="pf-entry">
                            <h3><?= e($exp['position']) ?> <span class="pf-at">tại <?= e($exp['company']) ?></span></h3>
                            <p class="pf-period"><?= e(format_date_vn($exp['start_date'])) ?> &ndash; <?= (int) $exp['is_current'] ? 'Hiện tại' : e(format_date_vn($exp['end_date'])) ?></p>
                            <?php if (!empty($exp['description'])): ?>
                                <p class="pf-text"><?= nl2br(e($exp['description'])) ?></p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>

            <?php if ($data['educations']): ?>
                <section class="pf-card">
                    <h2>Học vấn</h2>
                    <?php foreach ($data['educations'] as $edu): ?>
                        <article class="pf-entry">
                            <h3><?= e($edu['school']) ?></h3>
                            <p class="pf-period">Ngành học: <?= e($edu['degree']) ?> · <?= (int) $edu['start_year'] ?> &ndash; <?= $edu['end_year'] ? (int) $edu['end_year'] : 'Đang học' ?></p>
                            <?php if (!empty($edu['qualification'])): ?>
                                <p class="pf-text">Bằng cấp: <?= e($edu['qualification']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($edu['description'])): ?>
                                <p class="pf-text"><?= nl2br(e($edu['description'])) ?></p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>

            <?php if ($data['projects']): ?>
                <section class="pf-card">
                    <h2>Dự án</h2>
                    <div class="pf-projects">
                        <?php foreach ($data['projects'] as $p): ?>
                            <?php
                            $detailUrl = url('project.php?slug=' . rawurlencode($owner['slug']) . '&id=' . (int) $p['id']);
                            $pg = safe_url($p['github_url']);
                            $pd = safe_url($p['demo_url']);
                            ?>
                            <article class="pf-project">
                                <?php if (!empty($p['thumbnail_path'])): ?>
                                    <img class="pf-project-img" src="<?= e(image_url($p['thumbnail_path'])) ?>" alt="Ảnh dự án <?= e($p['title']) ?>">
                                <?php else: ?>
                                    <span class="pf-project-img pf-project-fallback" aria-hidden="true"><?= e(initials($p['title'])) ?></span>
                                <?php endif; ?>
                                <div class="pf-project-body">
                                    <h3>
                                        <a href="<?= e($detailUrl) ?>"><?= e($p['title']) ?></a>
                                        <?php if ((int) $p['is_featured'] === 1): ?><span class="pf-badge">Nổi bật</span><?php endif; ?>
                                    </h3>
                                    <?php if (!empty($p['short_description'])): ?>
                                        <p class="pf-text"><?= e($p['short_description']) ?></p>
                                    <?php endif; ?>
                                    <?php if (split_tags($p['technologies'])): ?>
                                        <ul class="pf-chips pf-chips-small">
                                            <?php foreach (split_tags($p['technologies']) as $tag): ?><li><?= e($tag) ?></li><?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                    <p class="pf-links">
                                        <a href="<?= e($detailUrl) ?>">Chi tiết</a>
                                        <?php if ($pg !== ''): ?><a href="<?= e($pg) ?>" target="_blank" rel="noopener noreferrer">GitHub</a><?php endif; ?>
                                        <?php if ($pd !== ''): ?><a href="<?= e($pd) ?>" target="_blank" rel="noopener noreferrer">Demo</a><?php endif; ?>
                                    </p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </div>

    <footer class="pf-foot">Hồ sơ được tạo bằng <?= e(config('app_name')) ?></footer>
</div>
<?php profile_page_close($isPreview ? [] : ['js/export.js']); ?>
