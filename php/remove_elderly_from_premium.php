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

// Include database configuration
require_once 'config.php';

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
    $stmt = $pdo->prepare("SELECT id, role FROM viegrand.user WHERE id = ?");
    $stmt->execute([$relativeUserId]);
    $relativeUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$relativeUser) {
        throw new Exception('Không tìm thấy người dùng relative');
    }
    
    if ($relativeUser['role'] !== 'relative') {
        throw new Exception('Chỉ người dùng có vai trò "relative" mới có thể xóa người cao tuổi');
    }
    
    // 2. Find elderly user by private_key
    $stmt = $pdo->prepare("SELECT id, full_name, phone FROM viegrand.user WHERE private_key = ?");
    $stmt->execute([$elderlyPrivateKey]);
    $elderlyUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$elderlyUser) {
        throw new Exception('Không tìm thấy người cao tuổi với private key này');
    }
    
    // 3. Get the premium subscription
    $stmt = $pdo->prepare("
        SELECT id, start_date, end_date, elderly_keys 
        FROM viegrand.premium_subscriptions_json 
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
    
    // 5. Check if elderly is in the list
    $keyIndex = array_search($elderlyPrivateKey, $elderlyKeys);
    if ($keyIndex === false) {
        throw new Exception('Người cao tuổi này không có trong gói Premium');
    }
    
    // 6. Remove elderly private key from the list
    array_splice($elderlyKeys, $keyIndex, 1);
    
    // 7. Update the premium subscription with new elderly_keys
    $stmt = $pdo->prepare("
        UPDATE viegrand.premium_subscriptions_json 
        SET elderly_keys = ? 
        WHERE id = ?
    ");
    $stmt->execute([json_encode($elderlyKeys), $subscription['id']]);
    
    // 8. Check if elderly user is still in any other premium subscription
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM viegrand.premium_subscriptions_json 
        WHERE elderly_keys LIKE ? AND end_date > NOW()
    ");
    $stmt->execute(['%"' . $elderlyPrivateKey . '"%']);
    $stillInPremium = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
    
    // 9. If elderly is not in any premium subscription, update status to regular
    if (!$stillInPremium) {
        $stmt = $pdo->prepare("UPDATE viegrand.user SET status = 'regular' WHERE id = ?");
        $stmt->execute([$elderlyUser['id']]);
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã xóa người cao tuổi khỏi gói Premium',
        'data' => [
            'elderly_user' => [
                'id' => $elderlyUser['id'],
                'full_name' => $elderlyUser['full_name'],
                'phone' => $elderlyUser['phone'],
                'private_key' => $elderlyPrivateKey
            ],
            'elderly_keys_count' => count($elderlyKeys),
            'elderly_status_updated' => !$stillInPremium ? 'regular' : 'premium'
        ]
    ]);

} catch (Exception $e) {
    // Rollback transaction if it was started
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    error_log("Remove elderly from premium error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
