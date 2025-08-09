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
    
    $testResults = [];
    
    // Test 1: Check if premium_subscriptions_json table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'premium_subscriptions_json'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    $testResults['table_exists'] = $tableExists;
    
    if ($tableExists) {
        // Test 2: Check table structure
        $stmt = $pdo->prepare("DESCRIBE premium_subscriptions_json");
        $stmt->execute();
        $columns = $stmt->fetchAll();
        $columnNames = array_column($columns, 'Field');
        $testResults['table_columns'] = $columnNames;
        
        // Test 3: Check if any premium subscriptions exist
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM premium_subscriptions_json");
        $stmt->execute();
        $subscriptionCount = $stmt->fetch()['count'];
        $testResults['subscription_count'] = $subscriptionCount;
        
        // Test 4: Get sample subscription data
        if ($subscriptionCount > 0) {
            $stmt = $pdo->prepare("SELECT * FROM premium_subscriptions_json LIMIT 1");
            $stmt->execute();
            $sampleSubscription = $stmt->fetch();
            $testResults['sample_subscription'] = $sampleSubscription;
        }
        
        // Test 5: Check user table for relative and elderly users
        $stmt = $pdo->prepare("SELECT role, COUNT(*) as count FROM user WHERE role IN ('relative', 'elderly') GROUP BY role");
        $stmt->execute();
        $userCounts = $stmt->fetchAll();
        $testResults['user_counts'] = $userCounts;
        
        // Test 6: Check premium users
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user WHERE premium_status = 1");
        $stmt->execute();
        $premiumUserCount = $stmt->fetch()['count'];
        $testResults['premium_user_count'] = $premiumUserCount;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'System check completed',
        'data' => $testResults
    ], JSON_PRETTY_PRINT);
    
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
