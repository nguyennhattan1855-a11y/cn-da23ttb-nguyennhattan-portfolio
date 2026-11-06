<?php
// Trang chủ DevPortfolio
require __DIR__ . '/includes/bootstrap.php';

$isFeaturesPage = ($_GET['tab'] ?? '') === 'features';
$pageTitle = $isFeaturesPage ? 'Tính năng' : 'Trang chủ';
$isLoggedIn = current_user() !== null;

require __DIR__ . '/includes/header.php';
?>
<?php if (!$isFeaturesPage): ?>

    <!-- Giới thiệu website -->
    <section class="hero">

        <h1>Xây dựng hồ sơ năng lực lập trình viên</h1>

        <p>
            Tạo và quản lý hồ sơ cá nhân, giới thiệu kỹ năng,
            kinh nghiệm, các dự án lập trình và chia sẻ năng lực
            đến nhà tuyển dụng hoặc đối tác.
        </p>

        <?php if ($isLoggedIn): ?>
            <a href="<?= e(url('dashboard/index.php')) ?>" class="btn">Vào bảng điều khiển</a>
        <?php else: ?>
            <a href="<?= e(url('register.php')) ?>" class="btn">Tạo hồ sơ ngay</a>
            <a href="<?= e(url('login.php')) ?>" class="btn btn-outline">Đăng nhập</a>
        <?php endif; ?>

    </section>

    <!-- Bắt đầu nhanh -->
    <section class="steps">
        <h2>Bắt đầu trong 3 bước</h2>
        <ol>
            <li><strong>Đăng ký</strong> tài khoản miễn phí bằng email.</li>
            <li><strong>Nhập thông tin</strong>: giới thiệu bản thân, kỹ năng, kinh nghiệm, học vấn.</li>
            <li><strong>Hoàn thiện hồ sơ</strong> để sẵn sàng chia sẻ với nhà tuyển dụng.</li>
        </ol>
    </section>

<?php else: ?>
    <!-- Nội dung riêng cho Tính năng, dùng chung index.php -->
    <section class="features-intro">
        <h1>Các tính năng của DevPortfolio</h1>
        <p>Quản lý, trình bày và chia sẻ hồ sơ năng lực lập trình viên trên cùng một nền tảng.</p>
    </section>

    <section class="features features-page" aria-label="Các chức năng nổi bật">
        <div class="feature-card"><h3>Hồ sơ cá nhân</h3><p>Cập nhật ảnh đại diện, giới thiệu bản thân, thông tin liên hệ, GitHub và LinkedIn.</p></div>
        <div class="feature-card"><h3>Kỹ năng, học vấn và kinh nghiệm</h3><p>Thêm, chỉnh sửa và quản lý những nội dung thể hiện năng lực chuyên môn.</p></div>
        <div class="feature-card"><h3>Dự án lập trình</h3><p>Giới thiệu dự án cùng hình ảnh, công nghệ, mã nguồn GitHub và đường dẫn demo.</p></div>
        <div class="feature-card"><h3>CV và chứng chỉ</h3><p>Tải lên và quản lý tài liệu hồ sơ, thiết lập quyền công khai khi cần.</p></div>
        <div class="feature-card"><h3>Giao diện hồ sơ</h3><p>Chọn mẫu hiển thị và tùy chỉnh màu sắc để tạo phong cách riêng.</p></div>
        <div class="feature-card"><h3>Chia sẻ và xuất hồ sơ</h3><p>Tạo đường dẫn công khai, mã QR và xuất hồ sơ sang PDF hoặc hình ảnh.</p></div>
    </section>
    <section class="features-action">
        <?php if ($isLoggedIn): ?>
            <a class="btn" href="<?= e(url('dashboard/index.php')) ?>">Vào bảng điều khiển</a>
        <?php else: ?>
            <a class="btn" href="<?= e(url('register.php')) ?>">Bắt đầu tạo hồ sơ</a>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
