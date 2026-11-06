<?php
// setup/ping.php - Bước 0: thử PHP có đọc được đường dẫn thư mục dự án hay không.
// Không nạp file nào khác, không kết nối CSDL. Mở: http://localhost/portfolio/setup/ping.php
header('Content-Type: text/plain; charset=UTF-8');

$bootstrap = __DIR__ . '/../includes/bootstrap.php';

echo "PHP " . PHP_VERSION . "\n";
echo "Thư mục hiện tại (__DIR__): " . __DIR__ . "\n";
echo "Đường dẫn có ký tự ngoài ASCII: " . (preg_match('/[^\x20-\x7E]/', __DIR__) ? 'CÓ' : 'KHÔNG') . "\n";
echo "Tìm thấy includes/bootstrap.php: " . (is_file($bootstrap) ? 'CÓ - OK' : 'KHÔNG - có vấn đề đường dẫn') . "\n";
echo "Tìm thấy config/config.php: " . (is_file(__DIR__ . '/../config/config.php') ? 'CÓ - OK' : 'KHÔNG') . "\n";
