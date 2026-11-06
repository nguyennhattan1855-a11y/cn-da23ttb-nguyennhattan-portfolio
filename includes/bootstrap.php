<?php
// includes/bootstrap.php
// File khởi động: nạp cấu hình, hàm tiện ích, CSDL, session, xác thực.
// Mọi trang PHP đều bắt đầu bằng một dòng:
//     require __DIR__ . '/includes/bootstrap.php';        (trang ở thư mục gốc)
//     require __DIR__ . '/../includes/bootstrap.php';     (trang trong dashboard/)

define('ROOT_PATH', dirname(__DIR__));

// 1. Nạp cấu hình (config.local.php, nếu có, sẽ ghi đè config.php)
$GLOBALS['app_config'] = require ROOT_PATH . '/config/config.php';
$localConfigFile = ROOT_PATH . '/config/config.local.php';
if (is_file($localConfigFile)) {
    $GLOBALS['app_config'] = array_replace_recursive($GLOBALS['app_config'], require $localConfigFile);
}

require_once ROOT_PATH . '/includes/helpers.php';
require_once ROOT_PATH . '/includes/db.php';
require_once ROOT_PATH . '/includes/auth.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/includes/mail.php';
require_once ROOT_PATH . '/includes/upload.php';
require_once ROOT_PATH . '/includes/profile_data.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');

// 2. Ghi lỗi vào logs/error.log, không hiện chi tiết kỹ thuật cho người dùng
error_reporting(E_ALL);
ini_set('display_errors', config('debug') ? '1' : '0');
ini_set('log_errors', '1');
$logDir = ROOT_PATH . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
ini_set('error_log', $logDir . '/error.log');

set_exception_handler(function (Throwable $ex) {
    error_log(get_class($ex) . ': ' . $ex->getMessage() . ' in ' . $ex->getFile() . ':' . $ex->getLine());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }
    $detail = config('debug') ? '<pre>' . e($ex->getMessage()) . '</pre>' : '';
    echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
        . '<title>Có lỗi xảy ra</title></head>'
        . '<body style="font-family:Arial,sans-serif;max-width:560px;margin:80px auto;padding:0 20px;color:#1f2937">'
        . '<h1 style="color:#1d4ed8">Rất tiếc, hệ thống đang gặp sự cố</h1>'
        . '<p>Vui lòng thử lại sau. Nếu bạn là người cài đặt, hãy kiểm tra Apache và MariaDB đã bật, '
        . 'đã nhập file SQL trong thư mục <code>setup</code>, rồi mở trang kiểm tra môi trường '
        . '<a href="' . e(url('setup/check.php')) . '">setup/check.php</a>. '
        . 'Chi tiết lỗi được ghi trong <code>logs/error.log</code>.</p>'
        . $detail
        . '<p><a href="' . e(url('index.php')) . '">&larr; Về trang chủ</a></p>'
        . '</body></html>';
    exit;
});

// 3. Header bảo mật cơ bản
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// 4. Session an toàn
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('DEVPORTFOLIOSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => base_url() . '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
