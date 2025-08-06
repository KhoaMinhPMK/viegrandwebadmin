<?php
// Disable all error output to prevent JSON corruption
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    $userId = $_GET['user_id'] ?? null;
    
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'Missing user_id parameter']);
        exit;
    }
    
    // First, get user details and private_key
    $userStmt = $pdo->prepare("SELECT userId, private_key, role, premium_status FROM user WHERE userId = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    if ($user['premium_status'] != 1) {
        echo json_encode(['success' => false, 'message' => 'User does not have premium status']);
        exit;
    }
    
    $premium = null;
    $isElderly = false;
    
    if ($user['role'] === 'relative') {
        // For relative users, find subscription where they are the young_person_key
        $premiumStmt = $pdo->prepare("SELECT premium_key, young_person_key, elderly_keys, start_date, end_date, note FROM premium_subscriptions_json WHERE young_person_key = ?");
        $premiumStmt->execute([$user['private_key']]);
        $premium = $premiumStmt->fetch();
    } else if ($user['role'] === 'elderly') {
        // For elderly users, find subscription where their private_key is in elderly_keys JSON array
        $premiumStmt = $pdo->prepare("SELECT premium_key, young_person_key, elderly_keys, start_date, end_date, note FROM premium_subscriptions_json WHERE JSON_CONTAINS(elderly_keys, JSON_QUOTE(?))");
        $premiumStmt->execute([$user['private_key']]);
        $premium = $premiumStmt->fetch();
        $isElderly = true;
    }
    $premium = $premiumStmt->fetch();
    
    if (!$premium) {
        echo json_encode(['success' => false, 'message' => 'Premium subscription not found']);
        exit;
    }
    
    // Parse elderly_keys array
    $elderlyKeys = json_decode($premium['elderly_keys'], true);
    if (!is_array($elderlyKeys)) {
        $elderlyKeys = [];
    }
    
    // Calculate time remaining
    $timeRemaining = null;
    $status = 'unknown';
    if ($premium['end_date']) {
        $now = new DateTime();
        $endDate = new DateTime($premium['end_date']);
        
        if ($endDate > $now) {
            $timeDiff = $endDate->getTimestamp() - $now->getTimestamp();
            $daysRemaining = ceil($timeDiff / (24 * 60 * 60));
            $timeRemaining = $daysRemaining;
            $status = 'active';
        } else {
            $timeRemaining = 0;
            $status = 'expired';
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Premium subscription details retrieved successfully',
        'data' => [
            'user_id' => $user['userId'],
            'user_role' => $user['role'],
            'premium_key' => $premium['premium_key'],
            'start_date' => $premium['start_date'],
            'end_date' => $premium['end_date'],
            'elderly_count' => count($elderlyKeys),
            'elderly_keys' => $elderlyKeys,
            'time_remaining_days' => $timeRemaining,
            'status' => $status,
            'note' => $premium['note'],
            'is_elderly' => $isElderly
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
