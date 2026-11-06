<?php
// dashboard/index.php - Tổng quan hồ sơ
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login();
$uid = (int) $user['id'];

$profile = db_one('SELECT * FROM profiles WHERE user_id = ?', [$uid]) ?? [];
$countSkills = (int) db_value('SELECT COUNT(*) FROM skills WHERE user_id = ?', [$uid]);
$countExperiences = (int) db_value('SELECT COUNT(*) FROM experiences WHERE user_id = ?', [$uid]);
$countEducations = (int) db_value('SELECT COUNT(*) FROM educations WHERE user_id = ?', [$uid]);
$countProjects = (int) db_value('SELECT COUNT(*) FROM projects WHERE user_id = ?', [$uid]);
$countDocuments = (int) db_value('SELECT COUNT(*) FROM documents WHERE user_id = ?', [$uid]);

// Danh sách việc cần làm để hồ sơ đầy đủ: [mô tả, đã xong?, trang để thực hiện]
$checklist = [
    ['Tải lên ảnh đại diện',                !empty($profile['avatar_path']), 'profile.php'],
    ['Nhập chức danh / vị trí mong muốn', !empty($profile['headline']),  'profile.php'],
    ['Viết phần giới thiệu bản thân',       !empty($profile['bio']),       'profile.php'],
    ['Nhập số điện thoại và khu vực',       !empty($profile['phone']) && !empty($profile['location']), 'profile.php'],
    ['Thêm liên kết GitHub hoặc LinkedIn',  !empty($profile['github_url']) || !empty($profile['linkedin_url']), 'profile.php'],
    ['Thêm ít nhất 1 kỹ năng',              $countSkills > 0,              'skills.php'],
    ['Thêm ít nhất 1 kinh nghiệm',          $countExperiences > 0,         'experiences.php'],
    ['Thêm ít nhất 1 mục học vấn',          $countEducations > 0,          'education.php'],
    ['Thêm ít nhất 1 dự án',                $countProjects > 0,            'projects.php'],
    ['Tải lên CV hoặc chứng chỉ',           $countDocuments > 0,           'documents.php'],
];
$done = 0;
foreach ($checklist as $item) {
    if ($item[1]) {
        $done++;
    }
}
$percent = (int) round($done * 100 / count($checklist));

$pageTitle = 'Tổng quan';
$activeNav = 'index.php';
require __DIR__ . '/../includes/dash_header.php';
?>
                <div class="stat-grid">
                    <a class="stat" href="<?= e(url('dashboard/skills.php')) ?>">
                        <span class="stat-number"><?= $countSkills ?></span>
                        <span class="stat-label">Kỹ năng</span>
                    </a>
                    <a class="stat" href="<?= e(url('dashboard/experiences.php')) ?>">
                        <span class="stat-number"><?= $countExperiences ?></span>
                        <span class="stat-label">Kinh nghiệm</span>
                    </a>
                    <a class="stat" href="<?= e(url('dashboard/education.php')) ?>">
                        <span class="stat-number"><?= $countEducations ?></span>
                        <span class="stat-label">Học vấn</span>
                    </a>
                    <a class="stat" href="<?= e(url('dashboard/projects.php')) ?>">
                        <span class="stat-number"><?= $countProjects ?></span>
                        <span class="stat-label">Dự án</span>
                    </a>
                    <a class="stat" href="<?= e(url('dashboard/documents.php')) ?>">
                        <span class="stat-number"><?= $countDocuments ?></span>
                        <span class="stat-label">Tài liệu</span>
                    </a>
                    <a class="stat" href="<?= e(url('dashboard/profile.php')) ?>">
                        <span class="stat-number"><?= !empty($profile['is_public']) ? 'Công khai' : 'Đang ẩn' ?></span>
                        <span class="stat-label">Trạng thái hồ sơ</span>
                    </a>
                </div>

                <div class="card">
                    <h2>Mức độ hoàn thiện hồ sơ: <?= $percent ?>%</h2>
                    <div class="progress" aria-hidden="true"><span style="width: <?= $percent ?>%"></span></div>

                    <ul class="checklist">
                        <?php foreach ($checklist as $item): ?>
                            <li class="<?= $item[1] ? 'is-done' : '' ?>">
                                <span class="check-mark"><?= $item[1] ? '✔' : '○' ?></span>
                                <?php if ($item[1]): ?>
                                    <?= e($item[0]) ?>
                                <?php else: ?>
                                    <a href="<?= e(url('dashboard/' . $item[2])) ?>"><?= e($item[0]) ?></a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="card">
                    <h2>Hồ sơ công khai của bạn</h2>
                    <p>Đường dẫn: <a href="<?= e(profile_url($user['slug'], false)) ?>" target="_blank" rel="noopener"><?= e(profile_url($user['slug'])) ?></a></p>
                    <a class="btn btn-sm" href="<?= e(url('dashboard/share.php')) ?>">Chia sẻ &amp; xuất hồ sơ</a>
                    <a class="btn btn-outline btn-sm" href="<?= e(url('dashboard/appearance.php')) ?>">Đổi giao diện</a>
                </div>
<?php require __DIR__ . '/../includes/dash_footer.php'; ?>
