<?php
/**
 * Get Users API - viegrand database
 * Fetches users from viegrand.user table
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database configuration
$host = '127.0.0.1';  // Using IP instead of localhost
$dbname = 'viegrand';
$username = 'root';
$password = '';      // Empty password for root
$charset = 'utf8mb4';

try {   
    // Create PDO connection
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Get pagination parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;
    
    // Count total users
    $countStmt = $pdo->query("SELECT COUNT(*) as total FROM user");
    $totalUsers = $countStmt->fetch()['total'];
    
    // Get users with pagination
    $stmt = $pdo->prepare("
        SELECT 
            userId, 
            userName as username, 
            email, 
            phone, 
            created_at, 
        FROM user 
        ORDER BY created_at DESC 
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetchAll();
    
    // Format user data for frontend
    $formattedUsers = [];
    foreach ($user as $user) {
        // Generate avatar from name
        $avatar = generateAvatar($user['full_name'] ?: $user['username']);
        
        // Format dates
        $createdAt = $user['created_at'] ? date('d/m/Y H:i', strtotime($user['created_at'])) : 'N/A';
        $lastLogin = $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Chưa đăng nhập';
        
        // Get role and status display names
        $roleDisplay = getRoleDisplay($user['role']);
        $statusDisplay = getStatusDisplay($user['status']);
        
        $formattedUsers[] = [
            'userId' => $user['userId'],
            'username' => $user['username'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'created_at' => $user['created_at'],
            'created_at_formatted' => $createdAt,
        ];
    }
    
    // Calculate pagination info
    $totalPages = ceil($totalUsers / $limit);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'user' => $formattedUser,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_users' => $totalUsers,
                'limit' => $limit,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ]
        ],
        'debug' => [
            'total_users' => $totalUsers,
            'current_page' => $page,
            'limit' => $limit,
            'offset' => $offset
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'debug' => [
            'host' => $host,
            'dbname' => $dbname,
            'username' => $username
        ]
    ]);
}

/**
 * Generate avatar from name
 */
function generateAvatar($name) {
    if (!$name) return 'U';
    
    $words = trim($name) ? explode(' ', trim($name)) : ['User'];
    if (count($words) >= 2) {
        return strtoupper($words[0][0] . $words[count($words) - 1][0]);
    } else {
        return strtoupper(substr($words[0], 0, 2));
    }
}

/**
 * Get role display name
 */
function getRoleDisplay($role) {
    $roles = [
        'admin' => 'Quản trị viên',
        'manager' => 'Quản lý',
        'user' => 'Người dùng',
        'customer' => 'Khách hàng'
    ];
    return $roles[$role] ?? 'Người dùng';
}

/**
 * Get status display name
 */
function getStatusDisplay($status) {
    $statuses = [
        'active' => 'Hoạt động',
        'inactive' => 'Không hoạt động',
        'suspended' => 'Bị khóa',
        'pending' => 'Chờ duyệt'
    ];
    return $statuses[$status] ?? 'Không xác định';
}
?> 