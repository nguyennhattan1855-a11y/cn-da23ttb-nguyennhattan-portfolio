<?php
// includes/profile_layout.php
// Phần khung HTML dùng chung cho trang hồ sơ công khai (u.php) và trang chi tiết dự án (project.php).

// Mở trang: <html>, <head> và thẻ bao ngoài #profile-root (mang theo màu chủ đạo + mẫu giao diện)
function profile_page_open(string $title, string $description, string $template, string $color, bool $noindex, string $bodyAttrs = ''): void
{
    $accentText = contrast_text_color($color);
    ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($description) ?>">
<?php if ($noindex): ?>
    <meta name="robots" content="noindex, nofollow">
<?php endif; ?>
    <link rel="stylesheet" href="<?= e(asset('css/profile.css')) ?>">
</head>
<body class="pf-body"<?= $bodyAttrs ?>>
<?php
}

function profile_page_close(array $scripts = []): void
{
    foreach ($scripts as $script) {
        echo '<script src="' . e(asset($script)) . '"></script>' . "\n";
    }
    echo "</body>\n</html>\n";
}

// Thuộc tính style cho thẻ gốc: biến màu
function profile_root_style(string $color): string
{
    // Màu nhạt (chữ phải đen) thì dùng màu tối cho chữ nhấn trên nền trắng để vẫn đọc được
    $ink = contrast_text_color($color) === '#ffffff' ? $color : '#111827';
    return '--accent:' . $color . ';--accent-text:' . contrast_text_color($color)
        . ';--accent-ink:' . $ink . ';--accent-rgb:' . hex_rgb_string($color);
}

// Ảnh đại diện hoặc chữ cái đầu
function render_avatar(?string $path, string $name, string $class = 'pf-avatar'): string
{
    $url = image_url($path);
    if ($url !== null) {
        return '<img class="' . e($class) . '" src="' . e($url) . '" alt="Ảnh đại diện của ' . e($name) . '">';
    }
    return '<span class="' . e($class) . ' pf-avatar-fallback" aria-hidden="true">' . e(initials($name)) . '</span>';
}
