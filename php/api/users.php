<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';
$dbParam = $_GET['db'] ?? 'admin';

// Hiện tại chỉ hỗ trợ admin DB để ổn định, main DB sẽ bổ sung sau
if ($dbParam !== 'admin') {
    json_response(['success' => false, 'message' => 'Hiện tại chỉ hỗ trợ db=admin'], 400);
}

switch ($method) {
    case 'GET':
        if ($action === 'get') {
            handle_get_user();
        } elseif ($action === 'search') {
            handle_list_users(true);
        } else { // default list
            handle_list_users(false);
        }
        break;
    case 'PUT':
        handle_update_user();
        break;
    default:
        json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

function handle_list_users(bool $isSearch): void {
    require_permission('user.read');
    [$page, $limit, $offset] = paginate_params();
    $pdo = db();
    $params = [];
    $where = '';
    if ($isSearch) {
        $q = trim((string)($_GET['q'] ?? ''));
        if ($q === '') {
            json_response(['success' => true, 'data' => ['users' => [], 'pagination' => ['current_page' => $page, 'limit' => $limit, 'total_users' => 0], 'database' => 'admin', 'table' => 'users']]);
        }
        $where = 'WHERE username LIKE ? OR email LIKE ? OR full_name LIKE ? OR phone LIKE ?';
        $kw = "%$q%";
        $params = [$kw, $kw, $kw, $kw];
    }
    $sql = "SELECT SQL_CALC_FOUND_ROWS id, username, email, full_name, phone, status, created_at, updated_at FROM users $where ORDER BY id DESC LIMIT $limit OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Chuẩn hóa dữ liệu hiển thị
    $users = array_map(function($u) {
        return [
            'id' => (int)$u['id'],
            'avatar' => strtoupper(substr(($u['full_name'] ?: $u['username']), 0, 1)),
            'username' => $u['username'],
            'email' => $u['email'],
            'full_name' => $u['full_name'] ?? '',
            'phone' => $u['phone'] ?? '',
            'role' => '-', // có thể lấy role chính từ user_roles sau
            'role_display' => '-',
            'status' => $u['status'],
            'status_display' => status_display($u['status']),
            'created_at_formatted' => format_datetime($u['created_at'] ?? null),
            'last_login_formatted' => '-',
            'database_source' => 'admin'
        ];
    }, $rows);

    $total = (int)$pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
    json_response([
        'success' => true,
        'data' => [
            'users' => $users,
            'pagination' => [
                'current_page' => $page,
                'limit' => $limit,
                'total_users' => $total,
                'total_pages' => (int)ceil($total / max(1, $limit))
            ],
            'database' => 'admin',
            'table' => 'users'
        ]
    ]);
}

function handle_get_user(): void {
    require_permission('user.read');
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) json_response(['success' => false, 'message' => 'Thiếu id'], 422);
    $stmt = db()->prepare('SELECT id, username, email, full_name, phone, status, created_at, updated_at FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$u) json_response(['success' => false, 'message' => 'Không tìm thấy user'], 404);
    $u['role'] = primary_role((int)$u['id']);
    $u['role_display'] = $u['role'] ?: '-';
    json_response(['success' => true, 'data' => $u, 'database' => 'admin']);
}

function handle_update_user(): void {
    require_permission('user.update');
    parse_str($_SERVER['QUERY_STRING'] ?? '', $q);
    $id = isset($q['id']) ? (int)$q['id'] : 0;
    $emailKey = isset($q['email']) ? trim((string)$q['email']) : '';
    if ($id <= 0 && $emailKey === '') {
        json_response(['success' => false, 'message' => 'Thiếu id hoặc email'], 422);
    }
    $input = read_json_input();
    $allowed = ['username','full_name','phone','status'];
    $sets = [];
    $params = [];
    foreach ($allowed as $f) {
        if (array_key_exists($f, $input)) {
            $sets[] = "$f = ?";
            $params[] = $input[$f];
        }
    }
    if (empty($sets)) {
        json_response(['success' => false, 'message' => 'Không có dữ liệu để cập nhật'], 422);
    }
    $where = '';
    if ($id > 0) {
        $where = 'id = ?';
        $params[] = $id;
    } else {
        $where = 'email = ?';
        $params[] = $emailKey;
    }
    $sql = 'UPDATE users SET ' . implode(',', $sets) . ', updated_at = NOW() WHERE ' . $where;
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    json_response(['success' => true, 'message' => 'Cập nhật người dùng thành công']);
}

// Helpers
function status_display(?string $status): string {
    switch ($status) {
        case 'active': return 'Hoạt động';
        case 'inactive': return 'Không hoạt động';
        case 'suspended': return 'Tạm khóa';
        default: return '-';
    }
}

function format_datetime(?string $dt): string {
    if (!$dt || $dt === '0000-00-00 00:00:00') return '-';
    try {
        return (new DateTime($dt))->format('d/m/Y H:i');
    } catch (Throwable $e) {
        return $dt;
    }
}

function primary_role(int $userId): ?string {
    try {
        $sql = 'SELECT r.name FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE ur.user_id = ? LIMIT 1';
        $stmt = db()->prepare($sql);
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['name'] : null;
    } catch (Throwable $e) {
        return null;
    }
}

?>


