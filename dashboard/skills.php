<?php
// dashboard/skills.php - Quản lý kỹ năng (thêm, sửa, xóa)
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login();
$uid = (int) $user['id'];

$categories = skill_categories();
$levels = skill_levels();
$skillSuggestions = json_decode(<<<'SKILLS_JSON'
{
  "language": [
    "PHP",
    "JavaScript",
    "TypeScript",
    "Python",
    "Java",
    "C",
    "C++",
    "C#",
    "Go",
    "Ruby",
    "Dart",
    "Kotlin",
    "Swift",
    "Rust",
    "SQL"
  ],
  "frontend": [
    "HTML5",
    "CSS3",
    "Responsive Design",
    "Sass / SCSS",
    "CSS Grid",
    "Flexbox",
    "Web Accessibility",
    "Web Performance"
  ],
  "framework": [
    "Bootstrap",
    "React",
    "Vue.js",
    "Angular",
    "Laravel",
    "Symfony",
    "Yii2",
    "Next.js",
    "Nuxt.js",
    "Express.js",
    "Django",
    "Flask",
    "Spring Boot",
    "jQuery"
  ],
  "database": [
    "MySQL",
    "MariaDB",
    "PostgreSQL",
    "SQL Server",
    "SQLite",
    "MongoDB",
    "Redis",
    "Firebase Firestore"
  ],
  "tool": [
    "Git",
    "GitHub",
    "Visual Studio Code",
    "XAMPP",
    "Docker",
    "Postman",
    "Composer",
    "npm",
    "GitHub Actions",
    "GitLab",
    "Linux"
  ],
  "design_analysis": [
    "Figma",
    "UI/UX",
    "Wireframe",
    "UML",
    "ERD",
    "Phân tích yêu cầu",
    "Thiết kế cơ sở dữ liệu",
    "Thiết kế hệ thống",
    "Prototype"
  ],
  "soft_skill": [
    "Làm việc nhóm",
    "Giao tiếp",
    "Giải quyết vấn đề",
    "Tư duy logic",
    "Quản lý thời gian",
    "Thuyết trình",
    "Tự học",
    "Tư duy phản biện"
  ],
  "other": []
}
SKILLS_JSON, true);

$errors = [];
$input = ['id' => 0, 'name' => '', 'category' => 'language', 'level' => 3];

// ---- Xóa ----
if (is_post() && post_str('action') === 'delete') {
    csrf_require();
    if (delete_owned('skills', (int) post_str('id'))) {
        flash('success', 'Đã xóa kỹ năng.');
    } else {
        flash('error', 'Không tìm thấy kỹ năng cần xóa.');
    }
    redirect('dashboard/skills.php');
}

// ---- Mở form sửa (?edit=ID) ----
if (!is_post() && isset($_GET['edit'])) {
    $row = find_owned('skills', (int) $_GET['edit']);
    if (!$row) {
        flash('error', 'Không tìm thấy kỹ năng cần sửa.');
        redirect('dashboard/skills.php');
    }
    $input = [
        'id'       => (int) $row['id'],
        'name'     => $row['name'],
        'category' => $row['category'],
        'level'    => (int) $row['level'],
    ];
}

// ---- Lưu (thêm mới hoặc cập nhật) ----
if (is_post() && post_str('action') === 'save') {
    csrf_require();

    $input = [
        'id'       => (int) post_str('id'),
        'name'     => post_str('name'),
        'category' => post_str('category'),
        'level'    => (int) post_str('level'),
    ];

    if ($input['id'] > 0 && !find_owned('skills', $input['id'])) {
        flash('error', 'Không tìm thấy kỹ năng cần sửa.');
        redirect('dashboard/skills.php');
    }

    $len = text_length($input['name']);
    if ($len < 1 || $len > 60) {
        $errors['name'] = 'Tên kỹ năng phải từ 1 đến 60 ký tự.';
    }
    if (!array_key_exists($input['category'], $categories)) {
        $errors['category'] = 'Nhóm kỹ năng không hợp lệ.';
    }
    if (!array_key_exists($input['level'], $levels)) {
        $errors['level'] = 'Mức độ không hợp lệ.';
    }
    // Ngăn phân loại nhầm khi kỹ năng đã có trong gợi ý của nhóm khác.
    // Tên chưa có trong danh sách vẫn được nhập tự do ở bất kỳ nhóm nào.
    if (!isset($errors['name']) && !isset($errors['category'])) {
        $normalized = function_exists('mb_strtolower')
            ? mb_strtolower(trim($input['name']), 'UTF-8')
            : strtolower(trim($input['name']));
        foreach ($skillSuggestions as $groupKey => $names) {
            foreach ($names as $suggestedName) {
                $known = function_exists('mb_strtolower')
                    ? mb_strtolower($suggestedName, 'UTF-8')
                    : strtolower($suggestedName);
                if ($known === $normalized && $groupKey !== $input['category']) {
                    $errors['category'] = 'Kỹ năng này thường thuộc nhóm "' . $categories[$groupKey] . '". Hãy chọn lại nhóm hoặc tên kỹ năng.';
                    break 2;
                }
            }
        }
    }
    if (!isset($errors['name']) &&
        (int) db_value('SELECT COUNT(*) FROM skills WHERE user_id = ? AND name = ? AND id <> ?',
            [$uid, $input['name'], $input['id']]) > 0) {
        $errors['name'] = 'Bạn đã có kỹ năng này rồi.';
    }

    if (!$errors) {
        try {
            if ($input['id'] > 0) {
                db_query('UPDATE skills SET name = ?, category = ?, level = ? WHERE id = ? AND user_id = ?',
                    [$input['name'], $input['category'], $input['level'], $input['id'], $uid]);
                flash('success', 'Đã cập nhật kỹ năng.');
            } else {
                db_query('INSERT INTO skills (user_id, name, category, level) VALUES (?, ?, ?, ?)',
                    [$uid, $input['name'], $input['category'], $input['level']]);
                flash('success', 'Đã thêm kỹ năng.');
            }
            redirect('dashboard/skills.php');
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') {
                $errors['name'] = 'Bạn đã có kỹ năng này rồi.';
            } else {
                throw $ex;
            }
        }
    }
}

// ---- Dữ liệu hiển thị, nhóm theo loại kỹ năng ----
$grouped = [];
foreach (db_all('SELECT * FROM skills WHERE user_id = ? ORDER BY name', [$uid]) as $row) {
    $grouped[$row['category']][] = $row;
}
$isEditing = $input['id'] > 0;

$pageTitle = 'Kỹ năng';
$activeNav = 'skills.php';
require __DIR__ . '/../includes/dash_header.php';
?>
                <form class="card" method="post" action="<?= e(url('dashboard/skills.php')) ?>" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= (int) $input['id'] ?>">
                    <h2><?= $isEditing ? 'Sửa kỹ năng' : 'Thêm kỹ năng' ?></h2>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="category">Nhóm kỹ năng</label>
                            <select class="form-control" id="category" name="category">
                                <?php foreach ($categories as $key => $label): ?>
                                    <option value="<?= e($key) ?>"<?= $input['category'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= field_error($errors, 'category') ?>
                        </div>
                        <div class="form-group">
                            <label for="name">Tên kỹ năng</label>
                            <input class="form-control" type="text" id="name" name="name" maxlength="60"
                                   required autocomplete="off" placeholder="Nhập hoặc chọn kỹ năng theo nhóm..."
                                   value="<?= e($input['name']) ?>" aria-controls="skill-suggestion-list" aria-expanded="false">
                            <div id="skill-suggestion-list" class="skill-suggestions" role="listbox" hidden></div>
                            <p id="skill-suggestion-hint" class="form-hint" aria-live="polite">Chọn nhóm rồi nhấn vào tên kỹ năng để xem gợi ý. Có thể tự nhập kỹ năng chưa có trong danh sách.</p>
                            <?= field_error($errors, 'name') ?>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="level">Mức độ</label>
                            <select class="form-control" id="level" name="level">
                                <?php foreach ($levels as $key => $label): ?>
                                    <option value="<?= (int) $key ?>"<?= (int) $input['level'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= field_error($errors, 'level') ?>
                        </div>
                    </div>

                    <button type="submit" class="btn"><?= $isEditing ? 'Cập nhật' : 'Thêm kỹ năng' ?></button>
                    <?php if ($isEditing): ?>
                        <a class="btn btn-outline" href="<?= e(url('dashboard/skills.php')) ?>">Hủy</a>
                    <?php endif; ?>
                </form>

                <div class="card">
                    <h2>Danh sách kỹ năng</h2>
                    <?php if (!$grouped): ?>
                        <p class="muted">Bạn chưa có kỹ năng nào. Hãy thêm kỹ năng đầu tiên ở form phía trên.</p>
                    <?php endif; ?>

                    <?php foreach ($categories as $catKey => $catLabel): ?>
                        <?php if (empty($grouped[$catKey])) { continue; } ?>
                        <h3 class="group-title"><?= e($catLabel) ?></h3>
                        <div class="item-list">
                            <?php foreach ($grouped[$catKey] as $skill): ?>
                                <div class="item">
                                    <div class="item-body">
                                        <strong><?= e($skill['name']) ?></strong>
                                        <span class="badge"><?= e($levels[(int) $skill['level']] ?? '') ?></span>
                                    </div>
                                    <div class="item-actions">
                                        <a class="btn btn-outline btn-sm" href="<?= e(url('dashboard/skills.php?edit=' . (int) $skill['id'])) ?>">Sửa</a>
                                        <form method="post" action="<?= e(url('dashboard/skills.php')) ?>"
                                              data-confirm="Bạn chắc chắn muốn xóa kỹ năng này?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int) $skill['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
<style>
.skill-suggestions {margin-top:5px;max-height:215px;overflow-y:auto;border:1px solid #cbd5e1;border-radius:8px;background:#fff;box-shadow:0 5px 15px #0001;}
.skill-suggestions[hidden] {display:none}
.skill-suggestion {display:block;width:100%;padding:10px 13px;border:0;border-bottom:1px solid #f1f5f9;text-align:left;background:white;font:inherit;cursor:pointer;color:#1e293b}
.skill-suggestion:hover,.skill-suggestion:focus {background:#eff6ff;outline:none}
</style>
<script type="application/json" id="skill-suggestions-data"><?= json_encode($skillSuggestions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script src="<?= e(asset('js/skill_suggestions.js') . '?v=20261101') ?>" defer></script>
<?php require __DIR__ . '/../includes/dash_footer.php'; ?>
