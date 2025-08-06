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
    
    // Get form data
    $userId = $_POST['userId'] ?? null;
    $newEndDate = $_POST['newEndDate'] ?? null;
    
    if (!$userId || !$newEndDate) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields: userId and newEndDate']);
        exit;
    }
    
    // Validate date format
    $dateTime = DateTime::createFromFormat('Y-m-d\TH:i', $newEndDate);
    if (!$dateTime) {
        echo json_encode(['success' => false, 'message' => 'Invalid date format']);
        exit;
    }
    
    // Convert to MySQL datetime format
    $formattedEndDate = $dateTime->format('Y-m-d H:i:s');
    
    // First, get user's private_key and role
    $userStmt = $pdo->prepare("SELECT private_key, role FROM user WHERE userId = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Find the subscription - different logic for relative vs elderly users
    $subscription = null;
    if ($user['role'] === 'relative') {
        // For relative users, find subscription where they are the young_person_key
        $findStmt = $pdo->prepare("SELECT premium_key FROM premium_subscriptions_json WHERE young_person_key = ?");
        $findStmt->execute([$user['private_key']]);
        $subscription = $findStmt->fetch();
    } else if ($user['role'] === 'elderly') {
        // For elderly users, find subscription where their private_key is in elderly_keys
        $findStmt = $pdo->prepare("SELECT premium_key FROM premium_subscriptions_json WHERE JSON_CONTAINS(elderly_keys, JSON_QUOTE(?))");
        $findStmt->execute([$user['private_key']]);
        $subscription = $findStmt->fetch();
    }
    
    if (!$subscription) {
        echo json_encode(['success' => false, 'message' => 'Premium subscription not found for this user']);
        exit;
    }
    
    // Update the premium subscription end_date using premium_key
    $updateStmt = $pdo->prepare("UPDATE premium_subscriptions_json SET end_date = ? WHERE premium_key = ?");
    $success = $updateStmt->execute([$formattedEndDate, $subscription['premium_key']]);
    
    if (!$success) {
        echo json_encode(['success' => false, 'message' => 'Failed to update premium end date']);
        exit;
    }
    
    // Also update the user table for consistency
    $updateUserStmt = $pdo->prepare("UPDATE user SET premium_end_date = ? WHERE userId = ?");
    $updateUserStmt->execute([$formattedEndDate, $userId]);
    
    // Get updated subscription data to return
    $updatedStmt = $pdo->prepare("SELECT start_date, end_date FROM premium_subscriptions_json WHERE premium_key = ?");
    $updatedStmt->execute([$subscription['premium_key']]);
    $updatedData = $updatedStmt->fetch();
    
    echo json_encode([
        'success' => true,
        'message' => 'Premium end date updated successfully',
        'data' => [
            'premium_start_date' => $updatedData['start_date'],
            'premium_end_date' => $updatedData['end_date']
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
