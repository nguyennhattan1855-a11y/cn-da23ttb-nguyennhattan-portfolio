<?php
// Sao chép file này thành config/config.local.php nếu cần đổi cấu hình.
// Ví dụ: MariaDB của bạn có đặt mật khẩu cho root, hoặc muốn bật chế độ debug.

return [
    'debug' => false,

    // Khi đưa lên hosting thật, bật gửi email thật cho chức năng quên mật khẩu:
    // 'mail' => ['mode' => 'mail', 'from' => 'no-reply@ten-mien-cua-ban'],
    'db' => [
        'pass' => 'mat-khau-mariadb-cua-ban',
    ],
];
