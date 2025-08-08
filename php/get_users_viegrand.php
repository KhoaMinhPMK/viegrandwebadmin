<?php
/**
 * Get Users API - viegrand database
 * Fetches users from viegrand.user table
 */

// Start output buffering to prevent any unwanted output
ob_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log errors to a file for debugging
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_errors.log');

header('Content-Type: application/json; charset=utf-8');
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
    // Log request parameters
    error_log("API Request - Page: " . ($_GET['page'] ?? 'undefined') . ", Limit: " . ($_GET['limit'] ?? 'undefined'));
    
    // Create PDO connection
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Get pagination parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;
    
    // Validate parameters
    if ($page < 1) {
        throw new Exception('Invalid page number');
    }
    if ($limit < 1 || $limit > 100) {
        throw new Exception('Invalid limit value');
    }
    
    error_log("Calculated offset: $offset");
    
    // Count total users
    $countStmt = $pdo->query("SELECT COUNT(*) as total FROM user");
    $totalUsers = $countStmt->fetch()['total'];
    
    error_log("Total users: $totalUsers");
    
    // Calculate pagination info first
    $totalPages = ceil($totalUsers / $limit);
    
    // Validate page number against total pages
    if ($page > $totalPages && $totalPages > 0) {
        throw new Exception("Page $page does not exist. Total pages: $totalPages");
    }
    
    // Use a simpler query without the problematic LEFT JOIN
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
    
    error_log("SQL Query: " . str_replace([':limit', ':offset'], [$limit, $offset], $sql));
    
    // Get users with pagination
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    error_log("Users found: " . count($users));
    
    // Ensure we have a valid response even if no users found
    if (count($users) === 0 && $page <= $totalPages) {
        error_log("No users found for page $page, but page is valid. This might indicate a data issue.");
    }
                                               
    // Format user data for frontend
    $formattedUsers = [];
    $processedCount = 0;
    $skippedCount = 0;
    $skippedUsers = [];
    
    foreach ($users as $index => $user) {
        $processedCount++;
        error_log("Processing user $processedCount (index $index): userId=" . ($user['userId'] ?? 'NULL') . ", userName=" . ($user['userName'] ?? 'NULL'));
        
        try {
            // More gentle UTF-8 handling - only fix if there are actual issues
            $sanitizedUser = [];
            foreach ($user as $key => $value) {
                if (is_string($value)) {
                    // Only sanitize if the string contains invalid UTF-8
                    if (!mb_check_encoding($value, 'UTF-8')) {
                        error_log("Found invalid UTF-8 in user $user[userId], field $key: " . substr($value, 0, 50));
                        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                    }
                }
                $sanitizedUser[$key] = $value;
            }
            $user = $sanitizedUser;
            
            // Generate avatar from name
            $avatar = generateAvatar($user['userName']);
            
            // Format dates with proper encoding
            $createdAt = $user['created_at'] ? date('d/m/Y H:i', strtotime($user['created_at'])) : 'N/A';
            $updatedAt = $user['updated_at'] ? date('d/m/Y H:i', strtotime($user['updated_at'])) : 'N/A';
            $lastHealthCheck = $user['last_health_check'] ? date('d/m/Y H:i', strtotime($user['last_health_check'])) : 'Chưa kiểm tra';
            
            // Get role and status based on premium_status
            $role = $user['premium_status'] ? 'premium' : 'user';
            $status = $user['premium_status'] ? 'premium' : 'active';
            $roleDisplay = getRoleDisplay($role);
            $statusDisplay = getStatusDisplay($status);
            
            // Get user role from database (relative/elderly)
            $userRole = $user['role'] ?? 'relative';  // Default to 'relative' if not set
            $userRoleDisplay = getUserRoleDisplay($userRole);
            
            // Format health data
            $healthInfo = formatHealthInfo($user);
            
            $formattedUsers[] = [
                'id' => $user['userId'],
                'username' => $user['userName'],
                'email' => $user['email'],
                'full_name' => $user['userName'],
                'phone' => $user['phone'],
                'user_role' => $userRole,
                'user_role_display' => $userRoleDisplay,
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
                'premium_key' => null,
                'age' => $user['age'],
                'gender' => $user['gender'],
                'blood' => $user['blood'],
                'health_info' => $healthInfo,
                'last_health_check' => $user['last_health_check'],
                'last_health_check_formatted' => $lastHealthCheck
            ];
            
            error_log("Successfully processed user: userId=" . $user['userId'] . ", userName=" . $user['userName']);
            
        } catch (Exception $e) {
            $skippedCount++;
            $skippedUsers[] = [
                'userId' => $user['userId'] ?? 'NULL',
                'userName' => $user['userName'] ?? 'NULL',
                'error' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine()
            ];
            error_log("Error formatting user data: userId=" . ($user['userId'] ?? 'NULL') . ", userName=" . ($user['userName'] ?? 'NULL') . ", error=" . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            // Continue with other users even if one fails
            continue;
        }
    }
    
    error_log("=== PROCESSING SUMMARY ===");
    error_log("Raw users from database: " . count($users));
    error_log("Successfully processed: " . count($formattedUsers));
    error_log("Skipped due to errors: " . $skippedCount);
    error_log("Total processed: " . $processedCount);
    
    if ($skippedCount > 0) {
        error_log("Skipped users details:");
        foreach ($skippedUsers as $skipped) {
            error_log("  - userId: " . $skipped['userId'] . ", userName: " . $skipped['userName'] . ", error: " . $skipped['error'] . " at " . $skipped['error_file'] . ":" . $skipped['error_line']);
        }
    }
    
    error_log("Total pages: $totalPages, Current page: $page");
    error_log("Formatted users count: " . count($formattedUsers));
    
    // Clean any output buffer before JSON output
    ob_clean();
    
    // Ensure no output before JSON
    if (ob_get_length()) {
        ob_clean();
    }
    
    $response = [
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
            'offset' => $offset,
            'users_found' => count($formattedUsers),
            'raw_users_count' => count($users),
            'total_pages' => $totalPages,
            'processing_summary' => [
                'raw_users_from_db' => count($users),
                'successfully_processed' => count($formattedUsers),
                'skipped_due_to_errors' => $skippedCount,
                'total_processed' => $processedCount
            ],
            'skipped_users' => $skippedUsers
        ]
    ];
    
    error_log("Sending response with " . count($formattedUsers) . " users");
    
    // Ensure we always return valid JSON with proper UTF-8 encoding
    $jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($jsonResponse === false) {
        $error = json_last_error_msg();
        error_log("JSON encoding failed: " . $error);
        
        // Try to identify which field is causing the issue
        foreach ($formattedUsers as $index => $user) {
            $testJson = json_encode($user, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($testJson === false) {
                error_log("User $index causes JSON error: " . json_last_error_msg());
                // Remove problematic user
                unset($formattedUsers[$index]);
            }
        }
        
        // Try again with cleaned data
        $response['data']['users'] = array_values($formattedUsers);
        $jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        if ($jsonResponse === false) {
            throw new Exception('Failed to encode JSON response even after cleaning: ' . json_last_error_msg());
        }
    }
    
    echo $jsonResponse;
    exit();
    
} catch (PDOException $e) {
    error_log("PDO Error: " . $e->getMessage());
    
    // Clean any output buffer before error response
    ob_clean();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error',
        'error' => $e->getMessage(),
        'debug' => [
            'host' => $host,
            'dbname' => $dbname,
            'username' => $username,
            'error_code' => $e->getCode(),
            'file' => basename(__FILE__),
            'line' => $e->getLine()
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit();
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    
    // Clean any output buffer before error response
    ob_clean();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error' => $e->getMessage(),
        'debug' => [
            'file' => basename(__FILE__),
            'line' => $e->getLine(),
            'page' => $page ?? 'undefined',
            'limit' => $limit ?? 'undefined',
            'offset' => $offset ?? 'undefined'
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Generate avatar from name
 */
function generateAvatar($name) {
    if (!$name) return 'U';
    
    // More gentle UTF-8 handling
    if (!mb_check_encoding($name, 'UTF-8')) {
        $name = mb_convert_encoding($name, 'UTF-8', 'UTF-8');
    }
    
    $words = trim($name) ? explode(' ', trim($name)) : ['User'];
    if (count($words) >= 2) {
        $first = isset($words[0][0]) ? $words[0][0] : 'U';
        $last = isset($words[count($words) - 1][0]) ? $words[count($words) - 1][0] : 'U';
        return strtoupper($first . $last);
    } else {
        $first = isset($words[0][0]) ? $words[0][0] : 'U';
        $second = isset($words[0][1]) ? $words[0][1] : 'U';
        return strtoupper($first . $second);
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
    return $statuses[$status] ?? 'Hoạt động';
}

/**
 * Format health information
 */
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

/**
 * Get user role display name
 */
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
