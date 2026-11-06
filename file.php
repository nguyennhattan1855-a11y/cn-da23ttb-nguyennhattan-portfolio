<?php
// file.php?id=N[&download=1] - Phát tài liệu (CV, bằng cấp, chứng chỉ) có kiểm tra quyền.
// Chủ sở hữu luôn xem được; người khác chỉ xem khi tài liệu được công khai VÀ hồ sơ đang công khai.
require __DIR__ . '/includes/bootstrap.php';

function deny_file(): void
{
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Không tìm thấy tài liệu.';
    exit;
}

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    deny_file();
}

$doc = db_one(
    'SELECT d.*, p.is_public AS profile_public
     FROM documents d
     JOIN users u ON u.id = d.user_id AND u.is_active = 1
     LEFT JOIN profiles p ON p.user_id = d.user_id
     WHERE d.id = ?',
    [$id]
);
if (!$doc) {
    deny_file();
}

$viewer = current_user();
$isOwner = $viewer !== null && (int) $viewer['id'] === (int) $doc['user_id'];
if (!$isOwner && !((int) $doc['is_public'] === 1 && (int) $doc['profile_public'] === 1)) {
    deny_file();
}

$path = documents_dir() . '/' . basename($doc['file_path']);
if (!is_file($path)) {
    deny_file();
}

$allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
$mime = in_array($doc['mime_type'], $allowedMimes, true) ? $doc['mime_type'] : 'application/octet-stream';
$download = isset($_GET['download']) && $_GET['download'] === '1';

// Tên file tải về: bỏ ký tự đặc biệt, giữ tiếng Việt qua filename*
$niceName = preg_replace('/[^\p{L}\p{N}._ -]+/u', '_', (string) $doc['original_name']);
if ($niceName === null || $niceName === '') {
    $niceName = 'tai-lieu';
}
$asciiName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $niceName);

header_remove('Content-Type');
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Content-Disposition: ' . ($download || $mime === 'application/octet-stream' ? 'attachment' : 'inline')
    . '; filename="' . $asciiName . '"; filename*=UTF-8\'\'' . rawurlencode($niceName));

// Giải phóng khóa session trước khi gửi file lớn
session_write_close();
readfile($path);
exit;
