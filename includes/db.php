<?php
// includes/db.php
// Kết nối CSDL bằng PDO và các hàm truy vấn dùng prepared statements
// (chống SQL Injection: dữ liệu người dùng KHÔNG được ghép trực tiếp vào câu SQL).

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = config('db');
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $c['host'],
            (int) $c['port'],
            $c['name']
        );
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

// Chạy câu lệnh có tham số "?" và trả về PDOStatement
function db_query(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

// Lấy một dòng (hoặc null nếu không có)
function db_one(string $sql, array $params = []): ?array
{
    $row = db_query($sql, $params)->fetch();
    return $row === false ? null : $row;
}

// Lấy nhiều dòng
function db_all(string $sql, array $params = []): array
{
    return db_query($sql, $params)->fetchAll();
}

// Lấy một giá trị (ví dụ COUNT(*))
function db_value(string $sql, array $params = [])
{
    $value = db_query($sql, $params)->fetchColumn();
    return $value === false ? null : $value;
}

// Danh sách bảng cho phép dùng với find_owned() / delete_owned()
function owned_tables(): array
{
    return ['skills', 'experiences', 'educations', 'projects', 'documents'];
}

// Tìm một bản ghi THUỘC VỀ người dùng đang đăng nhập (kiểm tra quyền sở hữu).
function find_owned(string $table, int $id): ?array
{
    if (!in_array($table, owned_tables(), true)) {
        throw new InvalidArgumentException('Bảng không hợp lệ.');
    }
    $user = current_user();
    if (!$user || $id <= 0) {
        return null;
    }
    return db_one("SELECT * FROM `$table` WHERE id = ? AND user_id = ?", [$id, $user['id']]);
}

// Xóa một bản ghi nếu nó thuộc về người dùng đang đăng nhập. Trả về true nếu đã xóa.
function delete_owned(string $table, int $id): bool
{
    if (find_owned($table, $id) === null) {
        return false;
    }
    $user = current_user();
    db_query("DELETE FROM `$table` WHERE id = ? AND user_id = ?", [$id, $user['id']]);
    return true;
}
