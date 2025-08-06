<?php
header('Content-Type: application/json');

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
    
    // Check if premium_subscriptions_json table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'premium_subscriptions_json'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        // Create the table
        $createTableSQL = "
            CREATE TABLE `premium_subscriptions_json` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `premium_key` varchar(255) NOT NULL UNIQUE,
                `young_person_key` varchar(255) NOT NULL,
                `elderly_keys` JSON DEFAULT NULL,
                `start_date` datetime DEFAULT NULL,
                `end_date` datetime DEFAULT NULL,
                `note` text DEFAULT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                INDEX `idx_premium_key` (`premium_key`),
                INDEX `idx_young_person_key` (`young_person_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->exec($createTableSQL);
        
        echo json_encode([
            'success' => true,
            'message' => 'premium_subscriptions_json table created successfully',
            'action' => 'created'
        ]);
    } else {
        // Table exists, check its structure
        $stmt = $pdo->prepare("DESCRIBE premium_subscriptions_json");
        $stmt->execute();
        $structure = $stmt->fetchAll();
        
        $columns = array_column($structure, 'Field');
        
        echo json_encode([
            'success' => true,
            'message' => 'premium_subscriptions_json table already exists',
            'action' => 'exists',
            'columns' => $columns
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
