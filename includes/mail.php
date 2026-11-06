<?php
// includes/mail.php - Gửi email văn bản (dùng cho chức năng quên mật khẩu)
//
// config 'mail.mode':
//   'log'  : không gửi thật; ghi nội dung email vào logs/mail.log (phù hợp XAMPP, không cần máy chủ thư)
//   'mail' : gửi bằng hàm mail() của PHP (cần máy chủ thư hoạt động, thường có trên hosting)

function mail_mode(): string
{
    return config('mail.mode', 'log') === 'mail' ? 'mail' : 'log';
}

function send_app_mail(string $to, string $subject, string $body): bool
{
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    // Loại ký tự xuống dòng để chống chèn header email
    $clean = function (string $s): string {
        return str_replace(["\r", "\n"], ' ', $s);
    };
    $subject = $clean($subject);

    if (mail_mode() === 'mail') {
        $from = $clean((string) config('mail.from', 'no-reply@localhost'));
        $fromName = $clean((string) config('mail.from_name', 'DevPortfolio'));
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: =?UTF-8?B?' . base64_encode($fromName) . '?= <' . $from . '>',
        ];
        return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));
    }

    $logDir = ROOT_PATH . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }
    $entry = "===== " . date('c') . " =====\nTo: " . $to . "\nSubject: " . $subject . "\n\n" . $body . "\n\n";
    return @file_put_contents($logDir . '/mail.log', $entry, FILE_APPEND | LOCK_EX) !== false;
}
