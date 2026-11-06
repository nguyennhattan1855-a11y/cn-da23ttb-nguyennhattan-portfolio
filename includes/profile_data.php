<?php
// includes/profile_data.php
// Nạp toàn bộ dữ liệu một hồ sơ để hiển thị công khai / xem trước.

// Tìm người dùng theo slug (chỉ tài khoản đang hoạt động)
function find_user_by_slug(string $slug): ?array
{
    if (!preg_match('/^[a-z0-9-]{1,120}$/', $slug)) {
        return null;
    }
    return db_one(
        'SELECT id, full_name, email, slug FROM users WHERE slug = ? AND is_active = 1',
        [$slug]
    );
}

// Gom dữ liệu hồ sơ của người dùng $user (mảng có khóa id, full_name, slug)
function load_profile_bundle(array $user): array
{
    $uid = (int) $user['id'];

    $profile = db_one('SELECT * FROM profiles WHERE user_id = ?', [$uid]) ?? [];
    $profile += [
        'headline' => null, 'bio' => null, 'contact_email' => null, 'phone' => null,
        'location' => null, 'avatar_path' => null, 'website_url' => null,
        'github_url' => null, 'linkedin_url' => null, 'is_public' => 1,
    ];

    $settings = db_one('SELECT template, primary_color FROM profile_settings WHERE user_id = ?', [$uid]);
    if (!$settings || !isset(profile_templates()[$settings['template']]) || !valid_hex_color($settings['primary_color'])) {
        $settings = ['template' => 'classic', 'primary_color' => '#2563eb'];
    }

    $skills = [];
    foreach (db_all('SELECT * FROM skills WHERE user_id = ? ORDER BY level DESC, name', [$uid]) as $row) {
        $skills[$row['category']][] = $row;
    }

    $experiences = db_all('SELECT * FROM experiences WHERE user_id = ? ORDER BY start_date DESC, id DESC', [$uid]);
    $educations = db_all('SELECT * FROM educations WHERE user_id = ? ORDER BY start_year DESC, id DESC', [$uid]);

    $projects = db_all(
        'SELECT * FROM projects WHERE user_id = ?
         ORDER BY is_featured DESC, project_date DESC, id DESC',
        [$uid]
    );
    $imagesByProject = [];
    foreach (db_all(
        'SELECT pi.* FROM project_images pi
         JOIN projects p ON p.id = pi.project_id
         WHERE p.user_id = ? ORDER BY pi.sort_order, pi.id',
        [$uid]
    ) as $img) {
        $imagesByProject[(int) $img['project_id']][] = $img;
    }

    $documents = db_all(
        'SELECT id, doc_type, title, original_name, mime_type, file_size
         FROM documents WHERE user_id = ? AND is_public = 1 ORDER BY doc_type, id DESC',
        [$uid]
    );

    return [
        'user'        => $user,
        'profile'     => $profile,
        'settings'    => $settings,
        'skills'      => $skills,
        'experiences' => $experiences,
        'educations'  => $educations,
        'projects'    => $projects,
        'images'      => $imagesByProject,
        'documents'   => $documents,
    ];
}

// Ai được xem hồ sơ? Chủ hồ sơ luôn được xem; người khác chỉ khi hồ sơ đang công khai.
function viewer_is_owner(array $owner): bool
{
    $viewer = current_user();
    return $viewer !== null && (int) $viewer['id'] === (int) $owner['id'];
}

// Trang 404 thân thiện (không nói rõ hồ sơ bị ẩn hay không tồn tại)
function render_profile_not_found(): void
{
    http_response_code(404);
    $pageTitle = 'Không tìm thấy hồ sơ';
    require ROOT_PATH . '/includes/header.php';
    echo '<div class="auth-wrap"><div class="auth-card"><h1>Không tìm thấy hồ sơ</h1>'
        . '<p class="muted">Hồ sơ này không tồn tại hoặc chủ sở hữu đang ẩn hồ sơ.</p>'
        . '<p><a class="btn btn-block" href="' . e(url('index.php')) . '">Về trang chủ</a></p></div></div>';
    require ROOT_PATH . '/includes/footer.php';
    exit;
}
