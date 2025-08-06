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
            userName,
            email,
            phone,
            private_key,
            age,
            gender,
            blood,
            chronic_diseases,
            allergies,
            premium_status,
            notifications,
            relative_phone,
            home_address,
            created_at,
            updated_at,
            premium_start_date,
            premium_end_date,
            hypertension,
            heart_disease,
            ever_married,
            work_type,
            residence_type,
            avg_glucose_level,
            bmi,
            smoking_status,
            stroke,
            height,
            weight,
            blood_pressure_systolic,
            blood_pressure_diastolic,
            heart_rate,
            last_health_check
        FROM user 
        ORDER BY created_at DESC 
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    // Format user data for frontend
    $formattedUsers = [];
    foreach ($users as $user) {
        // Generate avatar from name
        $avatar = generateAvatar($user['userName']);
        
        // Format dates
        $createdAt = $user['created_at'] ? date('d/m/Y H:i', strtotime($user['created_at'])) : 'N/A';
        $updatedAt = $user['updated_at'] ? date('d/m/Y H:i', strtotime($user['updated_at'])) : 'N/A';
        $lastHealthCheck = $user['last_health_check'] ? date('d/m/Y H:i', strtotime($user['last_health_check'])) : 'Chưa kiểm tra';
        
        // Get role and status based on premium_status
        $role = $user['premium_status'] ? 'premium' : 'user';
        $status = $user['premium_status'] ? 'premium' : 'active';
        $roleDisplay = getRoleDisplay($role);
        $statusDisplay = getStatusDisplay($status);
        
        // Format health data
        $healthInfo = formatHealthInfo($user);
        
        $formattedUsers[] = [
            'id' => $user['userId'],
            'username' => $user['userName'],
            'email' => $user['email'],
            'full_name' => $user['userName'],
            'phone' => $user['phone'],
            'private_key' => $user['private_key'],
            'role' => $role,
            'role_display' => $roleDisplay,
            'status' => $status,
            'status_display' => $statusDisplay,
            'created_at' => $user['created_at'],
            'created_at_formatted' => $createdAt,
            'updated_at' => $user['updated_at'],
            'updated_at_formatted' => $updatedAt,
            'avatar' => $avatar,
            'premium_status' => $user['premium_status'],
            'premium_start_date' => $user['premium_start_date'],
            'premium_end_date' => $user['premium_end_date'],
            'age' => $user['age'],
            'gender' => $user['gender'],
            'blood' => $user['blood'],
            'health_info' => $healthInfo,
            'last_health_check' => $user['last_health_check'],
            'last_health_check_formatted' => $lastHealthCheck
        ];
    }
    
    // Calculate pagination info
    $totalPages = ceil($totalUsers / $limit);
    
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
        'premium' => 'Premium',
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
        'pending' => 'Chờ duyệt',
        'premium' => 'Premium'
    ];
    return $statuses[$status] ?? 'Không xác định';
}

/**
 * Format health information
 */
function formatHealthInfo($user) {
    $healthInfo = [];
    
    // Basic health data
    if ($user['age']) $healthInfo['age'] = $user['age'] . ' tuổi';
    if ($user['gender']) $healthInfo['gender'] = $user['gender'];
    if ($user['blood']) $healthInfo['blood'] = 'Nhóm máu ' . $user['blood'];
    
    // Health conditions
    if ($user['hypertension']) $healthInfo['hypertension'] = 'Cao huyết áp';
    if ($user['heart_disease']) $healthInfo['heart_disease'] = 'Bệnh tim';
    if ($user['stroke']) $healthInfo['stroke'] = 'Đột quỵ';
    
    // Measurements
    if ($user['height']) $healthInfo['height'] = $user['height'] . ' cm';
    if ($user['weight']) $healthInfo['weight'] = $user['weight'] . ' kg';
    if ($user['bmi']) $healthInfo['bmi'] = 'BMI: ' . $user['bmi'];
    
    // Blood pressure
    if ($user['blood_pressure_systolic'] && $user['blood_pressure_diastolic']) {
        $healthInfo['blood_pressure'] = $user['blood_pressure_systolic'] . '/' . $user['blood_pressure_diastolic'] . ' mmHg';
    }
    
    // Heart rate
    if ($user['heart_rate']) $healthInfo['heart_rate'] = $user['heart_rate'] . ' bpm';
    
    // Glucose level
    if ($user['avg_glucose_level']) $healthInfo['glucose'] = $user['avg_glucose_level'] . ' mg/dL';
    
    // Lifestyle
    if ($user['smoking_status']) $healthInfo['smoking'] = $user['smoking_status'];
    if ($user['work_type']) $healthInfo['work'] = $user['work_type'];
    if ($user['residence_type']) $healthInfo['residence'] = $user['residence_type'];
    if ($user['ever_married']) $healthInfo['marital'] = $user['ever_married'];
    
    return $healthInfo;
}
?>
