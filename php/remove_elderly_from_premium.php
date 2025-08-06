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
$charset = 'utf8mb4';

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }
    
    $relativeUserId = $input['relative_user_id'] ?? null;
    $elderlyPrivateKey = $input['elderly_private_key'] ?? null;
    
    // Validate inputs
    if (!$relativeUserId || !$elderlyPrivateKey) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields: relative_user_id and elderly_private_key']);
        exit;
    }
    
    // First, verify that the relative user exists and has premium status
    $relativeStmt = $pdo->prepare("SELECT userId, private_key, role, premium_status FROM user WHERE userId = ? AND role = 'relative' AND premium_status = 1");
    $relativeStmt->execute([$relativeUserId]);
    $relative = $relativeStmt->fetch();
    
    if (!$relative) {
        echo json_encode(['success' => false, 'message' => 'Relative user not found or does not have premium status']);
        exit;
    }
    
    // Find the premium subscription for this relative user
    $premiumStmt = $pdo->prepare("SELECT premium_key, elderly_keys FROM premium_subscriptions_json WHERE young_person_key = ?");
    $premiumStmt->execute([$relative['private_key']]);
    $premium = $premiumStmt->fetch();
    
    if (!$premium) {
        echo json_encode(['success' => false, 'message' => 'Premium subscription not found for this user']);
        exit;
    }
    
    // Parse existing elderly_keys array
    $elderlyKeys = json_decode($premium['elderly_keys'], true) ?: [];
    
    // Check if this elderly user is in the list
    $keyIndex = array_search($elderlyPrivateKey, $elderlyKeys);
    if ($keyIndex === false) {
        echo json_encode(['success' => false, 'message' => 'This elderly user is not in the premium subscription']);
        exit;
    }
    
    // Remove the elderly private key from the array
    array_splice($elderlyKeys, $keyIndex, 1);
    
    // Update the premium_subscriptions_json table
    $updateStmt = $pdo->prepare("UPDATE premium_subscriptions_json SET elderly_keys = ? WHERE premium_key = ?");
    $updateStmt->execute([json_encode($elderlyKeys), $premium['premium_key']]);
    
    // Update the elderly user's premium status to 0 (remove premium)
    $updateElderlyStmt = $pdo->prepare("UPDATE user SET premium_status = 0 WHERE private_key = ?");
    $updateElderlyStmt->execute([$elderlyPrivateKey]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Elderly user removed from premium subscription successfully',
        'data' => [
            'premium_key' => $premium['premium_key'],
            'elderly_count' => count($elderlyKeys)
        ]
    ]);
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
