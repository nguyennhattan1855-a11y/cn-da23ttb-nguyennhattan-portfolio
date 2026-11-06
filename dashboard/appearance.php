<?php
// dashboard/appearance.php - Chọn mẫu giao diện và màu chủ đạo, có xem trước
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login();
$uid = (int) $user['id'];

$templates = profile_templates();
$settings = db_one('SELECT template, primary_color FROM profile_settings WHERE user_id = ?', [$uid]);
if (!$settings) {
    db_query('INSERT INTO profile_settings (user_id) VALUES (?)', [$uid]);
    $settings = ['template' => 'classic', 'primary_color' => '#2563eb'];
}
$errors = [];
$input = $settings;

if (is_post()) {
    csrf_require();
    $input['template'] = post_str('template');
    $input['primary_color'] = strtolower(post_str('primary_color'));

    if (!array_key_exists($input['template'], $templates)) {
        $errors['template'] = 'Mẫu giao diện không hợp lệ.';
    }
    if (!valid_hex_color($input['primary_color'])) {
        $errors['primary_color'] = 'Màu không hợp lệ.';
    }
    if (!$errors) {
        db_query('UPDATE profile_settings SET template = ?, primary_color = ? WHERE user_id = ?',
            [$input['template'], $input['primary_color'], $uid]);
        flash('success', 'Đã lưu giao diện hồ sơ.');
        redirect('dashboard/appearance.php');
    }
}

$swatches = [
    '#2563eb' => 'Xanh dương', '#0891b2' => 'Xanh biển', '#059669' => 'Xanh lá',
    '#7c3aed' => 'Tím', '#db2777' => 'Hồng', '#ea580c' => 'Cam',
    '#dc2626' => 'Đỏ', '#ca8a04' => 'Vàng đậm', '#0f766e' => 'Xanh ngọc',
    '#334155' => 'Xám than', '#111827' => 'Đen', '#64748b' => 'Xám'
];
$previewBase = profile_url($user['slug'], false);

$pageTitle = 'Giao diện hồ sơ';
$activeNav = 'appearance.php';
require __DIR__ . '/../includes/dash_header.php';
?>
<link rel="stylesheet" href="<?= e(asset('css/appearance_controls.css') . '?v=2') ?>">
                <form class="card" id="appearance-form" method="post" action="<?= e(url('dashboard/appearance.php')) ?>" novalidate>
                    <?= csrf_field() ?>

                    <h2>Mẫu giao diện</h2>
                    <div class="template-options">
                        <?php foreach ($templates as $key => $label): ?>
                            <label class="template-option">
                                <input type="radio" name="template" value="<?= e($key) ?>"<?= $input['template'] === $key ? ' checked' : '' ?>>
                                <span><strong><?= e($key === 'classic' ? 'Cổ điển' : 'Hiện đại') ?></strong><br>
                                <small class="muted"><?= e($label) ?></small></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?= field_error($errors, 'template') ?>

                    <h2>Màu chủ đạo</h2>
                    <p class="muted">Chọn màu gợi ý hoặc tự pha màu bằng các thanh kéo.</p>
                    <div class="color-suggestions" role="group" aria-label="Màu sắc đề xuất">
                        <?php foreach ($swatches as $c => $label): ?>
                            <button type="button" class="color-suggestion" data-color="<?= e($c) ?>" aria-label="Chọn <?= e($label) ?>"
                                    title="<?= e($label) ?>" aria-pressed="false">
                                <span class="swatch" style="background: <?= e($c) ?>"></span>
                                <span><?= e($label) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <div class="custom-color-panel">
                        <div class="color-picker-line">
                            <label for="primary_color">Màu tùy chọn</label>
                            <input type="color" id="primary_color" name="primary_color" value="<?= e($input['primary_color']) ?>" aria-label="Chọn màu chủ đạo">
                            <output id="color-hex" for="primary_color"><?= e(strtoupper($input['primary_color'])) ?></output>
                        </div>
                        <label for="color-hue">Sắc màu <output id="hue-value"></output></label>
                        <input id="color-hue" type="range" min="0" max="359" step="1" aria-label="Thanh kéo sắc màu">
                        <label for="color-saturation">Độ đậm <output id="saturation-value"></output></label>
                        <input id="color-saturation" type="range" min="0" max="100" step="1" aria-label="Thanh kéo độ đậm">
                        <label for="color-lightness">Độ sáng <output id="lightness-value"></output></label>
                        <input id="color-lightness" type="range" min="5" max="95" step="1" aria-label="Thanh kéo độ sáng">
                    </div>
                    <div class="form-hint">Màu sắc được xem trước ngay; nhấn «Lưu giao diện» để ghi vào hồ sơ. Chữ trên nền tự đổi trắng/đen để dễ đọc.</div>
                    <?= field_error($errors, 'primary_color') ?>

                    <button type="submit" class="btn">Lưu giao diện</button>
                </form>

                <div class="card">
                    <h2>Xem trước</h2>
                    <p class="muted">Chỉ xem và cuộn nội dung hồ sơ. Các nút tải xuống và liên kết được tắt trong khung xem trước; chỉ lưu khi bấm "Lưu giao diện".</p>
                    <iframe id="preview-frame" class="preview-frame" title="Xem trước hồ sơ"
                            sandbox="allow-same-origin"
                            data-base="<?= e($previewBase . '&preview=1') ?>"
                            src="<?= e($previewBase . '&preview=1&tpl=' . rawurlencode($input['template']) . '&color=' . rawurlencode($input['primary_color'])) ?>"></iframe>
                </div>
<?php
$extraScripts = ['js/appearance.js'];
require __DIR__ . '/../includes/dash_footer.php';
?>
