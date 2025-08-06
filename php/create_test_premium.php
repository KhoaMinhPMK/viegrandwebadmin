<?php
header('Content-Type: application/json');

// Database configuration
$host = '127.0.0.1';
$dbname = 'viegrand';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create a test premium subscription for user ID 65 (the relative user we found)
    $stmt = $pdo->prepare("
        INSERT INTO premium_subscriptions_json 
        (user_id, start_date, end_date, elderly_keys, created_at) 
        VALUES (?, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), ?, NOW())
        ON DUPLICATE KEY UPDATE 
        end_date = DATE_ADD(NOW(), INTERVAL 30 DAY),
        elderly_keys = ?
    ");
    
    $elderlyKeys = json_encode([]);  // Start with empty elderly keys
    $stmt->execute([65, $elderlyKeys, $elderlyKeys]);
    
    // Also update the user's premium status
    $stmt = $pdo->prepare("
        UPDATE user 
        SET premium_status = 1, 
            premium_start_date = NOW(), 
            premium_end_date = DATE_ADD(NOW(), INTERVAL 30 DAY)
        WHERE userId = 65
    ");
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Test premium subscription created for user 65',
        'subscription_id' => $pdo->lastInsertId()
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
