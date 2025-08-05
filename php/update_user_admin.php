<?php
/**
 * Update User API - viegrand_admin database
 * Updates user in viegrand_admin.users table
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
$dbname = 'viegrand_admin';
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
        'username', 'email', 'full_name', 'phone', 'role', 'status'
    ];
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field]) && $input[$field] !== '') {
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
    
    $sql = "UPDATE users SET " . implode(', ', $updateFields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    
    if ($result) {
        // Get updated user data
        $getUserStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $getUserStmt->execute([$userId]);
        $updatedUser = $getUserStmt->fetch();
        
        if ($updatedUser) {
            echo json_encode([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => [
                    'id' => $updatedUser['id'],
                    'username' => $updatedUser['username'],
                    'email' => $updatedUser['email'],
                    'full_name' => $updatedUser['full_name'],
                    'phone' => $updatedUser['phone'],
                    'role' => $updatedUser['role'],
                    'status' => $updatedUser['status'],
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