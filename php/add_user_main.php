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
    $full_name = $_POST['full_name'] ?? '';  // Ignore this for viegrand database
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';  // Default to 'user' if not specified
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
    
    // For viegrand database, only use username (ignore full_name completely)
    // The userName field in the database will store the username value
    
    // Validate required fields - we need username, email, and password
    if (empty($username) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields: username, email, and password are required']);
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Generate private key
    $private_key = 'pk_' . bin2hex(random_bytes(16)) . '_' . time();
    
    // Handle premium dates if user is premium
    $premium_start_date = null;
    $premium_end_date = null;
    if ($premium_status === '1') {
        $now = new DateTime();
        $endDate = clone $now;
        $endDate->add(new DateInterval('P30D')); // Add 30 days
        
        $premium_start_date = $now->format('Y-m-d H:i:s');
        $premium_end_date = $endDate->format('Y-m-d H:i:s');
    }
    
    // Check if username or email already exists
    $checkSql = "SELECT userId FROM user WHERE userName = :username OR email = :email";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindParam(':username', $username, PDO::PARAM_STR);  // Use username for check
    $checkStmt->bindParam(':email', $email, PDO::PARAM_STR);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        exit;
    }
    
    // Insert new user (userName field stores the username value)
    $sql = "INSERT INTO user (userName, email, phone, role, private_key, password, age, gender, blood, premium_status, 
                              height, weight, blood_pressure_systolic, blood_pressure_diastolic, heart_rate, 
                              premium_start_date, premium_end_date, created_at) 
            VALUES (:username, :email, :phone, :role, :private_key, :password, :age, :gender, :blood, :premium_status,
                    :height, :weight, :blood_pressure_systolic, :blood_pressure_diastolic, :heart_rate,
                    :premium_start_date, :premium_end_date, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);  // Store username in userName field
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
    $stmt->bindParam(':role', $role, PDO::PARAM_STR);
    $stmt->bindParam(':private_key', $private_key, PDO::PARAM_STR);
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
    $stmt->bindParam(':premium_start_date', $premium_start_date, PDO::PARAM_STR);
    $stmt->bindParam(':premium_end_date', $premium_end_date, PDO::PARAM_STR);
    
    if ($stmt->execute()) {
        $newUserId = $pdo->lastInsertId();
        
        // Insert into premium_subscriptions_json if user is premium
        if ($premium_status === '1') {
            try {
                // Generate premium_key in format: dd + 10-digit auto-increment ID + mmyy
                $now = new DateTime();
                
                // Get the next auto-increment value for premium_subscriptions_json table
                $countStmt = $pdo->prepare("SELECT COUNT(*) + 1 as next_id FROM premium_subscriptions_json");
                $countStmt->execute();
                $nextId = $countStmt->fetch()['next_id'];
                
                // Format the premium_key: dd + 10-digit zero-padded ID + mmyy
                $dayStr = $now->format('d'); // dd format (day)
                $monthYearStr = $now->format('my'); // mmyy format (month + year)
                $idStr = str_pad($nextId, 10, '0', STR_PAD_LEFT); // 10-digit zero-padded
                $premiumKey = $dayStr . $idStr . $monthYearStr;
                
                // Insert into premium_subscriptions_json
                $premiumInsertStmt = $pdo->prepare("
                    INSERT INTO premium_subscriptions_json 
                    (premium_key, young_person_key, elderly_keys, start_date, end_date) 
                    VALUES (?, ?, '[]', ?, ?)
                ");
                $premiumInsertStmt->execute([
                    $premiumKey,
                    $private_key,
                    $premium_start_date,
                    $premium_end_date
                ]);
                
            } catch (Exception $e) {
                // Log the error but don't fail the main insertion
                error_log("Failed to insert into premium_subscriptions_json during add user: " . $e->getMessage());
            }
        }
        
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
