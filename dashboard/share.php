<?php
// dashboard/share.php - Chia sẻ hồ sơ: sao chép đường dẫn, mã QR, xuất PDF / hình ảnh
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login();
$uid = (int) $user['id'];

$isPublic = (int) db_value('SELECT is_public FROM profiles WHERE user_id = ?', [$uid]) === 1;
$publicUrl = profile_url($user['slug']);
$relative = profile_url($user['slug'], false);

$pageTitle = 'Chia sẻ & xuất hồ sơ';
$activeNav = 'share.php';
require __DIR__ . '/../includes/dash_header.php';
?>
                <?php if (!$isPublic): ?>
                    <div class="alert alert-warning">
                        Hồ sơ của bạn đang <strong>ẩn</strong>: người khác mở đường dẫn sẽ không xem được.
                        Bật lại tại <a href="<?= e(url('dashboard/profile.php')) ?>">Thông tin cá nhân</a>.
                    </div>
                <?php endif; ?>

                <div class="card">
                    <h2>Đường dẫn hồ sơ</h2>
                    <div class="copy-row">
                        <input class="form-control" type="text" id="share-url" value="<?= e($publicUrl) ?>" readonly>
                        <button type="button" class="btn btn-sm" id="copy-btn">Sao chép</button>
                    </div>
                    <div class="form-hint" id="copy-status" aria-live="polite"></div>
                    <p><a class="btn btn-outline btn-sm" href="<?= e($relative) ?>" target="_blank" rel="noopener">Mở hồ sơ công khai</a></p>
                </div>

                <div class="card">
                    <h2>Mã QR</h2>
                    <p class="muted">Quét mã để mở hồ sơ trên điện thoại. Có thể in lên CV hoặc danh thiếp.</p>
                    <canvas id="qr-canvas" class="qr-canvas" width="280" height="280" data-text="<?= e($publicUrl) ?>"></canvas>
                    <noscript><p class="field-error">Cần bật JavaScript để tạo mã QR.</p></noscript>
                    <p class="field-error" id="qr-error" hidden></p>
                    <button type="button" class="btn btn-sm" id="qr-download">Tải mã QR (PNG)</button>
                </div>

                <div class="card">
                    <h2>Xuất hồ sơ</h2>
                    <p class="muted">
                        <strong>PDF:</strong> trang hồ sơ sẽ mở hộp thoại in của trình duyệt; chọn máy in
                        "Lưu dưới dạng PDF" (Save as PDF) rồi bấm Lưu.<br>
                        <strong>Hình ảnh:</strong> trang hồ sơ sẽ tự tạo và tải về một ảnh PNG.
                    </p>
                    <a class="btn btn-sm" href="<?= e($relative . '&export=pdf') ?>" target="_blank" rel="noopener">Xuất PDF</a>
                    <a class="btn btn-outline btn-sm" href="<?= e($relative . '&export=png') ?>" target="_blank" rel="noopener">Xuất hình ảnh PNG</a>
                </div>
<?php
$extraScripts = ['js/qrcode.js', 'js/share.js'];
require __DIR__ . '/../includes/dash_footer.php';
?>
