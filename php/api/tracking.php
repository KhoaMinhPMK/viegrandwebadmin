<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';

if ($method === 'GET' && $action === '') {
    // Public tracking by token
    $token = trim((string)($_GET['token'] ?? ''));
    if ($token === '') json_response(['success' => false, 'message' => 'Thiếu token'], 422);
    $pdo = db();
    $stmt = $pdo->prepare('SELECT task_id, expires_at FROM tracking_links WHERE token = ? LIMIT 1');
    $stmt->execute([$token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) json_response(['success' => false, 'message' => 'Token không hợp lệ'], 404);
    if (!empty($row['expires_at']) && strtotime($row['expires_at']) < time()) {
        json_response(['success' => false, 'message' => 'Token đã hết hạn'], 410);
    }
    $taskId = (int)$row['task_id'];
    $taskStmt = $pdo->prepare('SELECT id, code, customer_name, address, status, window_start, window_end, updated_at FROM tasks WHERE id = ?');
    $taskStmt->execute([$taskId]);
    $task = $taskStmt->fetch(PDO::FETCH_ASSOC);
    if (!$task) json_response(['success' => false, 'message' => 'Không tìm thấy công việc'], 404);
    $logs = fetch_logs($taskId, $pdo);
    $photos = fetch_photos($taskId, $pdo);
    json_response(['success' => true, 'data' => ['task' => $task, 'logs' => $logs, 'photos' => $photos]]);
}

if ($method === 'POST' && $action === 'generate') {
    require_permission('tracking.generate');
    $input = read_json_input();
    $taskId = (int)($input['task_id'] ?? 0);
    $ttl = (int)($input['ttl_minutes'] ?? 1440); // default 24h
    if ($taskId <= 0) json_response(['success' => false, 'message' => 'Thiếu task_id'], 422);
    $token = bin2hex(random_bytes(16));
    $expires = date('Y-m-d H:i:s', time() + ($ttl * 60));
    $stmt = db()->prepare('INSERT INTO tracking_links(task_id, token, expires_at) VALUES(?,?,?)');
    $stmt->execute([$taskId, $token, $expires]);
    json_response(['success' => true, 'data' => ['token' => $token, 'expires_at' => $expires]]);
}

json_response(['success' => false, 'message' => 'Unsupported request'], 400);

?>


