<?php
/**
 * API to view premium_subscriptions_json table data
 * GET /home/huy-pham/Workspace/viegrand/viegrandwebadmin/php/get_premium_subscriptions.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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
    
    // Get all premium subscriptions
    $stmt = $pdo->prepare("
        SELECT 
            ps.premium_key,
            ps.young_person_key,
            ps.elderly_keys,
            ps.start_date,
            ps.end_date,
            u.userName,
            u.email
        FROM premium_subscriptions_json ps
        LEFT JOIN user u ON u.private_key = ps.young_person_key
        ORDER BY ps.start_date DESC
    ");
    $stmt->execute();
    $subscriptions = $stmt->fetchAll();
    
    // Format the response
    $formattedSubscriptions = [];
    foreach ($subscriptions as $sub) {
        $formattedSubscriptions[] = [
            'premium_key' => $sub['premium_key'],
            'young_person_key' => $sub['young_person_key'],
            'user_name' => $sub['userName'] ?? 'Unknown',
            'user_email' => $sub['email'] ?? 'Unknown',
            'elderly_keys' => json_decode($sub['elderly_keys']),
            'start_date' => $sub['start_date'],
            'end_date' => $sub['end_date'],
            'start_date_formatted' => date('d/m/Y H:i', strtotime($sub['start_date'])),
            'end_date_formatted' => date('d/m/Y H:i', strtotime($sub['end_date'])),
            'status' => strtotime($sub['end_date']) > time() ? 'Active' : 'Expired',
            'days_remaining' => max(0, ceil((strtotime($sub['end_date']) - time()) / 86400))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'subscriptions' => $formattedSubscriptions,
            'total_count' => count($formattedSubscriptions)
        ],
        'message' => 'Premium subscriptions retrieved successfully'
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_premium_subscriptions.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General error in get_premium_subscriptions.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
