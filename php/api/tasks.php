<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        if ($action === 'get') {
            get_task();
        } else {
            list_tasks();
        }
        break;
    case 'POST':
        if ($action === 'assign') {
            assign_task();
        } elseif ($action === 'status') {
            update_status();
        } elseif ($action === 'create') {
            create_task();
        } elseif ($action === 'cancel') {
            cancel_task();
        } else {
            json_response(['success' => false, 'message' => 'Unsupported action'], 400);
        }
        break;
    case 'PUT':
        update_task();
        break;
    default:
        json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

function list_tasks(): void {
    require_permission('task.read');
    [$page, $limit, $offset] = paginate_params();
    $q = trim((string)($_GET['q'] ?? ''));
    $params = [];
    $where = '';
    if ($q !== '') {
        $where = 'WHERE t.code LIKE ? OR t.customer_name LIKE ? OR t.address LIKE ?';
        $kw = "%$q%";
        $params = [$kw, $kw, $kw];
    }
    $pdo = db();
    $sql = "SELECT SQL_CALC_FOUND_ROWS t.* FROM tasks t $where ORDER BY t.id DESC LIMIT $limit OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total = (int)$pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
    json_response(['success' => true, 'data' => ['tasks' => $rows, 'pagination' => ['current_page' => $page, 'limit' => $limit, 'total' => $total]]]);
}

function get_task(): void {
    require_permission('task.read');
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) json_response(['success' => false, 'message' => 'Thiếu id'], 422);
    $pdo = db();
    $task = fetch_task($id, $pdo);
    if (!$task) json_response(['success' => false, 'message' => 'Không tìm thấy công việc'], 404);
    $task['assignments'] = fetch_assignments($id, $pdo);
    $task['photos'] = fetch_photos($id, $pdo);
    $task['logs'] = fetch_logs($id, $pdo);
    json_response(['success' => true, 'data' => $task]);
}

function create_task(): void {
    require_permission('task.create');
    $input = read_json_input();
    $code = trim($input['code'] ?? '');
    $customer_name = trim($input['customer_name'] ?? '');
    $customer_phone = trim($input['customer_phone'] ?? '');
    $address = trim($input['address'] ?? '');
    $window_start = $input['window_start'] ?? null;
    $window_end = $input['window_end'] ?? null;
    $note = trim($input['note'] ?? '');
    if ($customer_name === '' || $address === '') {
        json_response(['success' => false, 'message' => 'Thiếu tên khách hoặc địa chỉ'], 422);
    }
    $pdo = db();
    $stmt = $pdo->prepare('INSERT INTO tasks(code, customer_name, customer_phone, address, window_start, window_end, note, status, created_by) VALUES(?,?,?,?,?,?,?,?,?)');
    $status = 'scheduled';
    $createdBy = require_auth();
    $stmt->execute([
        $code ?: null, $customer_name, $customer_phone, $address, $window_start, $window_end, $note, $status, $createdBy
    ]);
    $taskId = (int)$pdo->lastInsertId();
    add_log($taskId, $status, $createdBy, 'Task created');
    json_response(['success' => true, 'message' => 'Tạo công việc thành công', 'data' => ['id' => $taskId]]);
}

function update_task(): void {
    require_permission('task.update');
    parse_str($_SERVER['QUERY_STRING'] ?? '', $q);
    $id = isset($q['id']) ? (int)$q['id'] : 0;
    if ($id <= 0) json_response(['success' => false, 'message' => 'Thiếu id'], 422);
    $input = read_json_input();
    $sets = [];
    $params = [];
    $fields = ['customer_name','customer_phone','address','window_start','window_end','note'];
    foreach ($fields as $f) {
        if (array_key_exists($f, $input)) {
            $sets[] = "$f = ?";
            $params[] = $input[$f];
        }
    }
    if (empty($sets)) {
        json_response(['success' => false, 'message' => 'Không có dữ liệu để cập nhật'], 422);
    }
    $params[] = $id;
    $sql = 'UPDATE tasks SET ' . implode(',', $sets) . ', updated_at = NOW() WHERE id = ?';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    json_response(['success' => true, 'message' => 'Cập nhật công việc thành công']);
}

function assign_task(): void {
    require_permission('task.assign');
    $input = read_json_input();
    $taskId = (int)($input['task_id'] ?? 0);
    $technicianId = (int)($input['technician_id'] ?? 0);
    if ($taskId <= 0 || $technicianId <= 0) {
        json_response(['success' => false, 'message' => 'Thiếu task_id hoặc technician_id'], 422);
    }
    $pdo = db();
    $pdo->prepare('INSERT IGNORE INTO task_assignments(task_id, technician_id) VALUES(?, ?)')->execute([$taskId, $technicianId]);
    add_log($taskId, 'scheduled', require_auth(), 'Assigned to technician #' . $technicianId);
    json_response(['success' => true, 'message' => 'Phân công thành công']);
}

function update_status(): void {
    require_permission('task.update');
    $input = read_json_input();
    $taskId = (int)($input['task_id'] ?? 0);
    $status = trim((string)($input['status'] ?? ''));
    $allowed = ['scheduled','en_route','on_site','in_progress','completed','failed','canceled'];
    if ($taskId <= 0 || !in_array($status, $allowed, true)) {
        json_response(['success' => false, 'message' => 'Thiếu task_id hoặc trạng thái không hợp lệ'], 422);
    }
    $pdo = db();
    $pdo->prepare('UPDATE tasks SET status = ?, updated_at = NOW() WHERE id = ?')->execute([$status, $taskId]);
    add_log($taskId, $status, require_auth(), 'Status changed to ' . $status);
    json_response(['success' => true, 'message' => 'Cập nhật trạng thái thành công']);
}

function cancel_task(): void {
    require_permission('task.cancel');
    $input = read_json_input();
    $taskId = (int)($input['task_id'] ?? 0);
    if ($taskId <= 0) json_response(['success' => false, 'message' => 'Thiếu task_id'], 422);
    $pdo = db();
    $pdo->prepare("UPDATE tasks SET status = 'canceled', updated_at = NOW() WHERE id = ?")->execute([$taskId]);
    add_log($taskId, 'canceled', require_auth(), 'Task canceled');
    json_response(['success' => true, 'message' => 'Đã hủy công việc']);
}

// Helpers
function fetch_task(int $id, PDO $pdo): ?array {
    $stmt = $pdo->prepare('SELECT * FROM tasks WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function fetch_assignments(int $taskId, PDO $pdo): array {
    $sql = 'SELECT ur.user_id, u.full_name, u.username FROM task_assignments ur JOIN users u ON ur.technician_id = u.id WHERE ur.task_id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$taskId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetch_photos(int $taskId, PDO $pdo): array {
    $stmt = $pdo->prepare('SELECT id, type, file_path, uploaded_by, uploaded_at FROM task_photos WHERE task_id = ? ORDER BY id DESC');
    $stmt->execute([$taskId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetch_logs(int $taskId, PDO $pdo): array {
    $stmt = $pdo->prepare('SELECT id, status, note, changed_by, changed_at FROM task_status_logs WHERE task_id = ? ORDER BY id DESC');
    $stmt->execute([$taskId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function add_log(int $taskId, string $status, int $by, string $note = ''): void {
    $stmt = db()->prepare('INSERT INTO task_status_logs(task_id, status, changed_by, note) VALUES(?,?,?,?)');
    $stmt->execute([$taskId, $status, $by, $note]);
}

?>


