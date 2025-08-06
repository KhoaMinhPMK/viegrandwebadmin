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
    $userId = $_POST['userId'] ?? '';
    $newEndDate = $_POST['newEndDate'] ?? '';
    
    // Validate required fields
    if (empty($userId) || empty($newEndDate)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields: userId and newEndDate']);
        exit;
    }
    
    // Validate date format
    $dateTime = DateTime::createFromFormat('Y-m-d\TH:i', $newEndDate);
    if (!$dateTime) {
        echo json_encode(['success' => false, 'message' => 'Invalid date format']);
        exit;
    }
    
    // Convert to MySQL datetime format
    $mysqlDateTime = $dateTime->format('Y-m-d H:i:s');
    
    // Check if user exists
    $checkStmt = $pdo->prepare("SELECT userId FROM user WHERE userId = :userId");
    $checkStmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Update premium end date
    $updateStmt = $pdo->prepare("UPDATE user SET premium_end_date = :endDate WHERE userId = :userId");
    $updateStmt->bindParam(':endDate', $mysqlDateTime, PDO::PARAM_STR);
    $updateStmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    
    if ($updateStmt->execute()) {
        // Get updated user data to return
        $userStmt = $pdo->prepare("SELECT premium_start_date, premium_end_date FROM user WHERE userId = :userId");
        $userStmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $userStmt->execute();
        $userData = $userStmt->fetch();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Premium end date updated successfully',
            'data' => [
                'premium_start_date' => $userData['premium_start_date'],
                'premium_end_date' => $userData['premium_end_date']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update premium end date']);
    }
    
} catch (PDOException $e) {
    error_log("Database error in update_premium_date.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error in update_premium_date.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
