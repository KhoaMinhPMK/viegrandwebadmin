<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

// Only POST multipart/form-data
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

require_permission('task.photo.upload');

$taskId = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
$type = isset($_POST['type']) ? trim((string)$_POST['type']) : 'after';
$allowedTypes = ['before','during','after','signature'];
if ($taskId <= 0 || !in_array($type, $allowedTypes, true)) {
    json_response(['success' => false, 'message' => 'Thiếu task_id hoặc type không hợp lệ'], 422);
}

if (!isset($_FILES['file'])) {
    json_response(['success' => false, 'message' => 'Thiếu file upload'], 422);
}

$file = $_FILES['file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    json_response(['success' => false, 'message' => 'Lỗi upload file'], 400);
}

// Validate mime/type
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
$allowedMimes = ['image/jpeg','image/png','image/webp'];
if (!in_array($mime, $allowedMimes, true)) {
    json_response(['success' => false, 'message' => 'Định dạng ảnh không hỗ trợ'], 415);
}

// Ensure uploads dir
$uploadDir = __DIR__ . '/../uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$safeName = safe_filename($file['name']);
$targetPath = $uploadDir . '/' . $safeName;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    json_response(['success' => false, 'message' => 'Không thể lưu file'], 500);
}

// Store db record
$uid = require_auth();
$publicPath = 'uploads/' . $safeName; // relative web path from php/
$stmt = db()->prepare('INSERT INTO task_photos(task_id, type, file_path, uploaded_by) VALUES(?,?,?,?)');
$stmt->execute([$taskId, $type, $publicPath, $uid]);

json_response(['success' => true, 'message' => 'Upload thành công', 'data' => ['path' => $publicPath]]);

?>


