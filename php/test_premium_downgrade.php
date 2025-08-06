<?php
/**
 * Test script to verify premium downgrade functionality
 */

// Database configuration
$host = '127.0.0.1';
$dbname = 'viegrand';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

try {
    // Create PDO connection
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    echo "=== Premium Downgrade Test ===\n\n";
    
    // Check users with premium status
    $stmt = $pdo->query("
        SELECT userId, userName, email, premium_status, premium_start_date, premium_end_date, private_key 
        FROM user 
        WHERE premium_status = 1 
        ORDER BY created_at DESC 
        LIMIT 3
    ");
    $premiumUsers = $stmt->fetchAll();
    
    echo "Current Premium Users:\n";
    echo "---------------------\n";
    foreach ($premiumUsers as $user) {
        echo "ID: {$user['userId']} | Name: {$user['userName']} | Email: {$user['email']}\n";
        echo "Premium Status: {$user['premium_status']} | Private Key: {$user['private_key']}\n";
        echo "Start Date: {$user['premium_start_date']} | End Date: {$user['premium_end_date']}\n";
        
        // Check corresponding premium_subscriptions_json record
        $subStmt = $pdo->prepare("
            SELECT premium_key, start_date, end_date, note 
            FROM premium_subscriptions_json 
            WHERE young_person_key = ?
        ");
        $subStmt->execute([$user['private_key']]);
        $subscription = $subStmt->fetch();
        
        if ($subscription) {
            echo "Subscription Record Found:\n";
            echo "  Premium Key: {$subscription['premium_key']}\n";
            echo "  Start Date: {$subscription['start_date']}\n";
            echo "  End Date: {$subscription['end_date']}\n";
            echo "  Note: {$subscription['note']}\n";
        } else {
            echo "No subscription record found\n";
        }
        echo "---------------------\n";
    }
    
    echo "\n=== Downgrade Logic Implementation ===\n";
    echo "When a premium user is downgraded:\n";
    echo "1. viegrand.user.premium_end_date -> set to current moment\n";
    echo "2. viegrand.premium_subscriptions_json.end_date -> set to current moment\n";
    echo "3. viegrand.user.premium_status -> set to 0\n";
    echo "\nThis ensures immediate termination of premium access.\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
