<?php
// includes/dash_header.php - Khung giao diện quản lý (menu bên trái)
// Biến cần đặt trước khi include:  $pageTitle,  $activeNav (tên file của mục đang mở)
$showFlash = false;
require __DIR__ . '/header.php';

$dashMenu = [
    'index.php'       => 'Tổng quan',
    'profile.php'     => 'Thông tin cá nhân',
    'skills.php'      => 'Kỹ năng',
    'experiences.php' => 'Kinh nghiệm',
    'education.php'   => 'Học vấn',
    'projects.php'    => 'Dự án',
    'documents.php'   => 'CV & chứng chỉ',
    'appearance.php'  => 'Giao diện hồ sơ',
    'share.php'       => 'Chia sẻ & xuất hồ sơ',
    'account.php'     => 'Cài đặt tài khoản',
];
?>
        <div class="dash">
            <aside class="dash-nav">
                <div class="dash-user">Xin chào,<br><strong><?= e($currentUser['full_name']) ?></strong></div>
                <?php foreach ($dashMenu as $file => $label): ?>
                    <a href="<?= e(url('dashboard/' . $file)) ?>"<?= ($activeNav ?? '') === $file ? ' class="active"' : '' ?>><?= e($label) ?></a>
                <?php endforeach; ?>
                <a class="dash-public" href="<?= e(profile_url($currentUser['slug'], false)) ?>" target="_blank" rel="noopener">Xem hồ sơ công khai &#8599;</a>
            </aside>

            <section class="dash-main">
                <h1 class="dash-title"><?= e($pageTitle) ?></h1>
                <?php render_flash(); ?>
