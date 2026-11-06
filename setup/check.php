<?php
// setup/check.php - Trang kiểm tra môi trường cài đặt (chỉ dùng trên máy cá nhân).
// Mở: http://localhost/portfolio/setup/check.php
// Trang này KHÔNG hiển thị mật khẩu hay thông tin nhạy cảm.
require __DIR__ . '/../includes/bootstrap.php';

$checks = []; // [tên, đạt?, ghi chú]

// 1. Phiên bản PHP
$checks[] = ['Phiên bản PHP từ 8.0 trở lên (đang dùng ' . PHP_VERSION . ')', version_compare(PHP_VERSION, '8.0.0', '>='),
    'XAMPP 8.2.12 đáp ứng yêu cầu này.'];

// 2. Extension cần thiết
foreach (['pdo_mysql' => 'Kết nối MariaDB/MySQL', 'mbstring' => 'Xử lý chữ tiếng Việt',
          'fileinfo' => 'Kiểm tra loại file tải lên (giai đoạn 2)', 'gd' => 'Xử lý ảnh (giai đoạn 2-3)'] as $ext => $why) {
    $checks[] = ['Extension ' . $ext . ' (' . $why . ')', extension_loaded($ext),
        'Mở D:\\xampp\\php\\php.ini, bỏ dấu ; trước dòng extension=' . $ext . ', rồi Restart Apache.'];
}

// 3. Thư mục logs ghi được
$checks[] = ['Thư mục logs/ ghi được', is_dir(ROOT_PATH . '/logs') && is_writable(ROOT_PATH . '/logs'),
    'Tạo thư mục logs trong thư mục dự án và cho phép ghi.'];

// 3b. Thư mục tải lên ghi được (thử ghi thật một file tạm rồi xóa)
foreach (['uploads/images' => 'Thư mục uploads/images ghi được (ảnh đại diện, ảnh dự án)',
          'storage/documents' => 'Thư mục storage/documents ghi được (CV, chứng chỉ)'] as $rel => $label) {
    $dir = ROOT_PATH . '/' . $rel;
    ensure_dir($dir);
    $probe = $dir . '/.ghi_thu_' . bin2hex(random_bytes(4));
    $okWrite = @file_put_contents($probe, 'x') !== false;
    if ($okWrite) {
        @unlink($probe);
    }
    $checks[] = [$label, $okWrite, 'Kiểm tra quyền ghi của thư mục ' . $rel . ' trong dự án.'];
}

// 4. Kết nối CSDL
$dbOk = false;
$dbNote = '';
$pdo = null;
$c = config('db');
try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], (int) $c['port'], $c['name']),
        $c['user'], $c['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $dbOk = true;
} catch (PDOException $ex) {
    $code = (int) ($ex->errorInfo[1] ?? $ex->getCode());
    if ($code === 1049) {
        $dbNote = 'Chưa có CSDL "' . $c['name'] . '". Hãy nhập file setup/01_tao_csdl.sql trong phpMyAdmin.';
    } elseif ($code === 1045) {
        $dbNote = 'Sai tài khoản/mật khẩu MariaDB. Tạo file config/config.local.php (xem config.local.example.php).';
    } elseif ($code === 2002) {
        $dbNote = 'Không kết nối được MariaDB. Hãy bấm Start cho MySQL trong XAMPP Control Panel.';
    } else {
        $dbNote = 'Không kết nối được CSDL (mã lỗi ' . $code . '). Xem logs/error.log.';
        error_log('check.php DB error: ' . $ex->getMessage());
    }
}
$checks[] = ['Kết nối CSDL ' . $c['name'], $dbOk, $dbNote];

// 5. Các bảng cần có
if ($dbOk && $pdo) {
    $required = ['users', 'profiles', 'skills', 'experiences', 'educations',
                 'projects', 'project_images', 'documents', 'profile_settings',
                 'login_attempts', 'password_resets'];
    $existing = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $missing = array_diff($required, $existing);
    $checks[] = ['Đủ ' . count($required) . ' bảng dữ liệu', !$missing,
        $missing ? 'Thiếu bảng: ' . implode(', ', $missing) . '. Nếu là login_attempts / password_resets: nhập setup/nang_cap_v2.sql; nếu thiếu bảng khác: nhập setup/devportfolio_full.sql.' : ''];

    if (!$missing) {
        $n = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $checks[] = ['Số tài khoản hiện có: ' . $n, true, ''];
    }
}

$allOk = true;
foreach ($checks as $row) {
    if (!$row[1]) {
        $allOk = false;
    }
}
$pageTitle = 'Kiểm tra môi trường';
require __DIR__ . '/../includes/header.php';
?>
    <div class="auth-wrap">
        <div class="auth-card" style="max-width:760px">
            <h1>Kiểm tra môi trường</h1>
            <p class="muted">Trang hỗ trợ cài đặt trên XAMPP.</p>

            <div class="alert <?= $allOk ? 'alert-success' : 'alert-warning' ?>">
                <?= $allOk ? 'Mọi mục đều đạt. Bạn có thể dùng website.' : 'Còn mục chưa đạt, xem ghi chú bên dưới.' ?>
            </div>

            <ul class="checklist">
                <?php foreach ($checks as $row): ?>
                    <li class="<?= $row[1] ? 'is-done' : '' ?>">
                        <span class="check-mark"><?= $row[1] ? '✔' : '✖' ?></span>
                        <?= e($row[0]) ?>
                        <?php if (!$row[1] && $row[2] !== ''): ?>
                            <div class="field-error" style="margin-left:22px"><?= e($row[2]) ?></div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <p class="auth-switch"><a href="<?= e(url('index.php')) ?>">&larr; Về trang chủ</a></p>
        </div>
    </div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
