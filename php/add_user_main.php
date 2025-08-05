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
    // Get form data
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $age = !empty($_POST['age']) ? (int)$_POST['age'] : null;
    $gender = $_POST['gender'] ?? '';
    $blood = $_POST['blood'] ?? '';
    $premium_status = $_POST['premium_status'] ?? '0';
    
    // Health fields
    $height = !empty($_POST['height']) ? (float)$_POST['height'] : null;
    $weight = !empty($_POST['weight']) ? (float)$_POST['weight'] : null;
    $blood_pressure_systolic = !empty($_POST['blood_pressure_systolic']) ? (int)$_POST['blood_pressure_systolic'] : null;
    $blood_pressure_diastolic = !empty($_POST['blood_pressure_diastolic']) ? (int)$_POST['blood_pressure_diastolic'] : null;
    $heart_rate = !empty($_POST['heart_rate']) ? (int)$_POST['heart_rate'] : null;
    
    // Validate required fields
    if (empty($username) || empty($email) || empty($full_name) || empty($password)) {
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
    $checkSql = "SELECT userId FROM user WHERE userName = :username OR email = :email";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindParam(':username', $full_name, PDO::PARAM_STR);  // Use full_name for userName field check
    $checkStmt->bindParam(':email', $email, PDO::PARAM_STR);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        exit;
    }
    
    // Insert new user (note: userName field stores what we call full_name in the frontend)
    $sql = "INSERT INTO user (userName, email, phone, password, age, gender, blood, premium_status, 
                              height, weight, blood_pressure_systolic, blood_pressure_diastolic, heart_rate, created_at) 
            VALUES (:username, :email, :phone, :password, :age, :gender, :blood, :premium_status,
                    :height, :weight, :blood_pressure_systolic, :blood_pressure_diastolic, :heart_rate, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':username', $full_name, PDO::PARAM_STR);  // Store full_name in userName field
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
    $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
    $stmt->bindParam(':age', $age, PDO::PARAM_INT);
    $stmt->bindParam(':gender', $gender, PDO::PARAM_STR);
    $stmt->bindParam(':blood', $blood, PDO::PARAM_STR);
    $stmt->bindParam(':premium_status', $premium_status, PDO::PARAM_STR);
    $stmt->bindParam(':height', $height, PDO::PARAM_STR);
    $stmt->bindParam(':weight', $weight, PDO::PARAM_STR);
    $stmt->bindParam(':blood_pressure_systolic', $blood_pressure_systolic, PDO::PARAM_INT);
    $stmt->bindParam(':blood_pressure_diastolic', $blood_pressure_diastolic, PDO::PARAM_INT);
    $stmt->bindParam(':heart_rate', $heart_rate, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        $newUserId = $pdo->lastInsertId();
        echo json_encode([
            'success' => true, 
            'message' => 'User added successfully',
            'user_id' => $newUserId
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add user']);
    }
    
} catch (PDOException $e) {
    error_log("Database error in add_user_main.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error in add_user_main.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
