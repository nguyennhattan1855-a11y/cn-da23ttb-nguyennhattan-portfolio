
<?php
// includes/header.php - Thanh menu chung của DevPortfolio

$currentUser = current_user();
$pageTitle = $pageTitle ?? 'Trang chủ';

// Xác định đường dẫn trang hiện tại
$currentPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$currentFile = basename($currentPath);

// Phân biệt trang chủ với các trang trong Dashboard
$isDashboard = strpos($currentPath, '/dashboard/') !== false;

$isHome = !$isDashboard
    && $currentFile === 'index.php'
    && ($_GET['tab'] ?? '') !== 'features';

$isFeatures = !$isDashboard
    && $currentFile === 'index.php'
    && ($_GET['tab'] ?? '') === 'features';

$isLogin = !$isDashboard && $currentFile === 'login.php';
$isRegister = !$isDashboard && $currentFile === 'register.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($pageTitle) ?> - <?= e(config('app_name')) ?></title>

    <link rel="stylesheet"
          href="<?= e(asset('css/style.css') . '?v=20261031-menu3') ?>">
</head>

<body>

<!-- Thanh điều hướng chung -->
<header>
    <a class="logo" href="<?= e(url('index.php')) ?>">
        <?= e(config('app_name')) ?>
    </a>

    <nav>
        <!-- Trang chủ -->
        <a href="<?= e(url('index.php')) ?>"
           class="nav-link<?= $isHome ? ' active' : '' ?>"
           <?= $isHome ? 'aria-current="page"' : '' ?>>
            Trang chủ
        </a>

        <!-- Tính năng -->
        <a href="<?= e(url('index.php?tab=features')) ?>"
           class="nav-link<?= $isFeatures ? ' active' : '' ?>"
           <?= $isFeatures ? 'aria-current="page"' : '' ?>>
            Tính năng
        </a>

        <?php if ($currentUser): ?>

            <!-- Bảng điều khiển -->
            <a href="<?= e(url('dashboard/index.php')) ?>"
               class="nav-link<?= $isDashboard ? ' active' : '' ?>"
               <?= $isDashboard ? 'aria-current="page"' : '' ?>>
                Bảng điều khiển
            </a>

            <!-- Đăng xuất -->
            <form class="nav-form"
                  method="post"
                  action="<?= e(url('logout.php')) ?>">

                <?= csrf_field() ?>

                <button type="submit" class="nav-btn">
                    Đăng xuất
                </button>
            </form>

        <?php else: ?>

            <!-- Đăng nhập -->
            <a href="<?= e(url('login.php')) ?>"
               class="nav-link<?= $isLogin ? ' active' : '' ?>"
               <?= $isLogin ? 'aria-current="page"' : '' ?>>
                Đăng nhập
            </a>

            <!-- Đăng ký -->
            <a href="<?= e(url('register.php')) ?>"
               class="nav-link<?= $isRegister ? ' active' : '' ?>"
               <?= $isRegister ? 'aria-current="page"' : '' ?>>
                Đăng ký
            </a>

        <?php endif; ?>
    </nav>
</header>

<main>
<?php if (!isset($showFlash) || $showFlash !== false): ?>
    <div class="flash-wrap">
        <?php render_flash(); ?>
    </div>
<?php endif; ?>
