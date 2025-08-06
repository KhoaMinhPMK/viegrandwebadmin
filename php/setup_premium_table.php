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
    
    // Create premium_subscriptions_json table
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS `premium_subscriptions_json` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `start_date` datetime NOT NULL,
      `end_date` datetime NOT NULL,
      `elderly_keys` JSON DEFAULT NULL,
      `premium_key` VARCHAR(255) DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `user_id` (`user_id`),
      KEY `start_date` (`start_date`),
      KEY `end_date` (`end_date`),
      KEY `premium_key` (`premium_key`),
      FOREIGN KEY (`user_id`) REFERENCES `user` (`userId`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($createTableSQL);
    
    // Check if table was created successfully
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'premium_subscriptions_json'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        // Get table structure
        $stmt = $pdo->prepare("DESCRIBE premium_subscriptions_json");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'premium_subscriptions_json table created successfully',
            'table_exists' => true,
            'columns' => $columns
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create premium_subscriptions_json table'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
