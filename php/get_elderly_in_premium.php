<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Include database configuration
require_once 'config.php';

try {
    $userId = $_GET['user_id'] ?? null;
    
    if (!$userId) {
        throw new Exception('Thiếu user_id');
    }
    
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Verify that the user exists and has role 'relative'
    $stmt = $pdo->prepare("SELECT id, role FROM viegrand.user WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('Không tìm thấy người dùng');
    }
    
    if ($user['role'] !== 'relative') {
        throw new Exception('Chỉ người dùng có vai trò "relative" mới có thể xem danh sách người cao tuổi');
    }
    
    // 2. Get the premium subscription and elderly_keys
    $stmt = $pdo->prepare("
        SELECT id, start_date, end_date, elderly_keys 
        FROM viegrand.premium_subscriptions_json 
        WHERE user_id = ? AND end_date > NOW()
        ORDER BY end_date DESC 
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$subscription) {
        // No active premium subscription
        echo json_encode([
            'success' => true,
            'message' => 'Không có gói Premium đang hoạt động',
            'data' => []
        ]);
        exit;
    }
    
    // 3. Parse elderly_keys
    $elderlyKeys = [];
    if (!empty($subscription['elderly_keys'])) {
        $elderlyKeys = json_decode($subscription['elderly_keys'], true);
        if (!is_array($elderlyKeys)) {
            $elderlyKeys = [];
        }
    }
    
    if (empty($elderlyKeys)) {
        // No elderly users in subscription
        echo json_encode([
            'success' => true,
            'message' => 'Chưa có người cao tuổi nào trong gói Premium',
            'data' => []
        ]);
        exit;
    }
    
    // 4. Get details of elderly users
    $elderlyUsers = [];
    foreach ($elderlyKeys as $privateKey) {
        $stmt = $pdo->prepare("
            SELECT id, full_name, phone, status, private_key
            FROM viegrand.user 
            WHERE private_key = ?
        ");
        $stmt->execute([$privateKey]);
        $elderlyUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($elderlyUser) {
            $elderlyUsers[] = [
                'id' => $elderlyUser['id'],
                'full_name' => $elderlyUser['full_name'],
                'phone' => $elderlyUser['phone'],
                'status' => $elderlyUser['status'],
                'private_key' => $elderlyUser['private_key']
            ];
        } else {
            // Private key exists in elderly_keys but user not found
            // This could happen if user was deleted
            $elderlyUsers[] = [
                'id' => null,
                'full_name' => 'Người dùng không tồn tại',
                'phone' => null,
                'status' => 'unknown',
                'private_key' => $privateKey
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Lấy danh sách người cao tuổi thành công',
        'data' => $elderlyUsers
    ]);

} catch (Exception $e) {
    error_log("Get elderly in premium error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
