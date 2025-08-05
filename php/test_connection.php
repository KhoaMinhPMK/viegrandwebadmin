<?php
// Test database connection
header('Content-Type: application/json');

$host = '127.0.0.1';  // Using IP instead of localhost
$dbname = 'viegrand_admin';
$username = 'root';
$password = '';      // Empty password for root
$charset = 'utf8mb4';

try {
    // Create PDO connection
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'contact_messages'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        // Get table structure
        $stmt = $pdo->query("DESCRIBE contact_messages");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Database connection successful',
            'table_exists' => true,
            'columns' => $columns
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Table contact_messages does not exist',
            'table_exists' => false
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
}
?> 