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
    
    echo json_encode([
        'table_exists' => $tableExists,
        'message' => $tableExists ? 'premium_subscriptions_json table exists' : 'premium_subscriptions_json table does not exist'
    ]);
    
    if ($tableExists) {
        // Get table structure
        $stmt = $pdo->prepare("DESCRIBE premium_subscriptions_json");
        $stmt->execute();
        $structure = $stmt->fetchAll();
        
        echo "\n\nTable structure:\n";
        foreach ($structure as $column) {
            echo json_encode($column) . "\n";
        }
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'error' => true,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
