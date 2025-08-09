<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Database configuration
$host = '127.0.0.1';  // Using IP instead of localhost
$dbname = 'viegrand_admin';
$username = 'root';
$password = '';      // Empty password for root
$charset = 'utf8mb4';

try {
    // Create PDO connection
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Get form data
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $status = $_POST['status'] ?? 'active';
    
    // Validate required fields
    if (empty($username) || empty($email) || empty($full_name) || empty($password) || empty($role)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if username or email already exists
    $checkSql = "SELECT id FROM users WHERE username = :username OR email = :email";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindParam(':username', $username, PDO::PARAM_STR);
    $checkStmt->bindParam(':email', $email, PDO::PARAM_STR);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        exit;
    }
    
    // Insert new admin user
    $sql = "INSERT INTO users (username, email, full_name, phone, password, role, status, created_at) 
            VALUES (:username, :email, :full_name, :phone, :password, :role, :status, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':full_name', $full_name, PDO::PARAM_STR);
    $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
    $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
    $stmt->bindParam(':role', $role, PDO::PARAM_STR);
    $stmt->bindParam(':status', $status, PDO::PARAM_STR);
    
    if ($stmt->execute()) {
        $newUserId = $pdo->lastInsertId();
        echo json_encode([
            'success' => true, 
            'message' => 'Admin user added successfully',
            'user_id' => $newUserId
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add admin user']);
    }
    
} catch (PDOException $e) {
    error_log("Database error in add_user_admin.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error in add_user_admin.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
