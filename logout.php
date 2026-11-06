<?php
// logout.php - Đăng xuất (chỉ nhận yêu cầu POST có CSRF token)
require __DIR__ . '/includes/bootstrap.php';

if (!is_post()) {
    redirect('index.php');
}
csrf_require();

logout_user();
flash('success', 'Bạn đã đăng xuất.');
redirect('index.php');
