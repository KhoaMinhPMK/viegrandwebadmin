<?php
// Common helpers for API endpoints: auth, RBAC, JSON response, DB helpers

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

// Ensure JSON header for all API responses by default
header('Content-Type: application/json; charset=utf-8');

/**
 * Send JSON response and exit
 */
function json_response(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Get PDO connection for admin DB
 */
function db(): PDO {
    return Database::getInstance()->getConnection();
}

/**
 * Get PDO connection for main DB
 */
function db_main(): PDO {
    return Database::getMainInstance()->getConnection();
}

/**
 * Read JSON body into associative array
 */
function read_json_input(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * Simple session-based auth
 */
function current_user_id(): ?int {
    if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
        return (int)$_SESSION['user_id'];
    }
    // Optional: Support Authorization: Bearer <token> (future)
    return null;
}

function require_auth(): int {
    $uid = current_user_id();
    if (!$uid) {
        json_response(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    return $uid;
}

function get_user_by_id(int $userId): ?array {
    $stmt = db()->prepare('SELECT id, username, email, full_name, phone, status, created_at, updated_at FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * RBAC helpers
 */
function get_user_roles(int $userId): array {
    $sql = 'SELECT r.name FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE ur.user_id = ?';
    $stmt = db()->prepare($sql);
    $stmt->execute([$userId]);
    return array_values(array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name'));
}

function get_role_permissions(array $roleNames): array {
    if (empty($roleNames)) return [];
    $in = str_repeat('?,', count($roleNames) - 1) . '?';
    $sql = "SELECT DISTINCT p.name FROM roles r JOIN role_permissions rp ON r.id = rp.role_id JOIN permissions p ON p.id = rp.permission_id WHERE r.name IN ($in)";
    $stmt = db()->prepare($sql);
    $stmt->execute($roleNames);
    return array_values(array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name'));
}

function user_has_permission(int $userId, string $permission): bool {
    $roles = get_user_roles($userId);
    if (in_array('super_admin', $roles, true)) {
        return true;
    }
    if (empty($roles)) return false;
    $perms = get_role_permissions($roles);
    return in_array($permission, $perms, true);
}

function require_permission(string $permission): void {
    $uid = require_auth();
    if (!user_has_permission($uid, $permission)) {
        json_response(['success' => false, 'message' => 'Forbidden: missing permission ' . $permission], 403);
    }
}

/**
 * Pagination helper
 */
function paginate_params(): array {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 10;
    $offset = ($page - 1) * $limit;
    return [$page, $limit, $offset];
}

/**
 * Utility to generate safe file name
 */
function safe_filename(string $original): string {
    $ext = pathinfo($original, PATHINFO_EXTENSION);
    $base = bin2hex(random_bytes(8));
    $ext = $ext ? ('.' . strtolower(preg_replace('/[^a-z0-9]/i', '', $ext))) : '';
    return $base . '_' . time() . $ext;
}

?>


