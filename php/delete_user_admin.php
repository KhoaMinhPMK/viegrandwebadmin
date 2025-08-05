<?php
/**
 * Delete User API - viegrand_admin database
 * Deletes user from viegrand_admin.users table
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow DELETE requests
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get user ID from URL parameter
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Missing user ID']);
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
    
    // Check if user exists
    $checkStmt = $pdo->prepare("SELECT id, username, full_name FROM users WHERE id = ?");
    $checkStmt->execute([$userId]);
    $user = $checkStmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    // Delete the user
    $deleteStmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $result = $deleteStmt->execute([$userId]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'User deleted successfully',
            'data' => [
                'deleted_user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'full_name' => $user['full_name']
                ]
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 