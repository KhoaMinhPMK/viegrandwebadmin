<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        if ($action === 'list_permissions') {
            list_permissions();
        } else {
            list_roles();
        }
        break;
    case 'POST':
        if ($action === 'assign') {
            assign_role_to_user();
        } else {
            create_role();
        }
        break;
    case 'PUT':
        update_role();
        break;
    case 'DELETE':
        delete_role();
        break;
    default:
        json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

function list_roles(): void {
    require_permission('role.read');
    $rows = db()->query('SELECT id, name, description FROM roles ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
    json_response(['success' => true, 'data' => $rows]);
}

function list_permissions(): void {
    require_permission('role.read');
    $rows = db()->query('SELECT id, name, description FROM permissions ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
    json_response(['success' => true, 'data' => $rows]);
}

function create_role(): void {
    require_permission('role.create');
    $input = read_json_input();
    $name = trim($input['name'] ?? '');
    $description = trim($input['description'] ?? '');
    $permissions = $input['permissions'] ?? [];
    if ($name === '') {
        json_response(['success' => false, 'message' => 'Thiếu tên vai trò'], 422);
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO roles(name, description) VALUES(?, ?)');
        $stmt->execute([$name, $description]);
        $roleId = (int)$pdo->lastInsertId();
        if (is_array($permissions) && !empty($permissions)) {
            $permStmt = $pdo->prepare('SELECT id FROM permissions WHERE name = ?');
            $insStmt = $pdo->prepare('INSERT IGNORE INTO role_permissions(role_id, permission_id) VALUES(?, ?)');
            foreach ($permissions as $permName) {
                $permStmt->execute([$permName]);
                $perm = $permStmt->fetch(PDO::FETCH_ASSOC);
                if ($perm) {
                    $insStmt->execute([$roleId, (int)$perm['id']]);
                }
            }
        }
        $pdo->commit();
        json_response(['success' => true, 'message' => 'Tạo vai trò thành công', 'data' => ['id' => $roleId]]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        json_response(['success' => false, 'message' => 'Lỗi tạo vai trò'], 500);
    }
}

function update_role(): void {
    require_permission('role.update');
    parse_str($_SERVER['QUERY_STRING'] ?? '', $q);
    $id = isset($q['id']) ? (int)$q['id'] : 0;
    if ($id <= 0) json_response(['success' => false, 'message' => 'Thiếu id'], 422);
    $input = read_json_input();
    $name = isset($input['name']) ? trim((string)$input['name']) : null;
    $description = isset($input['description']) ? trim((string)$input['description']) : null;
    $permissions = $input['permissions'] ?? null; // array or null
    $pdo = db();
    $pdo->beginTransaction();
    try {
        if ($name !== null || $description !== null) {
            $sets = [];
            $params = [];
            if ($name !== null) { $sets[] = 'name = ?'; $params[] = $name; }
            if ($description !== null) { $sets[] = 'description = ?'; $params[] = $description; }
            $params[] = $id;
            $sql = 'UPDATE roles SET ' . implode(',', $sets) . ' WHERE id = ?';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        }
        if (is_array($permissions)) {
            // reset and insert
            $pdo->prepare('DELETE FROM role_permissions WHERE role_id = ?')->execute([$id]);
            if (!empty($permissions)) {
                $permStmt = $pdo->prepare('SELECT id FROM permissions WHERE name = ?');
                $insStmt = $pdo->prepare('INSERT IGNORE INTO role_permissions(role_id, permission_id) VALUES(?, ?)');
                foreach ($permissions as $permName) {
                    $permStmt->execute([$permName]);
                    $perm = $permStmt->fetch(PDO::FETCH_ASSOC);
                    if ($perm) {
                        $insStmt->execute([$id, (int)$perm['id']]);
                    }
                }
            }
        }
        $pdo->commit();
        json_response(['success' => true, 'message' => 'Cập nhật vai trò thành công']);
    } catch (Throwable $e) {
        $pdo->rollBack();
        json_response(['success' => false, 'message' => 'Lỗi cập nhật vai trò'], 500);
    }
}

function delete_role(): void {
    require_permission('role.delete');
    parse_str($_SERVER['QUERY_STRING'] ?? '', $q);
    $id = isset($q['id']) ? (int)$q['id'] : 0;
    if ($id <= 0) json_response(['success' => false, 'message' => 'Thiếu id'], 422);
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM role_permissions WHERE role_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM user_roles WHERE role_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM roles WHERE id = ?')->execute([$id]);
        $pdo->commit();
        json_response(['success' => true, 'message' => 'Xóa vai trò thành công']);
    } catch (Throwable $e) {
        $pdo->rollBack();
        json_response(['success' => false, 'message' => 'Lỗi xóa vai trò'], 500);
    }
}

function assign_role_to_user(): void {
    require_permission('permission.assign');
    $input = read_json_input();
    $userId = (int)($input['user_id'] ?? 0);
    $roleName = trim((string)($input['role_name'] ?? ''));
    if ($userId <= 0 || $roleName === '') {
        json_response(['success' => false, 'message' => 'Thiếu user_id hoặc role_name'], 422);
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT id FROM roles WHERE name = ? LIMIT 1');
        $stmt->execute([$roleName]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$role) json_response(['success' => false, 'message' => 'Không tìm thấy vai trò'], 404);
        $pdo->prepare('INSERT IGNORE INTO user_roles(user_id, role_id) VALUES(?, ?)')->execute([$userId, (int)$role['id']]);
        $pdo->commit();
        json_response(['success' => true, 'message' => 'Gán vai trò thành công']);
    } catch (Throwable $e) {
        $pdo->rollBack();
        json_response(['success' => false, 'message' => 'Lỗi gán vai trò'], 500);
    }
}

?>


