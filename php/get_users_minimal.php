<?php
/**
 * Minimal get_users API for debugging
 */

// Suppress all output before JSON
ob_start();

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
    
    // Get pagination parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 10;
    $offset = ($page - 1) * $limit;
    
    // Count total users
    $countStmt = $pdo->query("SELECT COUNT(*) as total FROM user");
    $totalUsers = $countStmt->fetch()['total'];
    
    // Get users without any joins
    $stmt = $pdo->prepare("
        SELECT 
            userId,
            userName,
            email,
            phone,
            role,
            private_key,
            age,
            gender,
            premium_status,
            created_at,
            updated_at
        FROM user 
        ORDER BY created_at DESC 
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    // Format users
    $formattedUsers = [];
    foreach ($users as $user) {
        $formattedUsers[] = [
            'id' => $user['userId'],
            'username' => $user['userName'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'role' => $user['premium_status'] ? 'premium' : 'user',
            'status' => $user['premium_status'] ? 'premium' : 'active',
            'created_at' => $user['created_at'],
            'updated_at' => $user['updated_at'],
            'avatar' => strtoupper(substr($user['userName'] ?? 'U', 0, 2))
        ];
    }
    
    // Calculate pagination
    $totalPages = ceil($totalUsers / $limit);
    
    // Clean any output buffer
    ob_clean();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'users' => $formattedUsers,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_users' => $totalUsers,
                'limit' => $limit,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ]
        ]
    ]);
    
} catch (Exception $e) {
    // Clean any output buffer
    ob_clean();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
