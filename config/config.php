<?php

return [
    'app_name' => 'DevPortfolio',

    // Đường dẫn website trên Apache: http://localhost/portfolio/
    'base_url' => '/portfolio',

    // true = hiện chi tiết lỗi (chỉ dùng khi đang sửa lỗi trên máy cá nhân)
    'debug' => false,

    // Giới hạn đăng nhập sai: tối đa N lần trong M phút (theo email; theo địa chỉ IP là 4 x N lần)
    'login_max_attempts' => 5,
    'login_lock_minutes' => 15,

    // Gửi email (quên mật khẩu).
    //  'log'  = KHÔNG gửi thật, ghi nội dung email vào logs/mail.log (dùng khi chạy thử trên XAMPP)
    //  'mail' = gửi bằng hàm mail() của PHP (cần cấu hình máy chủ thư trên hosting thật)
    'mail' => [
        'mode'      => 'log',
        'from'      => 'no-reply@devportfolio.local',
        'from_name' => 'DevPortfolio',
    ],

    // Kết nối MariaDB trong XAMPP (mặc định: root, không có mật khẩu)
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'devportfolio_db',
        'user' => 'root',
        'pass' => '',
    ],
];
