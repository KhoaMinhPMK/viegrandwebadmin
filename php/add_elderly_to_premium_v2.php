<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Database configuration
$host = '127.0.0.1';
$dbname = 'viegrand';
$username = 'root';
$password = '';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $relativeUserId = $input['relative_user_id'] ?? null;
    $elderlyPrivateKey = $input['elderly_private_key'] ?? null;
    
    // Validate inputs
    if (!$relativeUserId || !$elderlyPrivateKey) {
        throw new Exception('Thiếu thông tin bắt buộc: relative_user_id và elderly_private_key');
    }
    
    // Trim the private key
    $elderlyPrivateKey = trim($elderlyPrivateKey);
    
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Start transaction
    $pdo->beginTransaction();
    
    // 1. Verify that the relative user exists and has role 'relative'
    $stmt = $pdo->prepare("SELECT userId, role FROM user WHERE userId = ?");
    $stmt->execute([$relativeUserId]);
    $relativeUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$relativeUser) {
        throw new Exception('Không tìm thấy người dùng relative');
    }
    
    if ($relativeUser['role'] !== 'relative') {
        throw new Exception('Chỉ người dùng có vai trò "relative" mới có thể thêm người cao tuổi');
    }
    
    // 2. Find elderly user by private_key
    $stmt = $pdo->prepare("SELECT userId, userName, phone, premium_status FROM user WHERE private_key = ?");
    $stmt->execute([$elderlyPrivateKey]);
    $elderlyUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$elderlyUser) {
        throw new Exception('Không tìm thấy người cao tuổi với private key này');
    }
    
    // 3. Check if relative user has an active premium subscription
    $stmt = $pdo->prepare("
        SELECT id, start_date, end_date, elderly_keys 
        FROM premium_subscriptions_json 
        WHERE user_id = ? AND end_date > NOW()
        ORDER BY end_date DESC 
        LIMIT 1
    ");
    $stmt->execute([$relativeUserId]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$subscription) {
        throw new Exception('Người dùng relative không có gói Premium đang hoạt động');
    }
    
    // 4. Parse existing elderly_keys
    $elderlyKeys = [];
    if (!empty($subscription['elderly_keys'])) {
        $elderlyKeys = json_decode($subscription['elderly_keys'], true);
        if (!is_array($elderlyKeys)) {
            $elderlyKeys = [];
        }
    }
    
    // 5. Check if elderly is already in the list
    if (in_array($elderlyPrivateKey, $elderlyKeys)) {
        throw new Exception('Người cao tuổi này đã có trong gói Premium');
    }
    
    // 6. Add elderly private key to the list
    $elderlyKeys[] = $elderlyPrivateKey;
    
    // 7. Update the premium subscription with new elderly_keys
    $stmt = $pdo->prepare("
        UPDATE premium_subscriptions_json 
        SET elderly_keys = ? 
        WHERE id = ?
    ");
    $stmt->execute([json_encode($elderlyKeys), $subscription['id']]);
    
    // 8. Update elderly user status to premium
    $stmt = $pdo->prepare("UPDATE user SET premium_status = 1 WHERE userId = ?");
    $stmt->execute([$elderlyUser['userId']]);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Thêm người cao tuổi vào gói Premium thành công',
        'data' => [
            'elderly_user' => [
                'id' => $elderlyUser['userId'],
                'full_name' => $elderlyUser['userName'],
                'phone' => $elderlyUser['phone'],
                'private_key' => $elderlyPrivateKey
            ],
            'elderly_keys_count' => count($elderlyKeys)
        ]
    ]);

} catch (Exception $e) {
    // Rollback transaction if it was started
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    error_log("Add elderly to premium error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
