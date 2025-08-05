<?php
/**
 * Update User API - viegrand database
 * Updates user in viegrand.user table
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow PUT requests
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
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
    
    $userId = (int)$input['id'];
    
    // Build update query dynamically
    $updateFields = [];
    $params = [];
    
    // Allowed fields to update
    $allowedFields = [
        'userName', 'email', 'phone', 'age', 'gender', 'blood', 
        'chronic_diseases', 'allergies', 'premium_status', 'notifications',
        'relative_phone', 'home_address', 'hypertension', 'heart_disease',
        'ever_married', 'work_type', 'residence_type', 'avg_glucose_level',
        'bmi', 'smoking_status', 'stroke', 'height', 'weight',
        'blood_pressure_systolic', 'blood_pressure_diastolic', 'heart_rate'
    ];
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $updateFields[] = "$field = ?";
            $params[] = $input[$field];
        }
    }
    
    if (empty($updateFields)) {
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        exit();
    }
    
    // Add user ID to params
    $params[] = $userId;
    
    $sql = "UPDATE user SET " . implode(', ', $updateFields) . ", updated_at = CURRENT_TIMESTAMP WHERE userId = ?";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    
    if ($result) {
        // Get updated user data
        $getUserStmt = $pdo->prepare("SELECT * FROM user WHERE userId = ?");
        $getUserStmt->execute([$userId]);
        $updatedUser = $getUserStmt->fetch();
        
        if ($updatedUser) {
            echo json_encode([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => [
                    'id' => $updatedUser['userId'],
                    'username' => $updatedUser['userName'],
                    'email' => $updatedUser['email'],
                    'phone' => $updatedUser['phone'],
                    'premium_status' => $updatedUser['premium_status'],
                    'updated_at' => $updatedUser['updated_at']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found after update']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update user']);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 