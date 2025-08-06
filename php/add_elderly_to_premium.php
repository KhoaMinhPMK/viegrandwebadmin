<?php
// Disable all error output to prevent JSON corruption
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
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
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
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
    
    // Verify that the elderly user exists and has the correct role
    $elderlyStmt = $pdo->prepare("SELECT userId, userName FROM user WHERE private_key = ? AND role = 'elderly'");
    $elderlyStmt->execute([$elderlyPrivateKey]);
    $elderly = $elderlyStmt->fetch();
    
    if (!$elderly) {
        echo json_encode(['success' => false, 'message' => 'Elderly user not found or does not have elderly role']);
        exit;
    }
    
    // Check if this elderly person is already in another premium subscription (privacy protection)
    $existingPremiumStmt = $pdo->prepare("SELECT premium_key, young_person_key FROM premium_subscriptions_json WHERE JSON_CONTAINS(elderly_keys, JSON_QUOTE(?))");
    $existingPremiumStmt->execute([$elderlyPrivateKey]);
    $existingPremium = $existingPremiumStmt->fetch();
    
    if ($existingPremium) {
        // Check if it's the same premium subscription (trying to add again to same premium)
        if ($existingPremium['premium_key'] === $premium['premium_key']) {
            echo json_encode(['success' => false, 'message' => 'Người cao tuổi này đã có trong gói Premium này rồi']);
            exit;
        }
        
        // Different premium subscription - privacy violation
        $existingYoungStmt = $pdo->prepare("SELECT userName FROM user WHERE private_key = ?");
        $existingYoungStmt->execute([$existingPremium['young_person_key']]);
        $existingYoung = $existingYoungStmt->fetch();
        
        $existingYoungName = $existingYoung ? $existingYoung['userName'] : 'Unknown User';
        
        echo json_encode([
            'success' => false, 
            'message' => "Người cao tuổi này đã được thêm vào gói Premium của '{$existingYoungName}'. Mỗi người cao tuổi chỉ có thể tham gia một gói Premium duy nhất để bảo vệ quyền riêng tư."
        ]);
        exit;
    }
    
    // Parse existing elderly_keys array
    $elderlyKeys = json_decode($premium['elderly_keys'], true);
    if (!is_array($elderlyKeys)) {
        $elderlyKeys = [];
    }
    
    // Add the elderly private key to the array
    $elderlyKeys[] = $elderlyPrivateKey;
    
    // Update the premium_subscriptions_json table
    $updateStmt = $pdo->prepare("UPDATE premium_subscriptions_json SET elderly_keys = ? WHERE premium_key = ?");
    $success = $updateStmt->execute([json_encode($elderlyKeys), $premium['premium_key']]);
    
    if (!$success) {
        echo json_encode(['success' => false, 'message' => 'Failed to update premium subscription']);
        exit;
    }
    
    // Update the elderly user's premium status
    $updateElderlyStmt = $pdo->prepare("UPDATE user SET premium_status = 1 WHERE private_key = ?");
    $updateElderlyStmt->execute([$elderlyPrivateKey]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Elderly user added to premium subscription successfully',
        'data' => [
            'premium_key' => $premium['premium_key'],
            'elderly_user' => $elderly['userName'],
            'elderly_count' => count($elderlyKeys)
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
