<?php
// includes/helpers.php
// Các hàm tiện ích dùng chung cho toàn bộ website.

/* ================= Cấu hình & đường dẫn ================= */

// Đọc giá trị cấu hình, ví dụ config('db.host')
function config(string $key, $default = null)
{
    $value = $GLOBALS['app_config'] ?? [];
    foreach (explode('.', $key) as $part) {
        if (is_array($value) && array_key_exists($part, $value)) {
            $value = $value[$part];
        } else {
            return $default;
        }
    }
    return $value;
}

// Chuyển ký tự đặc biệt thành HTML an toàn (chống XSS). LUÔN dùng khi in dữ liệu ra trang.
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_url(): string
{
    return rtrim((string) config('base_url', ''), '/');
}

// url('login.php') => /portfolio/login.php
function url(string $path = ''): string
{
    return base_url() . '/' . ltrim($path, '/');
}

// asset('css/style.css') => /portfolio/assets/css/style.css
function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/* ================= Thông báo (flash) ================= */

// Loại thông báo: success, error, warning, info
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function render_flash(): void
{
    if (empty($_SESSION['flash'])) {
        return;
    }
    foreach ($_SESSION['flash'] as $item) {
        echo '<div class="alert alert-' . e($item['type']) . '" role="alert">' . e($item['message']) . '</div>';
    }
    unset($_SESSION['flash']);
}

/* ================= CSRF ================= */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Đặt hàm này (qua thẻ echo) bên trong mọi form method="post"
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

// Gọi đầu mỗi xử lý POST. Sai token => báo lỗi và quay lại trang hiện tại.
function csrf_require(): void
{
    // Gửi file quá lớn: PHP bỏ trống toàn bộ $_POST nên token cũng mất
    if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        flash('error', 'Dữ liệu gửi lên quá lớn (vượt giới hạn của máy chủ). Hãy chọn file nhỏ hơn.');
        $self = $_SERVER['SCRIPT_NAME'] ?? '';
        if ($self === '' || $self[0] !== '/' || strpos($self, '//') === 0) {
            $self = url('index.php');
        }
        header('Location: ' . $self);
        exit;
    }
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        flash('error', 'Phiên làm việc không hợp lệ hoặc đã hết hạn. Vui lòng thử lại.');
        $self = $_SERVER['SCRIPT_NAME'] ?? '';
        if ($self === '' || $self[0] !== '/' || strpos($self, '//') === 0) {
            $self = url('index.php');
        }
        header('Location: ' . $self);
        exit;
    }
}

/* ================= Đọc dữ liệu form ================= */

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

// Chuỗi đã cắt khoảng trắng hai đầu
function post_str(string $key): string
{
    $v = $_POST[$key] ?? '';
    return is_string($v) ? trim($v) : '';
}

// Chuỗi giữ nguyên (dùng cho mật khẩu)
function post_raw(string $key): string
{
    $v = $_POST[$key] ?? '';
    return is_string($v) ? $v : '';
}

// Ô tích (checkbox có value="1")
function post_bool(string $key): int
{
    return (isset($_POST[$key]) && $_POST[$key] === '1') ? 1 : 0;
}

function null_if_empty(string $value): ?string
{
    return $value === '' ? null : $value;
}

// Độ dài chuỗi theo ký tự (đúng với tiếng Việt có dấu)
function text_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

// Hiển thị lỗi bên dưới một ô nhập
function field_error(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<div class="field-error">' . e($errors[$key]) . '</div>' : '';
}

/* ================= Kiểm tra dữ liệu ================= */

// Kiểm tra ngày dạng Y-m-d. Trả về chuỗi chuẩn hóa hoặc null nếu sai.
function parse_date(string $value): ?string
{
    $d = DateTime::createFromFormat('!Y-m-d', $value);
    $errs = DateTime::getLastErrors();
    if (!$d || ($errs && ($errs['warning_count'] > 0 || $errs['error_count'] > 0))) {
        return null;
    }
    $year = (int) $d->format('Y');
    if ($year < 1950 || $year > 2100) {
        return null;
    }
    return $d->format('Y-m-d');
}

// 2026-10-21 => 21/10/2026
function format_date_vn(?string $ymd): string
{
    if (!$ymd) {
        return '';
    }
    $ts = strtotime($ymd);
    return $ts ? date('d/m/Y', $ts) : '';
}

function password_policy_error(string $password): ?string
{
    if (strlen($password) < 8) {
        return 'Mật khẩu phải có ít nhất 8 ký tự.';
    }
    if (strlen($password) > 72) {
        return 'Mật khẩu tối đa 72 ký tự.';
    }
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        return 'Mật khẩu phải gồm cả chữ và số.';
    }
    return null;
}

// Chuẩn hóa và kiểm tra URL. Chỉ chấp nhận http/https.
// $requiredDomain: ví dụ 'github.com' để bắt buộc đúng tên miền (chấp nhận cả www.github.com).
// Trả về [giá trị đã chuẩn hóa hoặc null, thông báo lỗi hoặc null].
function normalize_url(string $value, ?string $requiredDomain = null): array
{
    $value = trim($value);
    if ($value === '') {
        return [null, null];
    }
    if (strlen($value) > 255) {
        return [null, 'Đường dẫn quá dài (tối đa 255 ký tự).'];
    }
    if (!preg_match('#^https?://#i', $value)) {
        if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $value)) {
            return [null, 'Chỉ chấp nhận đường dẫn bắt đầu bằng http:// hoặc https://.'];
        }
        $value = 'https://' . $value;
    }
    if (!filter_var($value, FILTER_VALIDATE_URL)) {
        return [null, 'Đường dẫn không hợp lệ.'];
    }
    $parts = parse_url($value);
    $scheme = strtolower($parts['scheme'] ?? '');
    $host = strtolower($parts['host'] ?? '');
    if (!in_array($scheme, ['http', 'https'], true) || $host === '' || strpos($host, '.') === false) {
        return [null, 'Đường dẫn không hợp lệ.'];
    }
    if ($requiredDomain !== null) {
        $ok = ($host === $requiredDomain) || str_ends_with($host, '.' . $requiredDomain);
        if (!$ok) {
            return [null, 'Đường dẫn phải thuộc tên miền ' . $requiredDomain . '.'];
        }
    }
    return [$value, null];
}

/* ================= Slug (đường dẫn hồ sơ) ================= */

// "Nguyễn Văn Á" => "nguyen-van-a"
function slugify_vi(string $text): string
{
    $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
    $groups = [
        'a' => 'àáạảãâầấậẩẫăằắặẳẵ',
        'e' => 'èéẹẻẽêềếệểễ',
        'i' => 'ìíịỉĩ',
        'o' => 'òóọỏõôồốộổỗơờớợởỡ',
        'u' => 'ùúụủũưừứựửữ',
        'y' => 'ỳýỵỷỹ',
        'd' => 'đ',
    ];
    $map = [];
    foreach ($groups as $ascii => $chars) {
        foreach (preg_split('//u', $chars, -1, PREG_SPLIT_NO_EMPTY) as $ch) {
            $map[$ch] = $ascii;
        }
    }
    $text = strtr($text, $map);
    $text = preg_replace('/\p{Mn}+/u', '', $text) ?? $text; // bỏ dấu rời (nếu có)
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    $text = substr($text, 0, 60);
    $text = trim($text, '-');
    return $text === '' ? 'user' : $text;
}

// Tạo slug chưa ai dùng
function unique_slug(string $fullName): string
{
    $base = slugify_vi($fullName);
    $slug = $base;
    for ($i = 0; $i < 30; $i++) {
        if ((int) db_value('SELECT COUNT(*) FROM users WHERE slug = ?', [$slug]) === 0) {
            return $slug;
        }
        $slug = $base . '-' . random_int(100, 9999);
    }
    return $base . '-' . bin2hex(random_bytes(4));
}

/* ================= Danh mục dùng chung ================= */

function skill_categories(): array
{
    return [
        'language'        => 'Ngôn ngữ lập trình',
        'frontend'        => 'Công nghệ Frontend',
        'framework'       => 'Framework / Thư viện',
        'database'        => 'Cơ sở dữ liệu',
        'tool'            => 'Công cụ phát triển',
        'design_analysis' => 'Thiết kế & Phân tích',
        'soft_skill'      => 'Kỹ năng mềm',
        'other'           => 'Khác',
    ];
}

function skill_levels(): array
{
    return [1 => 'Mới học', 2 => 'Cơ bản', 3 => 'Khá', 4 => 'Tốt', 5 => 'Thành thạo'];
}

/* ================= Đường dẫn công khai, màu sắc ================= */

// Địa chỉ gốc của website, ví dụ http://localhost. Có thể đặt cố định bằng config 'site_url'.
function site_origin(): string
{
    $fixed = rtrim((string) config('site_url', ''), '/');
    if ($fixed !== '') {
        return $fixed;
    }
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (!preg_match('/^[A-Za-z0-9.\-]+(:\d{1,5})?$/', $host)) {
        $host = 'localhost';
    }
    return ($https ? 'https' : 'http') . '://' . $host;
}

// Đường dẫn hồ sơ công khai của một người dùng
function profile_url(string $slug, bool $absolute = true): string
{
    $path = url('u.php?slug=' . rawurlencode($slug));
    return $absolute ? site_origin() . $path : $path;
}

function valid_hex_color(string $color): bool
{
    return (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $color);
}

// Chọn màu chữ (trắng hoặc đen) dễ đọc trên nền màu $hex
function contrast_text_color(string $hex): string
{
    if (!valid_hex_color($hex)) {
        return '#ffffff';
    }
    $r = hexdec(substr($hex, 1, 2));
    $g = hexdec(substr($hex, 3, 2));
    $b = hexdec(substr($hex, 5, 2));
    $luma = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    return $luma > 0.62 ? '#111827' : '#ffffff';
}

function profile_templates(): array
{
    return [
        'classic' => 'Cổ điển – một cột, tiêu đề màu nổi bật',
        'modern'  => 'Hiện đại – thanh bên và nội dung chính',
    ];
}

function document_types(): array
{
    return ['cv' => 'CV', 'degree' => 'Bằng cấp', 'certificate' => 'Chứng chỉ'];
}

// 1536000 => "1,5 MB"
function format_bytes(int $bytes): string
{
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
    }
    return number_format(max(1, $bytes) / 1024, 0, ',', '.') . ' KB';
}

// Chữ cái đầu của họ tên, dùng làm ảnh đại diện thay thế
function initials(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        return '?';
    }
    $parts = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY);
    $first = $parts[0];
    $last = $parts[count($parts) - 1];
    $a = function_exists('mb_substr') ? mb_substr($first, 0, 1, 'UTF-8') : substr($first, 0, 1);
    $b = function_exists('mb_substr') ? mb_substr($last, 0, 1, 'UTF-8') : substr($last, 0, 1);
    $r = count($parts) > 1 ? $a . $b : $a;
    return function_exists('mb_strtoupper') ? mb_strtoupper($r, 'UTF-8') : strtoupper($r);
}

// "#2563eb" => "37, 99, 235" (dùng cho rgba() trong CSS)
function hex_rgb_string(string $hex): string
{
    if (!valid_hex_color($hex)) {
        $hex = '#2563eb';
    }
    return hexdec(substr($hex, 1, 2)) . ', ' . hexdec(substr($hex, 3, 2)) . ', ' . hexdec(substr($hex, 5, 2));
}

// Chỉ trả về URL nếu bắt đầu bằng http:// hoặc https:// (chặn javascript: ...). Lớp bảo vệ khi in ra trang.
function safe_url(?string $url): string
{
    $url = trim((string) $url);
    return preg_match('#^https?://#i', $url) ? $url : '';
}

// "PHP, MySQL, Git" => ['PHP', 'MySQL', 'Git']
function split_tags(?string $text): array
{
    $out = [];
    foreach (explode(',', (string) $text) as $part) {
        $part = trim($part);
        if ($part !== '') {
            $out[] = $part;
        }
    }
    return array_slice($out, 0, 20);
}

// Cắt chuỗi theo ký tự, thêm "..." nếu bị cắt
function limit_text(string $text, int $max): string
{
    $len = text_length($text);
    if ($len <= $max) {
        return $text;
    }
    return (function_exists('mb_substr') ? mb_substr($text, 0, $max - 1, 'UTF-8') : substr($text, 0, $max - 1)) . '…';
}
