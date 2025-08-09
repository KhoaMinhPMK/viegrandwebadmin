<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($method) {
    case 'POST':
        if ($action === 'login') {
            handle_login();
        } elseif ($action === 'logout') {
            handle_logout();
        } else {
            json_response(['success' => false, 'message' => 'Unsupported action'], 400);
        }
        break;
    case 'GET':
        if ($action === 'me') {
            handle_me();
        } else {
            json_response(['success' => false, 'message' => 'Unsupported action'], 400);
        }
        break;
    default:
        json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

function handle_login(): void {
    $input = read_json_input();
    $username = trim($input['username'] ?? ($_POST['username'] ?? ''));
    $password = (string)($input['password'] ?? ($_POST['password'] ?? ''));

    if ($username === '' || $password === '') {
        json_response(['success' => false, 'message' => 'Thiếu username hoặc password'], 422);
    }

    $stmt = db()->prepare('SELECT id, username, email, password_hash, password AS legacy_password, full_name, status FROM users WHERE username = ? OR email = ? LIMIT 1');
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        json_response(['success' => false, 'message' => 'Sai thông tin đăng nhập'], 401);
    }

    if (($user['status'] ?? 'active') !== 'active') {
        json_response(['success' => false, 'message' => 'Tài khoản không hoạt động'], 403);
    }

    $hash = $user['password_hash'] ?? null;
    $legacy = $user['legacy_password'] ?? null;
    $ok = false;
    if ($hash) {
        $ok = Utils::verifyPassword($password, $hash);
    } elseif ($legacy) {
        // chấp nhận cột legacy 'password' nếu tồn tại (đã được lưu bcrypt)
        $ok = Utils::verifyPassword($password, $legacy);
    }
    if (!$ok) {
        json_response(['success' => false, 'message' => 'Sai thông tin đăng nhập'], 401);
    }

    $_SESSION['user_id'] = (int)$user['id'];

    Utils::logActivity('User login: ' . $user['username']);

    json_response([
        'success' => true,
        'data' => [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'full_name' => $user['full_name'] ?? '',
            'roles' => get_user_roles((int)$user['id'])
        ],
        'message' => 'Đăng nhập thành công'
    ]);
}

function handle_logout(): void {
    $uid = current_user_id();
    if ($uid) {
        Utils::logActivity('User logout: ' . $uid);
    }
    session_unset();
    session_destroy();
    json_response(['success' => true, 'message' => 'Đăng xuất thành công']);
}

function handle_me(): void {
    $uid = require_auth();
    $user = get_user_by_id($uid);
    if (!$user) {
        json_response(['success' => false, 'message' => 'Không tìm thấy user'], 404);
    }
    $user['roles'] = get_user_roles($uid);
    json_response(['success' => true, 'data' => $user]);
}

?>


