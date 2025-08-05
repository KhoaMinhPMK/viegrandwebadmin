<?php
/**
 * Delete User API - viegrand database
 * Deletes user from viegrand.user table
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if this is a test request (GET method)
$isTestRequest = $_SERVER['REQUEST_METHOD'] === 'GET';

if ($isTestRequest) {
    echo json_encode([
        'success' => false,
        'message' => 'This is an API endpoint for deleting users. Use DELETE method with user ID parameter.',
        'usage' => [
            'method' => 'DELETE',
            'url_example' => '/delete_user_main.php?id=19',
            'note' => 'This endpoint is designed to be called from the frontend application, not accessed directly in browser.'
        ]
    ]);
    exit();
}

// Only allow DELETE requests for actual deletions
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode([
        'success' => false, 
        'message' => 'Method not allowed. This API only accepts DELETE requests.',
        'received_method' => $_SERVER['REQUEST_METHOD'],
        'expected_method' => 'DELETE'
    ]);
    exit();
}

// Get user ID from URL parameter
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$userId) {
    echo json_encode([
        'success' => false, 
        'message' => 'Missing user ID. Please provide user ID in URL parameter.',
        'example' => '/delete_user_main.php?id=19'
    ]);
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
    
    // Check if user exists
    $checkStmt = $pdo->prepare("SELECT userId, userName FROM user WHERE userId = ?");
    $checkStmt->execute([$userId]);
    $user = $checkStmt->fetch();
    
    if (!$user) {
        echo json_encode([
            'success' => false, 
            'message' => 'User not found',
            'requested_id' => $userId
        ]);
        exit();
    }
    
    // Delete the user
    $deleteStmt = $pdo->prepare("DELETE FROM user WHERE userId = ?");
    $result = $deleteStmt->execute([$userId]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'User deleted successfully',
            'data' => [
                'deleted_user' => [
                    'id' => $user['userId'],
                    'username' => $user['userName']
                ]
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'debug_info' => [
            'host' => $host,
            'dbname' => $dbname,
            'error_code' => $e->getCode()
        ]
    ]);
}
?> 