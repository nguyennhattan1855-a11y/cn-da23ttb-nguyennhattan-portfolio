<?php
// includes/upload.php
// Xử lý file tải lên một cách an toàn:
//  - kiểm tra lỗi, dung lượng, loại file THỰC SỰ (đọc nội dung, không tin tên file);
//  - đổi tên ngẫu nhiên, đuôi file lấy theo loại thật (không lấy từ tên người dùng);
//  - ảnh lưu ở uploads/images (công khai, đã chặn chạy PHP);
//  - tài liệu lưu ở storage/documents (chặn truy cập trực tiếp, chỉ tải qua file.php).

const IMAGE_MAX_BYTES = 3145728;  // 3 MB
const DOC_MAX_BYTES   = 5242880;  // 5 MB
const MAX_PROJECT_IMAGES = 8;

function images_dir(): string
{
    return ROOT_PATH . '/uploads/images';
}

function documents_dir(): string
{
    return ROOT_PATH . '/storage/documents';
}

function ensure_dir(string $dir): void
{
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
}

// Thông báo lỗi tải lên của PHP; null nếu không có lỗi.
function upload_error_message(int $code): ?string
{
    switch ($code) {
        case UPLOAD_ERR_OK:
            return null;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'File vượt quá dung lượng cho phép.';
        case UPLOAD_ERR_PARTIAL:
            return 'File tải lên chưa hoàn tất, vui lòng thử lại.';
        default:
            error_log('Lỗi tải file, mã ' . $code);
            return 'Máy chủ không lưu được file, vui lòng thử lại sau.';
    }
}

// Người dùng không chọn file?
function no_file_chosen(array $file): bool
{
    return ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE;
}

// Đổi $_FILES['images'] (nhiều file) thành danh sách từng file.
function normalize_files(string $key): array
{
    $out = [];
    if (empty($_FILES[$key]) || !is_array($_FILES[$key]['name'] ?? null)) {
        if (!empty($_FILES[$key]) && is_string($_FILES[$key]['name'] ?? null)) {
            $out[] = $_FILES[$key];
        }
        return $out;
    }
    $count = count($_FILES[$key]['name']);
    for ($i = 0; $i < $count; $i++) {
        $out[] = [
            'name'     => $_FILES[$key]['name'][$i],
            'type'     => $_FILES[$key]['type'][$i],
            'tmp_name' => $_FILES[$key]['tmp_name'][$i],
            'error'    => $_FILES[$key]['error'][$i],
            'size'     => $_FILES[$key]['size'][$i],
        ];
    }
    return $out;
}

// Lưu một ảnh JPG/PNG/WEBP. Trả về [tên file đã lưu hoặc null, lỗi hoặc null].
function save_uploaded_image(array $file): array
{
    if (no_file_chosen($file)) {
        return [null, null];
    }
    $err = upload_error_message((int) $file['error']);
    if ($err !== null) {
        return [null, $err];
    }
    $tmp = (string) $file['tmp_name'];
    if (!is_uploaded_file($tmp)) {
        return [null, 'File tải lên không hợp lệ.'];
    }
    if ((int) $file['size'] > IMAGE_MAX_BYTES) {
        return [null, 'Mỗi ảnh tối đa 3 MB.'];
    }

    $info = @getimagesize($tmp);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($types[$info[2]])) {
        return [null, 'Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.'];
    }
    if ($info[0] > 6000 || $info[1] > 6000) {
        return [null, 'Kích thước ảnh quá lớn (tối đa 6000 x 6000 điểm ảnh).'];
    }
    if (class_exists('finfo')) {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return [null, 'Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.'];
        }
    }

    ensure_dir(images_dir());
    $name = bin2hex(random_bytes(16)) . '.' . $types[$info[2]];
    if (!move_uploaded_file($tmp, images_dir() . '/' . $name)) {
        error_log('Không ghi được ảnh vào ' . images_dir());
        return [null, 'Không lưu được ảnh. Hãy kiểm tra quyền ghi thư mục uploads/images.'];
    }
    return [$name, null];
}

// Lưu tài liệu PDF/JPG/PNG. Trả về [mảng thông tin hoặc null, lỗi hoặc null].
function save_uploaded_document(array $file): array
{
    if (no_file_chosen($file)) {
        return [null, 'Vui lòng chọn một file.'];
    }
    $err = upload_error_message((int) $file['error']);
    if ($err !== null) {
        return [null, $err];
    }
    $tmp = (string) $file['tmp_name'];
    if (!is_uploaded_file($tmp)) {
        return [null, 'File tải lên không hợp lệ.'];
    }
    $size = (int) $file['size'];
    if ($size <= 0 || $size > DOC_MAX_BYTES) {
        return [null, 'Mỗi tài liệu tối đa 5 MB.'];
    }
    if (!class_exists('finfo')) {
        return [null, 'Máy chủ chưa bật extension fileinfo nên không kiểm tra được loại file.'];
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $types = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];
    if (!isset($types[$mime])) {
        return [null, 'Chỉ chấp nhận file PDF, JPG hoặc PNG.'];
    }
    if ($mime === 'application/pdf') {
        $head = (string) @file_get_contents($tmp, false, null, 0, 5);
        if ($head !== '%PDF-') {
            return [null, 'File PDF không hợp lệ.'];
        }
    }

    ensure_dir(documents_dir());
    $stored = bin2hex(random_bytes(16)) . '.' . $types[$mime];
    if (!move_uploaded_file($tmp, documents_dir() . '/' . $stored)) {
        error_log('Không ghi được tài liệu vào ' . documents_dir());
        return [null, 'Không lưu được file. Hãy kiểm tra quyền ghi thư mục storage/documents.'];
    }

    // Tên gốc chỉ để hiển thị: bỏ đường dẫn và ký tự điều khiển, cắt ngắn
    $original = basename(str_replace('\\', '/', (string) $file['name']));
    $original = preg_replace('/[\x00-\x1F\x7F]/u', '', $original) ?? '';
    if (function_exists('mb_substr')) {
        $original = mb_substr($original, 0, 150, 'UTF-8');
    } else {
        $original = substr($original, 0, 150);
    }
    if ($original === '') {
        $original = 'tai-lieu.' . $types[$mime];
    }

    return [[
        'file_path'     => $stored,
        'original_name' => $original,
        'mime_type'     => $mime,
        'file_size'     => $size,
    ], null];
}

// Xóa file đã lưu. $kind: 'image' hoặc 'doc'. Chỉ lấy phần tên file (chống ../)
function delete_stored_file(string $kind, ?string $name): void
{
    if ($name === null || $name === '') {
        return;
    }
    $dir = $kind === 'doc' ? documents_dir() : images_dir();
    $path = $dir . '/' . basename($name);
    if (is_file($path)) {
        @unlink($path);
    }
}

// Đường dẫn ảnh để đặt vào thẻ <img>; null nếu không có ảnh
function image_url(?string $name): ?string
{
    if ($name === null || $name === '') {
        return null;
    }
    return url('uploads/images/' . rawurlencode(basename($name)));
}
