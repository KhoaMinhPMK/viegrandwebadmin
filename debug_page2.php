<?php
/**
 * Debug script for page 2 issue
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Page 2 Issue</h1>";

try {
    // Database configuration
    $host = '127.0.0.1';
    $dbname = 'viegrand';
    $username = 'root';
    $password = '';
    $charset = 'utf8mb4';
    
    // Create PDO connection
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    echo "<h2>Database Connection: ✅ Success</h2>";
    
    // Test parameters for page 2
    $page = 2;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    echo "<h2>Test Parameters:</h2>";
    echo "<ul>";
    echo "<li>Page: $page</li>";
    echo "<li>Limit: $limit</li>";
    echo "<li>Offset: $offset</li>";
    echo "</ul>";
    
    // Count total users
    $countStmt = $pdo->query("SELECT COUNT(*) as total FROM user");
    $totalUsers = $countStmt->fetch()['total'];
    $totalPages = ceil($totalUsers / $limit);
    
    echo "<h2>Database Info:</h2>";
    echo "<ul>";
    echo "<li>Total Users: $totalUsers</li>";
    echo "<li>Total Pages: $totalPages</li>";
    echo "<li>Users per page: $limit</li>";
    echo "</ul>";
    
    // Test the query
    $sql = "
        SELECT 
            u.userId,
            u.userName,
            u.email,
            u.phone,
            u.role,
            u.private_key,
            u.age,
            u.gender,
            u.blood,
            u.chronic_diseases,
            u.allergies,
            u.premium_status,
            u.notifications,
            u.relative_phone,
            u.home_address,
            u.created_at,
            u.updated_at,
            u.premium_start_date,
            u.premium_end_date,
            u.hypertension,
            u.heart_disease,
            u.ever_married,
            u.work_type,
            u.residence_type,
            u.avg_glucose_level,
            u.bmi,
            u.smoking_status,
            u.stroke,
            u.height,
            u.weight,
            u.blood_pressure_systolic,
            u.blood_pressure_diastolic,
            u.heart_rate,
            u.last_health_check
        FROM user u
        ORDER BY u.created_at DESC 
        LIMIT :limit OFFSET :offset
    ";
    
    echo "<h2>SQL Query:</h2>";
    echo "<pre>" . str_replace([':limit', ':offset'], [$limit, $offset], $sql) . "</pre>";
    
    // Execute query
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    echo "<h2>Query Results:</h2>";
    echo "<ul>";
    echo "<li>Users found: " . count($users) . "</li>";
    echo "</ul>";
    
    if (count($users) > 0) {
        echo "<h3>First User Data:</h3>";
        echo "<pre>" . print_r($users[0], true) . "</pre>";
        
        // Test data formatting
        echo "<h3>Testing Data Formatting:</h3>";
        $user = $users[0];
        
        // Test each formatting function
        echo "<ul>";
        echo "<li>Avatar: " . generateAvatar($user['userName']) . "</li>";
        echo "<li>Created At: " . ($user['created_at'] ? date('d/m/Y H:i', strtotime($user['created_at'])) : 'N/A') . "</li>";
        echo "<li>Role Display: " . getRoleDisplay($user['premium_status'] ? 'premium' : 'user') . "</li>";
        echo "<li>Status Display: " . getStatusDisplay($user['premium_status'] ? 'premium' : 'active') . "</li>";
        echo "<li>User Role Display: " . getUserRoleDisplay($user['role'] ?? 'relative') . "</li>";
        echo "</ul>";
        
        // Test health info formatting
        $healthInfo = formatHealthInfo($user);
        echo "<h3>Health Info:</h3>";
        echo "<pre>" . print_r($healthInfo, true) . "</pre>";
        
        // Test JSON encoding
        $testData = [
            'id' => $user['userId'],
            'username' => $user['userName'],
            'email' => $user['email'],
            'full_name' => $user['userName'],
            'phone' => $user['phone'],
            'user_role' => $user['role'] ?? 'relative',
            'user_role_display' => getUserRoleDisplay($user['role'] ?? 'relative'),
            'private_key' => $user['private_key'],
            'role' => $user['premium_status'] ? 'premium' : 'user',
            'role_display' => getRoleDisplay($user['premium_status'] ? 'premium' : 'user'),
            'status' => $user['premium_status'] ? 'premium' : 'active',
            'status_display' => getStatusDisplay($user['premium_status'] ? 'premium' : 'active'),
            'created_at' => $user['created_at'],
            'created_at_formatted' => $user['created_at'] ? date('d/m/Y H:i', strtotime($user['created_at'])) : 'N/A',
            'updated_at' => $user['updated_at'],
            'updated_at_formatted' => $user['updated_at'] ? date('d/m/Y H:i', strtotime($user['updated_at'])) : 'N/A',
            'avatar' => generateAvatar($user['userName']),
            'premium_status' => $user['premium_status'],
            'premium_start_date' => $user['premium_start_date'],
            'premium_end_date' => $user['premium_end_date'],
            'premium_key' => null,
            'age' => $user['age'],
            'gender' => $user['gender'],
            'blood' => $user['blood'],
            'health_info' => $healthInfo,
            'last_health_check' => $user['last_health_check'],
            'last_health_check_formatted' => $user['last_health_check'] ? date('d/m/Y H:i', strtotime($user['last_health_check'])) : 'Chưa kiểm tra'
        ];
        
        echo "<h3>JSON Encoding Test:</h3>";
        $jsonResult = json_encode($testData);
        if ($jsonResult === false) {
            echo "<p style='color: red;'>❌ JSON encoding failed: " . json_last_error_msg() . "</p>";
        } else {
            echo "<p style='color: green;'>✅ JSON encoding successful</p>";
            echo "<pre>" . $jsonResult . "</pre>";
        }
        
    } else {
        echo "<p style='color: orange;'>⚠️ No users found for page 2</p>";
    }
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Helper functions (copied from the main API)
function generateAvatar($name) {
    if (!$name) return 'U';
    
    $words = trim($name) ? explode(' ', trim($name)) : ['User'];
    if (count($words) >= 2) {
        return strtoupper($words[0][0] . $words[count($words) - 1][0]);
    } else {
        return strtoupper(substr($words[0], 0, 2));
    }
}

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

function getStatusDisplay($status) {
    $statuses = [
        'active' => 'Hoạt động',
        'inactive' => 'Không hoạt động',
        'suspended' => 'Bị khóa',
        'pending' => 'Chờ duyệt',
        'premium' => 'Premium'
    ];
    return $statuses[$status] ?? 'Hoạt động';
}

function formatHealthInfo($user) {
    $healthInfo = [];
    
    // Basic health measurements
    if (!empty($user['height'])) $healthInfo['height'] = $user['height'];
    if (!empty($user['weight'])) $healthInfo['weight'] = $user['weight'];
    if (!empty($user['blood_pressure_systolic']) && !empty($user['blood_pressure_diastolic'])) {
        $healthInfo['blood_pressure'] = $user['blood_pressure_systolic'] . '/' . $user['blood_pressure_diastolic'];
    }
    if (!empty($user['heart_rate'])) $healthInfo['heart_rate'] = $user['heart_rate'];
    
    // Health conditions
    if (!empty($user['hypertension'])) $healthInfo['hypertension'] = $user['hypertension'];
    if (!empty($user['heart_disease'])) $healthInfo['heart_disease'] = $user['heart_disease'];
    if (!empty($user['stroke'])) $healthInfo['stroke'] = $user['stroke'];
    if (!empty($user['bmi'])) $healthInfo['bmi'] = $user['bmi'];
    if (!empty($user['avg_glucose_level'])) $healthInfo['avg_glucose_level'] = $user['avg_glucose_level'];
    if (!empty($user['smoking_status'])) $healthInfo['smoking_status'] = $user['smoking_status'];
    
    // Personal info
    if (!empty($user['blood'])) $healthInfo['blood_type'] = $user['blood'];
    if (!empty($user['age'])) $healthInfo['age'] = $user['age'];
    if (!empty($user['gender'])) $healthInfo['gender'] = $user['gender'];
    
    return $healthInfo;
}

function getUserRoleDisplay($userRole) {
    $roles = [
        'relative' => 'Người thân',
        'elderly' => 'Người cao tuổi',
        'admin' => 'Quản trị viên',
        'manager' => 'Quản lý',
        'user' => 'Người dùng'
    ];
    return $roles[$userRole] ?? 'Người dùng';
}
?> 